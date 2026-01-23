<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Service;

use Google\Protobuf\Internal\Message;
use SwooleBundle\SwooleBundle\Server\Grpc\Constant;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\Context;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\ContextKeys;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\InvokeException;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\ServiceException;
use SwooleBundle\SwooleBundle\Server\Grpc\GrpcService;
use SwooleBundle\SwooleBundle\Server\Grpc\Status;
use Throwable;
use TypeError;
use Psr\Container\ContainerInterface;

/**
 * Class ServiceHandler
 *
 * Handles registration, resolution, and invocation of gRPC service methods.
 */
class ServiceHandler
{
    protected array $services = [];

    protected array $abstracts = [];

    /**
     * list of services method
     *
     * @var ServiceMethodDefinition
     */
    protected array $methods = [];

    /**
     * ServiceHandler constructor.
     *
     * @param iterable<GrpcService> $services List of service instances.
     * @param ContainerInterface|null $container Optional PSR container for dependency resolution.
     */
    public function __construct(
        iterable $services = [],
        protected ?ContainerInterface $container = null,
    ) {
        foreach ($services as $service) {
            $this->addService($service);
        }
    }

    /**
     * Resolve a service from the container or instantiate it directly.
     *
     * @template T
     * @param class-string<T>|string $abstract
     * @return T
     */
    public function resolve(string $abstract)
    {
        if ($this->container && $this->container->has($abstract)) {
            return $this->container->get($abstract);
        }

        return new $abstract();
    }

    /**
     * Register a service class for later resolution.
     *
     * @template T
     * @param class-string<T>|string $abstract
     */
    public function register(string $abstract): self
    {
        $this->abstracts[] = $abstract;

        return $this;
    }

    /**
     * Boot all registered services and collect their methods.
     */
    public function boot(): self
    {
        foreach ($this->abstracts as $service) {
            $this->add($service);
        }

        return $this;
    }

    /**
     * Add a service instance to the handler.
     */
    public function addService(GrpcService $service): self
    {
        $this->services[$service::NAME] = $service;
        $this->methods[$service::NAME] = $this->discoverMethods($service);

        return $this;
    }

    /**
     * Handle a gRPC request by dispatching to the appropriate service method.
     *
     * @param Context $context
     * @return Context
     */
    public function handle(Context $context): Context
    {
        $serviceName = $context->getRequest()->getService();
        $method      = $context->getRequest()->getMethod();
        $input       = $context->getRequest()->getPayload();

        [$service, $serviceMethodDefinition] = $this->checkIfServiceAvailable($serviceName, $method);

        $context->withAttribute(ContextKeys::SERVICE_METHOD_DEFINITION, $serviceMethodDefinition);

        $callable = [$service, $method];

        $paramTypeClass = $serviceMethodDefinition->paramType;

        /**
         * @var Message $message
         */
        $message = new $paramTypeClass();

        if ($input !== null) {
            if ($context->getRequest()->getContentType() !== 'application/grpc+json') {
                $message->mergeFromString($input);
            } else {
                $message->mergeFromJsonString($input);
            }
        }

        $output = '';
        if ($serviceMethodDefinition->type === Constant::GRPC_CALL_TYPE_STREAM) {
            $streamReply = new ($serviceMethodDefinition->streamType)($context);
            try {
                $result = $callable($context, $message, $streamReply);
            } catch (TypeError $e) {
                throw InvokeException::create($e->getMessage(), Status::INTERNAL, $e);
            }
            $output = '';
            $context->getResponse()->withMessage('OK')->withStatus(Status::OK);
        } else {
            try {
                $result = $callable($context, $message);
                $output = $context->getRequest()->getContentType() !== 'application/grpc+json'
                    ? $result->serializeToString()
                    : $result->serializeToJsonString();
            } catch (TypeError $e) {
                throw InvokeException::create($e->getMessage(), Status::INTERNAL, $e);
            } catch (Throwable $e) {
                throw InvokeException::create($e->getMessage(), Status::INTERNAL, $e);
            }
        }

        $context->getResponse()->setPayload($output);

        return $context;
    }

    /**
     * Check if the requested service and method are available.
     *
     * @param string $serviceName
     * @param string $method
     * @return array{0: GrpcService, 1: ServiceMethodDefinition}
     * @throws InvokeException If the service or method is not found.
     */
    protected function checkIfServiceAvailable(string $serviceName, string $method): array
    {
        if (!array_key_exists($serviceName, $this->services)) {
            throw InvokeException::create('Service Code 5', Status::NOT_FOUND);
        }

        if (!array_key_exists($method, $this->methods[$serviceName] ?? [])) {
            throw InvokeException::create('Code 5', Status::NOT_FOUND);
        }

        return [$this->services[$serviceName], $this->methods[$serviceName][$method]];
    }

    /**
     * Discover gRPC methods on the given service instance.
     *
     * @param object $instance
     * @return array<string, ServiceMethodDefinition>
     */
    protected function discoverMethods(object $instance): array
    {
        return (new ServiceMethodScanner($instance))->getMethods();
    }

    /**
     * Add a service to the handler and discover its methods.
     *
     * @param string $abstract
     * @return self
     * @throws \Bardiz12\SwooleGRPC\Exception\ServiceException If the service does not implement GrpcService.
     */
    private function add(string $abstract): self
    {
        $service = $this->resolve($abstract);
        if (!($service instanceof GrpcService)) {
            throw new ServiceException("{$abstract} is not GrpcService");
        }

        return $this->addService($service);
    }
}
