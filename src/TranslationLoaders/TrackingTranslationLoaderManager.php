<?php

namespace Blackbadgestudio\TranslationEditor\TranslationLoaders;

use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;
use Spatie\TranslationLoader\TranslationLoaderManager;

class TrackingTranslationLoaderManager extends TranslationLoaderManager
{
    protected TranslationTracker $tracker;

    public function __construct(\Illuminate\Filesystem\Filesystem $app, array | string $files)
    {
        parent::__construct($app, $files);
        $this->tracker = app(TranslationTracker::class);
    }

    /**
     * Load the messages for the given locale and track individual key access
     */
    public function load($locale, $group, $namespace = null): array
    {
        // Don't track here - we'll track when keys are actually accessed
        // The tracking will happen via a service provider that hooks into translation retrieval

        return parent::load($locale, $group, $namespace);
    }
}
