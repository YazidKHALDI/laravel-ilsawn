<?php

use Illuminate\Support\Facades\File;
use ilsawn\LaravelIlsawn\LaravelIlsawn;

// ---------------------------------------------------------------------------
// Setup / teardown
// ---------------------------------------------------------------------------

beforeEach(function () {
    $this->tmpDir = sys_get_temp_dir().'/ilsawn-'.uniqid();
    $this->csvPath = $this->tmpDir.'/ilsawn.csv';
    $this->scanPath = $this->tmpDir.'/scan';

    File::makeDirectory($this->scanPath, 0755, true);
    File::makeDirectory($this->tmpDir.'/lang', 0755, true);

    // Redirect lang_path() so generateJsonFiles() writes inside our temp dir
    $this->app->useLangPath($this->tmpDir.'/lang');

    $this->service = new LaravelIlsawn(
        csvPath: $this->csvPath,
        delimiter: ';',
        locales: ['en', 'fr', 'ar'],
        defaultLocale: 'en',
        scanPaths: [$this->scanPath],
    );
});

afterEach(function () {
    File::deleteDirectory($this->tmpDir);
});

// ---------------------------------------------------------------------------
// Accessor
// ---------------------------------------------------------------------------

it('returns the configured CSV path', function () {
    expect($this->service->csvPath())->toBe($this->csvPath);
});

// ---------------------------------------------------------------------------
// loadCsv
// ---------------------------------------------------------------------------

it('returns an empty array for a header-only CSV', function () {
    writeCsvFile($this->csvPath, [['key', 'en', 'fr', 'ar']]);

    expect($this->service->loadCsv())->toBeEmpty();
});

it('loads rows with all locale columns', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', 'Bonjour', 'مرحبا'],
    ]);

    $data = $this->service->loadCsv();

    expect($data)->toHaveCount(1)
        ->and($data[0])->toBe([
            'key' => 'hello',
            'en' => 'Hello',
            'fr' => 'Bonjour',
            'ar' => 'مرحبا',
        ]);
});

it('fills missing locale columns with empty strings', function () {
    // CSV only has en and fr — ar column absent
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr'],
        ['hello', 'Hello', 'Bonjour'],
    ]);

    $data = $this->service->loadCsv();

    expect($data[0]['ar'])->toBe('');
});

it('skips rows with an empty key', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['',          'Hello', '', ''],
        ['valid.key', 'Valid',  '', ''],
    ]);

    expect($this->service->loadCsv())->toHaveCount(1);
});

// ---------------------------------------------------------------------------
// saveCsv
// ---------------------------------------------------------------------------

it('writes a header row followed by data sorted alphabetically', function () {
    writeCsvFile($this->csvPath, [['key', 'en', 'fr', 'ar']]);

    $this->service->saveCsv([
        ['key' => 'zoo',   'en' => 'Zoo',   'fr' => '', 'ar' => ''],
        ['key' => 'alpha', 'en' => 'Alpha', 'fr' => '', 'ar' => ''],
    ]);

    $loaded = $this->service->loadCsv();

    expect($loaded[0]['key'])->toBe('alpha')
        ->and($loaded[1]['key'])->toBe('zoo');
});

it('roundtrips data through save and load without loss', function () {
    writeCsvFile($this->csvPath, [['key', 'en', 'fr', 'ar']]);

    $data = [['key' => 'hello', 'en' => 'Hello', 'fr' => 'Bonjour', 'ar' => 'مرحبا']];

    $this->service->saveCsv($data);

    expect($this->service->loadCsv())->toBe($data);
});

// ---------------------------------------------------------------------------
// backupCsv
// ---------------------------------------------------------------------------

it('creates a timestamped backup file', function () {
    writeCsvFile($this->csvPath, [['key', 'en', 'fr', 'ar']]);

    $backupPath = $this->service->backupCsv();

    expect(file_exists($backupPath))->toBeTrue()
        ->and($backupPath)->toContain('.backup.');
});

it('backup is an exact copy of the original', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', 'Bonjour', ''],
    ]);

    $backupPath = $this->service->backupCsv();

    expect(file_get_contents($backupPath))->toBe(file_get_contents($this->csvPath));
});

