<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Tests\Unit\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass;

use PHPUnit\Framework\TestCase;
use SwooleBundle\SwooleBundle\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\GrpcControllerPass;
use SwooleBundle\SwooleBundle\Server\Grpc\Registry\ControllerActionRegistry;
use SwooleBundle\SwooleBundle\Tests\Unit\Bridge\Symfony\Bundle\DependencyInjection\CompilerPass\Stub\StubGrpcController;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class GrpcControllerPassTest extends TestCase
{
    public function testProcessRegistersRoutes(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('swoole_bundle.grpc.enabled', true);

        $registryDefinition = new Definition(ControllerActionRegistry::class);
        $container->setDefinition(ControllerActionRegistry::class, $registryDefinition);

        $controllerDefinition = new Definition(StubGrpcController::class);
        $controllerDefinition->addTag('controller.service_arguments');
        $container->setDefinition(StubGrpcController::class, $controllerDefinition);

        $pass = new GrpcControllerPass();
        $pass->process($container);

        $methodCalls = $registryDefinition->getMethodCalls();

        $this->assertCount(1, $methodCalls);
        $this->assertEquals('register', $methodCalls[0][0]);
        $this->assertEquals(['/test-grpc-route', StubGrpcController::class, 'testMethod', '', ''], $methodCalls[0][1]);
    }

    public function testProcessDoesNothingWhenGrpcDisabled(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('swoole_bundle.grpc.enabled', false);

        $registryDefinition = new Definition(ControllerActionRegistry::class);
        $container->setDefinition(ControllerActionRegistry::class, $registryDefinition);

        $controllerDefinition = new Definition(StubGrpcController::class);
        $controllerDefinition->addTag('controller.service_arguments');
        $container->setDefinition(StubGrpcController::class, $controllerDefinition);

        (new GrpcControllerPass())->process($container);

        $this->assertCount(0, $registryDefinition->getMethodCalls());
    }

    public function testProcessDoesNothingWhenParameterNotSet(): void
    {
        $container = new ContainerBuilder();

        $registryDefinition = new Definition(ControllerActionRegistry::class);
        $container->setDefinition(ControllerActionRegistry::class, $registryDefinition);

        $controllerDefinition = new Definition(StubGrpcController::class);
        $controllerDefinition->addTag('controller.service_arguments');
        $container->setDefinition(StubGrpcController::class, $controllerDefinition);

        (new GrpcControllerPass())->process($container);

        $this->assertCount(0, $registryDefinition->getMethodCalls());
    }

    public function testProcessDoesNothingWithoutRegistry(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('swoole_bundle.grpc.enabled', true);

        $pass = new GrpcControllerPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(ControllerActionRegistry::class));
    }
}
