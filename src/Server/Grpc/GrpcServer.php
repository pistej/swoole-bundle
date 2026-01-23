<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

use SwooleBundle\SwooleBundle\Server\HttpServer;
use SwooleBundle\SwooleBundle\Server\HttpServerConfiguration;

/**
 * gRPC Server for Swoole HTTP Server.
 * Basic implementation - to be extended with actual gRPC library.
 */
final readonly class GrpcServer implements Grpc
{
    public function __construct(
        private HttpServer $server,
        private HttpServerConfiguration $serverConfiguration,
    ) {
    }

    public function status(): array
    {
        $swooleServer = $this->server->getServer();

        return [
            'date' => date(DATE_ATOM),
            'enabled' => true,
            'message' => 'gRPC server structure initialized (no implementation)',
        ];
    }
}
