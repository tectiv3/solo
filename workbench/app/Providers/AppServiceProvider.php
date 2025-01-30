<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace App\Providers;

use SoloTerm\Solo\Commands\Command;
use SoloTerm\Solo\Commands\EnhancedTailCommand;
use SoloTerm\Solo\Facades\Solo;
use Illuminate\Support\ProcessUtils;
use Illuminate\Support\ServiceProvider;

use function Orchestra\Testbench\package_path;

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
            'About' => Command::from('php artisan solo:about')->interactive(),
            'Dumps' => 'php artisan solo:dumps',
            'Logs' => EnhancedTailCommand::file(storage_path('logs/laravel.log')),
            'Tail' => 'tail -f -n 100 ' . storage_path('logs/laravel.log'),
            //                'HTTP' => implode(' ', [
            //                    'php',
            //                    ProcessUtils::escapeArgument(package_path('vendor', 'bin', 'testbench')),
            //                    'serve'
            //                ]),
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
