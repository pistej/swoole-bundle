<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Configurator;

use Swoole\Http\Server;
use SwooleBundle\SwooleBundle\Server\HttpServerConfiguration;
use SwooleBundle\SwooleBundle\Server\TaskHandler\TaskFinishedHandler;

final readonly class WithTaskFinishedHandler implements Configurator
{
    public function __construct(
        private TaskFinishedHandler $handler,
        private HttpServerConfiguration $configuration,
    ) {}

    public function configure(Server $server): void
    {
        if ($this->configuration->getTaskWorkerCount() <= 0) {
            return;
        }

        $server->on('finish', $this->handler->handle(...));
    }
}
