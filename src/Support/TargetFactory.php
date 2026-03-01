<?php

namespace Tabuna\Map\Support;

use Illuminate\Contracts\Container\Container as ContainerContract;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class TargetFactory
{
    public function __construct(protected ContainerContract $container) {}

    /**
     * Create target object and resolve constructor arguments from attributes/container.
     *
     * @param class-string $targetClass
     * @param array        $attributes
     */
    public function make(string $targetClass, array $attributes): object
    {
        try {
            $reflection = new ReflectionClass($targetClass);
            $constructor = $reflection->getConstructor();

            if ($constructor === null || $constructor->getNumberOfParameters() === 0) {
                return $this->container->make($targetClass);
            }

            $arguments = [];
            $usesSourceAttributes = false;

            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();

                if (array_key_exists($name, $attributes)) {
                    $arguments[] = $attributes[$name];
                    $usesSourceAttributes = true;

                    continue;
                }

                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                    $arguments[] = $this->container->make($type->getName());

                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $arguments[] = $parameter->getDefaultValue();

                    continue;
                }

                if ($parameter->allowsNull()) {
                    $arguments[] = null;

                    continue;
                }

                return $this->container->make($targetClass);
            }

            if (! $usesSourceAttributes) {
                return $this->container->make($targetClass);
            }

            return $reflection->newInstanceArgs($arguments);
        } catch (ReflectionException) {
            return $this->container->make($targetClass);
        }
    }
}
