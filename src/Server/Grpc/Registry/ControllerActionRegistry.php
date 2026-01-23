<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Registry;

use SwooleBundle\SwooleBundle\Server\Grpc\Exception\InvokeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Status;

final class ControllerActionRegistry
{
    /** @var array<string, GrpcRoute> */
    private array $routes = [];

    public function register(
        string $grpcPath,
        string $controller,
        string $action,
        ?string $paramType = null,
        ?string $paramName = null,
    ): void {
        $this->routes[$grpcPath] = new GrpcRoute($controller, $action, $paramType, $paramName);
    }

    /**
     * Returns the GrpcRoute for the given path, or null if not found or missing a valid paramType.
     *
     * @throws InvokeException if the route is not found or missing a valid GRPC Message parameter
     */
    public function find(string $grpcPath): GrpcRoute
    {
        if (isset($this->routes[$grpcPath]) && $this->routes[$grpcPath]->paramType !== null) {
            return $this->routes[$grpcPath];
        }

        throw InvokeException::create(
            sprintf('gRPC route "%s" not found or missing valid GRPC Message parameter', $grpcPath),
            Status::UNIMPLEMENTED
        );
    }

    /**
     * @return array<string, GrpcRoute>
     */
    public function getAll(): array
    {
        return $this->routes;
    }

    public function __toString(): string
    {
        $output = "Registered gRPC Routes:\n";
        foreach ($this->routes as $path => $route) {
            $output .= sprintf(
                "  %s -> %s::%s(%s %s)\n",
                $path,
                $route->controller,
                $route->action,
                $route->paramType ?? 'void',
                $route->paramName ?? ''
            );
        }

        return $output;
    }
}
