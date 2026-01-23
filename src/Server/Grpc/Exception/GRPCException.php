<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Exception;

use RuntimeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Status;
use Throwable;

/**
 * Class GRPCException
 *
 * Base exception for gRPC errors, providing a static factory for creation.
 * todo: do we need this, use smthing from bundle ?!
 */
class GRPCException extends RuntimeException
{
    protected const CODE = Status::UNKNOWN;

    /**
     * GRPCException constructor.
     *
     * @param string $message
     * @param int|null $code
     * @param Throwable|null $previous
     */
    final public function __construct(
        string $message = '',
        ?int $code = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, (int) ($code ?? static::CODE), $previous);
    }

    /**
     * Create a new GRPCException instance.
     *
     * @param string $message
     * @param int|null $code
     * @param Throwable|null $previous
     * @return static
     */
    public static function create(
        string $message,
        ?int $code = null,
        ?Throwable $previous = null,
    ): self {
        return new static($message, $code, $previous);
    }
}
