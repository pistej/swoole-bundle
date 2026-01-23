<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\ArgumentResolver;

use Google\Protobuf\Internal\Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final class GrpcMessageValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<Message>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $type = $argument->getType();

        if ($type === null || !is_a($type, Message::class, true)) {
            return;
        }

        $content = $request->getContent();

        if ($content instanceof $type) {
            yield $content;

            return;
        }

        foreach ($request->attributes->all() as $attribute) {
            if ($attribute instanceof $type) {
                yield $attribute;

                return;
            }
        }

        return;
    }
}
