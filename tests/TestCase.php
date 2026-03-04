<?php

namespace ilsawn\LaravelIlsawn\Tests;

use ilsawn\LaravelIlsawn\LaravelIlsawnServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * @param  Application $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            LaravelIlsawnServiceProvider::class,
        ];
    }

    /**
     * @param  Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Required for Livewire sessions and encryption
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}
