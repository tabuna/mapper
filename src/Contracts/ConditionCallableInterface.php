<?php

namespace Tabuna\Map\Contracts;

interface ConditionCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): bool;
}
