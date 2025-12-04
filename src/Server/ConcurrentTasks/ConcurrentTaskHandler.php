<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\ConcurrentTasks;

use Swoole\Server;
use SwooleBundle\SwooleBundle\Server\TaskHandler\TaskFinisher;
use SwooleBundle\SwooleBundle\Server\TaskHandler\TaskHandler;

final class ConcurrentTaskHandler implements TaskHandler
{
    public function __construct(
        private readonly TaskHandler $decorated,
        private readonly TaskFinisher $taskFinisher,
    ) {}

    public function handle(Server $server, Server\Task $task): void
    {
        if ($task->data instanceof ConcurrentTask) {
            $this->taskFinisher->finish($task, ($task->data->getCallback())());

            return;
        }

        $this->decorated->handle($server, $task);
    }
}
