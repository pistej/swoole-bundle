<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\Builder;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\Context;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\GRPCException;
use SwooleBundle\SwooleBundle\Server\Grpc\Service\ServiceHandler;
use SwooleBundle\SwooleBundle\Server\HttpServer;
use SwooleBundle\SwooleBundle\Server\RequestHandler\RequestHandler;

/**
 * Basic gRPC request handler.
 */
final readonly class GrpcServerRequestHandler implements RequestHandler
{
    public function __construct(
        private HttpServer $server,
        private ServiceHandler $serviceHandler,
    ) {
    }

    public function handle(Request $request, Response $response): void
    {
        $context = Builder::createContext($this->server, $request, $response);

        try {
            $context->init();
            $context = $this->serviceHandler->handle($context);
        } catch (GRPCException $e) {
            $context
                ->getResponse()
                ->withMessage($e->getMessage())
                ->withStatus($e->getCode());
        }

        $this->send($context);
    }

    /**
     * Send the gRPC response to the client, including headers and trailers.
     */
    private function send(Context $context)
    {
        $rawResponse = $context->getResponse()->getSwooleResponse();
        $headers     = [
            'content-type' => $context->getRequest()->getContentType(),
            'trailer'      => 'grpc-status, grpc-message',
        ];

        $trailers = [
            Constant::GRPC_STATUS  => $context->getResponse()->getStatus(),
            Constant::GRPC_MESSAGE => $context->getResponse()->getMessage(),
        ];

        $payload = pack('CN', 0, strlen($context->getResponse()->getPayload())) . $context->getResponse()->getPayload();
        try {
            foreach ($headers as $name => $value) {
                $rawResponse->header($name, $value);
            }

            foreach ($trailers as $name => $value) {
                $rawResponse->trailer($name, (string) $value);
                $rawResponse->header($name, $value);
            }
            $rawResponse->end($payload);
        } catch (\Swoole\Exception $e) {
//            $this->logger?->warning($e->getMessage() . ', error code: ' . $e->getCode() . "\n" . $e->getTraceAsString());
        }
    }
}
