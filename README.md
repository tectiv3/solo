# Solo for Laravel
```
███████╗ ██████╗ ██╗      ██████╗ 
██╔════╝██╔═══██╗██║     ██╔═══██╗
███████╗██║   ██║██║     ██║   ██║
╚════██║██║   ██║██║     ██║   ██║
███████║╚██████╔╝███████╗╚██████╔╝
╚══════╝ ╚═════╝ ╚══════╝ ╚═════╝  (for Laravel)
```

## About 

Solo for Laravel is a package to run multiple commands at once, to aid in local development. After installing, you can open the SoloServiceProvider to add or remove commands.

You can have all the commands needed to run your application behind a single command: 

> php artisan solo

Each command runs in its own tab in Solo.

![Screenshot](https://github.com/aarondfrancis/solo/blob/main/art/screenshot.png?raw=true)

## Credits 

Solo was developed by Aaron Francis. If you like it, please let me know!
 
- Twitter: https://twitter.com/aarondfrancis
- Website: https://aaronfrancis.com
- YouTube: https://youtube.com/@aarondfrancis
- GitHub: https://github.com/aarondfrancis/solo

This would not be possible without Joe Tannenbaum's [Laracasts course](https://laracasts.com/series/cli-experiments), his [Chewie package](https://github.com/joetannenbaum/chewie), and of course [Laravel Prompts](https://laravel.com/docs/11.x/prompts).

## Installation

Require the package:

```shell
composer require aaronfrancis/solo
```

Install the Service Provider:
```shell
php artisan solo:install
```

## Usage

You can run Solo with the following command:

```shell
php artisan solo
```

This will start every command defined in your `SoloServiceProvider`.


## Customization

To customize Solo, you can open your `SoloServiceProvider` and make changes there.

By default, it will look something like this:

```php
namespace App\Providers;

use AaronFrancis\Solo\Commands\EnhancedTailCommand;
use AaronFrancis\Solo\Facades\Solo;
use AaronFrancis\Solo\Providers\SoloApplicationServiceProvider;

class SoloServiceProvider extends SoloApplicationServiceProvider
{
    public function register()
    {
        Solo::useTheme('dark')
            // Commands that auto start.
            ->addCommands([
                EnhancedTailCommand::make('Logs', 'tail -f -n 100 ' . storage_path('logs/laravel.log')),
                'Vite' => 'npm run dev',
                // 'HTTP' => 'php artisan serve',
                'About' => 'php artisan solo:about'
            ])
            // Not auto-started
            ->addLazyCommands([
                'Queue' => 'php artisan queue:listen --tries=1',
                // 'Reverb' => 'php artisan reverb:start',
                // 'Pint' => 'pint --ansi',
            ])
            // FQCNs of trusted classes that can add commands.
            ->allowCommandsAddedFrom([
                //
            ]);
    }

    public function boot()
    {
        //
    }
}
```

Several commands are provided to get you started in the right direction.

## Adding / removing commands

To add new commands, you can pass a key/value pair of name/command to `addCommands` or `addLazyCommands`.

Lazy commands do not auto start. That can be helpful when you don't need to run a command everytime, but it might be useful from time to time. Like Queues or Reverb.

You may also pass a `AaronFrancis\Solo\Commands\Command` instance (with no key) to the `addCommands` or `addLazyCommands` methods.

For example, notice the `EnhancedTailCommand` command here:

```php
Solo::useTheme('dark')
    // Commands that auto start.
    ->addCommands([
        EnhancedTailCommand::make('Logs', 'tail -f -n 100 ' . storage_path('logs/laravel.log')),
        'Vite' => 'npm run dev',
        // 'HTTP' => 'php artisan serve',
        'About' => 'php artisan solo:about'
    ])
```

`EnhancedTailCommand` is a subclass of `Command` with a little bit of logic to make the logs more readable. You're free to create your own subclasses if you want!

To remove a command, simply delete the command. No need to create a PR to fix the stub. We've provided a reasonable set of starting commands, but the `SoloServiceProvider` lives in your application, so you have full control of it.



