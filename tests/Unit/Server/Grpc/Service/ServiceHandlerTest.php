<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Service;

use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;
use Swoole\Http\Response as SwooleResponse;
use SwooleBundle\SwooleBundle\Server\HttpServerConfiguration;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\Context;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\Request;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\Response;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\InvokeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Service\ServiceHandler;
use SwooleBundle\SwooleBundle\Server\HttpServer;
use SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Service\Stub\StubService;

class ServiceHandlerTest extends TestCase
{
    use ProphecyTrait;

    private $container;
    private $server;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $config = $this->createMock(HttpServerConfiguration::class);
        $this->server = new HttpServer($config);
    }

    public function testAddServiceAndHandle(): void
    {
        $service = new StubService();
        $handler = new ServiceHandler();
        $handler->addService($service);

        $context = $this->createContext('stub.Service', 'UnaryMethod');

        $handler->handle($context);

        // The mock message returns 'serialized_payload' (simulated)
        // Wait, StubMessage::serializeToString returns 'payload' in my plan.
        $this->assertEquals('payload', $context->getResponse()->getPayload());
    }

    public function testHandleServiceNotFound(): void
    {
        $handler = new ServiceHandler();
        $context = $this->createContext('NonExistent', 'Method');

        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('Service Code 5');

        $handler->handle($context);
    }

    public function testHandleMethodNotFound(): void
    {
        $service = new StubService();
        $handler = new ServiceHandler();
        $handler->addService($service);

        $context = $this->createContext('stub.Service', 'NonExistent');

        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('Code 5');

        $handler->handle($context);
    }

    public function testResolveFromContainer(): void
    {
        $service = new StubService();
        $this->container->expects($this->once())
            ->method('has')
            ->with(StubService::class)
            ->willReturn(true);
        $this->container->expects($this->once())
            ->method('get')
            ->with(StubService::class)
            ->willReturn($service);

        $handler = new ServiceHandler([], $this->container);
        $handler->register(StubService::class);
        $handler->boot();

        $context = $this->createContext('stub.Service', 'UnaryMethod');
        $handler->handle($context);

        $this->assertEquals('payload', $context->getResponse()->getPayload());
    }

    public function testHandleStreamingMethod(): void
    {
        $service = new StubService();
        $handler = new ServiceHandler();
        $handler->addService($service);

        $context = $this->createContext('stub.Service', 'StreamMethod');

        $handler->handle($context);

        $this->assertEquals('OK', $context->getResponse()->getMessage());
        $this->assertEquals(0, $context->getResponse()->getStatus()); // Status::OK is 0
    }

    public function testHandleMethodThrowsException(): void
    {
        $service = new StubService();
        $handler = new ServiceHandler();
        $handler->addService($service);

        $context = $this->createContext('stub.Service', 'ErrorMethod');

        $this->expectException(InvokeException::class);
        $this->expectExceptionMessage('Service error');

        $handler->handle($context);
    }

    public function testHandleWithNullPayload(): void
    {
        $service = new StubService();
        $handler = new ServiceHandler();
        $handler->addService($service);

        $context = $this->createContext('stub.Service', 'UnaryMethod', null);

        $handler->handle($context);

        $this->assertEquals('payload', $context->getResponse()->getPayload());
    }

    private function createContext(string $service, string $method, ?string $payload = 'input'): Context
    {
        $request = $this->createMock(Request::class);
        $request->method('getService')->willReturn($service);
        $request->method('getMethod')->willReturn($method);
        $request->method('getPayload')->willReturn($payload);
        $request->method('getContentType')->willReturn('application/grpc');

        $swooleResponse = $this->prophesize(SwooleResponse::class);
        $response = new Response($swooleResponse->reveal());

        return new Context($this->server, $request, $response);
    }
}

