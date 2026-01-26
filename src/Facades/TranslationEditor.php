<?php

namespace Blackbadgestudio\TranslationEditor\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Blackbadgestudio\TranslationEditor\TranslationEditor
 */
class TranslationEditor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Blackbadgestudio\TranslationEditor\TranslationEditor::class;
    }
}
