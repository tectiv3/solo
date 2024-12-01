<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace App\Providers;

use AaronFrancis\Solo\Commands\EnhancedTailCommand;
use AaronFrancis\Solo\Facades\Solo;
use AaronFrancis\Solo\Providers\SoloApplicationServiceProvider;
use Illuminate\Support\ProcessUtils;

use function Orchestra\Testbench\package_path;

class AppServiceProvider extends SoloApplicationServiceProvider
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
            \AaronFrancis\Solo\Console\Commands\Test::class
        ]);

        Solo::useTheme('dark')
            // Commands that auto start.
            ->addCommands([
                'About' => implode(' ', [
                    'php',
                    package_path('vendor', 'bin', 'testbench'),
                    'solo:about'
                ]),

                EnhancedTailCommand::make('Logs', 'tail -f -n 100 ' . storage_path('logs/laravel.log')),
                'HTTP' => implode(' ', [
                    'php',
                    ProcessUtils::escapeArgument(package_path('vendor', 'bin', 'testbench')),
                    'serve'
                ]),
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
