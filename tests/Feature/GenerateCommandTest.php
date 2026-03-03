<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->csvPath = base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv'));

    File::ensureDirectoryExists(dirname($this->csvPath));
    File::ensureDirectoryExists(lang_path());

    // Default CSV — one row with translations in all locales
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', 'Bonjour', 'مرحبا'],
    ]);
});

afterEach(function () {
    @unlink($this->csvPath);

    foreach ((array) config('ilsawn.locales', []) as $locale) {
        @unlink(lang_path("{$locale}.json"));
    }

    foreach (glob($this->csvPath . '.backup.*') ?: [] as $backup) {
        @unlink($backup);
    }
});

// ---------------------------------------------------------------------------
// Basic generation
// ---------------------------------------------------------------------------

it('runs successfully', function () {
    $this->artisan('ilsawn:generate')->assertSuccessful();
});

it('generates a JSON file for every configured locale', function () {
    $this->artisan('ilsawn:generate');

    foreach ((array) config('ilsawn.locales') as $locale) {
        expect(file_exists(lang_path("{$locale}.json")))->toBeTrue();
    }
});

it('writes correct translations to each JSON file', function () {
    $this->artisan('ilsawn:generate');

    $en = json_decode(file_get_contents(lang_path('en.json')), true);
    $fr = json_decode(file_get_contents(lang_path('fr.json')), true);

    expect($en['hello'])->toBe('Hello')
        ->and($fr['hello'])->toBe('Bonjour');
});

// ---------------------------------------------------------------------------
// Fallback chain
// ---------------------------------------------------------------------------

it('applies the default locale fallback for empty cells', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', '', ''],   // fr and ar empty → fall back to en
    ]);

    $this->artisan('ilsawn:generate');

    $ar = json_decode(file_get_contents(lang_path('ar.json')), true);

    expect($ar['hello'])->toBe('Hello');
});

// ---------------------------------------------------------------------------
// --dry-run
// ---------------------------------------------------------------------------

it('dry-run does not produce any JSON files', function () {
    $this->artisan('ilsawn:generate --dry-run')->assertSuccessful();

    foreach ((array) config('ilsawn.locales') as $locale) {
        expect(file_exists(lang_path("{$locale}.json")))->toBeFalse();
    }
});

it('dry-run does not modify the CSV', function () {
    $before = file_get_contents($this->csvPath);

    $this->artisan('ilsawn:generate --dry-run --scan');

    expect(file_get_contents($this->csvPath))->toBe($before);
});

// ---------------------------------------------------------------------------
// Error handling
// ---------------------------------------------------------------------------

it('fails when the CSV file does not exist', function () {
    @unlink($this->csvPath);

    $this->artisan('ilsawn:generate')->assertFailed();
});

// ---------------------------------------------------------------------------
// --cleanup
// ---------------------------------------------------------------------------

it('cleanup removes unused keys from the CSV after confirmation', function () {
    // 'unused.key' is in the CSV but not referenced anywhere in scan paths
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello',      'Hello', 'Bonjour', ''],
        ['unused.key', 'Unused', '', ''],
    ]);

    $this->artisan('ilsawn:generate --cleanup')
        ->expectsConfirmation('Remove these unused keys from the CSV?', 'yes')
        ->assertSuccessful();

    $csv = File::get($this->csvPath);

    expect($csv)->not->toContain('unused.key');
});

it('cleanup dry-run lists unused keys without modifying the CSV', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello',      'Hello', '', ''],
        ['unused.key', 'Unused', '', ''],
    ]);

    $before = file_get_contents($this->csvPath);

    $this->artisan('ilsawn:generate --cleanup --dry-run')->assertSuccessful();

    expect(file_get_contents($this->csvPath))->toBe($before);
});
