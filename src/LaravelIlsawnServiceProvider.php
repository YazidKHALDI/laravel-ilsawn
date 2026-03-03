<?php

namespace ilsawn\LaravelIlsawn;

use ilsawn\LaravelIlsawn\Commands\LaravelIlsawnCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelIlsawnServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-ilsawn')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_ilsawn_table')
            ->hasCommand(LaravelIlsawnCommand::class);
    }
}
