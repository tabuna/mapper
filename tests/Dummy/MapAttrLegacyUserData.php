<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

#[Map(target: MapAttrLegacyUser::class, transform: [MapAttrLegacyUser::class, 'createFromLegacy'])]
class MapAttrLegacyUserData
{
    public int $userId = 0;

    public string $name = '';
}
