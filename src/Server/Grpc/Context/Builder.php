<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Context;

use SwooleBundle\SwooleBundle\Server\HttpServer;

/**
 * Class Builder
 *
 * Responsible for creating Context instances from Swoole HTTP requests and responses.
 */
final class Builder
{
    /**
     * Create a new Context instance.
     *
     * @param HttpServer $server the gRPC server instance
     * @param \Swoole\HTTP\Request $swooleRequest the Swoole HTTP request object
     * @param \Swoole\Http\Response $swooleResponse the Swoole HTTP response object
     */
    public static function createContext(
        HttpServer $server,
        \Swoole\HTTP\Request $swooleRequest,
        \Swoole\Http\Response $swooleResponse,
    ): Context
    {
        $request = new Request($swooleRequest);

        return new Context(
            server: $server,
            request: $request,
            response: new Response($swooleResponse),
        );
    }
}
