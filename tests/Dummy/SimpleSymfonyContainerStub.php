<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use RuntimeException;
use Tabuna\Map\Container\Contracts\SymfonyContainerLike;

class SimpleSymfonyContainerStub implements SymfonyContainerLike
{
    /**
     * @param array<string, mixed> $entries
     */
    public function __construct(private array $entries = []) {}

    /**
     * @return mixed
     */
    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new RuntimeException("Entry [$id] is not defined.");
        }

        return $this->entries[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries);
    }
}
