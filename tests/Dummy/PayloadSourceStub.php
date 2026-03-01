<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

class PayloadSourceStub implements PayloadSource
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(private array $attributes) {}

    public function payload(): array
    {
        return $this->attributes;
    }
}
