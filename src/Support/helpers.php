<?php

use Tabuna\Map\Mapper;

if (! function_exists('map')) {
    function map(mixed $source): Mapper
    {
        return Mapper::map($source);
    }
}
