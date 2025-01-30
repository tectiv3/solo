<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SoloTerm\Solo\Popups;

use SoloTerm\Solo\Support\Screen;

class Help extends Popup
{
    public Screen $screen;

    public bool $exitRequested = false;

    public function boot()
    {
        $this->screen->writeln('This is where the help text would go.');
    }

    public function renderSingleFrame()
    {
        //
    }

    public function handleInput($key)
    {
        if ($key === "\x18") {
            $this->exitRequested = true;

            return;
        }
    }

    public function shouldClose(): bool
    {
        return $this->exitRequested;
    }
}
