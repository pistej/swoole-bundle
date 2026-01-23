<?php

declare(strict_types=1);

namespace SwooleBundle\SwooleBundle\Server\Grpc\Service;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionObject;
use ReflectionUnionType;
use SwooleBundle\SwooleBundle\Server\Grpc\Constant;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\ContextInterface;
use SwooleBundle\SwooleBundle\Server\Grpc\Context\StreamResponseInterface;
use SwooleBundle\SwooleBundle\Server\Grpc\Exception\ServiceException;

/**
 * Class ServiceMethodScanner
 *
 * Scans a service instance for valid gRPC methods and returns their definitions.
 */
class ServiceMethodScanner
{
    /**
     * ServiceMethodScanner constructor.
     *
     * @param object $instance The service instance to scan for gRPC methods.
     */
    public function __construct(protected object $instance)
    {
    }

    /**
     * Scans the service instance for valid gRPC methods and returns their definitions.
     *
     * @return array<string, ServiceMethodDefinition> Array of method names to their definitions.
     * @throws ServiceException If a method does not meet gRPC requirements.
     */
    public function getMethods(): array
    {
        $reflection = new ReflectionObject($this->instance);

        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Check if its a gRPC method before doing this check

            if (count($method->getParameters()) > 0 && $method->getParameters()[0]->getType()->getName() == ContextInterface::class) {
                // This is a gRPC method
                $numParameters = $method->getNumberOfParameters();
                if ($numParameters < 2 || $numParameters > 3) {
                    throw new ServiceException('error method');
                }

                if ($numParameters === 2) {
                    $methods[$method->getName()] = $this->parseUnaryMethod($method);
                }

                if ($numParameters === 3) {
                    $methods[$method->getName()] = $this->parseStreamMethod($method);
                }
            }
        }

        return $methods;
    }

    /**
     * Parses a unary gRPC method and returns its definition.
     *
     * @param ReflectionFunctionAbstract $method the method to parse
     * @return ServiceMethodDefinition the definition of the unary method
     * @throws ServiceException if the return type is a union type
     */
    private function parseUnaryMethod(ReflectionFunctionAbstract $method): ServiceMethodDefinition
    {
        [, $input]  = $method->getParameters();
        $returnType = $method->getReturnType();

        if ($returnType instanceof ReflectionUnionType) {
            throw new ServiceException('error method: can\'t have union return type');
        }

        return new ServiceMethodDefinition(
            name: $method->getName(),
            paramType: $input->getType()->getName(),
            returnType: $returnType->getName(),
            type: Constant::GRPC_CALL_TYPE_UNARY
        );
    }

    /**
     * Parses a server-streaming gRPC method and returns its definition.
     *
     * @param ReflectionFunctionAbstract $method the method to parse
     * @return ServiceMethodDefinition the definition of the stream method
     * @throws ServiceException if the return type is not void or the output parameter does not implement StreamResponseInterface
     */
    private function parseStreamMethod(ReflectionFunctionAbstract $method): ServiceMethodDefinition
    {
        [, $input, $output] = $method->getParameters();
        // var_dump($input->getType(), $output->getType()->getName);
        $returnType = $method->getReturnType();

        if ($returnType->getName() !== 'void') {
            throw new ServiceException(
                "error method({$method->getName()}): since its stream response, should return void"
            );
        }

        $outputClassName = $output->getType()->getName();

        if (!in_array(StreamResponseInterface::class, class_implements($outputClassName))) {
            throw new ServiceException("error method({$method->getName()}): since its stream response, the third parameter should implement " . StreamResponseInterface::class);
        }

        $outputType = $this->retreiveSendParameterType($outputClassName);

        return new ServiceMethodDefinition(
            name: $method->getName(),
            paramType: $input->getType()->getName(),
            returnType: $outputType,
            type: Constant::GRPC_CALL_TYPE_STREAM,
            streamType: $outputClassName
        );
    }

    /**
     * Retrieves the type of the parameter accepted by the send() method of a stream response class.
     *
     * @param string $className the class name implementing StreamResponseInterface
     * @return string the type name of the send() method's parameter
     */
    private function retreiveSendParameterType(string $className): string
    {
        $rc = new ReflectionClass($className);

        $send = $rc->getMethod('send');

        [$msg] = $send->getParameters();

        return $msg->getType()->getName();
    }
}
