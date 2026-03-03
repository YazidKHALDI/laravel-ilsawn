<?php

namespace ilsawn\LaravelIlsawn;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Apply this trait to your HandleInertiaRequests middleware to automatically
 * share the active locale's translations as Inertia shared data.
 *
 * Usage:
 *
 *   use ilsawn\LaravelIlsawn\SharesTranslations;
 *
 *   class HandleInertiaRequests extends Middleware
 *   {
 *       use SharesTranslations;
 *
 *       public function share(Request $request): array
 *       {
 *           return array_merge(parent::share($request), [
 *               'translations' => $this->translations($request),
 *           ]);
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
    protected function translations(Request $request): array
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
     * Read and decode the ilsawn-generated JSON file for the given locale.
     *
     * Returns an empty array if the file does not exist yet (e.g. before running
     * ilsawn:generate for the first time), so the app never breaks.
     *
     * @return array<string, string>
     */
    private function loadTranslations(string $locale): array
    {
        $path = lang_path("{$locale}.json");

        if (! file_exists($path)) {
            return [];
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return [];
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }
}
