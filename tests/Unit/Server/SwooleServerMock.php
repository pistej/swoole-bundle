<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server;

use Swoole\Server;

final class SwooleServerMock extends Server
{
    private static ?self $instance = null;

    private bool $registeredEvent = false;

    /**
     * @var array{0?: string, 1?: Closure}
     */
    private array $registeredEventPair = [];

    /**
     * @var array|bool
     */
    private $taskWaitMultiResult = [];

    private function __construct(bool $taskworker)
    {
        parent::__construct('localhost', 31999);

        $this->taskworker = $taskworker;
    }

    public function registeredEvent(): bool
    {
        return $this->registeredEvent;
    }

    /**
     * @return array{0: string, 1: Closure}
     */
    public function registeredEventPair(): array
    {
        return $this->registeredEventPair;
    }

    public function setTaskWaitMultiResult(array|bool $result): void
    {
        $this->taskWaitMultiResult = $result;
    }

    public function taskWaitMulti(array $tasks, float $timeout = 0.5): array|false
    {
        return $this->taskWaitMultiResult;
    }

    public static function make(bool $taskworker = false): static
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self($taskworker);
        }

        return self::$instance;
    }

    private function clean(): void
    {
        $this->registeredEvent = false;
        $this->registeredEventPair = [];
        $this->taskWaitMultiResult = [];
    }
}
