<?php

namespace ilsawn\LaravelIlsawn\Commands;

use Illuminate\Console\Command;

class LaravelIlsawnCommand extends Command
{
    public $signature = 'laravel-ilsawn';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
