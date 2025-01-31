<p align="center">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/soloterm/solo/refs/heads/main/art/solo_logo_dark.png">
      <source media="(prefers-color-scheme: light)" srcset="https://raw.githubusercontent.com/soloterm/solo/refs/heads/main/art/solo_logo_light.png">
      <img alt="Solo for Laravel" src="https://raw.githubusercontent.com/soloterm/solo/refs/heads/main/art/solo_logo_light.png" style="max-width: 80%; height: auto;">
    </picture>
</p>

<h3 align="center">Your all-in-one Laravel command to tame local development</h3>

---

# Solo for Laravel

> [!IMPORTANT]
> This package requires ext-pcntl, so it will not work on Windows. Sorry about that. If you know how to fix that, let me
know!

## About

Solo for Laravel is a package to run multiple commands at once, to aid in local development. All the commands needed to
run your application live behind a single artisan command:

```shell
php artisan solo
```

Each command runs in its own tab in Solo. Use the left/right arrow keys to navigate between tabs and enjoy a powerful,
unified development environment.

![Screenshot](https://github.com/aarondfrancis/solo/blob/main/art/screenshot.png?raw=true)

## Installation

1. Require the package:

```shell
composer require aaronfrancis/solo --dev
```

2. Install the package:

```shell
php artisan solo:install
```

This will publish the configuration file to `config/solo.php`.

## Configuration

Solo is entirely config-driven through `config/solo.php`. Here's a quick overview of what you can configure:

### Commands

Define your commands in the `commands` array:

```php
'commands' => [
    'About' => 'php artisan solo:about',
    'Logs' => EnhancedTailCommand::file(storage_path('logs/laravel.log')),
    'Vite' => 'npm run dev',
    'Make' => new MakeCommand,
    
    // Lazy commands don't start automatically
    'Dumps' => Command::from('php artisan solo:dumps')->lazy(),
    'Queue' => Command::from('php artisan queue:work')->lazy(),
    'Tests' => Command::from('php artisan test --colors=always')->lazy(),
],
```

You can define commands in several ways:

- Simple string: `'name' => 'php artisan [...]'`
- Using Command class: `'name' => Command::from('php artisan [...]')->lazy()`
- Using custom Command classes: `'Logs' => EnhancedTailCommand::file($path)`

### Themes

Solo ships with both light and dark themes. Configure your preference in `config/solo.php`:

```php
'theme' => env('SOLO_THEME', 'dark'),

'themes' => [
    'light' => Themes\LightTheme::class,
    'dark' => Themes\DarkTheme::class,
],
```

You can define your own theme if you'd like. It's probably easiest to subclass one of the existing themes.

### Keybindings

Choose between default and vim-style keybindings:

```php
'keybinding' => env('SOLO_KEYBINDING', 'default'),

'keybindings' => [
    'default' => Hotkeys\DefaultHotkeys::class,
    'vim' => Hotkeys\VimHotkeys::class,
],
```

Again, you're welcome to define and register your own keybidings.

## Usage

Start Solo with:

```shell
php artisan solo
```

### Key Controls

- **Navigation**:
    - Left/Right arrows to switch between tabs
    - Up/Down arrows to scroll output
    - Shift + Up/Down to page scroll
    - 'g' to quickly jump to any tab

- **Command Controls**:
    - 's' to start/stop the current command
    - 'r' to restart
    - 'c' to clear output
    - 'p' to pause output
    - 'f' to resume (follow) output

- **Interactive Mode**:
    - 'i' to enter interactive mode
    - Ctrl+X to exit interactive mode

- **Global**:
    - 'q' or Ctrl+C to quit Solo

## Special Commands

### EnhancedTailCommand

The `EnhancedTailCommand` provides improved log viewing with features like:

- Vendor frame collapsing
- Stack trace formatting
- Toggle vendor frames with 'v'
- File truncating

```php
'Logs' => EnhancedTailCommand::file(storage_path('logs/laravel.log')),
```

### MakeCommand

The `MakeCommand` provides an interactive interface for Laravel's make commands:

```php
'Make' => new MakeCommand,
```

## FAQ

#### My command isn't working

Try these steps:

1. Test if it works outside of Solo
2. Check if it has an `--ansi` option
3. Verify it's writing to STDOUT
4. Look for options to force STDOUT output

#### Can I run Sail commands?

Yes! Use this format: `vendor/bin/sail artisan schedule:work --ansi`

#### Does Solo support Windows?

No, Solo requires `ext-pcntl` and other Unix-specific features. If you know hwo to fix that, please open a PR.

#### Can I use this in production?

Not recommended. Use supervisor or similar tools for production environments.

## Support

This is free! If you want to support me:

- Check out my courses:
    - [Mastering Postgres](https://masteringpostgres.com)
    - [High Performance SQLite](https://highperformancesqlite.com)
    - [Screencasting](https://screencasting.com)
- Share them with friends
- Help spread the word about things I make

## Credits

Solo was developed by Aaron Francis. If you like it, please let me know!

- Twitter: https://twitter.com/aarondfrancis
- Website: https://aaronfrancis.com
- YouTube: https://youtube.com/@aarondfrancis
- GitHub: https://github.com/aarondfrancis/solo

Special thanks to:

- [Joe Tannenbaum](https://x.com/joetannenbaum) for his [Laracasts course](https://laracasts.com/series/cli-experiments)
- Joe's [Chewie package](https://github.com/joetannenbaum/chewie)
- [Laravel Prompts](https://laravel.com/docs/11.x/prompts)
- [Will King](https://x.com/wking__) for the Solo logo