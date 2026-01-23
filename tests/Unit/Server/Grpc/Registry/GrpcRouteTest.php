<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Registry;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SwooleBundle\SwooleBundle\Server\Grpc\Registry\GrpcRoute;

final class GrpcRouteTest extends TestCase
{
    public function testConstructorSetsAllProperties(): void
    {
        $route = new GrpcRoute('App\Controller', 'handleMethod', 'App\Message', 'request');

        $this->assertSame('App\Controller', $route->controller);
        $this->assertSame('handleMethod', $route->action);
        $this->assertSame('App\Message', $route->paramType);
        $this->assertSame('request', $route->paramName);
    }

    public function testOptionalParamsDefaultToNull(): void
    {
        $route = new GrpcRoute('App\Controller', 'handleMethod');

        $this->assertNull($route->paramType);
        $this->assertNull($route->paramName);
    }

    public function testIsReadOnly(): void
    {
        $route = new GrpcRoute('App\Controller', 'handleMethod', 'App\Message', 'request');

        $reflection = new ReflectionClass($route);
        $this->assertTrue($reflection->isReadOnly());
    }
}
