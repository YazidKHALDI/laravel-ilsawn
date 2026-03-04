<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::ensureDirectoryExists(base_path('lang'));
    File::ensureDirectoryExists(app_path('Providers'));
});

afterEach(function () {
    @unlink(config_path('ilsawn.php'));
    @unlink(base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv')));
    @unlink(app_path('Providers/IlsawnServiceProvider.php'));
});

// ---------------------------------------------------------------------------
// lang/ directory prompt
// ---------------------------------------------------------------------------

it('does not prompt about lang:publish when lang/ already exists', function () {
    // beforeEach already creates base_path('lang'), so no prompt expected
    $this->artisan('ilsawn:install')
        ->doesntExpectOutputToContain('lang:publish')
        ->assertSuccessful();
});

it('prompts to run lang:publish when lang/ directory is missing', function () {
    File::deleteDirectory(base_path('lang'));

    $this->artisan('ilsawn:install')
        ->expectsConfirmation('The <comment>lang/</comment> directory does not exist. Run <comment>php artisan lang:publish</comment> to create it?', 'no')
        ->assertSuccessful();
});

// ---------------------------------------------------------------------------
// Basic success
// ---------------------------------------------------------------------------

it('runs successfully', function () {
    $this->artisan('ilsawn:install')->assertSuccessful();
});

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------

it('outputs a confirmation after publishing the config', function () {
    $this->artisan('ilsawn:install')
        ->expectsOutputToContain('config/ilsawn.php')
        ->assertSuccessful();
});

// ---------------------------------------------------------------------------
// CSV
// ---------------------------------------------------------------------------

it('creates the CSV file at the configured path', function () {
    $this->artisan('ilsawn:install');

    $csvPath = base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv'));

    expect(file_exists($csvPath))->toBeTrue();
});

it('writes a correct header row to the CSV', function () {
    $this->artisan('ilsawn:install');

    $csvPath  = base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv'));
    $handle   = fopen($csvPath, 'r');
    $firstRow = fgetcsv($handle, 0, (string) config('ilsawn.delimiter', ';'));
    fclose($handle);

    $expected = array_merge(['key'], (array) config('ilsawn.locales', ['en', 'fr', 'ar']));

    expect($firstRow)->toBe($expected);
});

it('does not overwrite an existing CSV', function () {
    $csvPath = base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv'));
    file_put_contents($csvPath, 'existing content');

    $this->artisan('ilsawn:install');

    expect(file_get_contents($csvPath))->toBe('existing content');
});

it('overwrites an existing CSV when --force is passed', function () {
    $csvPath = base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv'));
    file_put_contents($csvPath, 'existing content');

    $this->artisan('ilsawn:install --force');

    expect(file_get_contents($csvPath))->not->toBe('existing content');
});

// ---------------------------------------------------------------------------
// Gate provider
// ---------------------------------------------------------------------------

it('publishes the IlsawnServiceProvider', function () {
    $this->artisan('ilsawn:install');

    expect(file_exists(app_path('Providers/IlsawnServiceProvider.php')))->toBeTrue();
});

it('published provider contains the viewIlsawn gate definition', function () {
    $this->artisan('ilsawn:install');

    $content = file_get_contents(app_path('Providers/IlsawnServiceProvider.php'));

    expect($content)->toContain('viewIlsawn');
});

it('does not overwrite an existing IlsawnServiceProvider', function () {
    $providerPath = app_path('Providers/IlsawnServiceProvider.php');
    file_put_contents($providerPath, '// existing provider');

    $this->artisan('ilsawn:install');

    expect(file_get_contents($providerPath))->toBe('// existing provider');
});

it('overwrites an existing IlsawnServiceProvider when --force is passed', function () {
    $providerPath = app_path('Providers/IlsawnServiceProvider.php');
    file_put_contents($providerPath, '// existing provider');

    $this->artisan('ilsawn:install --force');

    expect(file_get_contents($providerPath))->not->toBe('// existing provider');
});

// ---------------------------------------------------------------------------
// JS hooks
// ---------------------------------------------------------------------------

it('prints JS hook instructions when Inertia is installed', function () {
    // Fake a composer.json with inertiajs/inertia-laravel in require
    $composerPath = base_path('composer.json');
    $original     = file_exists($composerPath) ? file_get_contents($composerPath) : null;

    file_put_contents($composerPath, json_encode([
        'require' => ['inertiajs/inertia-laravel' => '^1.0'],
    ]));

    $this->artisan('ilsawn:install')
        ->expectsOutputToContain('laravel-ilsawn-js')
        ->assertSuccessful();

    // Restore original composer.json
    if ($original !== null) {
        file_put_contents($composerPath, $original);
    } else {
        @unlink($composerPath);
    }
});

it('does not print JS hook instructions when Inertia is not installed', function () {
    $composerPath = base_path('composer.json');
    $original     = file_exists($composerPath) ? file_get_contents($composerPath) : null;

    file_put_contents($composerPath, json_encode(['require' => []]));

    $this->artisan('ilsawn:install')
        ->doesntExpectOutputToContain('laravel-ilsawn-js')
        ->assertSuccessful();

    if ($original !== null) {
        file_put_contents($composerPath, $original);
    } else {
        @unlink($composerPath);
    }
});
