<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

class MapAttrPrecedenceTarget
{
    #[Map(source: 'legacy_code')]
    public string $code = '';
}
