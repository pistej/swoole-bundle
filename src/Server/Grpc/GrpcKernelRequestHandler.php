<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use SwooleBundle\SwooleBundle\Bridge\Symfony\HttpKernel\KernelPool;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\GRPCException;
use SwooleBundle\SwooleBundle\Server\Grpc\Factory\GrpcRequestFactory;
use SwooleBundle\SwooleBundle\Server\Grpc\HttpFoundation\GrpcResponse;
use SwooleBundle\SwooleBundle\Server\Grpc\Registry\ControllerActionRegistry;
use SwooleBundle\SwooleBundle\Server\Grpc\Serialization\ProtobufSerializerDeserializer;
use SwooleBundle\SwooleBundle\Server\Grpc\Writer\ResponseWriter;
use SwooleBundle\SwooleBundle\Server\RequestHandler\RequestHandler;
use SwooleBundle\SwooleBundle\Server\Runtime\Bootable;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpKernel\TerminableInterface;

final readonly class GrpcKernelRequestHandler implements RequestHandler, Bootable
{
    public function __construct(
        private ControllerActionRegistry $registry,
        private GrpcRequestFactory $requestFactory,
        private ResponseWriter $responseWriter,
        private KernelPool $kernelPool,
        private ProtobufSerializerDeserializer $protobufSerializer,
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
            $context->validateRequest()->parseRequest();
            $route = $this->registry->find($context->getRequestUri());
        } catch (GRPCException $e) {
            $this->responseWriter->writeError($response, $e->getCode(), $e->getMessage());

            return;
        }

        $message = $this->protobufSerializer->deserialize(
            (string) ($request->rawContent() ?: ''),
            $route->paramType ?? '',
            $context
        );

        $httpFoundationRequest = $this->requestFactory->make($request, $message);
        $httpFoundationRequest->getContent();
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
                $this->responseWriter->writeError($response, Status::INTERNAL, 'Expected GrpcResponse');

                return;
            }

            $serializedMessage = $this->protobufSerializer->serialize(
                $httpFoundationResponse->getMessage(),
                $context
            );

            $this->responseWriter->write($response, $serializedMessage);

            if ($kernel instanceof TerminableInterface) {
                $kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
            }
        } finally {
            $this->kernelPool->return($kernel);
        }
    }
}
