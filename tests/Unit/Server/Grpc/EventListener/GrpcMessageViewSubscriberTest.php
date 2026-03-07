<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Server\Grpc\EventListener;

use Google\Protobuf\Internal\Message;
use PHPUnit\Framework\TestCase;
use stdClass;
use SwooleBundle\SwooleBundle\Server\Grpc\EventListener\GrpcMessageViewSubscriber;
use SwooleBundle\SwooleBundle\Server\Grpc\HttpFoundation\GrpcResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class GrpcMessageViewSubscriberTest extends TestCase
{
    private GrpcMessageViewSubscriber $subscriber;

    protected function setUp(): void
    {
        if (!extension_loaded('grpc')) {
            self::markTestSkipped('Extension grpc is not loaded.');
        }

        $this->subscriber = new GrpcMessageViewSubscriber();
    }

    public function testSubscribesToViewEvent(): void
    {
        $events = GrpcMessageViewSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::VIEW, $events);
    }

    public function testWrapsProtobufMessageInGrpcResponse(): void
    {
        $message = $this->createMock(Message::class);
        $event = $this->createViewEvent($message);

        $this->subscriber->onKernelView($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(GrpcResponse::class, $response);
        $this->assertSame($message, $response->getMessage());
    }

    public function testIgnoresNonProtobufControllerResult(): void
    {
        $event = $this->createViewEvent(new stdClass());

        $this->subscriber->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresStringControllerResult(): void
    {
        $event = $this->createViewEvent('some string result');

        $this->subscriber->onKernelView($event);

        $this->assertNull($event->getResponse());
    }

    private function createViewEvent(mixed $controllerResult): ViewEvent
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/test');

        return new ViewEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $controllerResult);
    }
}
