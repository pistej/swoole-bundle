<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Exception;

use Swoole\Http\Request;
use Swoole\Http\Response;
use SwooleBundle\SwooleBundle\Server\Grpc\Status;
use SwooleBundle\SwooleBundle\Server\Grpc\Writer\ResponseWriter;
use SwooleBundle\SwooleBundle\Server\RequestHandler\ExceptionHandler\ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final readonly class GrpcExceptionHandler implements ExceptionHandler
{
    public function __construct(
        private ResponseWriter $responseWriter,
    ) {
    }

    public function handle(Request $request, Throwable $exception, Response $response): void
    {
        $contentType = $request->header['content-type'] ?? 'application/grpc';

        $this->responseWriter->writeError(
            $response,
            $this->resolveStatus($exception),
            $exception->getMessage(),
            $contentType,
        );
    }

    private function resolveStatus(Throwable $exception): Status
    {
        if ($exception instanceof GRPCException) {
            return Status::from($exception->getCode());
        }

        if ($exception instanceof HttpExceptionInterface) {
            return self::mapHttpStatus($exception->getStatusCode());
        }

        return Status::INTERNAL;
    }

    public static function mapHttpStatus(int $httpStatus): Status
    {
        return match ($httpStatus) {
            400 => Status::INVALID_ARGUMENT,
            401 => Status::UNAUTHENTICATED,
            403 => Status::PERMISSION_DENIED,
            404 => Status::NOT_FOUND,
            408 => Status::DEADLINE_EXCEEDED,
            409 => Status::ABORTED,
            412 => Status::FAILED_PRECONDITION,
            429 => Status::RESOURCE_EXHAUSTED,
            501 => Status::UNIMPLEMENTED,
            502, 503 => Status::UNAVAILABLE,
            504 => Status::DEADLINE_EXCEEDED,
            default => $httpStatus >= 500 ? Status::INTERNAL : Status::UNKNOWN,
        };
    }
}
