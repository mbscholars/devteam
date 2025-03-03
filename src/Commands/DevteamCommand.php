<?php

namespace mbscholars\Devteam\Commands;

use Illuminate\Console\Command;

class DevteamCommand extends Command
{
    public $signature = 'devteam';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
