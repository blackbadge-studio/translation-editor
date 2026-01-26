<?php

use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;
use Blackbadgestudio\TranslationEditor\TranslationTrackerDecorator;
use Illuminate\Contracts\Translation\Translator;

it('tracks translation keys when translation is found', function () {
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->shouldReceive('get')
        ->with('validation.required', [], null)
        ->once()
        ->andReturn('The :attribute field is required.');

    $tracker = new TranslationTracker;
    $tracker->clear();

    $decorator = new TranslationTrackerDecorator($mockTranslator, $tracker);

    $result = $decorator->get('validation.required');

    expect($result)->toBe('The :attribute field is required.');

    $tracked = $tracker->getTracked();
    expect($tracked)->toHaveCount(1)
        ->and($tracked[0]['group'])->toBe('validation')
        ->and($tracked[0]['key'])->toBe('required');
});

it('does not track when translation returns the key itself', function () {
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->shouldReceive('get')
        ->with('nonexistent.key', [], null)
        ->once()
        ->andReturn('nonexistent.key');

    $tracker = new TranslationTracker;
    $tracker->clear();

    $decorator = new TranslationTrackerDecorator($mockTranslator, $tracker);

    $result = $decorator->get('nonexistent.key');

    expect($result)->toBe('nonexistent.key');

    $tracked = $tracker->getTracked();
    expect($tracked)->toBeEmpty();
});

it('does not track keys without dot notation', function () {
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->shouldReceive('get')
        ->with('simplekey', [], null)
        ->once()
        ->andReturn('Translated value');

    $tracker = new TranslationTracker;
    $tracker->clear();

    $decorator = new TranslationTrackerDecorator($mockTranslator, $tracker);

    $result = $decorator->get('simplekey');

    expect($result)->toBe('Translated value');

    $tracked = $tracker->getTracked();
    expect($tracked)->toBeEmpty();
});

it('passes through to underlying translator', function () {
    $mockTranslator = Mockery::mock(Translator::class);
    $mockTranslator->shouldReceive('getLocale')
        ->once()
        ->andReturn('en');

    $tracker = new TranslationTracker;
    $decorator = new TranslationTrackerDecorator($mockTranslator, $tracker);

    expect($decorator->getLocale())->toBe('en');
});
