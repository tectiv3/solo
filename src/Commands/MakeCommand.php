<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace SoloTerm\Solo\Commands;

class MakeCommand extends Command
{
    public function boot(): void
    {
        $this->name = 'Make';
        $this->command = 'php artisan solo:make';
        $this->interactive = true;
        $this->autostart = true;
    }

    public function hotkeys(): array
    {
        return [
            //
        ];
    }
}
