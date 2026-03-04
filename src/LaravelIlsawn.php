<?php

namespace ilsawn\LaravelIlsawn;

use Illuminate\Support\Facades\File;

class LaravelIlsawn
{
    /**
     * Laravel's built-in PHP lang files.
     * Keys in these files belong to the framework and must not be duplicated in the CSV.
     */
    private const STANDARD_LANG_FILES = [
        'auth.php',
        'pagination.php',
        'passwords.php',
        'validation.php',
    ];

    /** @var array<string, array<string, mixed>> */
    private array $loadedLangFiles = [];

    /**
     * @param string[] $locales       Locale codes that map to CSV columns (e.g. ['en','fr','ar'])
     * @param string[] $scanPaths     Absolute paths to scan for translation key references
     * @param string[] $excludePaths  Absolute paths to skip during scanning
     */
    public function __construct(
        private readonly string $csvPath,
        private readonly string $delimiter,
        private readonly array $locales,
        private readonly string $defaultLocale,
        private readonly array $scanPaths,
        private readonly array $excludePaths = [],
    ) {}

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function csvPath(): string
    {
        return $this->csvPath;
    }

    // -------------------------------------------------------------------------
    // CSV I/O
    // -------------------------------------------------------------------------

    /**
     * Create a timestamped backup of the CSV and return its absolute path.
     */
    public function backupCsv(): string
    {
        $backupPath = $this->csvPath . '.backup.' . date('Y-m-d-H-i-s');
        File::copy($this->csvPath, $backupPath);

        return $backupPath;
    }

    /**
     * Delete the oldest CSV backups, keeping only the $limit most recent ones.
     * Does nothing when $limit is 0 (keep all).
     */
    public function pruneBackups(int $limit): void
    {
        if ($limit <= 0) {
            return;
        }

        $pattern = $this->csvPath . '.backup.*';
        $files   = glob($pattern);

        if ($files === false || count($files) <= $limit) {
            return;
        }

        sort($files); // ascending by name = chronological (timestamped)

        $toDelete = array_slice($files, 0, count($files) - $limit);

        foreach ($toDelete as $file) {
            File::delete($file);
        }
    }

    /**
     * Load the CSV into a list of rows keyed by locale.
     *
     * Expected CSV format (first row is the header):
     *   key;en;fr;ar
     *   dashboard.title;Dashboard;Tableau de bord;لوحة القيادة
     *
     * Columns for locales absent from the header are returned as empty strings.
     * Rows with an empty key are skipped silently.
     *
     * @return array<int, array<string, string>>
     */
    public function loadCsv(): array
    {
        $lines = file($this->csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false || empty($lines)) {
            return [];
        }

        $header = str_getcsv(array_shift($lines), $this->delimiter);

        $data = [];

        foreach ($lines as $line) {
            $row = str_getcsv($line, $this->delimiter);

            if (trim($row[0] ?? '') === '') {
                continue;
            }

            $entry = ['key' => $row[0]];

            foreach ($this->locales as $locale) {
                $colIndex = array_search($locale, $header, true);
                $entry[$locale] = ($colIndex !== false) ? ($row[(int) $colIndex] ?? '') : '';
            }

            $data[] = $entry;
        }

        return $data;
    }

    /**
     * Persist CSV data to disk, sorted alphabetically by key.
     * Writes the header row first, then one data row per entry.
     *
     * @param array<int, array<string, string>> $data
     */
    public function saveCsv(array $data): void
    {
        usort($data, fn ($a, $b) => strcasecmp($a['key'], $b['key']));

        $handle = fopen($this->csvPath, 'w');

        if ($handle === false) {
            return;
        }

        fputcsv($handle, array_merge(['key'], $this->locales), $this->delimiter);

        foreach ($data as $row) {
            $line = [$row['key']];
            foreach ($this->locales as $locale) {
                $line[] = $row[$locale] ?? '';
            }
            fputcsv($handle, $line, $this->delimiter);
        }

        fclose($handle);
    }

    // -------------------------------------------------------------------------
    // JSON generation
    // -------------------------------------------------------------------------

