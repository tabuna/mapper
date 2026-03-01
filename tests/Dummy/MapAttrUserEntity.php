<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

#[Map(target: MapAttrPublicUserProfile::class)]
#[Map(target: MapAttrAdminUserProfile::class)]
class MapAttrUserEntity
{
    #[Map(target: 'ipAddress', if: MapAttrAdminTargetCondition::class)]
    public ?string $lastLoginIp = null;

    #[Map(target: 'memberSince')]
    public string $registrationDate = '';
}
