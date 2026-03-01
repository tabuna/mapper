<?php

namespace Tabuna\Map\Condition;

use Tabuna\Map\Contracts\ConditionCallableInterface;

class TargetClass implements ConditionCallableInterface
{
    /**
     * @param class-string $targetClass
     */
    public function __construct(protected string $targetClass) {}

    public function __invoke(mixed $value, object $source, ?object $target): bool
    {
        return is_object($target) && $target instanceof $this->targetClass;
    }
}
