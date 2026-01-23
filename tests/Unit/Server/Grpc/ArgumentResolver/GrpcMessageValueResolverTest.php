<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\ArgumentResolver;

use Google\Protobuf\Int32Value;
use Google\Protobuf\Internal\Message;
use Google\Protobuf\StringValue;
use PHPUnit\Framework\TestCase;
use stdClass;
use SwooleBundle\SwooleBundle\Server\Grpc\ArgumentResolver\GrpcMessageValueResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class GrpcMessageValueResolverTest extends TestCase
{
    private GrpcMessageValueResolver $resolver;

    protected function setUp(): void
    {
        if (!extension_loaded('grpc')) {
            self::markTestSkipped('Extension grpc is not loaded.');
        }

        $this->resolver = new GrpcMessageValueResolver();
    }

    public function testYieldsNothingWhenTypeIsNotAMessageOrNUll(): void
    {
        $result = $this->resolver->resolve(
            Request::create('/'),
            $this->makeArgument(stdClass::class)
        );

        $this->assertEmpty(iterator_to_array($result));

        $result = $this->resolver->resolve(
            Request::create('/'),
            $this->makeArgument(null)
        );

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testYieldsMessageFromRequestContent(): void
    {
        $message = new StringValue();
        $message->setValue('hello');

        $request = Request::create('/');
        $request->initialize([], [], [], [], [], [], $message);

        $result = $this->resolver->resolve(
            $request,
            $this->makeArgument(StringValue::class)
        );

        $resolved = iterator_to_array($result);
        $this->assertCount(1, $resolved);
        $this->assertSame($message, $resolved[0]);
    }

    public function testYieldsMessageFromRequestAttributes(): void
    {
        $message = new StringValue();
        $message->setValue('from-attribute');

        $request = Request::create('/');
        $request->attributes->set('grpc_message', $message);

        $result = $this->resolver->resolve(
            $request,
            $this->makeArgument(StringValue::class)
        );

        $resolved = iterator_to_array($result);
        $this->assertCount(1, $resolved);
        $this->assertSame($message, $resolved[0]);
    }

    public function testYieldsNothingWhenMessageTypeMismatch(): void
    {
        $message = new StringValue();
        $message->setValue('hello');

        $request = Request::create('/');
        $request->attributes->set('msg', $message);

        $result = $this->resolver->resolve(
            $request,
            $this->makeArgument(Int32Value::class)
        );

        $this->assertEmpty(iterator_to_array($result));
    }

    public function testAcceptsBaseMessageClass(): void
    {
        $message = new StringValue();

        $request = Request::create('/');
        $request->initialize([], [], [], [], [], [], $message);

        $result = $this->resolver->resolve(
            $request,
            $this->makeArgument(Message::class)
        );

        $resolved = iterator_to_array($result);
        $this->assertCount(1, $resolved);
        $this->assertSame($message, $resolved[0]);
    }

    private function makeArgument(?string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('message', $type, false, false, null);
    }
}
