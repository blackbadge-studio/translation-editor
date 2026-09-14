<?php

namespace Blackbadgestudio\TranslationEditor\Livewire;

use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TranslationEditorModal extends Component
{
    public bool $isOpen = true;

    public bool $isCollapsed = true;

    public array $translationStrings = [];

    public ?string $activeLocale = null;

    public array $editableTranslations = [];

    public bool $isLoading = false;

    public function mount(): void
    {
        // Automatically collect translations when component mounts
        $this->collectPageTranslations();

        // Set default active locale to the authenticated user's locale, or first available locale
        $locales = $this->getLocaleCodes();
        if ($locales !== [] && ! $this->activeLocale) {
            // Try to get the authenticated user's locale using the configured column
            $user = Auth::user();
            $localeColumn = config('translation-editor.auth.column', 'locale');
            $userLocale = $user ? ($user->{$localeColumn} ?? null) : null;

            // Check if the user's locale exists in available locales
            if ($userLocale && in_array($userLocale, $locales, true)) {
                $this->activeLocale = $userLocale;
            } else {
                // Fall back to first available locale
                $this->activeLocale = $locales[0];
            }
        }
    }

    public function toggleCollapse(): void
    {
        $this->isCollapsed = ! $this->isCollapsed;
    }

    public function collectPageTranslations(): void
    {
        $tracker = app(TranslationTracker::class);
        $tracked = $tracker->getTracked();

        if (! empty($tracked)) {
            $this->setTranslationStrings($tracked);
        }
    }

    public function setTranslationStrings(array $strings): void
    {
        // Filter out invalid entries
        $validStrings = [];
        foreach ($strings as $string) {
            if (is_array($string) && isset($string['group']) && isset($string['key'])) {
                $validStrings[] = $string;
            }
        }

        $this->translationStrings = $validStrings;
        $this->loadExistingTranslations();
    }

    protected function loadExistingTranslations(): void
    {
        $translationModel = $this->getTranslationModelClass();
        $locales = $this->getLocaleCodes();

        foreach ($this->translationStrings as $string) {
            $group = $string['group'] ?? null;
            $key = $string['key'] ?? null;
            if (! $group) {
                continue;
            }
            if (! $key) {
                continue;
            }

            $translationString = $translationModel::where('group', $group)
                ->where('key', $key)
                ->first();

            if ($translationString && is_array($translationString->text)) {
                foreach ($translationString->text as $locale => $text) {
                    // Ensure the value is always a string, not an array
                    $this->editableTranslations["{$group}.{$key}.{$locale}"] = is_array($text) ? '' : (string) $text;
                }
            }

            // For each locale, if no value exists, try to get the current translation value
            foreach ($locales as $localeCode) {
                $translationKey = "{$group}.{$key}.{$localeCode}";
                if (! isset($this->editableTranslations[$translationKey])) {
                    // Try to get the current translation value using Laravel's trans helper
                    $currentValue = trans("{$group}.{$key}", [], $localeCode);
                    // Handle case where trans() returns an array (nested keys)
                    if (is_array($currentValue)) {
                        $this->editableTranslations[$translationKey] = '';
                    } elseif ($currentValue !== "{$group}.{$key}") {
                        // Only use it if it's different from the key (meaning a translation exists)
                        $this->editableTranslations[$translationKey] = (string) $currentValue;
                    } else {
                        $this->editableTranslations[$translationKey] = '';
                    }
                }
            }
        }
    }

    public function updateTranslation(string $group, string $key, string $locale, string $value): void
    {
        $this->editableTranslations["{$group}.{$key}.{$locale}"] = $value;
    }

    public function updateTranslationValue(string $translationKey, string $value): void
    {
        $this->editableTranslations[$translationKey] = $value;
    }

    public function saveTranslations(string $locale): void
    {
        $this->isLoading = true;

        try {
            // Save edited translations to database
            $translationModel = $this->getTranslationModelClass();

            foreach ($this->translationStrings as $string) {
                $group = $string['group'] ?? null;
                $key = $string['key'] ?? null;
                if (! $group) {
                    continue;
                }
                if (! $key) {
                    continue;
                }

                $translationKey = "{$group}.{$key}.{$locale}";
                $newValue = $this->editableTranslations[$translationKey] ?? null;

                if ($newValue === null) {
                    continue;
                }

                $translationString = $translationModel::firstOrCreate(
                    [
                        'group' => $group,
                        'key' => $key,
                    ],
                    [
                        'text' => [],
                    ]
                );

                $text = $translationString->text ?? [];
                $text[$locale] = $newValue;
                $translationString->text = $text;
                $translationString->is_new = true;
                $translationString->saveQuietly();
            }

            // Reload translations to show updated values
            $this->loadExistingTranslations();

            Notification::make()
                ->title('Translations saved')
                ->body('Translations have been saved successfully.')
                ->success()
                ->send();

            Artisan::call('translations:export', ['--force-confirm' => true]);
            Artisan::call('optimize:clear');

            $this->js('window.location.reload()');
        } catch (Exception $e) {
            Notification::make()
                ->title('Error saving translations')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getTranslationModelClass(): string
    {
        $modelClass = config('translation-editor.models.translation');

        if (! is_string($modelClass) || $modelClass === '') {
            throw new Exception('No translation model configured. Please set `translation-editor.models.translation` in your config.');
        }

        if (! is_a(new $modelClass, 'Spatie\\TranslationLoader\\LanguageLine')) {
            throw new Exception("Invalid translation model [{$modelClass}]. It must extend Spatie\\TranslationLoader\\LanguageLine.");
        }

        return $modelClass;
    }

    protected function getLocaleModelClass(): string
    {
        $modelClass = config('translation-editor.models.locale');

        if (! is_string($modelClass) || $modelClass === '') {
            throw new Exception('Invalid locale model configuration. To use a model, set `translation-editor.models.locale` to the model class name.');
        }

        if (! class_exists($modelClass)) {
            throw new Exception("Invalid locale model [{$modelClass}]. The class does not exist.");
        }

        return $modelClass;
    }

    /**
     * The locale codes to offer, in the order they should be shown.
     *
     * @return array<int, string>
     */
    protected function getLocaleCodes(): array
    {
        return array_keys($this->getLocaleOptions());
    }

    /**
     * The locales to offer, keyed by locale code, with the label shown on the tab.
     *
     * Supported configurations for translation-editor.models.locale:
     *  - array of strings: ['en', 'fr'] (labelled with the upper-cased code)
     *  - array with 'model' (and optional 'column' / 'label_column'):
     *      ['model' => App\Models\Locale::class, 'column' => 'code', 'label_column' => 'name']
     *  - string model class: App\Models\Locale::class (uses the 'key' column, no label column)
     *
     * @return array<string, string>
     */
    public function getLocaleOptions(): array
    {
        $config = config('translation-editor.models.locale');

        // Case 1: explicitly configured list of locale codes
        if (is_array($config) && isset($config[0]) && is_string($config[0])) {
            $codes = array_values(array_filter($config, fn ($value): bool => is_string($value) && $value !== ''));

            return array_combine($codes, array_map(strtoupper(...), $codes));
        }

        // Case 2: array with 'model' key and optional 'column' / 'label_column'
        if (is_array($config) && isset($config['model'])) {
            $modelClass = $config['model'];

            if (! is_string($modelClass) || $modelClass === '' || ! class_exists($modelClass)) {
                throw new Exception('Invalid locale model configuration. The `model` class does not exist.');
            }

            return $this->getLocaleOptionsFromModel(
                $modelClass,
                $config['column'] ?? 'key',
                $config['label_column'] ?? null,
            );
        }

        // Case 3: string model class name, default column 'key'
        if (is_string($config) && $config !== '') {
            return $this->getLocaleOptionsFromModel($this->getLocaleModelClass(), 'key', null);
        }

        // Fallback: use the application's current locale if nothing is configured
        $fallback = app()->getLocale();

        return $fallback !== '' ? [$fallback => strtoupper($fallback)] : [];
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, string>
     */
    protected function getLocaleOptionsFromModel(string $modelClass, string $codeColumn, ?string $labelColumn): array
    {
        $options = [];

        foreach ($modelClass::query()->get() as $locale) {
            $code = $locale->{$codeColumn};

            if (! is_string($code) || $code === '') {
                continue;
            }

            $label = $labelColumn !== null ? $locale->{$labelColumn} : null;

            $options[$code] = (is_string($label) && $label !== '') ? $label : strtoupper($code);
        }

        return $options;
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;

        // Initialize values for translations that don't exist yet
        foreach ($this->translationStrings as $string) {
            $group = $string['group'] ?? null;
            $key = $string['key'] ?? null;
            if (! $group) {
                continue;
            }
            if (! $key) {
                continue;
            }

            $translationKey = "{$group}.{$key}.{$locale}";

            if (! isset($this->editableTranslations[$translationKey])) {
                // Try to get the current translation value using Laravel's trans helper
                $currentValue = trans("{$group}.{$key}", [], $locale);
                // Handle case where trans() returns an array (nested keys)
                if (is_array($currentValue)) {
                    $this->editableTranslations[$translationKey] = '';
                } elseif ($currentValue !== "{$group}.{$key}") {
                    // Only use it if it's different from the key (meaning a translation exists)
                    $this->editableTranslations[$translationKey] = (string) $currentValue;
                } else {
                    $this->editableTranslations[$translationKey] = '';
                }
            }
        }
    }

    public function render(): Factory | View
    {
        // Only render if user has translation modal enabled
        $user = Auth::user();
        $toggleColumn = config('translation-editor.auth.toggle_column', 'translation_modal_enabled');

        if (! $user || ! ($user->{$toggleColumn} ?? false)) {
            return view('translation-editor::livewire.translation-editor-modal-empty');
        }

        return view('translation-editor::livewire.translation-editor-modal');
    }
}