    /**
     * Generate one JSON file per locale at lang/{locale}.json.
     *
     * Fallback chain applied per cell:
     *   1. The locale's own value (if non-empty)
     *   2. The default_locale value (if non-empty)
     *   3. The translation key itself — the app never breaks.
     *
     * Returns a map of locale => absolute file path for each file written,
     * so the caller can log or display which files were generated.
     *
     * @param  array<int, array<string, string>> $csvData
     * @return array<string, string>
     */
    public function generateJsonFiles(array $csvData): array
    {
        usort($csvData, fn ($a, $b) => strcasecmp($a['key'], $b['key']));

        /** @var array<string, array<string, string>> $output */
        $output = array_fill_keys($this->locales, []);

        foreach ($csvData as $row) {
            $key          = $row['key'];
            $defaultValue = ($row[$this->defaultLocale] ?? '') !== ''
                ? $row[$this->defaultLocale]
                : $key;

            foreach ($this->locales as $locale) {
                $value = $row[$locale] ?? '';
                $output[$locale][$key] = $value !== '' ? $value : $defaultValue;
            }
        }

        File::ensureDirectoryExists(lang_path());

        $written = [];

        foreach ($this->locales as $locale) {
            $filePath = lang_path("{$locale}.json");
            File::put($filePath, json_encode($output[$locale], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $written[$locale] = $filePath;
        }

        return $written;
    }

    // -------------------------------------------------------------------------
    // Key analysis
    // -------------------------------------------------------------------------

    /**
     * Scan source files for translation keys that are not yet in the CSV.
     *
     * Returns two lists so the caller can handle output separately:
     *  - 'missing'  → keys that should be added to the CSV
     *  - 'skipped'  → keys omitted because they already exist in Laravel's
     *                 own lang files (mapped to the conflicting filename)
     *
     * @param  array<int, array<string, string>> $csvData
     * @return array{missing: string[], skipped: array<string, string>}
     */
    public function scanForNewKeys(array $csvData): array
    {
        $existingKeys = array_column($csvData, 'key');
        $foundKeys    = array_unique($this->scanSourceFiles());

        $this->loadStandardLangFiles();

        $missing = [];
        $skipped = [];

        foreach (array_diff($foundKeys, $existingKeys) as $key) {
            $conflict = $this->keyExistsInLangFiles($key);

            if ($conflict === null) {
                $missing[] = $key;
            } else {
                $skipped[$key] = $conflict;
            }
        }

        return ['missing' => $missing, 'skipped' => $skipped];
    }

    /**
     * Return CSV keys that are not referenced in any source file.
     *
     * @param  array<int, array<string, string>> $csvData
     * @return string[]
     */
    public function findUnusedKeys(array $csvData): array
    {
        $existingKeys = array_column($csvData, 'key');
        $usedKeys     = array_unique($this->scanSourceFiles());

        return array_values(array_diff($existingKeys, $usedKeys));
    }

    /**
     * Return existing CSV keys that duplicate keys in Laravel's built-in lang files,
     * mapped to the filename where the conflict was found.
     *
     * @param  array<int, array<string, string>> $csvData
     * @return array<string, string>
     */
    public function findDuplicatesInLangFiles(array $csvData): array
    {
        $this->loadStandardLangFiles();

        $duplicates = [];

        foreach (array_column($csvData, 'key') as $key) {
            $file = $this->keyExistsInLangFiles($key);
            if ($file !== null) {
                $duplicates[$key] = $file;
            }
        }

        return $duplicates;
    }

    /**
     * Append an empty row per missing key (one column per locale).
     * Keys that already exist in $data are skipped.
     *
     * @param  array<int, array<string, string>> $data
     * @param  string[] $missingKeys
     * @return array<int, array<string, string>>
     */
    public function addMissingKeys(array $data, array $missingKeys): array
    {
        $existingKeys = array_column($data, 'key');

        foreach ($missingKeys as $key) {
            if (! in_array($key, $existingKeys, true)) {
                $entry = ['key' => $key];
                foreach ($this->locales as $locale) {
                    $entry[$locale] = '';
                }
                $data[] = $entry;
            }
        }

        return $data;
    }

    /**
     * Return $data with every row whose key appears in $keys removed.
     *
     * @param  array<int, array<string, string>> $data
     * @param  string[] $keys
     * @return array<int, array<string, string>>
     */
    public function removeKeys(array $data, array $keys): array
    {
        return array_values(
            array_filter($data, fn ($row) => ! in_array($row['key'], $keys, true))
        );
    }

    // -------------------------------------------------------------------------
    // Source-file scanning
    // -------------------------------------------------------------------------

    /**
     * Walk all configured scan paths and collect every translation key referenced
     * in source files.
     *
     * Detected patterns:
     *  - PHP / Blade  : __('key'), trans('key'), @lang('key')
     *  - JS / TS / Vue / Svelte : __('key'), t('key')
     *
     * @return string[]
     */
    private function scanSourceFiles(): array
    {
        $phpExtensions = ['php'];
        $jsExtensions  = ['js', 'jsx', 'ts', 'tsx', 'vue', 'svelte'];

        $keys = [];

        foreach ($this->scanPaths as $scanPath) {
            if (! is_dir($scanPath)) {
                continue;
            }

            foreach (File::allFiles($scanPath) as $file) {
                $realPath = $file->getRealPath();

                $excluded = false;
                foreach ($this->excludePaths as $excludePath) {
                    if (str_starts_with($realPath, $excludePath . DIRECTORY_SEPARATOR) || $realPath === $excludePath) {
                        $excluded = true;
                        break;
                    }
                }
                if ($excluded) {
                    continue;
                }

                $ext     = $file->getExtension();
                $content = File::get($realPath);

                if (in_array($ext, $phpExtensions, true)) {
                    preg_match_all(
                        '/(?:__\s*\(\s*|trans\s*\(\s*|@lang\s*\(\s*)([\'"])((?:(?!\1)[^\\\\]|\\\\.)*)(\1)/',
                        $content,
                        $matches
                    );
                    $keys = array_merge($keys, $matches[2]);
                }

                if (in_array($ext, $jsExtensions, true)) {
                    preg_match_all(
                        '/(?:__\s*\(\s*|(?<![.\w])t\s*\(\s*)([\'"`])((?:(?!\1)[^\\\\]|\\\\.)*)(\1)/',
                        $content,
                        $matches
                    );
                    $keys = array_merge($keys, $matches[2]);
                }
            }
        }

        return $keys;
    }

    // -------------------------------------------------------------------------
    // Laravel built-in lang file detection
    // -------------------------------------------------------------------------

    /**
     * Load Laravel's standard PHP lang files into memory (runs once per instance).
     */
    private function loadStandardLangFiles(): void
    {
        if (! empty($this->loadedLangFiles)) {
            return;
        }

        foreach ($this->locales as $locale) {
            $langDir = base_path("lang/{$locale}");

            foreach (self::STANDARD_LANG_FILES as $file) {
                $filePath = "{$langDir}/{$file}";

                if (File::exists($filePath)) {
                    $translations = include $filePath;
                    if (is_array($translations)) {
                        $this->loadedLangFiles["{$locale}/{$file}"] = $translations;
                    }
                }
            }
        }
    }

    /**
     * Return the filename if $key exists in any loaded lang file, null otherwise.
     */
    private function keyExistsInLangFiles(string $key): ?string
    {
        foreach ($this->loadedLangFiles as $file => $translations) {
            if ($this->keyExistsIn($key, $translations)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Recursively check whether $key (dot-notation aware) exists in $translations.
     *
     * @param array<string, mixed> $translations
     */
    private function keyExistsIn(string $key, array $translations, string $prefix = ''): bool
    {
        foreach ($translations as $k => $v) {
            $full = $prefix !== '' ? "{$prefix}.{$k}" : (string) $k;

            if ($full === $key) {
                return true;
            }

            if (is_array($v) && $this->keyExistsIn($key, $v, $full)) {
                return true;
            }
        }

        return false;
    }
}
