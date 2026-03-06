<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\EventListener;

use Google\Protobuf\Internal\Message;
use SwooleBundle\SwooleBundle\Server\Grpc\HttpFoundation\GrpcResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class GrpcMessageViewSubscriber implements EventSubscriberInterface
{
    public function onKernelView(ViewEvent $event): void
    {
        $result = $event->getControllerResult();

        if (!($result instanceof Message)) {
            return;
        }

        $event->setResponse(new GrpcResponse($result));
    }

    /**
     * @return array<string, array<int, string|int>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 10],
        ];
    }
}
