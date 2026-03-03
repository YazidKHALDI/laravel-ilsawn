<?php

use ilsawn\LaravelIlsawn\SharesTranslations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

// Concrete class used to exercise the trait
function makeTranslationsMiddleware(): object
{
    return new class {
        use SharesTranslations;

        public function get(Request $request): array
        {
            return $this->translations($request);
        }
    };
}

// ---------------------------------------------------------------------------
// Setup / teardown
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->tmpLang  = sys_get_temp_dir() . '/ilsawn-lang-' . uniqid();
    $this->request  = Request::create('/');
    $this->subject  = makeTranslationsMiddleware();

    File::makeDirectory($this->tmpLang, 0755, true);
    $this->app->useLangPath($this->tmpLang);
});

afterEach(function () {
    Cache::flush();
    File::deleteDirectory($this->tmpLang);
});

// ---------------------------------------------------------------------------
// Basic loading
// ---------------------------------------------------------------------------

it('returns an empty array when no JSON file exists for the locale', function () {
    app()->setLocale('en');

    expect($this->subject->get($this->request))->toBeEmpty();
});

it('loads translations from the locale JSON file', function () {
    app()->setLocale('en');
    file_put_contents($this->tmpLang . '/en.json', json_encode(['hello' => 'Hello', 'bye' => 'Goodbye']));

    $result = $this->subject->get($this->request);

    expect($result)->toBe(['hello' => 'Hello', 'bye' => 'Goodbye']);
});

it('returns an empty array when the JSON file is invalid', function () {
    app()->setLocale('en');
    file_put_contents($this->tmpLang . '/en.json', 'not valid json');

    expect($this->subject->get($this->request))->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Local environment — no cache
// ---------------------------------------------------------------------------

it('reflects file changes immediately in the local environment', function () {
    $this->app->detectEnvironment(fn () => 'local');
    app()->setLocale('en');

    file_put_contents($this->tmpLang . '/en.json', json_encode(['key' => 'first']));
    $first = $this->subject->get($this->request);

    file_put_contents($this->tmpLang . '/en.json', json_encode(['key' => 'second']));
    $second = $this->subject->get($this->request);

    expect($first['key'])->toBe('first')
        ->and($second['key'])->toBe('second');
});

// ---------------------------------------------------------------------------
// Non-local environment — cached
// ---------------------------------------------------------------------------

it('caches translations in non-local environments', function () {
    // Testbench runs as 'testing', which is non-local → cache is active
    app()->setLocale('en');

    file_put_contents($this->tmpLang . '/en.json', json_encode(['key' => 'first']));
    $this->subject->get($this->request);   // primes the cache

    // Modify the file — cached value should be returned
    file_put_contents($this->tmpLang . '/en.json', json_encode(['key' => 'second']));
    $result = $this->subject->get($this->request);

    expect($result['key'])->toBe('first');
});

it('uses a separate cache entry per locale', function () {
    file_put_contents($this->tmpLang . '/en.json', json_encode(['key' => 'English']));
    file_put_contents($this->tmpLang . '/fr.json', json_encode(['key' => 'French']));

    app()->setLocale('en');
    $en = $this->subject->get($this->request);

    app()->setLocale('fr');
    $fr = $this->subject->get($this->request);

    expect($en['key'])->toBe('English')
        ->and($fr['key'])->toBe('French');
});

it('stores translations under an ilsawn-prefixed cache key', function () {
    app()->setLocale('en');
    file_put_contents($this->tmpLang . '/en.json', json_encode(['key' => 'Hello']));

    $this->subject->get($this->request);

    expect(Cache::has('ilsawn_translations_en'))->toBeTrue();
});
