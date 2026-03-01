<?php

namespace Tabuna\Map\Transform;

use function array_keys;

class AttributeRules
{
    /**
     * Source-to-target attribute name map.
     *
     * @var array<string, string>
     */
    protected array $attributeRenames = [];

    /**
     * Enable automatic snake_case/kebab-case to camelCase key conversion.
     */
    protected bool $snakeToCamel = false;

    /**
     * Keep only selected source keys.
     *
     * @var array<int, string>|null
     */
    protected ?array $onlyKeys = null;

    /**
     * Exclude selected source keys.
     *
     * @var array<int, string>
     */
    protected array $exceptKeys = [];

    /**
     * Optional dot-notated path to nested source payload.
     */
    protected ?string $sourcePath = null;

    /**
     * Rename source attributes before mapping.
     *
     * @param array<string, string> $map
     */
    public function rename(array $map): void
    {
        foreach ($map as $from => $to) {
            $this->attributeRenames[$from] = $to;
        }
    }

    /**
     * Convert snake_case and kebab-case keys to camelCase before mapping.
     */
    public function snakeToCamelKeys(): void
    {
        $this->snakeToCamel = true;
    }

    /**
     * Keep only selected source keys before mapping.
     *
     * @param array<int, string> $keys
     */
    public function only(array $keys): void
    {
        $this->onlyKeys = $keys;
    }

    /**
     * Exclude selected source keys before mapping.
     *
     * @param array<int, string> $keys
     */
    public function except(array $keys): void
    {
        $this->exceptKeys = [...$this->exceptKeys, ...$keys];
    }

    /**
     * Select nested payload path before mapping (dot notation).
     */
    public function path(string $path): void
    {
        $this->sourcePath = trim($path);
    }

    /**
     * Apply path, filtering and key-conversion rules in one pass.
     *
     * @param array $attributes
     *
     * @return array
     */
    public function transform(array $attributes): array
    {
        if ($this->sourcePath !== null && $this->sourcePath !== '') {
            $attributes = $this->extractPathAttributes($attributes, $this->sourcePath);
        }

        if ($this->onlyKeys !== null) {
            $attributes = array_intersect_key($attributes, array_flip($this->onlyKeys));
        }

        if ($this->exceptKeys !== []) {
            foreach ($this->exceptKeys as $key) {
                unset($attributes[$key]);
            }
        }

        if ($this->attributeRenames !== []) {
            $renamed = [];

            foreach ($attributes as $key => $value) {
                $resolvedKey = is_string($key) && array_key_exists($key, $this->attributeRenames)
                    ? $this->attributeRenames[$key]
                    : $key;

                $renamed[$resolvedKey] = $value;
            }

            $attributes = $renamed;
        }

        if ($this->snakeToCamel) {
            $attributes = $this->convertArrayKeysToCamelCase($attributes);
        }

        return $attributes;
    }

    /**
     * Resolve dot-notated path from normalized attributes.
     *
     * @param array  $attributes
     * @param string $path
     *
     * @return array
     */
    public function extractPathAttributes(array $attributes, string $path): array
    {
        $resolved = $attributes;

        foreach (explode('.', $path) as $segment) {
            if ($segment === '') {
                continue;
            }

            if (! is_array($resolved) || ! array_key_exists($segment, $resolved)) {
                return [];
            }

            $resolved = $resolved[$segment];
        }

        return is_array($resolved) ? $resolved : [];
    }

    /**
     * Convert all associative keys to camelCase recursively.
     *
     * @param array $attributes
     *
     * @return array
     */
    public function convertArrayKeysToCamelCase(array $attributes): array
    {
        if ($this->isListArray($attributes)) {
            return array_map(
                fn ($value) => is_array($value) ? $this->convertArrayKeysToCamelCase($value) : $value,
                $attributes
            );
        }

        $converted = [];

        foreach ($attributes as $key => $value) {
            $resolvedKey = is_string($key) ? $this->toCamelCase($key) : $key;

            $converted[$resolvedKey] = is_array($value)
                ? $this->convertArrayKeysToCamelCase($value)
                : $value;
        }

        return $converted;
    }

    /**
     * Check if array keys are a zero-based sequential index.
     *
     * @param array $attributes
     */
    public function isListArray(array $attributes): bool
    {
        $index = 0;

        foreach (array_keys($attributes) as $key) {
            if ($key !== $index) {
                return false;
            }

            $index++;
        }

        return true;
    }

    /**
     * Convert a key from snake_case / kebab-case to camelCase.
     */
    public function toCamelCase(string $key): string
    {
        $key = str_replace('-', '_', $key);
        $segments = explode('_', $key);
        $first = array_shift($segments);

        if ($first === null) {
            return $key;
        }

        return $first.implode('', array_map('ucfirst', $segments));
    }
}
