<?php

namespace Tabuna\Map\Container\Contracts;

interface SymfonyContainerLike
{
    /**
     * @return mixed
     */
    public function get(string $id);

    public function has(string $id): bool;
}
