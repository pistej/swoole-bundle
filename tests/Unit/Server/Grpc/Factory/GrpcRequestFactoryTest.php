<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Factory;

use Google\Protobuf\Internal\Message;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleRequest;
use SwooleBundle\SwooleBundle\Server\Grpc\Factory\GrpcRequestFactory;

final class GrpcRequestFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!extension_loaded('grpc')) {
            self::markTestSkipped('Extension grpc is not loaded.');
        }
    }

    public function testMakeForwardsHeadersAndMessage(): void
    {
        $factory = new GrpcRequestFactory();

        $swooleRequest = new SwooleRequest();
        $swooleRequest->server = [
            'request_uri' => '/test.Service/Method',
            'request_method' => 'POST',
            'query_string' => 'foo=bar',
        ];
        $swooleRequest->header = [
            'content-type' => 'application/grpc+json',
            'x-custom-metadata' => 'value',
        ];
        $swooleRequest->get = null;
        $swooleRequest->post = null;
        $swooleRequest->cookie = null;
        $swooleRequest->files = null;

        $message = $this->createMock(Message::class);

        $request = $factory->make($swooleRequest, $message);

        $this->assertEquals('/test.Service/Method?foo=bar', $request->getRequestUri());
        $this->assertSame($message, $request->getContent());
        $this->assertEquals('application/grpc+json', $request->headers->get('content-type'));
        $this->assertEquals('value', $request->headers->get('x-custom-metadata'));
    }

    public function testMakeWithoutQueryString(): void
    {
        $factory = new GrpcRequestFactory();

        $swooleRequest = new SwooleRequest();
        $swooleRequest->server = [
            'request_uri' => '/test',
            'query_string' => '',
        ];
        $swooleRequest->header = [];
        $swooleRequest->get = null;
        $swooleRequest->post = null;
        $swooleRequest->cookie = null;
        $swooleRequest->files = null;

        $message = $this->createMock(Message::class);

        $request = $factory->make($swooleRequest, $message);

        $this->assertEquals('/test', $request->getRequestUri());
        $this->assertSame($message, $request->getContent());
    }
}
