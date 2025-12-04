<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\ConcurrentTasks;

use Closure;
use SwooleBundle\SwooleBundle\Server\HttpServer;

final class ConcurrentTasks
{
    public function __construct(
        private readonly HttpServer $httpServer,
    ) {}

    /**
     * @param array<int|string, callable> $callbacks
     * @return array<mixed|null> The results of the tasks in the same order as the input callbacks.
     *                           If a task fails, its result will be false.
     *                           If a task exceeds the timeout, its result will be null.
     */
    public function run(array $callbacks, float $timeout = 10.0): array
    {
        $tasks = [];

        foreach ($callbacks as $key => $callback) {
            $tasks[$key] = new ConcurrentTask($callback);
        }

        $results = $this->httpServer->getServer()->taskWaitMulti($tasks, $timeout);

        if ($results === false) {
            return [];
        }

        $orderedResults = [];
        foreach ($callbacks as $key => $callback) {
            /** @var array<mixed> $results */
            $orderedResults[$key] = $results[$key] ?? null;
        }

        return $orderedResults;
    }
}
