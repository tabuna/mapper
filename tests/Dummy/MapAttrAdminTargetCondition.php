<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Tabuna\Map\Contracts\ConditionCallableInterface;

class MapAttrAdminTargetCondition implements ConditionCallableInterface
{
    public function __invoke(mixed $value, object $source, ?object $target): bool
    {
        return $target instanceof MapAttrAdminUserProfile;
    }
}
