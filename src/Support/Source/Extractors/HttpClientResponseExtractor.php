<?php

namespace Tabuna\Map\Support\Source\Extractors;

use Tabuna\Map\Support\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class HttpClientResponseExtractor implements ObjectPayloadExtractor
{
    public function extract(object $source): ?array
    {
        $jsonPayload = $this->extractJsonPayload($source);

        if (is_array($jsonPayload)) {
            return $jsonPayload;
        }

        $body = $this->extractBodyString($source);

        if (! is_string($body) || $body === '') {
            return null;
        }

        return $this->decodeJsonArray($body);
    }

    /**
     * Try response-specific JSON extractors.
     */
    protected function extractJsonPayload(object $source): ?array
    {
        if (! method_exists($source, 'json')) {
            return null;
        }

        try {
            $payload = $source->json();
        } catch (Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    /**
     * Try response-specific body extractors for Laravel HTTP and PSR-7/Guzzle-like responses.
     */
    protected function extractBodyString(object $source): ?string
    {
        if (method_exists($source, 'body')) {
            try {
                $body = $source->body();
            } catch (Throwable) {
                $body = null;
            }

            if (is_string($body)) {
                return $body;
            }
        }

        if (! method_exists($source, 'getBody')) {
            return null;
        }

        try {
            $stream = $source->getBody();
        } catch (Throwable) {
            return null;
        }

        if (is_string($stream)) {
            return $stream;
        }

        if (! is_object($stream)) {
            return null;
        }

        if (method_exists($stream, '__toString')) {
            try {
                return (string) $stream;
            } catch (Throwable) {
                // no-op: fallback to getContents() below
            }
        }

        if (method_exists($stream, 'getContents')) {
            try {
                $contents = $stream->getContents();
            } catch (Throwable) {
                return null;
            }

            return is_string($contents) ? $contents : null;
        }

        return null;
    }

    /**
     * Decode JSON body and keep only object-like payloads.
     */
    protected function decodeJsonArray(string $body): ?array
    {
        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
