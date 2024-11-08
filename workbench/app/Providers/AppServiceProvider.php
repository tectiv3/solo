<?php

namespace App\Providers;

use AaronFrancis\Solo\Commands\EnhancedTailCommand;
use AaronFrancis\Solo\Facades\Solo;
use AaronFrancis\Solo\Providers\SoloApplicationServiceProvider;
use Illuminate\Support\ProcessUtils;

use function Illuminate\Support\php_binary;
use function Orchestra\Testbench\package_path;

class AppServiceProvider extends SoloApplicationServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        Solo::useTheme('dark')
            // Commands that auto start.
            ->addCommands([
                EnhancedTailCommand::make('Logs', 'tail -f -n 100 ' . storage_path('logs/laravel.log')),
                'HTTP' => implode(' ', [
                    'php',
                    ProcessUtils::escapeArgument(package_path('vendor', 'bin', 'testbench')),
                    'serve'
                ]),
                'About' => implode(' ', [
                    'php',
                    ProcessUtils::escapeArgument(package_path('vendor', 'bin', 'testbench')),
                    'solo:about'
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
