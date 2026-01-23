<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Serialization;

use Google\Protobuf\StringValue;
use PHPUnit\Framework\TestCase;
use Swoole\Http\Request as SwooleRequest;
use SwooleBundle\SwooleBundle\Server\Grpc\Context;
use SwooleBundle\SwooleBundle\Server\Grpc\Serialization\ProtobufSerializerDeserializer;

final class ProtobufSerializerDeserializerTest extends TestCase
{
    private ProtobufSerializerDeserializer $serializer;

    protected function setUp(): void
    {
        if (!extension_loaded('grpc')) {
            self::markTestSkipped('Extension grpc is not loaded.');
        }

        $this->serializer = new ProtobufSerializerDeserializer();
    }

    private function makeContext(string $contentType): Context
    {
        $request = new SwooleRequest();
        $request->header = [
            'content-type' => $contentType,
            'te' => 'trailers',
        ];
        $request->server = ['request_uri' => '/test.Service/Method'];

        $context = new Context($request);
        $context->validateRequest()->parseRequest();

        return $context;
    }

    public function testSerializesToProtobufBinary(): void
    {
        $message = new StringValue();
        $message->setValue('hello');

        $context = $this->makeContext('application/grpc');
        $result = $this->serializer->serialize($message, $context);

        $this->assertSame($message->serializeToString(), $result);
    }

    public function testSerializesToJsonWhenContentTypeIsGrpcJson(): void
    {
        $message = new StringValue();
        $message->setValue('hello');

        $context = $this->makeContext('application/grpc+json');
        $result = $this->serializer->serialize($message, $context);

        $this->assertSame($message->serializeToJsonString(), $result);
    }

    public function testDeserializeStripsGrpcFramingAndParsesProtobuf(): void
    {
        $original = new StringValue();
        $original->setValue('world');
        $binary = $original->serializeToString();

        // Build gRPC-framed payload: 1 byte flag + 4 bytes length + message
        $framed = pack('CN', 0, strlen($binary)) . $binary;

        $context = $this->makeContext('application/grpc');
        /** @var StringValue $result */
        $result = $this->serializer->deserialize($framed, StringValue::class, $context);

        $this->assertSame('world', $result->getValue());
    }

    public function testDeserializeReturnsEmptyMessageWhenPayloadTooShort(): void
    {
        $context = $this->makeContext('application/grpc');
        /** @var StringValue $result */
        $result = $this->serializer->deserialize('tiny', StringValue::class, $context);

        $this->assertInstanceOf(StringValue::class, $result);
        $this->assertSame('', $result->getValue());
    }
}

