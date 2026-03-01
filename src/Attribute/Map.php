<?php

namespace Tabuna\Map\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Map
{
    public function __construct(
        public ?string $target = null,
        public ?string $source = null,
        public mixed $if = true,
        public mixed $transform = null
    ) {}
}
