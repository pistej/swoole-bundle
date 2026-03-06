<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use SwooleBundle\SwooleBundle\Bridge\Symfony\HttpFoundation\RequestFactory;
use SwooleBundle\SwooleBundle\Bridge\Symfony\HttpKernel\KernelPool;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\GRPCException;
use SwooleBundle\SwooleBundle\Server\Grpc\HttpFoundation\GrpcResponse;
use SwooleBundle\SwooleBundle\Server\Grpc\Serialization\PayloadSerializer;
use SwooleBundle\SwooleBundle\Server\Grpc\Writer\ResponseWriter;
use SwooleBundle\SwooleBundle\Server\RequestHandler\RequestHandler;
use SwooleBundle\SwooleBundle\Server\Runtime\Bootable;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

final readonly class GrpcKernelRequestHandler implements RequestHandler, Bootable
{
    public function __construct(
        private RequestFactory $requestFactory,
        private ResponseWriter $responseWriter,
        private KernelPool $kernelPool,
        private PayloadSerializer $protobufSerializer,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        $this->kernelPool->boot();
    }

    public function handle(SwooleRequest $request, SwooleResponse $response): void
    {
        $context = new Context($request);

        try {
            $context->validateRequest();
        } catch (GRPCException $e) {
            $this->responseWriter->writeError(
                $response,
                Status::from($e->getCode()),
                $e->getMessage(),
                'application/grpc'
            );

            return;
        }

        $httpFoundationRequest = $this->requestFactory->make($request);
        $this->executeKernelHandle($httpFoundationRequest, $response, $context);
    }

    private function executeKernelHandle(
        HttpFoundationRequest $httpFoundationRequest,
        SwooleResponse $response,
        Context $context,
    ): void {
        $kernel = $this->kernelPool->get();

        try {
            $httpFoundationResponse = $kernel->handle($httpFoundationRequest);

            if (!$httpFoundationResponse instanceof GrpcResponse) {
                $status = $httpFoundationResponse->getStatusCode() === 404 ? Status::UNIMPLEMENTED : Status::INTERNAL;
                $this->responseWriter->writeError(
                    $response,
                    $status,
                    'Expected response of type ' . GrpcResponse::class,
                    $context->getContentType(),
                );

                $this->executeKernelTerminate($kernel, $httpFoundationRequest, new Response());

                return;
            }

            $serializedMessage = $this->protobufSerializer->serialize(
                $httpFoundationResponse->getMessage(),
                $context->getContentType(),
            );

            $this->responseWriter->write($response, $serializedMessage, contentType: $context->getContentType());

            $this->executeKernelTerminate($kernel, $httpFoundationRequest, $httpFoundationResponse);
        } finally {
            $this->kernelPool->return($kernel);
        }
    }

    private function executeKernelTerminate(
        KernelInterface $kernel,
        HttpFoundationRequest $httpFoundationRequest,
        Response $httpFoundationResponse,
    ): void {
        if (!($kernel instanceof TerminableInterface)) {
            return;
        }

        $kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
    }
}
