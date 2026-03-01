<?php

namespace Tabuna\Map\Source\Extractors;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Tabuna\Map\Source\Contracts\WordPressRequestPayload;
use Throwable;

class WordPressRestRequestExtractor implements ObjectPayloadExtractor
{
    protected const REQUEST_CLASS = 'WP_REST_Request';

    public function extract(object $source): ?array
    {
        if (! $this->isSupportedSource($source)) {
            return null;
        }

        try {
            $resolved = $source->get_params();
        } catch (Throwable) {
            return null;
        }

        return is_array($resolved) ? $resolved : null;
    }

    protected function isSupportedSource(object $source): bool
    {
        if ($source instanceof WordPressRequestPayload) {
            return true;
        }

        $class = self::REQUEST_CLASS;

        return class_exists($class) && $source instanceof $class;
    }
}
