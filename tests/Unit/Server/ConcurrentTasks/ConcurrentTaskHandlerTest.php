<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\ConcurrentTasks;

use PHPUnit\Framework\TestCase;
use Swoole\Server;
use SwooleBundle\SwooleBundle\Server\ConcurrentTasks\ConcurrentTask;
use SwooleBundle\SwooleBundle\Server\ConcurrentTasks\ConcurrentTaskHandler;
use SwooleBundle\SwooleBundle\Server\TaskHandler\TaskFinisher;
use SwooleBundle\SwooleBundle\Server\TaskHandler\TaskHandler;

final class ConcurrentTaskHandlerTest extends TestCase
{
    public function testHandleExecutesConcurrentTask(): void
    {
        $server = $this->createMock(Server::class);
        $task = new Server\Task();
        $task->data = new ConcurrentTask(fn() => 'result');

        $taskFinisher = $this->createMock(TaskFinisher::class);
        $taskFinisher->expects($this->once())
            ->method('finish')
            ->with($task, 'result');

        $decorated = $this->createMock(TaskHandler::class);
        $decorated->expects($this->never())
            ->method('handle');

        $handler = new ConcurrentTaskHandler($decorated, $taskFinisher);
        $handler->handle($server, $task);
    }

    public function testHandleDelegatesOtherTasks(): void
    {
        $server = $this->createMock(Server::class);
        $task = new Server\Task();
        $task->data = 'some other data';

        $taskFinisher = $this->createMock(TaskFinisher::class);
        $taskFinisher->expects($this->never())
            ->method('finish');

        $decorated = $this->createMock(TaskHandler::class);
        $decorated->expects($this->once())
            ->method('handle')
            ->with($server, $task);

        $handler = new ConcurrentTaskHandler($decorated, $taskFinisher);
        $handler->handle($server, $task);
    }
}
