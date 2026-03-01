<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Source\Contracts\WordPressRequestPayload;

class WordPressRequestStub implements WordPressRequestPayload
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(private array $attributes) {}

    public function get_params(): array
    {
        return $this->attributes;
    }
}
