<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use ilsawn\LaravelIlsawn\Livewire\TranslationsTable;
use Livewire\Livewire;

beforeEach(function () {
    $this->csvPath = base_path((string) config('ilsawn.csv_path', 'lang/ilsawn.csv'));

    File::ensureDirectoryExists(dirname($this->csvPath));
    File::ensureDirectoryExists(lang_path());

    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello',      'Hello',   'Bonjour', 'مرحبا'],
        ['bye',        'Goodbye', '',         ''],
        ['nav.home',   'Home',    'Accueil',  ''],
    ]);

    Gate::define('viewIlsawn', fn () => true);
});

afterEach(function () {
    @unlink($this->csvPath);

    foreach ((array) config('ilsawn.locales', []) as $locale) {
        @unlink(lang_path("{$locale}.json"));
    }
});

// ---------------------------------------------------------------------------
// Route
// ---------------------------------------------------------------------------

it('returns 200 at the configured route prefix', function () {
    $user = new User;

    $this->actingAs($user)->get('/ilsawn')->assertOk();
});

it('returns 403 when the viewIlsawn gate denies access', function () {
    Gate::define('viewIlsawn', fn () => false);

    $user = new User;

    $this->actingAs($user)->get('/ilsawn')->assertForbidden();
});

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

it('renders all translation rows', function () {
    Livewire::test(TranslationsTable::class)
        ->assertSee('hello')
        ->assertSee('bye')
        ->assertSee('nav.home');
});

it('highlights missing translations', function () {
    Livewire::test(TranslationsTable::class)
        ->assertSee('missing');
});

// ---------------------------------------------------------------------------
// Search
// ---------------------------------------------------------------------------

it('filters rows by search term', function () {
    Livewire::test(TranslationsTable::class)
        ->set('search', 'nav')
        ->assertSee('nav.home')
        ->assertDontSee('hello');
});

it('shows empty state when search matches nothing', function () {
    Livewire::test(TranslationsTable::class)
        ->set('search', 'nonexistent')
        ->assertSee('No keys match');
});

// ---------------------------------------------------------------------------
// Inline editing
// ---------------------------------------------------------------------------

it('switches a row into edit mode', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'hello')
        ->assertSet('editingKey', 'hello');
});

it('populates editingValues with current row values', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'hello')
        ->assertSet('editingValues.en', 'Hello')
        ->assertSet('editingValues.fr', 'Bonjour');
});

it('clears edit state on cancel', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'hello')
        ->call('cancelEdit')
        ->assertSet('editingKey', null);
});

it('saves updated values to the CSV', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'bye')
        ->set('editingValues.fr', 'Au revoir')
        ->call('saveRow');

    $handle = fopen($this->csvPath, 'r');
    $rows = [];
    $header = fgetcsv($handle, 0, ';');
    while (($row = fgetcsv($handle, 0, ';')) !== false) {
        $rows[] = array_combine($header, $row);
    }
    fclose($handle);

    $bye = collect($rows)->firstWhere('key', 'bye');
    expect($bye['fr'])->toBe('Au revoir');
});

it('clears edit state after saving', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'bye')
        ->set('editingValues.fr', 'Au revoir')
        ->call('saveRow')
        ->assertSet('editingKey', null);
});

// ---------------------------------------------------------------------------
// Actions
// ---------------------------------------------------------------------------

it('generate action sets a confirmation message', function () {
    Livewire::test(TranslationsTable::class)
        ->call('generate')
        ->assertDispatched('flash', message: 'JSON files generated.', type: 'success');
});

it('sets needsGenerate after saving a row', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'bye')
        ->set('editingValues.fr', 'Au revoir')
        ->call('saveRow')
        ->assertSet('needsGenerate', true);
});

it('clears needsGenerate after generate', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'bye')
        ->set('editingValues.fr', 'Au revoir')
        ->call('saveRow')
        ->call('generate')
        ->assertSet('needsGenerate', false);
});

it('copyKeyAsTranslation copies the key into the target locale', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'hello')
        ->call('copyKeyAsTranslation', 'fr')
        ->assertSet('editingValues.fr', 'hello');
});

it('autoTranslate is a no-op when laravel/ai is not installed', function () {
    Livewire::test(TranslationsTable::class)
        ->call('startEdit', 'hello')
        ->call('autoTranslate', 'fr')
        ->assertSet('editingValues.fr', 'Bonjour'); // unchanged — laravel/ai not installed
});

it('scan action sets a confirmation message', function () {
    Livewire::test(TranslationsTable::class)
        ->call('scan')
        ->assertDispatched('flash', message: 'Scan complete — new keys added, framework duplicates removed.', type: 'success');
});
