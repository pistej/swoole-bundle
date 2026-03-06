<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\ArgumentResolver;

use Google\Protobuf\Internal\Message;
use SwooleBundle\SwooleBundle\Server\Grpc\Serialization\PayloadDeserializer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

final readonly class GrpcMessageValueResolver implements ValueResolverInterface
{
    public function __construct(
        private PayloadDeserializer $protobufSerializer,
    ) {
    }

    /**
     * @return iterable<Message>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        /** @var class-string<Message>|null $type */
        $type = $argument->getType();

        if ($type === null || !is_a($type, Message::class, true)) {
            return;
        }

        $content = $request->getContent();

        if (is_string($content)) {
            $contentType = $request->headers->get('content-type', '');

            yield $this->protobufSerializer->deserialize($content, $type, $contentType);

            return;
        }

        return;
    }
}
