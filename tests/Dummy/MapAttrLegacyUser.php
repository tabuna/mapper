<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

class MapAttrLegacyUser
{
    public string $name = '';

    private int $legacyId = 0;

    public static function createFromLegacy(mixed $value, object $source): self
    {
        $user = new self();

        if (isset($source->userId)) {
            $user->legacyId = (int) $source->userId;
        }

        return $user;
    }

    public function legacyId(): int
    {
        return $this->legacyId;
    }
}
