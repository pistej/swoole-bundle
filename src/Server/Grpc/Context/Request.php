<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Context;

use Swoole\Http\Request as HttpRequest;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\InvokeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Status;

/**
 * Class Request
 *
 * Represents a gRPC request, providing access to service, method, payload, and headers.
 */
final class Request
{
    protected string $contentType = '';

    protected string $payload = '';

    protected string $service = '';

    protected string $method = '';

    /**
     * Request constructor.
     *
     * @param HttpRequest $rawRequest the underlying Swoole HTTP request object
     */
    public function __construct(protected HttpRequest $rawRequest)
    {
    }

    /**
     * Get the gRPC service name from the request URI.
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * Get the gRPC method name from the request URI.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Get the request payload.
     */
    public function getPayload(): string
    {
        return $this->payload;
    }

    /**
     * Get the content-type header from the request.
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Initialize the request by validating and parsing it.
     */
    public function init(): static
    {
        return $this->validateRequest()
            ->parseRequest();
    }

    /**
     * Get the underlying Swoole HTTP request object.
     */
    public function getSwooleRequest(): HttpRequest
    {
        return $this->rawRequest;
    }

    /**
     * Parse the Swoole HTTP request to extract service, method, and payload.
     */
    protected function parseRequest(): static
    {
        [, $service, $method] = explode('/', $this->rawRequest->server['request_uri'] ?? '');

        $this->service = '/' . $service;
        $this->payload = $this->rawRequest->getContent() ? substr($this->rawRequest->getContent(), 5) : '';
        $this->method = $method;

        return $this;
    }

    /**
     * Validate the Swoole HTTP request headers for gRPC compliance.
     *
     * @return static
     * @throws InvokeException if required headers are missing or content-type is not supported
     */
    protected function validateRequest(): static
    {
        if (!isset($this->rawRequest->header['content-type']) || !isset($this->rawRequest->header['te'])) {
            throw InvokeException::create('illegal GRPC request, missing content-type or te header');
        }

        if (
            $this->rawRequest->header['content-type'] !== 'application/grpc'
            && $this->rawRequest->header['content-type'] !== 'application/grpc+proto'
            && $this->rawRequest->header['content-type'] !== 'application/grpc+json'
        ) {
            throw InvokeException::create(
                "Content-type not supported: {$this->rawRequest->header['content-type']}",
                Status::INTERNAL
            );
        }

        $this->contentType = $this->rawRequest->header['content-type'] ?? '';

        return $this;
    }
}
