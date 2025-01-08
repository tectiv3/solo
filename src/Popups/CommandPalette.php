<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Popups;

use AaronFrancis\Solo\Support\CapturedMultiSelectPrompt;
use AaronFrancis\Solo\Support\CapturedTextPrompt;
use Generator;

class CommandPalette extends Popup
{
    use HasForm;

    public bool $exitRequested = false;

    public function boot()
    {
        $this->screen->writeln('Pick all the commands you want to have in your dashboard.');
    }

    public function form(): Generator
    {
        $commands = collect(config('solo.commands'))->map(function ($command, $name) {
            if (is_string($command)) {
                return $name;
            }

            return $command->name ?? $name;
        });

        yield $select = new CapturedMultiSelectPrompt(
            label: 'Pick a command',
            options: $commands,
            scroll: 10,
        );

        yield 'Type in whatever you want';

        yield $random = new CapturedTextPrompt(
            label: 'Type a new command',
            placeholder: 'php artisan foo:bar'
        );

        dump($random->value());
    }

    public function handleInput($key)
    {
        if ($key === "\x18") {
            $this->exitRequested = true;

            return;
        }

        $this->handleFormInput($key);
    }

    public function shouldClose(): bool
    {
        return $this->exitRequested;
    }
}
