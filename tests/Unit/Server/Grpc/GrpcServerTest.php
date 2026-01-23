<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc;

use PHPUnit\Framework\TestCase;
use SwooleBundle\SwooleBundle\Server\Grpc\Grpc;
use SwooleBundle\SwooleBundle\Server\Grpc\GrpcServer;

final class GrpcServerTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(GrpcServer::class));
    }

    public function testImplementsGrpcInterface(): void
    {
        $reflection = new \ReflectionClass(GrpcServer::class);
        $this->assertTrue($reflection->implementsInterface(Grpc::class));
    }

    public function testHasStatusMethod(): void
    {
        $reflection = new \ReflectionClass(GrpcServer::class);
        $this->assertTrue($reflection->hasMethod('status'));

        $method = $reflection->getMethod('status');
        $this->assertTrue($method->isPublic());
        $returnType = $method->getReturnType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $returnType);
        $this->assertEquals('array', $returnType->getName());
    }
}
