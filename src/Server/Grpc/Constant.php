<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

final class Constant
{
    public const CONTENT_TYPE = 'content-type';

    public const GRPC_STATUS = 'grpc-status';

    public const GRPC_MESSAGE = 'grpc-message';

    public const GRPC_CALL_TYPE_UNARY = 1;

    public const GRPC_CALL_TYPE_STREAM = 2;
}
