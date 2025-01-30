<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class Install extends Command
{
    protected $signature = 'solo:install';

    protected $description = 'Install the Solo service provider';

    public function handle()
    {
        $this->comment('Publishing Solo configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'solo-config']);

        $this->info('Solo installed successfully.');
        $this->info('Run `php artisan solo` to start.');
    }

}
