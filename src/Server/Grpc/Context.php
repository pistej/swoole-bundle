<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

use Swoole\Http\Request as SwooleRequest;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\InvokeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Enum\ContentType;

final class Context
{
    private string $contentType = '';
    private string $requestUri = '';

    public function __construct(
        private readonly SwooleRequest $request,
    ) {
    }

    /**
     * Get the content-type header from the request.
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Get the parsed request URI.
     */
    public function getRequestUri(): string
    {
        return $this->requestUri;
    }

    /**
     * Validate the Swoole HTTP request headers for gRPC compliance.
     *
     * @throws InvokeException if required headers are missing or content-type is not supported
     */
    public function validateRequest(): self
    {
        if (!isset($this->request->header['content-type'], $this->request->header['te'])) {
            throw InvokeException::create(
                'Illegal GRPC request, missing content-type or te header',
                Status::INVALID_ARGUMENT
            );
        }

        $validContentTypes = ContentType::validTypes();
        if (!in_array($this->request->header['content-type'], $validContentTypes, true)) {
            throw InvokeException::create(
                "Content-type not supported: {$this->request->header['content-type']}",
                Status::INTERNAL
            );
        }

        $this->contentType = $this->request->header['content-type'];

        return $this;
    }

    /**
     * Parse the request URI from the Swoole HTTP request.
     *
     * @throws InvokeException if request URI is empty
     */
    public function parseRequest(): self
    {
        $requestUri = $this->request->server['request_uri'];

        if (empty($requestUri)) {
            throw InvokeException::create('Invalid gRPC request: empty request URI', Status::INVALID_ARGUMENT);
        }

        $this->requestUri = $requestUri;

        return $this;
    }
}
