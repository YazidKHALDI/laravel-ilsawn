<?php

namespace ilsawn\LaravelIlsawn;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Add this trait to your existing HandleInertiaRequests middleware to
 * automatically share the active locale's translations as Inertia shared data.
 *
 * Open app/Http/Middleware/HandleInertiaRequests.php and make two additions:
 *
 *   // 1. Import the trait
 *   use ilsawn\LaravelIlsawn\SharesTranslations;
 *
 *   class HandleInertiaRequests extends Middleware
 *   {
 *       use SharesTranslations; // ← add this
 *
 *       public function share(Request $request): array
 *       {
 *           return [
 *               ...parent::share($request),
 *               // ... your existing props ...
 *               'translations' => $this->translations($request), // ← add this
 *           ];
 *       }
 *   }
 *
 * On the JS side, read from usePage().props.translations.
 */
/** @phpstan-ignore trait.unused */
trait SharesTranslations
{
    /**
     * Return the translations for the current application locale.
     *
     * In the local environment the JSON file is read on every request so changes
     * from ilsawn:generate reflect immediately without a cache flush.
     *
     * In all other environments the result is cached forever under the key
     * "ilsawn_translations_{locale}". The cache is invalidated automatically
     * when ilsawn:generate runs because it calls `php artisan optimize:clear`.
     *
     * @return array<string, string>
     */
    protected function translations(Request $_request): array
    {
        $locale = app()->getLocale();

        if (app()->environment('local')) {
            return $this->loadTranslations($locale);
        }

        $cached = Cache::rememberForever(
            "ilsawn_translations_{$locale}",
            fn () => $this->loadTranslations($locale)
        );

        return is_array($cached) ? $cached : [];
    }

    /**
     * Load all translations for the given locale, merging three sources in order:
     *
     *  1. PHP lang files from lang/{locale}/ (auth.php → "auth.failed", …)
     *  2. JSON files from lang/{locale}/ (e.g. breeze.json from packages)
     *  3. ilsawn-generated lang/{locale}.json — has highest priority, overwrites above
     *
     * Returns an empty array if no sources exist (e.g. before the first
     * ilsawn:generate run), so the app never breaks.
     *
     * @return array<string, string>
     */
    private function loadTranslations(string $locale): array
    {
        $translations = [];

        $langDir = lang_path($locale);

        // 1. PHP lang files — file basename becomes the key namespace prefix
        if (is_dir($langDir)) {
            foreach (glob("{$langDir}/*.php") ?: [] as $phpFile) {
                $data = include $phpFile;
                if (is_array($data)) {
                    $prefix = pathinfo($phpFile, PATHINFO_FILENAME);
                    $translations = array_merge($translations, $this->flattenWithPrefix($data, $prefix));
                }
            }
        }

        // 2. JSON files in lang/{locale}/ (package files such as breeze.json)
        if (is_dir($langDir)) {
            foreach (glob("{$langDir}/*.json") ?: [] as $jsonFile) {
                $content = file_get_contents($jsonFile);
                if ($content !== false) {
                    $data = json_decode($content, true);
                    if (is_array($data)) {
                        $translations = array_merge($translations, $data);
                    }
                }
            }
        }

        // 3. ilsawn-generated lang/{locale}.json — overwrites any conflicts above
        $ilsawnPath = lang_path("{$locale}.json");
        if (file_exists($ilsawnPath)) {
            $content = file_get_contents($ilsawnPath);
            if ($content !== false) {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    $translations = array_merge($translations, $data);
                }
            }
        }

        return $translations;
    }

    /**
     * Recursively flatten a nested PHP lang array, prepending the given prefix.
     *
     * flattenWithPrefix(['required' => 'The :attribute field is required.'], 'validation')
     * → ['validation.required' => 'The :attribute field is required.']
     *
     * @param  array<string, mixed>  $array
     * @return array<string, string>
     */
    private function flattenWithPrefix(array $array, string $prefix): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $fullKey = "{$prefix}.{$key}";

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenWithPrefix($value, $fullKey));
            } else {
                $result[$fullKey] = (string) $value;
            }
        }

        return $result;
    }
}
