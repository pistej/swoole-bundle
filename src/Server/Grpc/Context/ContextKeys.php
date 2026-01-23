<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Context;

/**
 * Class ContextKeys
 *
 * Defines constant keys used for storing and retrieving context-related data.
 */
class ContextKeys
{
    /**
     * Key for the service method definition in the context.
     */
    public const SERVICE_METHOD_DEFINITION = 'service-method-definition';
}
