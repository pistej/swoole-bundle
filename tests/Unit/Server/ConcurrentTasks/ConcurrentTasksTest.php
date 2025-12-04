<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\ConcurrentTasks;

use PHPUnit\Framework\TestCase;
use SwooleBundle\SwooleBundle\Server\ConcurrentTasks\ConcurrentTasks;
use SwooleBundle\SwooleBundle\Server\HttpServer;
use SwooleBundle\SwooleBundle\Tests\Unit\Server\SwooleHttpServerMockFactory;

final class ConcurrentTasksTest extends TestCase
{
    public function testRunExecutesTasksConcurrently(): void
    {
        $swooleServer = SwooleHttpServerMockFactory::make();
        $swooleServer->setTaskWaitMultiResult(['foo', 'bar']);

        $httpServer = $this->createMock(HttpServer::class);
        $httpServer->expects($this->once())
            ->method('getServer')
            ->willReturn($swooleServer);

        $concurrentTasks = new ConcurrentTasks($httpServer);

        $results = $concurrentTasks->run([
            fn() => 'foo',
            fn() => 'bar',
        ]);

        $this->assertSame(['foo', 'bar'], $results);
    }

    public function testRunHandlesFailures(): void
    {
        $swooleServer = SwooleHttpServerMockFactory::make();
        $swooleServer->setTaskWaitMultiResult(false);

        $httpServer = $this->createMock(HttpServer::class);
        $httpServer->expects($this->once())
            ->method('getServer')
            ->willReturn($swooleServer);

        $concurrentTasks = new ConcurrentTasks($httpServer);

        $results = $concurrentTasks->run([
            fn() => 'foo',
        ]);

        $this->assertSame([], $results);
    }
}
