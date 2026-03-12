<?php

namespace ilsawn\LaravelIlsawn\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    protected $signature = 'ilsawn:install
                            {--force : Overwrite already-published files}';

    protected $description = 'Install Ilsawn: publish config, create the CSV stub, and publish the Gate provider';

    public function handle(): int
    {
        $this->info('Installing Ilsawn...');
        $this->newLine();

        $this->publishConfig();
        $this->ensureLangDirectory();
        $this->createCsv();
        $this->publishProvider();
        $this->printProviderInstructions();

        if ($this->inertiaIsInstalled()) {
            $this->newLine();
            $this->printInertiaInstructions();
            $this->newLine();
            $this->printJsHookInstructions();
        } else {
            $this->newLine();
            $this->printBladeJsInstructions();
        }

        $this->newLine();
        $this->info('Ilsawn installed successfully.');
        $this->line('Run <comment>php artisan ilsawn:generate</comment> to generate your JSON locale files.');

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Install steps
    // -------------------------------------------------------------------------

    private function publishConfig(): void
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'ilsawn-config',
            '--force' => $this->option('force'),
        ]);

        $this->line('<info>✓</info> Config published → <comment>config/ilsawn.php</comment>');
    }

    private function ensureLangDirectory(): void
    {
        $langPath = base_path('lang');

        if (File::isDirectory($langPath)) {
            return;
        }

        if ($this->confirm('The <comment>lang/</comment> directory does not exist. Run <comment>php artisan lang:publish</comment> to create it?', true)) {
            $this->call('lang:publish');
        }
    }

    private function createCsv(): void
    {
        $csvPath = base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv'));

        if (File::exists($csvPath) && ! $this->option('force')) {
            $this->line('<comment>⚠</comment>  CSV already exists, skipping → <comment>'.$csvPath.'</comment>');

            return;
        }

        File::ensureDirectoryExists(dirname($csvPath));

        $delimiter = (string) config('ilsawn.delimiter', ';');
        $locales = (array) config('ilsawn.locales', ['en']);

        $handle = fopen($csvPath, 'w');

        if ($handle === false) {
            $this->error("Cannot create CSV: {$csvPath}");

            return;
        }

        fputcsv($handle, array_merge(['key'], $locales), $delimiter);
        fclose($handle);

        $this->line('<info>✓</info> CSV created → <comment>'.$csvPath.'</comment>');
    }

    private function publishProvider(): void
    {
        $destination = app_path('Providers/IlsawnServiceProvider.php');

        if (File::exists($destination) && ! $this->option('force')) {
            $this->line('<comment>⚠</comment>  IlsawnServiceProvider already exists, skipping.');

            return;
        }

        File::ensureDirectoryExists(app_path('Providers'));
        File::copy(__DIR__.'/../../stubs/IlsawnServiceProvider.stub', $destination);

        $this->line('<info>✓</info> Gate provider published → <comment>app/Providers/IlsawnServiceProvider.php</comment>');
    }

    // -------------------------------------------------------------------------
    // Post-install instructions
    // -------------------------------------------------------------------------

    private function printProviderInstructions(): void
    {
        $this->newLine();
        $this->comment('Register IlsawnServiceProvider in your application:');

        if (version_compare(app()->version(), '11.0.0', '>=')) {
            $this->line('  Add to <info>bootstrap/providers.php</info>:');
        } else {
            $this->line('  Add to the <info>providers</info> array in <info>config/app.php</info>:');
        }

        $this->line('  <comment>App\Providers\IlsawnServiceProvider::class,</comment>');
    }

    private function printInertiaInstructions(): void
    {
        $this->comment('Inertia.js detected — share translations with your frontend:');
        $this->newLine();
        $this->line('  Open <info>app/Http/Middleware/HandleInertiaRequests.php</info> and make two additions:');
        $this->newLine();
        $this->line('  1. Add the trait import at the top of the class:');
        $this->line('     <comment>use ilsawn\LaravelIlsawn\SharesTranslations;</comment>');
        $this->newLine();
        $this->line('  2. Use the trait and add translations to your share() method:');
        $this->line('     <comment>use SharesTranslations; // ← add this inside the class</comment>');
        $this->newLine();
        $this->line('     <comment>public function share(Request $request): array</comment>');
        $this->line('     <comment>{</comment>');
        $this->line('     <comment>    return [</comment>');
        $this->line('     <comment>        ...parent::share($request),</comment>');
        $this->line('     <comment>        // ... your existing props ...</comment>');
        $this->line('     <comment>        \'translations\' => $this->translations($request), // ← add this</comment>');
        $this->line('     <comment>    ];</comment>');
        $this->line('     <comment>}</comment>');
    }

    private function printBladeJsInstructions(): void
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'ilsawn-js',
            '--force' => $this->option('force'),
        ]);

        $this->line('<info>✓</info> JS hooks published → <comment>resources/js/vendor/ilsawn/</comment>');
        $this->newLine();
        $this->comment('Blade / Alpine.js — use translations in JS:');
        $this->newLine();
        $this->line('  1. Add to your main layout <info><head></info>:');
        $this->line('     <comment>@ilsawnTranslations</comment>');
        $this->newLine();
        $this->line('  2. Import the adapter in your JS entry file:');
        $this->line("     <comment>import '@/vendor/ilsawn/adapters/blade';</comment>");
        $this->newLine();
        $this->line('  Then <comment>__(\'key\')</comment> works everywhere, including Alpine.js expressions:');
        $this->line('     <comment>x-text="__(\'dashboard.title\')"</comment>');
    }

    private function printJsHookInstructions(): void
    {
        $this->callSilently('vendor:publish', [
            '--tag' => 'ilsawn-js',
            '--force' => $this->option('force'),
        ]);

        $this->line('<info>✓</info> JS hooks published → <comment>resources/js/vendor/ilsawn/</comment>');
        $this->newLine();

        $adapter = $this->detectJsFramework();

        if ($adapter !== null) {
            [$label, $path] = $adapter;
            $this->comment("Import the {$label} adapter:");
            $this->line("  <comment>import { useLang } from '@/vendor/ilsawn/adapters/{$path}';</comment>");
        } else {
            $this->comment('Import the adapter that matches your frontend:');
            $this->line('  React  → <comment>import { useLang } from \'@/vendor/ilsawn/adapters/react\';</comment>');
            $this->line('  Vue 3  → <comment>import { useLang } from \'@/vendor/ilsawn/adapters/vue\';</comment>');
            $this->line('  Svelte → <comment>import { useLang } from \'@/vendor/ilsawn/adapters/svelte\';</comment>');
        }

        $this->newLine();
        $this->line('  Then use <comment>__(\'key\')</comment> — same as PHP.');
    }

    // -------------------------------------------------------------------------
    // Inertia / JS framework detection
    // -------------------------------------------------------------------------

    private function inertiaIsInstalled(): bool
    {
        $composerPath = base_path('composer.json');

        if (! File::exists($composerPath)) {
            return false;
        }

        $composer = json_decode(File::get($composerPath), true);

        if (! is_array($composer)) {
            return false;
        }

        return isset($composer['require']['inertiajs/inertia-laravel'])
            || isset($composer['require-dev']['inertiajs/inertia-laravel']);
    }

    /**
     * Detect the JS framework from package.json.
     *
     * @return array{0: string, 1: string}|null [label, adapter path] or null if unknown
     */
    private function detectJsFramework(): ?array
    {
        $packagePath = base_path('package.json');

        if (! File::exists($packagePath)) {
            return null;
        }

        $package = json_decode(File::get($packagePath), true);

        if (! is_array($package)) {
            return null;
        }

        $deps = array_merge(
            (array) ($package['dependencies'] ?? []),
            (array) ($package['devDependencies'] ?? []),
        );

        if (isset($deps['react'])) {
            return ['React', 'react'];
        }

        if (isset($deps['vue'])) {
            return ['Vue 3', 'vue'];
        }

        if (isset($deps['svelte'])) {
            return ['Svelte', 'svelte'];
        }

        return null;
    }
}
