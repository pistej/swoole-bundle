<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Service;

use SwooleBundle\SwooleBundle\Server\Grpc\Constant;

/**
 * Class ServiceMethodDefinition
 *
 * Represents the definition of a gRPC service method, including its name, parameter type, return type, and streaming information.
 */
class ServiceMethodDefinition
{
    /**
     * ServiceMethodDefinition constructor.
     *
     * @param string $name The method name.
     * @param string $paramType The parameter type.
     * @param string $returnType The return type.
     * @param int $type The method type (e.g., 'unary' / 'stream).
     * @param string|null $streamType The stream type, if applicable.
     */
    public function __construct(
        public string $name,
        public string $paramType,
        public string $returnType,
        public int $type = Constant::GRPC_CALL_TYPE_UNARY,
        public ?string $streamType = null,
    ) {
    }
}
