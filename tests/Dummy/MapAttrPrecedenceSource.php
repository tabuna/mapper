<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

#[Map(target: MapAttrPrecedenceTarget::class)]
class MapAttrPrecedenceSource
{
    #[Map(target: 'code')]
    public string $legacyCode = '';
}
