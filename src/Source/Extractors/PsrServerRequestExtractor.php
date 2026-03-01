<?php

namespace Tabuna\Map\Source\Extractors;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class PsrServerRequestExtractor implements ObjectPayloadExtractor
{
    protected const SERVER_REQUEST_INTERFACE = 'Psr\\Http\\Message\\ServerRequestInterface';

    public function extract(object $source): ?array
    {
        $interface = self::SERVER_REQUEST_INTERFACE;

        if (! interface_exists($interface) || ! $source instanceof $interface) {
            return null;
        }

        try {
            $resolved = $source->getParsedBody();
        } catch (Throwable) {
            return null;
        }

        return is_array($resolved) ? $resolved : null;
    }
}
