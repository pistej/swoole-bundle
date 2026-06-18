<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Config;

use Assert\Assertion;
use Generator;

final class Sockets
{
    /**
     * @var array<int, Socket>
     */
    private array $additionalSockets;

    public function __construct(
        private Socket $serverSocket,
        private ?Socket $apiSocket = null,
        Socket ...$additionalSockets,
    ) {
        $this->additionalSockets = $additionalSockets;
    }

    /**
     * @return array<int, Socket>
     */
    public function getAdditionalSockets(): array
    {
        return $this->additionalSockets;
    }

    /**
     * Register an extra listener socket, bound next to the main, API and native gRPC sockets.
     *
     * Intended as the extension point for third-party libraries that need to serve another
     * protocol (e.g. gRPC over HTTP/2) on a dedicated port within the same Swoole server.
     */
    public function addAdditionalSocket(Socket $socket): void
    {
        $this->additionalSockets[] = $socket;
    }

    public function changeServerSocket(Socket $socket): void
    {
        $this->serverSocket = $socket;
    }

    public function getServerSocket(): Socket
    {
        return $this->serverSocket;
    }

    public function getApiSocket(): Socket
    {
        Assertion::isInstanceOf($this->apiSocket, Socket::class, 'API Socket is not defined.');

        return $this->apiSocket;
    }

    public function hasApiSocket(): bool
    {
        return $this->apiSocket instanceof Socket;
    }

    public function disableApiSocket(): void
    {
        $this->apiSocket = null;
    }

    public function changeApiSocket(Socket $socket): void
    {
        $this->apiSocket = $socket;
    }

    /**
     * Get sockets in order:
     * - first server socket
     * - next if defined api socket
     * - rest of sockets.
     *
     * @return Generator<int, Socket>
     */
    public function getAll(): Generator
    {
        yield $this->serverSocket;

        if ($this->hasApiSocket()) {
            yield $this->apiSocket;
        }

        yield from $this->additionalSockets;
    }
}
