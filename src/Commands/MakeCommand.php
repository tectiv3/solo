<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Commands;

use AaronFrancis\Solo\Facades\Solo;
use AaronFrancis\Solo\Hotkeys\Hotkey;
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
        $this->command = 'php artisan make:model';
        $this->interactive = true;
        $this->autostart = true;
    }

    /**
     * @return array<string, Hotkey>
     */
    public function hotkeys(): array
    {
        return [
            'model' => Hotkey::make('e', function (Dashboard $prompt, KeyPressListener $listener) {
                $listener->clearExisting();

                $listener->wildcard(function ($key) {
                    $this->command .= $key;
                });

                $listener->on(Key::ENTER, function () use ($prompt) {
                    $prompt->rebindHotkeys();
                });

//                $this->command = "php artisan make:interface";
//                $this->afterTerminate(function () {
//                    $this->clear();
//                    $this->addLine('M: make model');
//                });
//                $this->start();
//                $prompt->enterInteractiveMode();
            })
        ];
    }

}

//   make:activity             Create a new activity class
//  make:cache-table          [cache:table] Create a migration for the cache database table
//  make:cast                 Create a new custom Eloquent cast class
//  make:channel              Create a new channel class
//  make:class                Create a new class
//  make:command              Create a new Artisan command
//  make:component            Create a new view component class
//  make:controller           Create a new controller class
//  make:enum                 Create a new enum
//  make:event                Create a new event class
//  make:exception            Create a new custom exception class
//  make:factory              Create a new model factory
//  make:interface            Create a new interface
//  make:job                  Create a new job class
//  make:job-middleware       Create a new job middleware class
//  make:listener             Create a new event listener class
//  make:mail                 Create a new email class
//  make:middleware           Create a new HTTP middleware class
//  make:migration            Create a new migration file
//  make:model                Create a new Eloquent model class
//  make:notification         Create a new notification class
//  make:notifications-table  [notifications:table] Create a migration for the notifications table
//  make:observer             Create a new observer class
//  make:policy               Create a new policy class
//  make:provider             Create a new service provider class
//  make:queue-batches-table  [queue:batches-table] Create a migration for the batches database table
//  make:queue-failed-table   [queue:failed-table] Create a migration for the failed queue jobs database table
//  make:queue-table          [queue:table] Create a migration for the queue jobs database table
//  make:request              Create a new form request class
//  make:resource             Create a new resource
//  make:rule                 Create a new validation rule
//  make:scope                Create a new scope class
//  make:seeder               Create a new seeder class
//  make:session-table        [session:table] Create a migration for the session database table
//  make:test                 Create a new test class
//  make:trait                Create a new trait
//  make:view                 Create a new view
//  make:workflow             Create a new workflow class
