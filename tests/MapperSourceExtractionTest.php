<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests;

use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Client\Response as LaravelHttpResponse;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Collection;
use LogicException;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Tabuna\Map\Mapper;
use Tabuna\Map\Tests\Dummy\DummyAirport;
use Tabuna\Map\Tests\Dummy\DummyAirportPublicPrivateSource;
use Tabuna\Map\Tests\Dummy\WordPressRequestStub;

class MapperSourceExtractionTest extends MapperTestCase
{
    public function testItMapsIlluminateRequestToModel(): void
    {
        $request = IlluminateRequest::create('/fake', 'POST', [
            'code' => 'LPK',
            'city' => 'Lipetsk',
        ]);

        $mapped = Mapper::map($request)->to(DummyAirport::class);

        $this->assertInstanceOf(DummyAirport::class, $mapped);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItMapsSymfonyRequestToModel(): void
    {
        $request = SymfonyRequest::create('/fake', 'POST', [
            'code' => 'LED',
            'city' => 'Saint Petersburg',
        ]);

        $mapped = Mapper::map($request)->to(DummyAirport::class);

        $this->assertSame('LED', $mapped->code);
        $this->assertSame('Saint Petersburg', $mapped->city);
    }

    public function testItPrefersValidatedPayloadOverAllMethod(): void
    {
        $requestLike = new class extends FormRequest
        {
            public function validated($key = null, $default = null)
            {
                return ['code' => 'LPK', 'city' => 'Lipetsk'];
            }

            public function all($keys = null): array
            {
                return ['code' => 'RAW', 'city' => 'Raw City'];
            }
        };

        $mapped = Mapper::map($requestLike)->to(DummyAirport::class);

        $this->assertSame('LPK', $mapped->code);
        $this->assertSame('Lipetsk', $mapped->city);
    }

    public function testItUsesSafeAllPayloadWhenValidatedPayloadFails(): void
    {
        $requestLike = new class extends FormRequest
        {
            public function validated($key = null, $default = null)
            {
                throw new LogicException('Validation not available.');
            }

            public function safe(?array $keys = null)
            {
                return new class
                {
                    public function all(): array
                    {
                        return ['code' => 'DXB', 'city' => 'Dubai'];
                    }
                };
            }

            public function all($keys = null): array
            {
                return ['code' => 'RAW', 'city' => 'Raw City'];
            }
        };

        $mapped = Mapper::map($requestLike)->to(DummyAirport::class);

        $this->assertSame('DXB', $mapped->code);
        $this->assertSame('Dubai', $mapped->city);
    }

    public function testItFallsBackToAllWhenValidatedExtractionFails(): void
    {
        $requestLike = new class extends FormRequest
        {
            public function validated($key = null, $default = null)
            {
                throw new LogicException('Validation not available.');
            }

            public function safe(?array $keys = null)
            {
                throw new LogicException('Safe payload is not available.');
            }

            public function all($keys = null): array
            {
                return ['code' => 'SVO', 'city' => 'Moscow'];
            }
        };

        $mapped = Mapper::map($requestLike)->to(DummyAirport::class);

        $this->assertSame('SVO', $mapped->code);
        $this->assertSame('Moscow', $mapped->city);
    }

    public function testItIgnoresFrameworkMethodsForUnsupportedSourceClass(): void
    {
        $requestLike = new class
        {
            public string $code = 'RAW';
            public string $city = 'Raw City';

            public function safe(): object
            {
                return new class
                {
                    public function all(): array
                    {
                        return ['code' => 'DXB', 'city' => 'Dubai'];
                    }
                };
            }

            public function all(): array
            {
                return ['code' => 'DXB', 'city' => 'Dubai'];
            }
        };

        $mapped = Mapper::map($requestLike)->to(DummyAirport::class);

        $this->assertSame('RAW', $mapped->code);
        $this->assertSame('Raw City', $mapped->city);
    }

    public function testItMapsWordPressPayloadContractSource(): void
    {
        $requestLike = new WordPressRequestStub([
            'code' => 'LED',
            'city' => 'Saint Petersburg',
        ]);

        $mapped = Mapper::map($requestLike)->to(DummyAirport::class);

        $this->assertSame('LED', $mapped->code);
        $this->assertSame('Saint Petersburg', $mapped->city);
    }

    public function testItMapsArrayableSourceWhenSupportedInterfaceIsImplemented(): void
    {
        $source = new Collection(['code' => 'DXB', 'city' => 'Dubai']);

        $mapped = Mapper::map($source)->to(DummyAirport::class);

        $this->assertSame('DXB', $mapped->code);
        $this->assertSame('Dubai', $mapped->city);
    }

    public function testItMapsPsr7ParsedBodyFromServerRequest(): void
    {
        $requestLike = (new ServerRequest('POST', '/fake'))
            ->withParsedBody(['code' => 'JFK', 'city' => 'New York']);

        $mapped = Mapper::map($requestLike)->to(DummyAirport::class);

        $this->assertSame('JFK', $mapped->code);
        $this->assertSame('New York', $mapped->city);
    }

    public function testItMapsObjectUsingLaravelHttpJsonMethod(): void
    {
        $responseLike = new LaravelHttpResponse(
            new Psr7Response(200, ['Content-Type' => 'application/json'], '{"code":"LAX","city":"Los Angeles"}')
        );

        $mapped = Mapper::map($responseLike)->to(DummyAirport::class);

        $this->assertSame('LAX', $mapped->code);
        $this->assertSame('Los Angeles', $mapped->city);
    }

    public function testItMapsObjectUsingLaravelHttpBodyMethod(): void
    {
        $responseLike = new class(new Psr7Response(200, [], '{}')) extends LaravelHttpResponse
        {
            public function json($key = null, $default = null, $flags = null)
            {
                return null;
            }

            public function body()
            {
                return '{"code":"HND","city":"Tokyo"}';
            }
        };

        $mapped = Mapper::map($responseLike)->to(DummyAirport::class);

        $this->assertSame('HND', $mapped->code);
        $this->assertSame('Tokyo', $mapped->city);
    }

    public function testItMapsObjectUsingGuzzleLikeBodyStream(): void
    {
        $responseLike = new Psr7Response(200, ['Content-Type' => 'application/json'], '{"code":"BKK","city":"Bangkok"}');

        $mapped = Mapper::map($responseLike)->to(DummyAirport::class);

        $this->assertSame('BKK', $mapped->code);
        $this->assertSame('Bangkok', $mapped->city);
    }

    public function testItMapsCurlExecJsonStringSource(): void
    {
        $mapped = Mapper::map('{"code":"DXB","city":"Dubai"}')->to(DummyAirport::class);

        $this->assertSame('DXB', $mapped->code);
        $this->assertSame('Dubai', $mapped->city);
    }

    public function testItFallsBackToPublicPropertiesWhenSupportedExtractorThrows(): void
    {
        $responseLike = new class(new Psr7Response(200, ['Content-Type' => 'application/json'], '{}')) extends LaravelHttpResponse
        {
            public string $code = 'SVO';
            public string $city = 'Moscow';

            public function json($key = null, $default = null, $flags = null)
            {
                throw new LogicException('Cannot read json payload.');
            }

            public function body()
            {
                throw new LogicException('Cannot read body payload.');
            }
        };

        $mapped = Mapper::map($responseLike)->to(DummyAirport::class);

        $this->assertSame('SVO', $mapped->code);
        $this->assertSame('Moscow', $mapped->city);
    }

    public function testToArrayDoesNotExposePrivateSourceProperties(): void
    {
        $source = new DummyAirportPublicPrivateSource('LPK', 'hidden');

        $array = Mapper::map($source)->toArray();

        $this->assertSame(['code' => 'LPK'], $array);
        $this->assertArrayNotHasKey('secret', $array);
    }
}
