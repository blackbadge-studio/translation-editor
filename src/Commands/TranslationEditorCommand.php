<?php

namespace Blackbadgestudio\TranslationEditor\Commands;

use Illuminate\Console\Command;

class TranslationEditorCommand extends Command
{
    public $signature = 'translation-editor';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
