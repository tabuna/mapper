<?php

namespace Tabuna\Map\Contracts;

interface TransformCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): mixed;
}
