<?php

namespace Blackbadgestudio\TranslationEditor;

use Blackbadgestudio\TranslationEditor\Commands\TranslationEditorCommand;
use Blackbadgestudio\TranslationEditor\Livewire\TranslationEditorModal;
use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;
use Blackbadgestudio\TranslationEditor\Testing\TestsTranslationEditor;
use Blackbadgestudio\TranslationEditor\TranslationLoaders\TrackingDbLoader;
use Blackbadgestudio\TranslationEditor\TranslationLoaders\TrackingTranslationLoaderManager;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class TranslationEditorServiceProvider extends PackageServiceProvider
{
    public static string $name = 'translation-editor';

    public static string $viewNamespace = 'translation-editor';

    public function boot(): void
    {
        parent::boot();

        Livewire::component('translation-editor-modal', TranslationEditorModal::class);

        $this->app->extend('translator', function ($translator, $app): TranslationTrackerDecorator {
            $tracker = app(TranslationTracker::class);

            return new TranslationTrackerDecorator($translator, $tracker);
        });
    }

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('blackbadge-studio/translation-editor');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        // Set config early to ensure it's available before spatie/laravel-translation-loader registers its singleton
        // This must run in packageRegistered() to ensure it happens before other packages boot
        $loaderConfig = config('translation-editor.translation_loader', []);

        $translationLoaders = $loaderConfig['translation_loaders'] ?? [TrackingDbLoader::class];
        $translationManager = $loaderConfig['translation_manager'] ?? TrackingTranslationLoaderManager::class;
        $translationModel = config('translation-editor.models.translation');

        config([
            'translation-loader.translation_loaders' => $translationLoaders,
            'translation-loader.translation_manager' => $translationManager,
            'translation-loader.model' => $translationModel,
        ]);
    }

    public function packageBooted(): void
    {

        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/translation-editor/{$file->getFilename()}"),
                ], 'translation-editor-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsTranslationEditor);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'blackbadge-studio/translation-editor';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            Js::make('translation-editor-scripts', __DIR__ . '/../resources/dist/translation-editor.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            TranslationEditorCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'add_locale_column_to_authenticatable_table',
        ];
    }
}
