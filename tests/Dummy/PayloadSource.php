<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

interface PayloadSource
{
    /**
     * @return array<string, mixed>
     */
    public function payload(): array;
}