it('pruneBackups deletes oldest files beyond the limit', function () {
    writeCsvFile($this->csvPath, [['key', 'en']]);

    // Create 5 backups with distinct timestamps
    $paths = [];
    for ($i = 1; $i <= 5; $i++) {
        $path = $this->csvPath.'.backup.2026-01-0'.$i.'-00-00-00';
        file_put_contents($path, "backup {$i}");
        $paths[] = $path;
    }

    $this->service->pruneBackups(3);

    expect(file_exists($paths[0]))->toBeFalse() // oldest deleted
        ->and(file_exists($paths[1]))->toBeFalse()
        ->and(file_exists($paths[2]))->toBeTrue()  // newest 3 kept
        ->and(file_exists($paths[3]))->toBeTrue()
        ->and(file_exists($paths[4]))->toBeTrue();

    foreach ($paths as $p) {
        @unlink($p);
    }
});

it('pruneBackups keeps all files when limit is 0', function () {
    writeCsvFile($this->csvPath, [['key', 'en']]);

    $paths = [];
    for ($i = 1; $i <= 5; $i++) {
        $path = $this->csvPath.'.backup.2026-01-0'.$i.'-00-00-00';
        file_put_contents($path, "backup {$i}");
        $paths[] = $path;
    }

    $this->service->pruneBackups(0);

    foreach ($paths as $p) {
        expect(file_exists($p))->toBeTrue();
        @unlink($p);
    }
});

// ---------------------------------------------------------------------------
// generateJsonFiles
// ---------------------------------------------------------------------------

it('generates one JSON file per locale and returns locale → path map', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', 'Bonjour', 'مرحبا'],
    ]);

    $written = $this->service->generateJsonFiles($this->service->loadCsv());

    expect($written)->toHaveKeys(['en', 'fr', 'ar']);

    foreach ($written as $path) {
        expect(file_exists($path))->toBeTrue();
    }
});

it('writes correct translations to each JSON file', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', 'Bonjour', 'مرحبا'],
    ]);

    $written = $this->service->generateJsonFiles($this->service->loadCsv());

    expect(json_decode(file_get_contents($written['en']), true)['hello'])->toBe('Hello')
        ->and(json_decode(file_get_contents($written['fr']), true)['hello'])->toBe('Bonjour')
        ->and(json_decode(file_get_contents($written['ar']), true)['hello'])->toBe('مرحبا');
});

it('falls back to the default locale value when a locale cell is empty', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', '', ''],   // fr and ar are empty → fall back to en
    ]);

    $written = $this->service->generateJsonFiles($this->service->loadCsv());

    expect(json_decode(file_get_contents($written['fr']), true)['hello'])->toBe('Hello')
        ->and(json_decode(file_get_contents($written['ar']), true)['hello'])->toBe('Hello');
});

it('falls back to the key itself when even the default locale cell is empty', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', '', '', ''],
    ]);

    $written = $this->service->generateJsonFiles($this->service->loadCsv());

    expect(json_decode(file_get_contents($written['en']), true)['hello'])->toBe('hello');
});

it('outputs JSON keys sorted alphabetically', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['zoo',   'Zoo',   '', ''],
        ['alpha', 'Alpha', '', ''],
    ]);

    $written = $this->service->generateJsonFiles($this->service->loadCsv());
    $keys = array_keys(json_decode(file_get_contents($written['en']), true));

    expect($keys)->toBe(['alpha', 'zoo']);
});

// ---------------------------------------------------------------------------
// addMissingKeys
// ---------------------------------------------------------------------------

it('appends an empty row per missing key with one column per locale', function () {
    $data = [['key' => 'existing', 'en' => 'Existing', 'fr' => '', 'ar' => '']];
    $result = $this->service->addMissingKeys($data, ['new.key']);
    $newRow = collect($result)->firstWhere('key', 'new.key');

    expect($result)->toHaveCount(2)
        ->and($newRow)->toBe(['key' => 'new.key', 'en' => '', 'fr' => '', 'ar' => '']);
});

it('does not add a key that already exists', function () {
    $data = [['key' => 'existing', 'en' => 'Existing', 'fr' => '', 'ar' => '']];
    $result = $this->service->addMissingKeys($data, ['existing']);

    expect($result)->toHaveCount(1);
});

// ---------------------------------------------------------------------------
// removeKeys
// ---------------------------------------------------------------------------

