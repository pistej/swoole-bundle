<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use Google\Protobuf\Internal\Message;
use ReflectionAttribute;
use ReflectionMethod;
use ReflectionNamedType;
use SwooleBundle\SwooleBundle\Server\Grpc\Registry\ControllerActionRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Attribute\Route as RouteAttribute;

final class GrpcControllerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasParameter('swoole_bundle.grpc.enabled')
            || !$container->getParameter('swoole_bundle.grpc.enabled')
        ) {
            return;
        }

        if (!$container->hasDefinition(ControllerActionRegistry::class)) {
            return;
        }

        $registryDefinition = $container->getDefinition(ControllerActionRegistry::class);

        foreach (array_keys($container->findTaggedServiceIds('controller.service_arguments')) as $id) {
            $class = $container->getDefinition($id)->getClass();

            if ($class === null) {
                continue;
            }

            $reflectionClass = $container->getReflectionClass($class);
            if ($reflectionClass === null) {
                continue;
            }

            foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(RouteAttribute::class, ReflectionAttribute::IS_INSTANCEOF);

                if ($attributes === []) {
                    continue;
                }

                foreach ($attributes as $attribute) {
                    /** @var RouteAttribute $route */
                    $route = $attribute->newInstance();
                    $path = $route->getPath();

                    if ($path === null || $path === '') {
                        continue;
                    }

                    [$paramType, $paramName] = $this->resolveProtobufParam($method);

                    $registryDefinition->addMethodCall(
                        'register',
                        [$path, $id, $method->getName(), $paramType, $paramName]
                    );
                }
            }
        }
    }

    /**
     * Returns [typeName, paramName] for the first protobuf Message parameter, or [null, null].
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveProtobufParam(ReflectionMethod $method): array
    {
        foreach ($method->getParameters() as $parameter) {
            $type = $parameter->getType();

            if (!($type instanceof ReflectionNamedType) || $type->isBuiltin()) {
                continue;
            }

            $typeName = $type->getName();
            if (is_subclass_of($typeName, Message::class) || $typeName === Message::class) {
                return [$typeName, $parameter->getName()];
            }
        }

        return [null, null];
    }
}
