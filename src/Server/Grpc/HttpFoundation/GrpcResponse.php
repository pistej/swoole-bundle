<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\HttpFoundation;

use Google\Protobuf\Internal\Message;
use Symfony\Component\HttpFoundation\Response;

final class GrpcResponse extends Response
{
    public function __construct(
        private readonly Message $message,
        array $headers = [],
    ) {
        parent::__construct('', Response::HTTP_OK, $headers);
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
