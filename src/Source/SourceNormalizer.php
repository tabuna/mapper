<?php

namespace Tabuna\Map\Source;

use Tabuna\Map\Source\Contracts\ObjectPayloadExtractor;
use Tabuna\Map\Source\Extractors\ArrayableObjectExtractor;
use Tabuna\Map\Source\Extractors\HttpClientResponseExtractor;
use Tabuna\Map\Source\Extractors\IlluminateRequestExtractor;
use Tabuna\Map\Source\Extractors\PsrServerRequestExtractor;
use Tabuna\Map\Source\Extractors\SymfonyRequestExtractor;
use Tabuna\Map\Source\Extractors\ValidatedPayloadExtractor;
use Tabuna\Map\Source\Extractors\WordPressRestRequestExtractor;

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
            new IlluminateRequestExtractor(),
            new SymfonyRequestExtractor(),
            new WordPressRestRequestExtractor(),
            new PsrServerRequestExtractor(),
            new HttpClientResponseExtractor(),
            new ArrayableObjectExtractor(),
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

        return get_object_vars($item);
    }
}
