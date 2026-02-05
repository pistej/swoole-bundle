<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc;

/**
 * Marker interface for gRPC service classes.
 *
 * All gRPC services must implement this interface and define a NAME constant
 * representing the fully-qualified service name (e.g., '/myapp.MyService').
 */
interface GrpcService
{
    /**
     * The fully-qualified name of the gRPC service.
     * Must start with '/' and follow the format '/package.ServiceName'
     *
     * Example: '/myapp.UserService'
     */
    public const NAME = '';
}
