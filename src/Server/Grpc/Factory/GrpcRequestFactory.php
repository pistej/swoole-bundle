<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Factory;

use Google\Protobuf\Internal\Message;
use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

final class GrpcRequestFactory
{
    public function make(SwooleRequest $request, Message $message): HttpFoundationRequest
    {
        $server = array_change_key_case($request->server, CASE_UPPER);

        // Add formatted headers to server
        foreach ($request->header as $key => $value) {
            $server['HTTP_' . mb_strtoupper(str_replace('-', '_', (string) $key))] = $value;
        }

        $queryString = $server['QUERY_STRING'] ?? '';
        $server['REQUEST_URI'] ??= '';
        $server['REQUEST_URI'] .= $queryString !== '' ? '?' . $queryString : '';

        return new HttpFoundationRequest(
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            $server,
            $message,
        );
    }
}
