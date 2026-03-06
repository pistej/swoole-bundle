<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc;

use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleRequest;
use SwooleBundle\SwooleBundle\Server\Grpc\Context;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\InvokeException;

final class ContextTest extends TestCase
{
    private function createMockSwooleRequest(array $server = [], array $header = []): SwooleRequest
    {
        $request = new SwooleRequest();
        $request->server = $server;
        $request->header = $header;

        return $request;
    }

    public function testValidateRequestWithValidHeaders(): void
    {
        $request = $this->createMockSwooleRequest(
            [],
            [
                'content-type' => 'application/grpc',
                'te' => 'trailers',
            ]
        );

        $context = new Context($request);
        $result = $context->validateRequest();

        $this->assertSame($context, $result);
        $this->assertEquals('application/grpc', $context->getContentType());
    }

    public function testValidateRequestWithGrpcProtoContentType(): void
    {
        $request = $this->createMockSwooleRequest(
            [],
            [
                'content-type' => 'application/grpc+proto',
                'te' => 'trailers',
            ]
        );

        $context = new Context($request);
        $result = $context->validateRequest();

        $this->assertSame($context, $result);
        $this->assertEquals('application/grpc+proto', $context->getContentType());
    }

    public function testValidateRequestWithGrpcJsonContentType(): void
    {
        $request = $this->createMockSwooleRequest(
            [],
            [
                'content-type' => 'application/grpc+json',
                'te' => 'trailers',
            ]
        );

        $context = new Context($request);
        $result = $context->validateRequest();

        $this->assertSame($context, $result);
        $this->assertEquals('application/grpc+json', $context->getContentType());
    }

    public function testValidateRequestThrowsWhenContentTypeIsMissing(): void
    {
        $request = $this->createMockSwooleRequest(
            [],
            ['te' => 'trailers']
        );

        $context = new Context($request);

        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('Illegal GRPC request, missing content-type or te header');

        $context->validateRequest();
    }

    public function testValidateRequestThrowsWhenTeHeaderIsMissing(): void
    {
        $request = $this->createMockSwooleRequest(
            [],
            ['content-type' => 'application/grpc']
        );

        $context = new Context($request);

        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('Illegal GRPC request, missing content-type or te header');

        $context->validateRequest();
    }

    public function testValidateRequestThrowsWhenContentTypeIsNotSupported(): void
    {
        $request = $this->createMockSwooleRequest(
            [],
            [
                'content-type' => 'application/json',
                'te' => 'trailers',
            ]
        );

        $context = new Context($request);

        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('Content-type not supported: application/json');

        $context->validateRequest();
    }

}
