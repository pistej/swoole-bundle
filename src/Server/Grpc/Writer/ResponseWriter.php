<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Writer;

use Psr\Log\LoggerInterface;
use Swoole\Exception;
use Swoole\Http\Response;
use SwooleBundle\SwooleBundle\Server\Grpc\Constant;
use SwooleBundle\SwooleBundle\Server\Grpc\Status;

/**
 * Writes gRPC responses to Swoole HTTP responses.
 *
 * Handles headers, trailers, and payload formatting according to gRPC spec.
 */
final readonly class ResponseWriter
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Write a gRPC error response.
     */
    public function writeError(Response $response, int $status, string $message): void
    {
        $this->writeResponse($response, '', $status, $message);
    }

    /**
     * Write a general gRPC success response.
     */
    public function write(Response $response, string $payload, int $status = Status::OK, string $message = 'OK'): void
    {
        $this->writeResponse($response, $payload, $status, $message);
    }

    private function writeResponse(
        Response $response,
        string $payload,
        int $status = Status::OK,
        string $message = 'OK',
    ): void
    {
        $headers = [
            'content-type' => 'application/grpc',
            'trailer' => 'grpc-status, grpc-message',
        ];

        $trailers = [
            Constant::GRPC_STATUS => $status,
            Constant::GRPC_MESSAGE => $message,
        ];

        try {
            foreach ($headers as $name => $value) {
                $response->header($name, $value);
            }

            foreach ($trailers as $name => $value) {
                $response->trailer($name, (string) $value);
                $response->header($name, (string) $value);
            }

            $response->end($this->framePayload($payload));
        } catch (Exception $e) {
            $this->logger->warning(
                'Failed to send gRPC response: ' . $e->getMessage(),
                [
                    'error_code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ]
            );
        }
    }

    /**
     * Frame the payload according to gRPC specification.
     *
     * Format: [1-byte compression flag][4-byte message length][message]
     */
    private function framePayload(string $payload): string
    {
        // Compression flag: 0 = not compressed
        // Message length: 4 bytes, network byte order (big-endian)
        return pack('CN', 0, strlen($payload)) . $payload;
    }
}