it('removes only the specified keys', function () {
    $data = [
        ['key' => 'keep',   'en' => 'Keep',   'fr' => '', 'ar' => ''],
        ['key' => 'remove', 'en' => 'Remove', 'fr' => '', 'ar' => ''],
    ];

    $result = $this->service->removeKeys($data, ['remove']);

    expect($result)->toHaveCount(1)
        ->and($result[0]['key'])->toBe('keep');
});

it('returns all rows untouched when no keys match', function () {
    $data = [['key' => 'hello', 'en' => 'Hello', 'fr' => '', 'ar' => '']];
    $result = $this->service->removeKeys($data, ['nonexistent']);

    expect($result)->toHaveCount(1);
});

// ---------------------------------------------------------------------------
// scanForNewKeys
// ---------------------------------------------------------------------------

it('detects keys in PHP files that are absent from the CSV', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['existing.key', 'Existing', '', ''],
    ]);
    file_put_contents(
        $this->scanPath.'/test.php',
        '<?php echo __("existing.key"); echo __("new.key");'
    );

    $result = $this->service->scanForNewKeys($this->service->loadCsv());

    expect($result['missing'])->toContain('new.key')
        ->and($result['missing'])->not->toContain('existing.key');
});

it('detects keys in JS/TS files using __() and t()', function () {
    writeCsvFile($this->csvPath, [['key', 'en', 'fr', 'ar']]);
    file_put_contents(
        $this->scanPath.'/app.tsx',
        'const a = t("welcome.title"); const b = __("nav.home");'
    );

    $result = $this->service->scanForNewKeys($this->service->loadCsv());

    expect($result['missing'])->toContain('welcome.title')
        ->and($result['missing'])->toContain('nav.home');
});

it('marks keys as skipped when they exist in Laravel own lang files', function () {
    $langDir = base_path('lang/en');
    File::makeDirectory($langDir, 0755, true);
    File::put($langDir.'/auth.php', "<?php return ['failed' => 'Credentials do not match.'];");

    writeCsvFile($this->csvPath, [['key', 'en', 'fr', 'ar']]);
    file_put_contents($this->scanPath.'/test.php', '<?php echo __("auth.failed");');

    $result = $this->service->scanForNewKeys($this->service->loadCsv());

    File::deleteDirectory($langDir);

    expect($result['skipped'])->toHaveKey('auth.failed')
        ->and($result['missing'])->not->toContain('auth.failed');
});

// ---------------------------------------------------------------------------
// findUnusedKeys
// ---------------------------------------------------------------------------

it('detects CSV keys not referenced in any source file', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['used.key',   'Used',   '', ''],
        ['unused.key', 'Unused', '', ''],
    ]);
    file_put_contents($this->scanPath.'/test.php', '<?php echo __("used.key");');

    $unused = $this->service->findUnusedKeys($this->service->loadCsv());

    expect($unused)->toContain('unused.key')
        ->and($unused)->not->toContain('used.key');
});

it('returns an empty array when all keys are used', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['hello', 'Hello', '', ''],
    ]);
    file_put_contents($this->scanPath.'/test.php', '<?php echo __("hello");');

    expect($this->service->findUnusedKeys($this->service->loadCsv()))->toBeEmpty();
});

// ---------------------------------------------------------------------------
// findDuplicatesInLangFiles
// ---------------------------------------------------------------------------

it('detects CSV keys that duplicate keys in Laravel lang files', function () {
    $langDir = base_path('lang/en');
    File::makeDirectory($langDir, 0755, true);
    File::put($langDir.'/auth.php', "<?php return ['failed' => 'Credentials do not match.'];");

    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['auth.failed', 'Failed', '', ''],
        ['unique.key',  'Unique', '', ''],
    ]);

    $duplicates = $this->service->findDuplicatesInLangFiles($this->service->loadCsv());

    File::deleteDirectory($langDir);

    expect($duplicates)->toHaveKey('auth.failed')
        ->and($duplicates)->not->toHaveKey('unique.key');
});

it('returns an empty array when no duplicates exist', function () {
    writeCsvFile($this->csvPath, [
        ['key', 'en', 'fr', 'ar'],
        ['my.custom.key', 'Custom', '', ''],
    ]);

    expect($this->service->findDuplicatesInLangFiles($this->service->loadCsv()))->toBeEmpty();
});
