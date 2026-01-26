<?php

namespace Blackbadgestudio\TranslationEditor;

use Blackbadgestudio\TranslationEditor\Services\TranslationTracker;
use Illuminate\Contracts\Translation\Translator;

class TranslationTrackerDecorator implements Translator
{
    public function __construct(protected Translator $translator, protected TranslationTracker $tracker) {}

    public function get($key, array $replace = [], $locale = null)
    {
        $result = $this->translator->get($key, $replace, $locale);

        // Track the translation key that was actually accessed
        // Only track if we got a translation (not the key itself)
        if ($result !== $key && str_contains($key, '.')) {
            $parts = explode('.', $key, 2);
            $group = $parts[0];
            $translationKey = $parts[1] ?? '';

            if ($group && $translationKey) {
                $this->tracker->track($group, $translationKey);
            }
        }

        return $result;
    }

    public function choice($key, $number, array $replace = [], $locale = null)
    {
        return $this->translator->choice($key, $number, $replace, $locale);
    }

    public function getLocale()
    {
        return $this->translator->getLocale();
    }

    public function setLocale($locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function __call(string $method, array $parameters)
    {
        return $this->translator->{$method}(...$parameters);
    }

    public function __get(string $property): mixed
    {
        return $this->translator->{$property};
    }

    public function __set(string $property, mixed $value)
    {
        $this->translator->{$property} = $value;
    }
}
