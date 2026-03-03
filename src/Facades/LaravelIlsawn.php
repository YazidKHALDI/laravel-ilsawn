<?php

namespace ilsawn\LaravelIlsawn\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ilsawn\LaravelIlsawn\LaravelIlsawn
 */
class LaravelIlsawn extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \ilsawn\LaravelIlsawn\LaravelIlsawn::class;
    }
}
