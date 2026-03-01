<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Attribute\Map;

#[Map(source: MapAttrApiPayload::class)]
class MapAttrProductFromPayload
{
    #[Map(source: 'product_name')]
    public string $name = '';

    #[Map(source: 'price_amount', transform: [self::class, 'cleanPrice'])]
    public int $price = 0;

    public string $code = '';

    public static function cleanPrice(mixed $value): int
    {
        return (int) preg_replace('/[^0-9]/', '', (string) $value);
    }
}
