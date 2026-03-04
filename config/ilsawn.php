<?php

// config for ilsawn/laravel-ilsawn

return [

    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | The list of locales your application supports. Each locale corresponds
    | to a column in the CSV file. You may add or remove locales freely;
    | the CSV will be updated the next time you run ilsawn:generate.
    |
    */
    'locales' => ['en', 'fr', 'ar'],

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The primary locale used as the first fallback when a translation is
    | missing for the requested locale. If the default locale column is also
    | empty, the translation key itself is returned — the app never breaks.
    |
    */
    'default_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | CSV File Path
    |--------------------------------------------------------------------------
    |
    | Path to the CSV file that stores all translations, relative to the
    | Laravel base_path(). This file is the single source of truth for all
    | translations managed by this package.
    |
    */
    'csv_path' => 'lang/ilsawn.csv',

    /*
    |--------------------------------------------------------------------------
    | CSV Delimiter
    |--------------------------------------------------------------------------
    |
    | The character used to separate columns in the CSV file. Semicolon is
    | the default to avoid conflicts with commas that appear naturally in
    | translation strings.
    |
    */
    'delimiter' => ';',

    /*
    |--------------------------------------------------------------------------
    | Scan Paths
    |--------------------------------------------------------------------------
    |
    | Directories scanned by ilsawn:generate to collect translation keys from
    | your source code. Paths are relative to base_path(). The scanner detects
    | PHP/Blade calls (__(), trans(), @lang()) and JS/TS calls (__(), t()).
    |
    */
    'scan_paths' => [
        'app',
        'resources',
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URL prefix for the Ilsawn translation management UI. With the default
    | value the UI will be accessible at /ilsawn. Change this if it conflicts
    | with an existing route in your application.
    |
    */
    'route_prefix' => 'ilsawn',

    /*
    |--------------------------------------------------------------------------
    | UI Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to all Ilsawn UI routes. The 'web' middleware group is
    | required for session and CSRF support. Add 'auth' or any custom middleware
    | to further restrict access. Fine-grained authorization is handled by the
    | Gate defined in app/Providers/IlsawnServiceProvider.php.
    |
    */
    'middleware' => ['web'],

];
