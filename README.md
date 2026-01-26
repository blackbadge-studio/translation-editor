# A Filament plugin that allows editing application translations directly in context via a modal or floating UI.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/blackbadge-studio/translation-editor.svg?style=flat-square)](https://packagist.org/packages/blackbadge-studio/translation-editor)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/blackbadge-studio/translation-editor/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/blackbadge-studio/translation-editor/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/blackbadge-studio/translation-editor/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/blackbadge-studio/translation-editor/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/blackbadge-studio/translation-editor.svg?style=flat-square)](https://packagist.org/packages/blackbadge-studio/translation-editor)



A Filament plugin that provides an in-context translation editor. Edit your application translations directly from any Filament page without leaving the interface. The editor appears as a floating modal that tracks translation keys used on the current page, allowing you to edit and save translations on the fly.

## Installation

You can install the package via composer:

```bash
composer require blackbadge-studio/translation-editor
```

> [!IMPORTANT]
> If you have not set up a custom theme and are using Filament Panels follow the instructions in the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

After setting up a custom theme add the plugin's views to your theme css file or your app's css file if using the standalone packages.

```css
@source '../../../../vendor/blackbadge-studio/translation-editor/resources/**/*.blade.php';
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="translation-editor-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="translation-editor-config"
```

> [!IMPORTANT]
> **After updating the package**, if you have already published the config file, you should republish it to get the latest namespace changes:
> ```bash
> php artisan vendor:publish --tag="translation-editor-config" --force
> ```
> Or manually update your published config file to use the new package namespace (`Blackbadgestudio\TranslationEditor\`) instead of `App\`.

Register the plugin in your Filament panel configuration (usually in `app/Providers/Filament/AdminPanelProvider.php` or similar):

```php
use Blackbadgestudio\TranslationEditor\TranslationEditorPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... other configuration
        ->plugins([
            TranslationEditorPlugin::make(),
            // ... other plugins
        ]);
}
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="translation-editor-views"
```

This is the contents of the published config file:

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model configuration
    |--------------------------------------------------------------------------
    |
    | Configure which Eloquent models / sources are used for translations
    | and locales.
    |
    */

    'models' => [
        // The model that represents your translatable strings. This should
        // extend Spatie\TranslationLoader\LanguageLine.
        'translation' => 'Spatie\\TranslationLoader\\LanguageLine',

        // The configuration that represents your locales.
        //
        // Supported values:
        //  - an array of locale codes: ['en', 'fr']
        //  - an array with a model configuration:
        //      ['model' => App\Models\Locale::class, 'column' => 'code']
        //  - a model class string that has a `key` (or configured) column.
        //
        // Example using a locales table:
        // 'locale' => [
        //     'model' => 'App\\Models\\Locale',
        //     'column' => 'code',
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
    | Configure how the package determines where to store the user's locale.
    |
    | By default it will use the configured user model and its table:
    |   config('auth.providers.users.model')
    |
    | Supported options:
    |  - 'model':  The Authenticatable model class (e.g. App\Models\User::class)
    |  - 'table':  The table name (if you want to override the model's table)
    |  - 'column': The column name for the locale (defaults to 'locale')
    |  - 'toggle_column': The column name for enabling/disabling the modal (defaults to 'translation_modal_enabled')
    */

    'auth' => [
        'model' => 'App\\Models\\User',
        'table' => 'users',
        'column' => 'locale',
        'toggle_column' => 'translation_modal_enabled',
    ],
];
```

## Usage

### Adding the Translation Editor Action

You can easily add a toggle action to any Filament resource to enable/disable the translation editor modal for users. Simply use the provided action class:

```php
use Blackbadgestudio\TranslationEditor\Actions\TranslationEditorAction;
use Filament\Resources\Resource;

class UserResource extends Resource
{
    // ...

    public static function getHeaderActions(): array
    {
        return [
            TranslationEditorAction::make(),
            // ... other actions
        ];
    }
}
```

The action will automatically:
- Show "Enable Translation Editor" or "Disable Translation Editor" based on the current state
- Display the appropriate icon (eye/eye-slash)
- Use success/danger colors accordingly
- Show a notification when toggled
- Use the configured `toggle_column` from your config file

### Enabling the Translation Editor

After installing and configuring the package, you need to enable the translation editor for individual users:

1. **Using the Action Button** (Recommended):
   - Add the `TranslationEditorAction` to your user resource (see above)
   - Click the "Enable Translation Editor" button for the user

2. **Manually via Database**:
   ```php
   $user = User::find(1);
   $user->translation_modal_enabled = true;
   $user->save();
   ```

### How It Works

- The translation editor modal appears as a floating bubble in the bottom-right corner of Filament pages
- It only appears for users who have `translation_modal_enabled = true`
- The modal automatically tracks translation keys that are used on the current page
- You can edit translations directly in the modal and save them to the database
- Translations are tracked via the `TranslationTracker` service which wraps Laravel's translator

### Troubleshooting

**The modal doesn't appear:**
- Ensure the plugin is registered in your panel configuration
- Verify the user has `translation_modal_enabled = true` in the database
- Check that translations are actually being used on the page (the modal only shows tracked translations)
- Clear caches: `php artisan optimize:clear`
- Check browser console for any JavaScript errors

**No translations are tracked:**
- Make sure you're using Laravel's translation functions (`trans()`, `__()`, etc.)
- Verify the `TranslationTrackerDecorator` is wrapping the translator (check service provider)
- Ensure translations are using the `group.key` format (e.g., `trans('validation.required')`)

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Sofian Mourabit](https://github.com/blackbadge-studio)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
