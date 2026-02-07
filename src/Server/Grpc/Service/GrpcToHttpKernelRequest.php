<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Service;

use SwooleBundle\SwooleBundle\Bridge\Symfony\HttpKernel\KernelPool;
use SwooleBundle\SwooleBundle\Server\Grpc\Attribute\GrpcService;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\ContextInterface;
use SwooleBundle\SwooleBundle\Server\Grpc\Factory\HttpFoundationFactory;
use SwooleBundle\SwooleBundle\Server\Grpc\Generated\Psr7Request;
use SwooleBundle\SwooleBundle\Server\Grpc\Generated\Psr7Response;
use SwooleBundle\SwooleBundle\Server\Runtime\Bootable;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * gRPC service that bridges PSR-7 requests to Symfony's HTTP Kernel.
 *
 * This service converts incoming PSR-7 request protobuf messages into Symfony
 * HttpFoundation requests, executes them through the Symfony Kernel, and converts
 * the responses back to PSR-7 response protobuf messages.
 *
 * @phpstan-import-type RuntimeConfiguration from Bootable
 */
#[GrpcService(package: 'SwooleBundle')]
final class GrpcToHttpKernelRequest implements Bootable
{
    public function __construct(
        private readonly HttpFoundationFactory $httpFoundationFactory,
        private readonly KernelPool $kernelPool,
    ) {
    }

    /**
     * Boot the kernel pool before server starts.
     *
     * @param RuntimeConfiguration $runtimeConfiguration
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        $this->kernelPool->boot();
    }

    /**
     * Handle a PSR-7 request by converting it to Symfony request,
     * executing through kernel, and converting response back.
     *
     * This method follows the same pattern as HttpKernelRequestHandler:
     * 1. Convert PSR-7 request to HttpFoundation request
     * 2. Get kernel from pool
     * 3. Handle the request through Symfony kernel
     * 4. Convert Symfony response to PSR-7 response
     * 5. Terminate kernel if it's terminable
     * 6. Return kernel to pool (always, even on exceptions)
     */
    public function HandleRequest(ContextInterface $ctx, Psr7Request $psr7Request): Psr7Response
    {
        $httpFoundationRequest = $this->httpFoundationFactory->make($psr7Request);
        $kernel = $this->kernelPool->get();

        try {
            $httpFoundationResponse = $kernel->handle($httpFoundationRequest);
            $psr7Response = $this->httpFoundationFactory->convertResponse($httpFoundationResponse);

            if ($kernel instanceof TerminableInterface) {
                $kernel->terminate($httpFoundationRequest, $httpFoundationResponse);
            }

            return $psr7Response;
        } finally {
            $this->kernelPool->return($kernel);
        }
    }
}
