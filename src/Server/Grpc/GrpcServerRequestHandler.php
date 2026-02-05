<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\GRPCException;
use SwooleBundle\SwooleBundle\Server\Grpc\Factory\ContextFactory;
use SwooleBundle\SwooleBundle\Server\Grpc\Service\ServiceHandler;
use SwooleBundle\SwooleBundle\Server\Grpc\Writer\ResponseWriter;
use SwooleBundle\SwooleBundle\Server\HttpServer;
use SwooleBundle\SwooleBundle\Server\RequestHandler\RequestHandler;

/**
 * Refactored gRPC request handler using ResponseWriter.
 */
final readonly class GrpcServerRequestHandler implements RequestHandler
{
    public function __construct(
        private HttpServer $server,
        private ServiceHandler $serviceHandler,
        private ResponseWriter $responseWriter,
        private ContextFactory $contextFactory,
    ) {
    }

    public function handle(Request $request, Response $response): void
    {
        $context = $this->contextFactory->createContext($this->server, $request, $response);

        try {
            $context->init();
            $context = $this->serviceHandler->handle($context);
        } catch (GRPCException $e) {
            $context
                ->getResponse()
                ->withMessage($e->getMessage())
                ->withStatus($e->getCode());
        }

        $this->responseWriter->write($context);
    }
}
