<?php

namespace Log1x\LaravelWebfonts\Facades;

use Illuminate\Support\Facades\Facade;

class Webfonts extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-webfonts';
    }
}
