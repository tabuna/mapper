<?php

namespace Tabuna\Map\Source\Extractors;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class ValidatedPayloadExtractor implements ObjectPayloadExtractor
{
    protected const FORM_REQUEST_CLASS = 'Illuminate\\Foundation\\Http\\FormRequest';

    protected const LARAVEL_VALIDATOR_INTERFACE = 'Illuminate\\Contracts\\Validation\\Validator';

    /**
     * Sources for which validated payload strategy is enabled.
     *
     * @var array<int, class-string>
     */
    protected array $supportedClasses = [
        self::FORM_REQUEST_CLASS,
        self::LARAVEL_VALIDATOR_INTERFACE,
    ];

    public function extract(object $source): ?array
    {
        if (! $this->isSupportedSource($source)) {
            return null;
        }

        $validated = $this->extractValidatedArray($source);

        if (is_array($validated)) {
            return $validated;
        }

        if (! $this->isFormRequestSource($source)) {
            return null;
        }

        return $this->extractSafeArray($source);
    }

    protected function isSupportedSource(object $source): bool
    {
        foreach ($this->supportedClasses as $class) {
            if ((class_exists($class) || interface_exists($class)) && $source instanceof $class) {
                return true;
            }
        }

        return false;
    }

    protected function isFormRequestSource(object $source): bool
    {
        $class = self::FORM_REQUEST_CLASS;

        return class_exists($class) && $source instanceof $class;
    }

    /**
     * @return array|null
     */
    protected function extractValidatedArray(object $source): ?array
    {
        try {
            $validated = $source->validated();
        } catch (Throwable) {
            return null;
        }

        return is_array($validated) ? $validated : null;
    }

    /**
     * @return array|null
     */
    protected function extractSafeArray(object $source): ?array
    {
        try {
            $safe = $source->safe();
        } catch (Throwable) {
            return null;
        }

        if (! is_object($safe)) {
            return null;
        }

        try {
            $resolved = $safe->all();
        } catch (Throwable) {
            return null;
        }

        return is_array($resolved) ? $resolved : null;
    }
}
