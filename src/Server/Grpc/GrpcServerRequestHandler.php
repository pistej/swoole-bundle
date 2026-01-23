<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleBundle\SwooleBundle\Server\RequestHandler\RequestHandler;

/**
 * Basic gRPC request handler.
 * Returns HTTP 501 Not Implemented until actual gRPC library is integrated.
 */
final readonly class GrpcServerRequestHandler implements RequestHandler
{
    public function __construct(
        private Grpc $grpcServer,
    ) {
    }

    public function handle(Request $request, Response $response): void
    {
        $response->status(501);
        $response->header('Content-Type', 'application/json');
        $response->end(json_encode([
            'error' => 'gRPC server not implemented',
            'message' => 'gRPC structure is configured but requires library integration (Case 2 or 3)',
        ]));
    }
}
