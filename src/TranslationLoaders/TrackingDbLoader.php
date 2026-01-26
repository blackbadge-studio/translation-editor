<?php

namespace Blackbadgestudio\TranslationEditor\TranslationLoaders;

use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;
use Spatie\TranslationLoader\TranslationLoaders\Db;
use Spatie\TranslationLoader\TranslationLoaders\TranslationLoader;

class TrackingDbLoader implements TranslationLoader
{
    protected Db $dbLoader;

    protected TranslationTracker $tracker;

    public function __construct()
    {
        $this->dbLoader = new Db;
        $this->tracker = app(TranslationTracker::class);
    }

    public function loadTranslations(string $locale, string $group): array
    {
        return $this->dbLoader->loadTranslations($locale, $group);
    }
}
