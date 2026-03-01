<?php

namespace Tabuna\Map\Support\Source\Extractors;

use Tabuna\Map\Support\Source\Contracts\ObjectPayloadExtractor;
use Throwable;

class HttpClientResponseExtractor implements ObjectPayloadExtractor
{
    protected const LARAVEL_HTTP_RESPONSE_CLASS = 'Illuminate\\Http\\Client\\Response';

    protected const PSR_HTTP_RESPONSE_INTERFACE = 'Psr\\Http\\Message\\ResponseInterface';

    public function extract(object $source): ?array
    {
        if (! $this->isSupportedSource($source)) {
            return null;
        }

        if ($this->isLaravelHttpResponse($source)) {
            return $this->extractLaravelResponsePayload($source);
        }

        if ($this->isPsrHttpResponse($source)) {
            return $this->extractPsrResponsePayload($source);
        }

        return null;
    }

    protected function isSupportedSource(object $source): bool
    {
        return $this->isLaravelHttpResponse($source) || $this->isPsrHttpResponse($source);
    }

    protected function isLaravelHttpResponse(object $source): bool
    {
        $class = self::LARAVEL_HTTP_RESPONSE_CLASS;

        return class_exists($class) && $source instanceof $class;
    }

    protected function isPsrHttpResponse(object $source): bool
    {
        $interface = self::PSR_HTTP_RESPONSE_INTERFACE;

        return interface_exists($interface) && $source instanceof $interface;
    }

    protected function extractLaravelResponsePayload(object $source): ?array
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

    protected function extractPsrResponsePayload(object $source): ?array
    {
        $body = $this->extractPsrBodyString($source);

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
        try {
            $payload = $source->json();
        } catch (Throwable) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    /**
     * Try response-specific body extractors for Laravel HTTP responses.
     */
    protected function extractBodyString(object $source): ?string
    {
        try {
            $body = $source->body();
        } catch (Throwable) {
            $body = null;
        }

        return is_string($body) ? $body : null;
    }

    /**
     * Try response body extractors for PSR-7/Guzzle-like responses.
     */
    protected function extractPsrBodyString(object $source): ?string
    {
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

        $streamInterface = 'Psr\\Http\\Message\\StreamInterface';

        if (! interface_exists($streamInterface) || ! $stream instanceof $streamInterface) {
            return null;
        }

        try {
            $contents = $stream->getContents();
        } catch (Throwable) {
            return null;
        }

        return is_string($contents) ? $contents : null;
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
