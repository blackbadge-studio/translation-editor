<?php

namespace Blackbadgestudio\TranslationEditor\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class TranslationEditorAction
{
    public static function make(string $name = 'translationEditor'): Action
    {
        $toggleColumn = config('translation-editor.auth.toggle_column', 'translation_modal_enabled');

        return Action::make($name)
            ->label(function () use ($toggleColumn): string {
                $user = static::getUser();
                $isEnabled = false;

                if ($user) {
                    $isEnabled = (bool) $user->{$toggleColumn};
                }

                return $isEnabled ? 'Disable Translation Editor' : 'Enable Translation Editor';
            })
            ->icon(function () use ($toggleColumn): string {
                $user = static::getUser();
                $isEnabled = false;

                if ($user) {
                    $isEnabled = (bool) $user->{$toggleColumn};
                }

                return $isEnabled ? 'heroicon-o-eye-slash' : 'heroicon-o-eye';
            })
            ->color(function () use ($toggleColumn): string {
                $user = static::getUser();
                $isEnabled = false;

                if ($user) {
                    $isEnabled = (bool) $user->{$toggleColumn};
                }

                return $isEnabled ? 'danger' : 'success';
            })
            ->action(function () use ($toggleColumn): void {
                $user = static::getUser();

                if (! $user) {
                    return;
                }

                $currentValue = $user->{$toggleColumn} ?? false;
                $user->{$toggleColumn} = ! $currentValue;
                $user->saveQuietly();

                Notification::make()
                    ->title($user->{$toggleColumn}
                        ? 'Translation editor enabled'
                        : 'Translation editor disabled')
                    ->success()
                    ->send();
            });
    }

    protected static function getUser()
    {
        return Auth::user();
    }
}
