<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Serialization;

use Google\Protobuf\Internal\Message;
use SwooleBundle\SwooleBundle\Server\Grpc\Context;

/**
 * Interface for deserializing payloads to protobuf message.
 */
interface PayloadDeserializer
{
    /**
     * Deserialize a payload into a message.
     *
     * @param string $payload The payload to deserialize
     * @param class-string<Message> $messageClass The message class to deserialize into
     * @param Context $context The gRPC context (for content-type detection)
     * @return Message The deserialized message
     */
    public function deserialize(string $payload, string $messageClass, Context $context): Message;
}
