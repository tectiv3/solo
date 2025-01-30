<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 *
 * @link https://aaronfrancis.com
 * @link https://x.com/aarondfrancis
 */

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SoloTerm\Solo\Commands\Command;
use SoloTerm\Solo\Commands\EnhancedTailCommand;
use SoloTerm\Solo\Commands\MakeCommand;
use SoloTerm\Solo\Facades\Solo;

class AppServiceProvider extends ServiceProvider
{
    public static function allowCommandsFromTest($class)
    {
        Solo::allowCommandsAddedFrom([
            $class
        ]);
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        Solo::allowCommandsAddedFrom([
            \SoloTerm\Solo\Console\Commands\Test::class
        ]);

        Solo::addCommands([
            'About' => 'php artisan solo:about',
            'Logs' => EnhancedTailCommand::file(storage_path('logs/laravel.log')),
            'Vite' => 'npm run dev',
            'Make' => MakeCommand::class,

            // Lazy commands do no automatically start when Solo starts.
            'HTTP' => Command::from('php artisan serve')->lazy(),
            'Dumps' => Command::from('php artisan solo:dumps')->lazy(),
            'Reverb' => Command::from('php artisan reverb')->lazy(),
            'Pint' => Command::from('./vendor/bin/pint --ansi')->lazy(),
            'Queue' => Command::from('php artisan queue:work')->lazy(),
            'Tests' => Command::from('php artisan test --colors=always')->lazy(),
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
