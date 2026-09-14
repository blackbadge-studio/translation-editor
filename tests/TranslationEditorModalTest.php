<?php

use Blackbadgestudio\TranslationEditor\Livewire\TranslationEditorModal;
use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\TranslationLoader\LanguageLine;

beforeEach(function (): void {
    Schema::create('language_lines', function (Blueprint $table): void {
        $table->id();
        $table->string('group')->index();
        $table->string('key');
        $table->json('text');
        $table->boolean('is_new')->default(false);
        $table->timestamps();
    });

    config()->set('translation-editor.models.translation', LanguageLine::class);
    config()->set('translation-editor.models.locale', ['en', 'nl']);
    config()->set('translation-editor.auth.toggle_column', 'translation_modal_enabled');
    config()->set('translation-editor.auth.column', 'locale');

    app(TranslationTracker::class)->clear();
});

afterEach(function (): void {
    app(TranslationTracker::class)->clear();
});

function editorUser(bool $enabled = true, ?string $locale = null): User
{
    $user = new class extends User
    {
        protected $table = 'users';

        protected $guarded = [];
    };

    $user->forceFill([
        'id' => 1,
        'name' => 'Editor',
        'translation_modal_enabled' => $enabled,
        'locale' => $locale,
    ]);

    return $user;
}

it('renders an empty root element for a user who did not enable the editor', function (): void {
    $this->actingAs(editorUser(enabled: false));

    Livewire::test(TranslationEditorModal::class)
        ->assertOk()
        ->assertDontSee('Translation Editor');
});

it('renders an empty root element for a guest', function (): void {
    Livewire::test(TranslationEditorModal::class)
        ->assertOk()
        ->assertDontSee('Translation Editor');
});

it('lists the translation keys used on the page in the locale of the user', function (): void {
    LanguageLine::create([
        'group' => 'validation',
        'key' => 'required',
        'text' => ['en' => 'This field is required.', 'nl' => 'Dit veld is verplicht.'],
    ]);
    app(TranslationTracker::class)->track('validation', 'required');

    $this->actingAs(editorUser(locale: 'nl'));

    Livewire::test(TranslationEditorModal::class)
        ->assertOk()
        ->assertSet('activeLocale', 'nl')
        ->assertSee('Translation Editor')
        ->assertSee('EN')
        ->assertSee('NL')
        ->assertSee('required')
        ->assertSee('Dit veld is verplicht.')
        ->call('setActiveLocale', 'en')
        ->assertSet('activeLocale', 'en')
        ->assertSee('This field is required.');
});

it('falls back to the first configured locale when the user has none', function (): void {
    app(TranslationTracker::class)->track('validation', 'required');

    $this->actingAs(editorUser());

    Livewire::test(TranslationEditorModal::class)
        ->assertSet('activeLocale', 'en');
});

it('tells the user when the page used no translations', function (): void {
    $this->actingAs(editorUser());

    Livewire::test(TranslationEditorModal::class)
        ->assertSee('Translation Editor')
        ->assertSee('No translations found on this page.');
});

it('saves the edited translation for the active locale and refreshes the exported files', function (): void {
    LanguageLine::create([
        'group' => 'validation',
        'key' => 'required',
        'text' => ['en' => 'This field is required.'],
    ]);
    app(TranslationTracker::class)->track('validation', 'required');

    // Testbench's console kernel is final, so the facade cannot be mocked in place;
    // swapping in a mock of the kernel contract keeps the real export from writing files.
    $kernel = Mockery::mock(Kernel::class);
    $kernel->shouldReceive('call')->once()->with('translations:export', ['--force-confirm' => true])->andReturn(0);
    $kernel->shouldReceive('call')->once()->with('optimize:clear')->andReturn(0);
    Artisan::swap($kernel);

    $this->actingAs(editorUser(locale: 'nl'));

    Livewire::test(TranslationEditorModal::class)
        ->call('updateTranslationValue', 'validation.required.nl', 'Dit veld is verplicht.')
        ->call('saveTranslations', 'nl')
        ->assertNotified('Translations saved')
        ->assertSet('isLoading', false);

    $line = LanguageLine::query()->where('group', 'validation')->where('key', 'required')->firstOrFail();

    expect($line->text)->toBe(['en' => 'This field is required.', 'nl' => 'Dit veld is verplicht.'])
        ->and((bool) $line->is_new)->toBeTrue();
});

it('labels the locale tabs with the upper-cased code for a plain list of locales', function (): void {
    $this->actingAs(editorUser());

    $options = Livewire::test(TranslationEditorModal::class)->instance()->getLocaleOptions();

    expect($options)->toBe(['en' => 'EN', 'nl' => 'NL']);
});

it('labels model-backed locales with the configured label column', function (): void {
    Schema::create('locales', function (Blueprint $table): void {
        $table->id();
        $table->string('key');
        $table->string('name');
    });

    $locale = new class extends Model
    {
        protected $table = 'locales';

        protected $guarded = [];

        public $timestamps = false;
    };

    $locale::query()->insert([
        ['key' => 'nl', 'name' => 'Nederlands'],
        ['key' => 'en', 'name' => 'English'],
    ]);

    config()->set('translation-editor.models.locale', [
        'model' => $locale::class,
        'column' => 'key',
        'label_column' => 'name',
    ]);
    app(TranslationTracker::class)->track('validation', 'required');

    $this->actingAs(editorUser(locale: 'nl'));

    $component = Livewire::test(TranslationEditorModal::class)
        ->assertSet('activeLocale', 'nl')
        ->assertSee('Nederlands')
        ->assertSee('English');

    expect($component->instance()->getLocaleOptions())->toBe(['nl' => 'Nederlands', 'en' => 'English']);
});

it('labels model-backed locales with the code when no label column is configured', function (): void {
    Schema::create('locales', function (Blueprint $table): void {
        $table->id();
        $table->string('key');
    });

    $locale = new class extends Model
    {
        protected $table = 'locales';

        protected $guarded = [];

        public $timestamps = false;
    };

    $locale::query()->insert([['key' => 'fr'], ['key' => 'de']]);

    config()->set('translation-editor.models.locale', $locale::class);

    $this->actingAs(editorUser());

    expect(Livewire::test(TranslationEditorModal::class)->instance()->getLocaleOptions())
        ->toBe(['fr' => 'FR', 'de' => 'DE']);
});
