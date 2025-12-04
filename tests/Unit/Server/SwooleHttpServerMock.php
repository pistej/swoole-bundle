<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server;

use Closure;
use Swoole\Http\Server;

abstract class SwooleHttpServerMock extends Server
{
    protected bool $registeredEvent = false;

    /**
     * @var array{0?: string, 1?: Closure}
     */
    protected array $registeredEventPair = [];

    private static ?self $instance = null;

    /**
     * @var array|bool
     */
    private $taskWaitMultiResult = [];

    private function __construct()
    {
        parent::__construct('localhost', 31999);
    }

    public static function make(): static
    {
        if (!self::$instance instanceof static) {
            self::$instance = new static(); /** @phpstan-ignore new.static */
        }

        self::$instance->clean();

        /** @phpstan-ignore return.type */
        return self::$instance;
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

    private function clean(): void
    {
        $this->registeredEvent = false;
        $this->registeredEventPair = [];
        $this->taskWaitMultiResult = [];
    }
}
