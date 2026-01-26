<?php

use Blackbadgestudio\TranslationEditor\Actions\TranslationEditorAction;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    Config::set('translation-editor.auth.toggle_column', 'translation_modal_enabled');
});

it('shows enable label when modal is disabled', function () {
    $user = new class extends User
    {
        protected $table = 'users';

        public $translation_modal_enabled = false;
    };

    Auth::shouldReceive('user')->andReturn($user);

    $action = TranslationEditorAction::make();

    expect($action->getLabel())->toBe('Enable Translation Editor');
});

it('shows disable label when modal is enabled', function () {
    $user = new class extends User
    {
        protected $table = 'users';

        public $translation_modal_enabled = true;
    };

    Auth::shouldReceive('user')->andReturn($user);

    $action = TranslationEditorAction::make();

    expect($action->getLabel())->toBe('Disable Translation Editor');
});

it('shows correct icon based on toggle state', function () {
    $user = new class extends User
    {
        protected $table = 'users';

        public $translation_modal_enabled = false;
    };

    Auth::shouldReceive('user')->andReturn($user);

    $action = TranslationEditorAction::make();

    expect($action->getIcon())->toBe('heroicon-o-eye');

    $user->translation_modal_enabled = true;
    $action = TranslationEditorAction::make();

    expect($action->getIcon())->toBe('heroicon-o-eye-slash');
});

it('shows correct color based on toggle state', function () {
    $user = new class extends User
    {
        protected $table = 'users';

        public $translation_modal_enabled = false;
    };

    Auth::shouldReceive('user')->andReturn($user);

    $action = TranslationEditorAction::make();

    expect($action->getColor())->toBe('success');

    $user->translation_modal_enabled = true;
    $action = TranslationEditorAction::make();

    expect($action->getColor())->toBe('danger');
});
