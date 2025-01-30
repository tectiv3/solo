<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace SoloTerm\Solo\Support;

interface CapturedPromptInterface
{
    public function setScreen(Screen $screen);

    public function renderSingleFrame();

    public function handleInput($key);
}
