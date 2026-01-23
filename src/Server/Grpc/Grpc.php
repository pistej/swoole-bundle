<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

interface Grpc
{
    /**
     * Get gRPC server status information.
     *
     * @return array<string, mixed>
     */
    public function status(): array;
}
