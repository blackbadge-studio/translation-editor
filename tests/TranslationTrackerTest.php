<?php

use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;

it('tracks translation keys with group and key', function () {
    $tracker = new TranslationTracker;
    $tracker->clear();

    $tracker->track('validation', 'required');
    $tracker->track('auth', 'failed');

    $tracked = $tracker->getTracked();

    expect($tracked)->toHaveCount(2)
        ->and($tracked[0])->toHaveKeys(['group', 'key'])
        ->and($tracked[0]['group'])->toBe('validation')
        ->and($tracked[0]['key'])->toBe('required')
        ->and($tracked[1]['group'])->toBe('auth')
        ->and($tracked[1]['key'])->toBe('failed');
});

it('does not track duplicate keys', function () {
    $tracker = new TranslationTracker;
    $tracker->clear();

    $tracker->track('validation', 'required');
    $tracker->track('validation', 'required');
    $tracker->track('validation', 'required');

    $tracked = $tracker->getTracked();

    expect($tracked)->toHaveCount(1)
        ->and($tracked[0]['group'])->toBe('validation')
        ->and($tracked[0]['key'])->toBe('required');
});

it('clears all tracked keys', function () {
    $tracker = new TranslationTracker;
    $tracker->clear();

    $tracker->track('validation', 'required');
    $tracker->track('auth', 'failed');

    expect($tracker->getTracked())->toHaveCount(2);

    $tracker->clear();

    expect($tracker->getTracked())->toBeEmpty();
});
