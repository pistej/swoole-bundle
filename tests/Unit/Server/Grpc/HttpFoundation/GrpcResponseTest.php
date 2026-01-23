<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\HttpFoundation;

use Google\Protobuf\Internal\Message;
use PHPUnit\Framework\TestCase;
use SwooleBundle\SwooleBundle\Server\Grpc\HttpFoundation\GrpcResponse;
use Symfony\Component\HttpFoundation\Response;

final class GrpcResponseTest extends TestCase
{
    protected function setUp(): void
    {
        if (extension_loaded('grpc')) {
            return;
        }

        self::markTestSkipped('Extension grpc is not loaded.');
    }

    public function testResponseHasHttpOkStatus(): void
    {
        $message = $this->createMock(Message::class);
        $response = new GrpcResponse($message);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testGetMessageReturnsProvidedMessage(): void
    {
        $message = $this->createMock(Message::class);
        $response = new GrpcResponse($message);

        $this->assertSame($message, $response->getMessage());
    }

    public function testResponseExtendsSymfonyResponse(): void
    {
        $message = $this->createMock(Message::class);
        $response = new GrpcResponse($message);

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testCustomHeadersAreSet(): void
    {
        $message = $this->createMock(Message::class);
        $response = new GrpcResponse($message, ['x-custom' => 'value']);

        $this->assertEquals('value', $response->headers->get('x-custom'));
    }
}
