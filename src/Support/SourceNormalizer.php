<?php

namespace Tabuna\Map\Support;

use Illuminate\Contracts\Support\Arrayable;
use Tabuna\Map\Support\Source\Contracts\ObjectPayloadExtractor;
use Tabuna\Map\Support\Source\Extractors\HttpClientResponseExtractor;
use Tabuna\Map\Support\Source\Extractors\MethodPayloadExtractor;
use Tabuna\Map\Support\Source\Extractors\PropertyBagPayloadExtractor;
use Tabuna\Map\Support\Source\Extractors\ValidatedPayloadExtractor;

class SourceNormalizer
{
    /**
     * @var array<int, ObjectPayloadExtractor>
     */
    protected array $objectExtractors;

    /**
     * @param array<int, ObjectPayloadExtractor>|null $objectExtractors
     */
    public function __construct(?array $objectExtractors = null)
    {
        $this->objectExtractors = $objectExtractors ?? [
            new ValidatedPayloadExtractor(),
            new MethodPayloadExtractor(['all', 'toArray', 'get_params', 'getParsedBody']),
            new PropertyBagPayloadExtractor(['request', 'query', 'attributes']),
            new HttpClientResponseExtractor(),
        ];
    }

    public function prependObjectExtractor(ObjectPayloadExtractor $extractor): void
    {
        array_unshift($this->objectExtractors, $extractor);
    }

    public function appendObjectExtractor(ObjectPayloadExtractor $extractor): void
    {
        $this->objectExtractors[] = $extractor;
    }

    /**
     * Normalize top-level source before mapping starts.
     */
    public function prepareSource(mixed $source): mixed
    {
        return match (true) {
            is_string($source) => json_decode($source, true, 512, JSON_THROW_ON_ERROR),
            default            => $source,
        };
    }

    /**
     * Normalize any supported source item to an array of attributes.
     *
     * @param mixed $item
     *
     * @return array
     */
    public function normalizeAttributes(mixed $item): array
    {
        return match (true) {
            is_array($item)  => $item,
            is_object($item) => $this->normalizeObjectAttributes($item),
            default          => (array) $item,
        };
    }

    /**
     * Normalize an object source using common request-like extractors.
     */
    public function normalizeObjectAttributes(object $item): array
    {
        foreach ($this->objectExtractors as $extractor) {
            $attributes = $extractor->extract($item);

            if (is_array($attributes)) {
                return $attributes;
            }
        }

        if ($item instanceof Arrayable) {
            return $item->toArray();
        }

        return get_object_vars($item);
    }

    /**
     * Try extracting validated payload from Laravel-style request objects.
     */
    public function extractValidatedAttributes(object $item): ?array
    {
        return (new ValidatedPayloadExtractor())->extract($item);
    }

    /**
     * Try extracting array payload via a parameterless method.
     */
    public function extractAttributesFromMethod(object $item, string $method): ?array
    {
        return (new MethodPayloadExtractor([$method]))->extract($item);
    }

    /**
     * Try extracting array payload from Symfony-like request bags.
     */
    public function extractAttributesFromPropertyBag(object $item, string $property): ?array
    {
        return (new PropertyBagPayloadExtractor([$property]))->extract($item);
    }
}
