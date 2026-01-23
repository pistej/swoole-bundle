<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Exception;

use Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Enum\Status;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\GrpcException;

final class GrpcExceptionTest extends TestCase
{
    public function testDefaultCodeIsUnknown(): void
    {
        $exception = new GrpcException('something went wrong');

        $this->assertSame(Status::UNKNOWN->value, $exception->getCode());
        $this->assertSame('something went wrong', $exception->getMessage());
    }

    public function testCustomCodeIsUsed(): void
    {
        $exception = new GrpcException('not found', Status::NOT_FOUND);

        $this->assertSame(Status::NOT_FOUND->value, $exception->getCode());
    }

    public function testCreateFactoryMethod(): void
    {
        $exception = GrpcException::create('internal error', Status::INTERNAL);

        $this->assertInstanceOf(GrpcException::class, $exception);
        $this->assertSame('internal error', $exception->getMessage());
        $this->assertSame(Status::INTERNAL->value, $exception->getCode());
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new GrpcException('error');

        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    public function testCreateWithPreviousException(): void
    {
        $previous = new Exception('root cause');
        $exception = GrpcException::create('wrapper', Status::UNKNOWN, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
