<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Registry;

final readonly class GrpcRoute
{
    public function __construct(
        public string $controller,
        public string $action,
        public ?string $paramType = null,
        public ?string $paramName = null,
    ) {
    }
}
