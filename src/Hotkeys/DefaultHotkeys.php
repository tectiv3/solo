<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Hotkeys;

use AaronFrancis\Solo\Commands\Command;
use AaronFrancis\Solo\Contracts\HotkeyProvider;
use AaronFrancis\Solo\Prompt\Dashboard;
use Chewie\Input\KeyPressListener;
use Laravel\Prompts\Key;

class DefaultHotkeys implements HotkeyProvider
{

    /**
     * @return array<string, Hotkey>
     */
    public static function keymap()
    {
        // The array keys have no meaning beyond providing a way
        // to directly address hotkeys. This is useful in the
        // VimKeys class to remap the navigation keys.
        return [
            'interactive' => Hotkey::make('i', KeyHandler::Interactive),
            'clear' => Hotkey::make('c', KeyHandler::Clear),
            'pause' => Hotkey::make('p', KeyHandler::Pause)
                ->display(fn(Command $command) => !$command->paused),

            'follow' => Hotkey::make('f', KeyHandler::Follow)
                ->display(fn(Command $command) => $command->paused),

            'start_stop' => Hotkey::make('r', KeyHandler::StartStop),
            'restart' => Hotkey::make('r', KeyHandler::Restart),
            'quit' => Hotkey::make(['q', Key::CTRL_C], KeyHandler::Quit),

            'previous_tab' => Hotkey::make([Key::LEFT, Key::LEFT_ARROW], KeyHandler::PreviousTab),
            'next_tab' => Hotkey::make([Key::RIGHT, Key::RIGHT_ARROW], KeyHandler::NextTab),

            'scroll_up' => Hotkey::make([Key::UP, Key::UP_ARROW], KeyHandler::ScrollUp),
            'scroll_down' => Hotkey::make([Key::DOWN, Key::DOWN_ARROW], KeyHandler::ScrollDown),

            'page_up' => Hotkey::make(Key::SHIFT_UP, KeyHandler::PageUp)
                ->hidden(),

            'page_down' => Hotkey::make(Key::SHIFT_DOWN, KeyHandler::PageDown)
                ->hidden(),

            'dd' => Hotkey::make('d', KeyHandler::DD)
                ->hidden(),
        ];

    }

    /**
     * @return array<Hotkey>
     */
    public static function keys(): array
    {
        return array_values(static::keymap());
    }
}