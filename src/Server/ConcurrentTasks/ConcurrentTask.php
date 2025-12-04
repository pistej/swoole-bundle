<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\ConcurrentTasks;

final class ConcurrentTask
{
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }
}
