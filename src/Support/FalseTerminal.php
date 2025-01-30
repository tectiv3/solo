<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Support;

use Laravel\Prompts\Terminal;

class FalseTerminal extends Terminal
{
    public $width = 80;

    public $height = 24;

    public function __construct()
    {
        //
    }

    public function read(): string
    {
        return '';
    }

    public function setTty(string $mode): void
    {
        //
    }

    public function restoreTty(): void
    {
        //
    }

    public function cols(): int
    {
        return $this->width;
    }

    public function lines(): int
    {
        return $this->height;
    }

    public function initDimensions(): void
    {
        //
    }

    public function exit(): void
    {
        //
    }

    protected function exec(string $command): string
    {
        return '';
    }
}
