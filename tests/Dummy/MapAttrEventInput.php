<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

#[Map(target: MapAttrOnlineEvent::class, if: [self::class, 'isOnline'])]
#[Map(target: MapAttrPhysicalEvent::class, if: [self::class, 'isPhysical'])]
class MapAttrEventInput
{
    public string $type = 'online';

    public string $title = '';

    public static function isOnline(mixed $value, object $source): bool
    {
        return $source instanceof self && $source->type === 'online';
    }

    public static function isPhysical(mixed $value, object $source): bool
    {
        return $source instanceof self && $source->type === 'physical';
    }
}
