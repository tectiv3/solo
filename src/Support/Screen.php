<?php

namespace AaronFrancis\Solo\Support;

use Exception;
use SplQueue;

class Screen
{
    const ANSI_CODE_REGEX = '/
    (
        \e\[          # Escape code      
        [0-9;?]*      # Params
        [a-zA-Z]      # Command    
    )
/x';

    const ANSI_CODE_PARTS_REGEX = '/
        (?P<params>   
            [0-9;?]*  # Capture the params
        )     
        (?P<command>  
            [a-zA-Z]  # Capture the command
        )    
/x';

    public AnsiBuffer $ansi;

    public array $buffer = [];

    public int $cursorRow = 0;

    public int $cursorCol = 0;

    protected bool $unflushedAnsi = false;

    public function __construct()
    {
        $this->ansi = new AnsiBuffer;
    }

    public function containsAnsiMoveCodes(SplQueue $lines): bool
    {
        foreach ($lines as $line) {
            if (preg_match(self::ANSI_CODE_REGEX, $line)) {
                return true;
            }
        }
        return false;
    }

    public function emulateAnsiCodes(SplQueue $lines): SplQueue
    {
        $lines->rewind();

        while ($lines->valid()) {
            $this->processLine($lines->current());
            $lines->next();
        }

        $ansi = $this->ansi->compressedAnsiBuffer();

        $buffer = $this->buffer;

        foreach ($buffer as $k => &$line) {
            // At this point, the keys represent the column where the ANSI code should
            // be placed in the string and the values are the ANSI strings.
            $ansiForLine = $ansi[$k] ?? [];

            // Sort them in reverse by position so that we can start at the end of the
            // string and work backwards so that all positions remain valid.
            krsort($ansiForLine);

            // Now, work backwards through the line inserting the codes.
            foreach ($ansiForLine as $pos => $code) {
                $line = mb_substr($line, 0, $pos, 'UTF-8') . $code . mb_substr($line, $pos, null, 'UTF-8');
            }
        }

        unset($line);

        return array_to_splqueue($buffer);
    }

    public function processLine(string $line): void
    {
        // Split the line by ANSI codes. Each item in the resulting array
        // will be a set of printable characters or an ANSI code.
        $parts = preg_split(
            static::ANSI_CODE_REGEX, $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $i = 0;

        $hasPrintableCharacters = false;
        $hasAnsiCodes = false;

        while ($i < count($parts)) {
            $part = $parts[$i];

            if (str_starts_with($part, "\e[")) {
                $hasAnsiCodes = true;
                // Split out the ANSI code by its command and optional parameters.
                preg_match(self::ANSI_CODE_PARTS_REGEX, $part, $matches);
                $this->handleAnsiCode($matches['command'], $matches['params']);
            } else {
                $hasPrintableCharacters = true;
                $this->write($part);
            }

            $i++;
        }

        // There may be some ANSI codes that we're keeping track of that have not yet been
        // written into the buffer. Since the ANSI codes are written into the buffer
        // during the `write` method, it's possible to have ANSI codes that aren't
        // followed by printable characters, meaning that they will never get
        // written in. We fix that here by checking the flag.
        if ($this->unflushedAnsi) {
            // Add the ANSI at the exact point of the cursor.
            $this->ansi->fillBuffer(
                row: $this->cursorRow, startCol: $this->cursorCol, endCol: $this->cursorCol,
            );
        }

        // Some lines are just blank which means they don't have any printable
        // character, but we still want to add a newline. What we don't want
        // to add a newline for is lines that contain purely ANSI codes.
        if ($hasPrintableCharacters || !$hasAnsiCodes) {
            $this->moveCursorRow(relative: 1);
            $this->moveCursorCol(absolute: 0);
        }
    }

    protected function handleAnsiCode($command, $param)
    {
        // Some commands have a default of zero and some have a default of one. Just
        // make both options and decide within the body of the if statement.
        // We could do a match here but it doesn't seem worth it.
        $paramDefaultZero = ($param !== '' && is_numeric($param)) ? intval($param) : 0;
        $paramDefaultOne = ($param !== '' && is_numeric($param)) ? intval($param) : 1;

        if ('A' === $command) {
            // Cursor up
            $this->moveCursorRow(relative: -$paramDefaultOne);

        } elseif ('B' === $command) {
            // Cursor down
            $this->moveCursorRow(relative: $paramDefaultOne);

        } elseif ('C' === $command) {
            // Cursor forward
            $this->moveCursorCol(relative: $paramDefaultOne);

        } elseif ('D' === $command) {
            // Cursor backward
            $this->moveCursorCol(relative: -$paramDefaultOne);

        } elseif ('E' === $command) {
            // Cursor to beginning of next line, a number of lines down
            $this->moveCursorRow(relative: $paramDefaultOne);
            $this->moveCursorCol(absolute: 0);

        } elseif ('F' === $command) {
            // Cursor to beginning of next line, a number of lines up
            $this->moveCursorRow(relative: -$paramDefaultOne);
            $this->moveCursorCol(absolute: 0);

        } elseif ('G' === $command) {
            // Cursor to column #, accounting for one-based indexing.
            $this->moveCursorCol($paramDefaultOne - 1);

        } elseif ('H' === $command) {
            // Cursor home
            $this->moveCursorRow(absolute: 0);
            $this->moveCursorCol(absolute: 0);

        } elseif ('J' === $command) {
            // Erase display
            $this->handleEraseDisplay($paramDefaultZero);

        } elseif ('K' === $command) {
            // Erase in line
            $this->handleEraseInLine($paramDefaultZero);

        } elseif ('l' === $command || 'h' === $command) {
            // Show/hide cursor. We simply ignore them.

        } elseif ('m' === $command) {
            // Colors / graphics mode
            $this->handleSGR($param);

        } else {
            // @TODO? Throw an error?
        }
    }

    public function write(string $text): void
    {
        // Ensure the buffer has enough lines up to cursorRow
        while (count($this->buffer) <= $this->cursorRow) {
            $this->buffer[] = '';
        }

        // Get the current line content
        $lineContent = $this->buffer[$this->cursorRow];

        // If cursorCol is beyond current line length, pad with spaces
        if (mb_strlen($lineContent, 'UTF-8') < $this->cursorCol) {
            $lineContent = str_pad($lineContent, $this->cursorCol, ' ');
        }

        // Insert the text at the cursor position
        $beforeText = mb_substr($lineContent, 0, $this->cursorCol, 'UTF-8');
        $afterTextStart = $this->cursorCol + mb_strlen($text, 'UTF-8');
        $afterText = mb_substr($lineContent, $afterTextStart, null, 'UTF-8');

        $lineContent = $beforeText . $text . $afterText;

        // Update the buffer
        $this->buffer[$this->cursorRow] = $lineContent;

        $startCol = $this->cursorCol;

        // Update the cursor position forward by the length of the text
        $this->moveCursorCol(relative: mb_strlen($text, 'UTF-8'));

        // Fill the ANSI buffer with currently active flags, based
        // on where the cursor started and where it ended.
        $this->ansi->fillBuffer($this->cursorRow, $startCol, $this->cursorCol - 1);

        // We no longer have ANSI codes that haven't been written into
        // the ANSI buffer, so disable the flag.
        $this->unflushedAnsi = false;
    }

    public function clearBuffer(
        int $startRow = 0,
        int $startCol = 0,
        int $endRow = PHP_INT_MAX,
        int $endCol = PHP_INT_MAX
    ): void {
        $this->ansi->clearBuffer(
            startRow: $startRow, startCol: $startCol,
            endRow: $endRow, endCol: $endCol,
        );

        // Short-circuit if we're clearing the whole buffer.
        if ($startRow === 0 && $startCol === 0 && $endRow === PHP_INT_MAX && $endCol === PHP_INT_MAX) {
            $this->buffer = [];
            return;
        }

        $endRow = min($endRow, count($this->buffer) - 1);

        for ($row = $startRow; $row <= $endRow; $row++) {
            if (!array_key_exists($row, $this->buffer)) {
                continue;
            }

            if ($startRow === $endRow) {
                $cols = [$startCol, $endCol];
            } elseif ($row === $startRow) {
                $cols = [$startCol, PHP_INT_MAX];
            } elseif ($row === $endRow) {
                $cols = [0, $endCol];
            } else {
                $cols = [0, PHP_INT_MAX];
            }

            $line = $this->buffer[$row];
            $length = mb_strlen($line);

            $cols = [
                max($cols[0], 0),
                min($cols[1], $length),
            ];

            if ($cols[0] === 0 && $cols[1] === $length) {
                // Benchmarked slightly faster to just replace the entire row.
                $this->buffer[$row] = '';
            } elseif ($cols[0] > 0 && $cols[1] === $length) {
                // Chop off the end of the string since we're clearing to the end of the line.
                $this->buffer[$row] = mb_substr($line, 0, $cols[0], 'UTF-8');
            } else {
                $replacement = str_repeat(' ', $cols[1] - $cols[0] + 1);
                $beforeStart = mb_substr($line, 0, $cols[0], 'UTF-8');
                $afterEnd = mb_substr($line, $cols[1] + 1, null, 'UTF-8');

                $this->buffer[$row] = $beforeStart . $replacement . $afterEnd;
            }
        }
    }

    public function moveCursorCol($absolute = null, $relative = null)
    {
        $this->moveCursor('x', $absolute, $relative);
    }

    public function moveCursorRow($absolute = null, $relative = null)
    {
        $this->moveCursor('y', $absolute, $relative);

        while (count($this->buffer) < $this->cursorRow) {
            $this->buffer[] = '';
        }
    }

    protected function moveCursor(string $direction, $absolute = null, $relative = null): void
    {
        $this->ensureCursorParams($absolute, $relative);

        $property = $direction === 'x' ? 'cursorCol' : 'cursorRow';

        if (!is_null($absolute)) {
            $this->{$property} = $absolute;
        }

        if (!is_null($relative)) {
            $this->{$property} += $relative;
        }

        $this->{$property} = max($this->{$property}, 0);
    }

    protected function ensureCursorParams($absolute, $relative): void
    {
        if (!is_null($absolute) && !is_null($relative)) {
            throw new Exception("Use either relative or absolute, but not both.");
        }

        if (is_null($absolute) && is_null($relative)) {
            throw new Exception("Relative and absolute cannot both be blank.");
        }
    }

    /**
     * Handle SGR (Select Graphic Rendition) ANSI codes for colors and styles.
     */
    protected function handleSGR($params): void
    {
        $this->unflushedAnsi = true;

        // Support multiple codes, like \e[30;41m
        $codes = array_map(intval(...), explode(';', $params));

        $this->ansi->addAnsiCodes(...$codes);
    }

    protected function handleEraseDisplay(int $param): void
    {
        if ($param === 0) {
            // \e[0J - Erase from cursor until end of screen
            $this->clearBuffer(
                startRow: $this->cursorRow,
                startCol: $this->cursorCol
            );
        } elseif ($param === 1) {
            // \e[1J - Erase from cursor until beginning of screen
            $this->clearBuffer(
                endRow: $this->cursorRow,
                endCol: $this->cursorCol
            );
        } elseif ($param === 2) {
            // \e[2J - Erase entire screen
            $this->clearBuffer();
        }
    }

    protected function handleEraseInLine(int $param): void
    {
        if ($param === 0) {
            // \e[0K - Erase from cursor to end of line
            $this->clearBuffer(
                startRow: $this->cursorRow,
                startCol: $this->cursorCol,
                endRow: $this->cursorRow
            );

        } elseif ($param == 1) {
            // \e[1K - Erase start of line to the cursor
            $this->clearBuffer(
                startRow: $this->cursorRow,
                endRow: $this->cursorRow,
                endCol: $this->cursorCol
            );
        } elseif ($param === 2) {
            //\e[2K - Erase the entire line
            $this->clearBuffer(
                startRow: $this->cursorRow,
                endRow: $this->cursorRow
            );
        }
    }
}