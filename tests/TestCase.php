<?php

namespace ilsawn\LaravelIlsawn\Tests;

use ilsawn\LaravelIlsawn\LaravelIlsawnServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelIlsawnServiceProvider::class,
        ];
    }
}
