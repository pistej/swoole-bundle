<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Registry;

use PHPUnit\Framework\TestCase;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\InvokeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Registry\ControllerActionRegistry;
use SwooleBundle\SwooleBundle\Server\Grpc\Registry\GrpcRoute;

final class ControllerActionRegistryTest extends TestCase
{
    private ControllerActionRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ControllerActionRegistry();
    }

    public function testGetAll(): void
    {
        $this->registry->register('/path1', 'C1', 'A1');
        $this->registry->register('/path2', 'C2', 'A2');

        $all = $this->registry->getAll();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('/path1', $all);
        $this->assertArrayHasKey('/path2', $all);
    }

    public function testFindReturnsRouteWhenParamTypeIsSet(): void
    {
        $this->registry->register(
            '/test.Service/Method',
            'App\Controller\TestController',
            'testMethod',
            'App\Message',
            'request'
        );

        $route = $this->registry->find('/test.Service/Method');

        $this->assertInstanceOf(GrpcRoute::class, $route);
        $this->assertEquals('App\Controller\TestController', $route->controller);
        $this->assertEquals('testMethod', $route->action);
        $this->assertEquals('App\Message', $route->paramType);
    }

    public function testFindThrowsWhenRouteNotFound(): void
    {
        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('gRPC route "/unknown" not found or missing valid GRPC Message parameter');

        $this->registry->find('/unknown');
    }

    public function testFindThrowsWhenParamTypeIsNull(): void
    {
        $this->registry->register('/test.Service/Method', 'App\Controller\TestController', 'testMethod');

        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('gRPC route "/test.Service/Method" not found or missing valid GRPC Message parameter');

        $this->registry->find('/test.Service/Method');
    }
}
