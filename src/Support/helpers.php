<?php

use Tabuna\Map\Mapper;

if (! function_exists('map')) {
    function map(mixed $source): Mapper
    {
        return Mapper::map($source);
    }
}

if (! function_exists('map_into')) {
    /**
     * @param class-string $targetClass
     *
     * @return mixed
     */
    function map_into(mixed $source, string $targetClass): mixed
    {
        return Mapper::into($source, $targetClass);
    }
}

if (! function_exists('map_into_many')) {
    /**
     * @param class-string $targetClass
     */
    function map_into_many(mixed $source, string $targetClass): \Illuminate\Support\Collection
    {
        return Mapper::intoMany($source, $targetClass);
    }
}
