<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

#[Map(target: MapAttrProduct::class)]
class MapAttrProductInput
{
    #[Map(target: 'email')]
    public string $customerEmail = '';

    #[Map(if: false)]
    public string $internalNotes = '';

    #[Map(target: 'code', transform: [self::class, 'normalizeCode'])]
    public string $sku = '';

    public static function normalizeCode(mixed $value): string
    {
        return strtoupper((string) $value);
    }
}
