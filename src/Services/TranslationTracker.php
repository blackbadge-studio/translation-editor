<?php

namespace Blackbadgestudio\TranslationEditor\Services;

class TranslationTracker
{
    /**
     * Tracked translation keys for the current request
     *
     * @var array<string>
     */
    protected static array $tracked = [];

    /**
     * Track a translation key that was accessed
     */
    public function track(string $group, string $key): void
    {
        $trackedKey = "{$group}.{$key}";

        if (! in_array($trackedKey, self::$tracked, true)) {
            self::$tracked[] = $trackedKey;
        }
    }

    /**
     * Get all tracked translation keys for the current request
     *
     * @return array<int, array{group: string, key: string}>
     */
    public function getTracked(): array
    {
        $result = [];
        foreach (self::$tracked as $trackedKey) {
            if (str_contains($trackedKey, '.')) {
                [$group, $key] = explode('.', $trackedKey, 2);
                $result[] = [
                    'group' => $group,
                    'key' => $key,
                ];
            }
        }

        return $result;
    }

    /**
     * Clear tracked translations for the current request
     */
    public function clear(): void
    {
        self::$tracked = [];
    }
}
