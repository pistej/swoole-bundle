<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\Exception;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use SwooleBundle\SwooleBundle\Server\Grpc\Enum\Status;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\GRPCException;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\GrpcExceptionHandler;
use SwooleBundle\SwooleBundle\Server\Grpc\Writer\ResponseWriter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GrpcExceptionHandlerTest extends TestCase
{
    private GrpcExceptionHandler $handler;
    private SwooleRequest $request;

    /** @var array<string, string> */
    private array $capturedTrailers = [];

    /** @var array<string, string> */
    private array $capturedHeaders = [];

    protected function setUp(): void
    {
        $responseWriter = new ResponseWriter($this->createMock(LoggerInterface::class));
        $this->handler = new GrpcExceptionHandler($responseWriter);

        $this->request = new SwooleRequest();
        $this->request->header = ['content-type' => 'application/grpc+proto'];
    }

    public function testGrpcExceptionStatusIsForwarded(): void
    {
        $this->handler->handle(
            $this->request,
            GRPCException::create('not found', Status::NOT_FOUND),
            $this->createResponse(),
        );

        $this->assertSame((string) Status::NOT_FOUND->value, $this->capturedTrailers['grpc-status']);
    }

    public function testHttpNotFoundExceptionMapsToNotFound(): void
    {
        $this->handler->handle(
            $this->request,
            new NotFoundHttpException('route not found'),
            $this->createResponse(),
        );

        $this->assertSame((string) Status::NOT_FOUND->value, $this->capturedTrailers['grpc-status']);
    }

    public function testHttpAccessDeniedExceptionMapsToPermissionDenied(): void
    {
        $this->handler->handle(
            $this->request,
            new AccessDeniedHttpException('forbidden'),
            $this->createResponse(),
        );

        $this->assertSame((string) Status::PERMISSION_DENIED->value, $this->capturedTrailers['grpc-status']);
    }

    public function testGenericExceptionMapsToInternal(): void
    {
        $this->handler->handle(
            $this->request,
            new RuntimeException('something broke'),
            $this->createResponse(),
        );

        $this->assertSame((string) Status::INTERNAL->value, $this->capturedTrailers['grpc-status']);
    }

    public function testExceptionMessageIsForwardedAsGrpcMessage(): void
    {
        $this->handler->handle(
            $this->request,
            new RuntimeException('custom error message'),
            $this->createResponse(),
        );

        $this->assertSame('custom error message', $this->capturedTrailers['grpc-message']);
    }

    public function testContentTypeFromRequestIsForwarded(): void
    {
        $this->handler->handle(
            $this->request,
            new RuntimeException('error'),
            $this->createResponse(),
        );

        $this->assertSame('application/grpc+proto', $this->capturedHeaders['content-type']);
    }

    private function createResponse(): SwooleResponse
    {
        $this->capturedTrailers = [];
        $this->capturedHeaders = [];

        $response = $this->createMock(SwooleResponse::class);
        $response->method('trailer')
            ->willReturnCallback(function (string $name, string $value): bool {
                $this->capturedTrailers[$name] = $value;

                return true;
            });
        $response->method('header')
            ->willReturnCallback(function (string $name, string $value): bool {
                $this->capturedHeaders[$name] = $value;

                return true;
            });

        return $response;
    }
}
