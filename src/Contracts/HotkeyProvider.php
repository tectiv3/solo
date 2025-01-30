<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace SoloTerm\Solo\Contracts;

use SoloTerm\Solo\Hotkeys\Hotkey;

interface HotkeyProvider
{
    /**
     * @return array<Hotkey>
     */
    public static function keys(): array;
}
