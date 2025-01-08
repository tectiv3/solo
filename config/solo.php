<?php

use AaronFrancis\Solo\Commands\Command;
use AaronFrancis\Solo\Commands\EnhancedTailCommand;
use AaronFrancis\Solo\Hotkeys as Hotkeys;
use AaronFrancis\Solo\Themes as Themes;

// Solo may not (should not!) exist in prod, so we have to
// check here first to see if it's installed.
if (!class_exists('\AaronFrancis\Solo\Manager')) {
    return [
        //
    ];
}

return [
    /*
    |--------------------------------------------------------------------------
    | Themes
    |--------------------------------------------------------------------------
    */
    'theme' => env('SOLO_THEME', 'dark'),

    'themes' => [
        'light' => Themes\LightTheme::class,
        'dark' => Themes\DarkTheme::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Keybindings
    |--------------------------------------------------------------------------
    */
    'keybinding' => env('SOLO_KEYBINDING', 'default'),

    'keybindings' => [
        'default' => Hotkeys\DefaultHotkeys::class,
        'vim' => Hotkeys\VimHotkeys::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Commands
    |--------------------------------------------------------------------------
    |
    | You can organize your commands into groups, which is helpful for teams
    | working on different parts of the stack. You can use the magic key
    | "prompt" if you want Solo to ask you which group you want to run.
    |
    */
    'group' => env('SOLO_COMMAND_GROUP', 'default'),

    'commands' => [
        'About' => 'php artisan solo:about',
        'Logs' => EnhancedTailCommand::file(storage_path('logs/laravel.log')),
        'Vite' => 'npm run dev',

        'HTTP' => 'php artisan serve',
        'Dumps' => 'php artisan solo:dumps',
        'Queue' => 'php artisan queue:work',

        // Lazy commands do no automatically start when Solo starts.
        'Reverb' => Command::from('php artisan reverb')->lazy(),
        'Pint' => Command::from('php artisan pint')->lazy(),
    ],

    'groups' => [
        'default' => [
            'About' => 'php artisan solo:about',
            'Logs' => EnhancedTailCommand::file(storage_path('logs/laravel.log')),
            'Vite' => 'npm run dev',

            // 'HTTP' => 'php artisan serve',
            // 'Dumps' => 'php artisan solo:dumps',
            // 'Queue' => 'php artisan queue:work',

            // Lazy commands do no automatically start when Solo starts.
            'Reverb' => Command::from('php artisan reverb')->lazy(),
            // 'Pint' => Command::make('php artisan pint')->lazy(),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous
    |--------------------------------------------------------------------------
    */

    /*
     * If you run the solo:dumps command, Solo will start a server to receive
     * the dumps. This is the address. You probably don't need to change
     * this unless the default is already taken for some reason.
     */
    'dump_server_host' => env('SOLO_DUMP_SERVER_HOST', 'tcp://127.0.0.1:9984')
];
