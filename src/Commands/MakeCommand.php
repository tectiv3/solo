<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Commands;

use AaronFrancis\Solo\Facades\Solo;
use AaronFrancis\Solo\Hotkeys\Hotkey;
use AaronFrancis\Solo\Hotkeys\KeyHandler;
use AaronFrancis\Solo\Prompt\Dashboard;
use Chewie\Input\KeyPressListener;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Prompts\Key;

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

