<?php

namespace Blackbadgestudio\TranslationEditor;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;

class TranslationEditorPlugin implements Plugin
{
    public function getId(): string
    {
        return 'translation-editor';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        // Register the translation editor modal component to be rendered in the layout
        // Using the body end hook to inject the modal at the end of the page
        $hookName = class_exists(\Filament\View\PanelsRenderHook::class)
            ? \Filament\View\PanelsRenderHook::BODY_END
            : 'panels::body.end';

        FilamentView::registerRenderHook(
            $hookName,
            fn (): string => view('translation-editor::livewire.translation-editor-modal-wrapper')->render(),
        );
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
