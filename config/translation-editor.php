<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure which Eloquent models should be used by the
    | translation editor. This allows you to swap the underlying models
    | without having to change the package code.
    |
    */

    'models' => [

        /*
         * The model that represents your translatable strings.
         * This model should extend `Spatie\TranslationLoader\LanguageLine`.
         */
        'translation' => 'Spatie\\TranslationLoader\\LanguageLine',

        /*
         * The configuration that represents your locales.
         *
         * Supported values:
         *  - an array of locale codes: ['en', 'fr']
         *  - an array with a model configuration:
         *      ['model' => App\Models\Locale::class, 'column' => 'code']
         *  - a model class string that has a `key` (or configured) column.
         */

        // 'locale' => [
        //     'model' => 'App\\Models\\Locale',
        //     'column' => 'key',
        // ],
        'locale' => [
            'en',
            'nl',
            'fr',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Translation loader configuration
    |--------------------------------------------------------------------------
    |
    | Configure how the package integrates with spatie/laravel-translation-loader.
    | These values will be applied to the `translation-loader` config at runtime.
    | You can override them in your own app config if needed.
    |
    */

    'translation_loader' => [
        // The translation loaders to register with spatie/laravel-translation-loader.
        // By default we use the tracking DB loader that works with TranslationTracker.
        'translation_loaders' => [
            \Blackbadgestudio\TranslationEditor\TranslationLoaders\TrackingDbLoader::class,
        ],

        // The translation manager class which overrides the default Laravel
        // `translation.loader`.
        'translation_manager' => \Blackbadgestudio\TranslationEditor\TranslationLoaders\TrackingTranslationLoaderManager::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticatable model locale column
    |--------------------------------------------------------------------------
    |
    | Here you can configure how the translation editor migration should determine
    | which table and column to use for storing the user's locale. By default,
    | the migration will try to resolve the table from the configured
    | authenticatable model (config('auth.providers.users.model')).
    |
    | Supported options:
    |  - 'model':  The Authenticatable model class (e.g. App\Models\User::class)
    |  - 'table':  The table name (if you want to override the model's table)
    |  - 'column': The column name for the locale (defaults to 'locale')
    |  - 'toggle_column': The column name for enabling/disabling the modal (defaults to 'translation_modal_enabled')
    |
    |
    */

    'auth' => [
        'model' => 'App\\Models\\User',
        'table' => 'users',
        'column' => 'locale',
        'toggle_column' => 'translation_modal_enabled',
    ],
];
