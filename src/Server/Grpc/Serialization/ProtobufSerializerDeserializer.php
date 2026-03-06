<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Serialization;

use Exception;
use Google\Protobuf\Internal\Message;
use InvalidArgumentException;
use SwooleBundle\SwooleBundle\Server\Grpc\Enum\ContentType;

/**
 * Protobuf serializer/deserializer for gRPC Message.
 * Handles serialization and deserialization of protobuf messages with gRPC framing.
 */
final class ProtobufSerializerDeserializer implements PayloadSerializer, PayloadDeserializer
{
    public function serialize(Message $message, string $contentType): string
    {
        return $this->getContentType($contentType)->isJson()
            ? $message->serializeToJsonString()
            : $message->serializeToString();
    }

    /**
     * @throws Exception
     */
    public function deserialize(string $payload, string $messageClass, string $contentType): Message
    {
        // Strip gRPC framing (first 5 bytes: 1 byte compressed flag + 4 bytes message length)
        $stripedPayload = strlen($payload) > 5 ? substr($payload, 5) : '';

        /** @var Message $message */
        $message = new $messageClass();

        if ($stripedPayload === '') {
            return $message;
        }

        if ($this->getContentType($contentType)->isJson()) {
            $message->mergeFromJsonString($stripedPayload);
        } else {
            $message->mergeFromString($stripedPayload);
        }

        return $message;
    }

    /**
     * Get the content type from string.
     */
    private function getContentType(string $contentType): ContentType
    {
        try {
            return ContentType::fromString($contentType);
        } catch (InvalidArgumentException) {
            // Default to protobuf if content type is invalid
            return ContentType::GRPC;
        }
    }
}
