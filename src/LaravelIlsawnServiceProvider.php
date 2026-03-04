<?php

namespace ilsawn\LaravelIlsawn;

use ilsawn\LaravelIlsawn\Commands\InstallCommand;
use ilsawn\LaravelIlsawn\Commands\LaravelIlsawnCommand;
use ilsawn\LaravelIlsawn\Http\Middleware\Authorize;
use ilsawn\LaravelIlsawn\Livewire\TranslationsTable;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelIlsawnServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ilsawn')
            /*
             * Registers config/ilsawn.php and makes it publishable under the
             * "laravel-ilsawn-config" tag. Values are accessible via config('ilsawn.*').
             * The filename argument must match the config file name, not the package name.
             */
            ->hasConfigFile('ilsawn')
            /*
             * Registers the package's Blade / Livewire views under the "laravel-ilsawn"
             * namespace, publishable via the "laravel-ilsawn-views" tag.
             */
            ->hasViews()
            ->hasCommands(InstallCommand::class, LaravelIlsawnCommand::class);
    }

    public function bootingPackage(): void
    {
        /*
         * Publish the JS hooks under the "laravel-ilsawn-js" tag so users can
         * copy only the adapter(s) they need:
         *
         *   php artisan vendor:publish --tag=laravel-ilsawn-js
         */
        $this->publishes([
            __DIR__ . '/../resources/js' => resource_path('js/vendor/ilsawn'),
        ], 'laravel-ilsawn-js');

        Livewire::component('ilsawn-translations-table', TranslationsTable::class);

        $this->registerRoutes();
    }

    public function packageRegistered(): void
    {
        /*
         * Bind the core service as a singleton. The closure is resolved lazily,
         * so config() values are always fully merged before the service is built.
         */
        $this->app->singleton(LaravelIlsawn::class, function (): LaravelIlsawn {
            return new LaravelIlsawn(
                csvPath: base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv')),
                delimiter: (string) config('ilsawn.delimiter', ';'),
                locales: (array) config('ilsawn.locales', ['en']),
                defaultLocale: (string) config('ilsawn.default_locale', 'en'),
                scanPaths: array_map(
                    fn (string $path) => base_path($path),
                    (array) config('ilsawn.scan_paths', ['app', 'resources'])
                ),
            );
        });
    }

    private function registerRoutes(): void
    {
        Route::middleware(
            array_merge(
                (array) config('ilsawn.middleware', ['web']),
                [Authorize::class]
            )
        )
            ->prefix((string) config('ilsawn.route_prefix', 'ilsawn'))
            ->group(__DIR__ . '/../routes/web.php');
    }
}
