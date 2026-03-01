<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

#[Map(target: MapAttrOnlineEvent::class)]
#[Map(target: MapAttrPhysicalEvent::class)]
class MapAttrAmbiguousInput
{
    public string $title = '';
}
