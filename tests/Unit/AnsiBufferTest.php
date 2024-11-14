<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Tests\Unit;

use AaronFrancis\Solo\Helpers\AnsiAware;
use AaronFrancis\Solo\Support\AnsiBuffer;
use AaronFrancis\Solo\Support\Screen;
use Illuminate\Support\Benchmark;
use PHPUnit\Framework\Attributes\Test;
use SplQueue;

class AnsiBufferTest extends Base
{
    #[Test]
    public function add_foreground_colors(): void
    {
        $ansi = new AnsiBuffer;

        // Black
        $ansi->addAnsiCode(30);

        $this->assertEquals("\e[30m", $ansi->getMaskAsAnsi());

        // Red
        $ansi->addAnsiCode(31);

        $this->assertEquals("\e[31m", $ansi->getMaskAsAnsi());
    }

    #[Test]
    public function default_foreground(): void
    {
        $ansi = new AnsiBuffer;

        // FG
        $ansi->addAnsiCode(30);
        // BG
        $ansi->addAnsiCode(40);
        // Reset FG only
        $ansi->addAnsiCode(39);

        $this->assertEquals("\e[39;40m", $ansi->getMaskAsAnsi());
    }

    #[Test]
    public function default_background(): void
    {
        $ansi = new AnsiBuffer;

        // FG
        $ansi->addAnsiCode(30);
        // BG
        $ansi->addAnsiCode(40);
        // Reset BG only
        $ansi->addAnsiCode(49);

        $this->assertEquals("\e[30;49m", $ansi->getMaskAsAnsi());
    }

    #[Test]
    public function reset_decorations(): void
    {
        $ansi = new AnsiBuffer;

        // Bold
        $ansi->addAnsiCode(1);
        $this->assertEquals("\e[1m", $ansi->getMaskAsAnsi());

        // unset bold
        $ansi->addAnsiCode(22);
        $this->assertEquals("\e[22m", $ansi->getMaskAsAnsi());

        // Bold
        $ansi->addAnsiCode(1);
        // Italic
        $ansi->addAnsiCode(3);
        $this->assertEquals("\e[1;3m", $ansi->getMaskAsAnsi());

        // unset bold
        $ansi->addAnsiCode(22);
        $this->assertEquals("\e[3;22m", $ansi->getMaskAsAnsi());
    }

    #[Test]
    public function reset_bold_and_dim(): void
    {
        $ansi = new AnsiBuffer;

        // Bold
        $ansi->addAnsiCode(1);
        $ansi->addAnsiCode(2);
        $this->assertEquals("\e[1;2m", $ansi->getMaskAsAnsi());

        // unset both
        $ansi->addAnsiCode(22);
        $this->assertEquals("\e[22m", $ansi->getMaskAsAnsi());
    }

    #[Test]
    public function reset_all(): void
    {
        $ansi = new AnsiBuffer;

        // Black fg
        $ansi->addAnsiCode(30);
        // Blue bg
        $ansi->addAnsiCode(44);
        // Italic
        $ansi->addAnsiCode(3);

        $this->assertEquals("\e[3;30;44m", $ansi->getMaskAsAnsi());

        $ansi->addAnsiCode(0);

        $this->assertEquals("\e[0m", $ansi->getMaskAsAnsi());
    }

    #[Test]
    public function clear_test(): void
    {
        $ansi = new AnsiBuffer;
        $ansi->buffer = [
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
        ];

        $ansi->clearBuffer(
            startRow: 1,
            startCol: 3,
            endRow: 3,
            endCol: 2
        );

        $buffer = array_map(fn($line) => implode(',', $line), $ansi->buffer);

        $this->assertSame([
            "1,1,1,1,1",
            "1,1,1",
            "",
            "0,0,0,1,1",
            "1,1,1,1,1",
        ], $buffer);
    }

    #[Test]
    public function clear_beyond_cols_test(): void
    {
        $ansi = new AnsiBuffer;
        $ansi->buffer = [
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
        ];

        $ansi->clearBuffer(startRow: 1, startCol: -3, endRow: 3, endCol: 50);

        $buffer = array_map(fn($line) => implode(',', $line), $ansi->buffer);

        $this->assertSame([
            "1,1,1,1,1",
            "",
            "",
            "",
            "1,1,1,1,1",
        ], $buffer);
    }

    #[Test]
    public function clear_beyond_rows_test(): void
    {
        $ansi = new AnsiBuffer;
        $ansi->buffer = [
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
        ];

        $ansi->clearBuffer(startRow: 10, startCol: 1, endRow: 13, endCol: 10);

        $buffer = array_map(fn($line) => implode(',', $line), $ansi->buffer);

        $this->assertSame([
            "1,1,1,1,1",
            "1,1,1,1,1",
            "1,1,1,1,1",
            "1,1,1,1,1",
            "1,1,1,1,1",
        ], $buffer);
    }

    #[Test]
    public function same_line_clear(): void
    {
        $ansi = new AnsiBuffer;
        $ansi->buffer = [
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
            [1, 1, 1, 1, 1],
        ];

        $ansi->clearBuffer(startRow: 1, startCol: 1, endRow: 1, endCol: 2);

        $buffer = array_map(fn($line) => implode(',', $line), $ansi->buffer);

        $this->assertSame([
            "1,1,1,1,1",
            "1,0,0,1,1",
            "1,1,1,1,1",
            "1,1,1,1,1",
            "1,1,1,1,1",
        ], $buffer);
    }

//    #[Test]
//    public function speed(): void
//    {
//        $ansi = new AnsiBuffer;
//
//        $mask = (1 << 0) + (1 << 3);
//
//        Benchmark::dd(function () use ($ansi, $mask) {
//            $ansi->ansiCodesFromMask($mask);
//        }, 20000);
//    }


}