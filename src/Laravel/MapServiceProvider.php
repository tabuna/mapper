<?php

namespace Tabuna\Map\Laravel;

use Illuminate\Support\ServiceProvider;
use Tabuna\Map\Mapper;

class MapServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mapper::useContainer($this->app);
    }
}
