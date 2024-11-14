<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Support;

class Buffer
{
    public $buffer = [];

    public function __construct(public bool $usesStrings)
    {
        //
    }

    public function clearBuffer(
        int $startRow = 0,
        int $startCol = 0,
        int $endRow = PHP_INT_MAX,
        int $endCol = PHP_INT_MAX
    ): void {
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

            $cols = $this->normalizeClearColumns($row, $startRow, $startCol, $endRow, $endCol);

            $line = $this->buffer[$row];

            $length = $this->usesStrings ? mb_strlen($line) : count($this->buffer[$row]) - 1;

            $cols = [
                max($cols[0], 0),
                min($cols[1], $length),
            ];

            if ($cols[0] === 0 && $cols[1] === $length) {
                // Benchmarked slightly faster to just replace the entire row.
                $this->buffer[$row] = $this->usesStrings ? '' : [];
            } elseif ($cols[0] > 0 && $cols[1] === $length) {
                $this->buffer[$row] = $this->usesStrings
                    // Chop off the end of the string since we're clearing to the end of the line.
                    ? mb_substr($line, 0, $cols[0], 'UTF-8')
                    // Chop off the end of the array since we're clearing to the end of the line.
                    : array_slice($this->buffer[$row], 0, $cols[0]);
            } else {
                if ($this->usesStrings) {
                    $replacement = str_repeat(' ', $cols[1] - $cols[0] + 1);
                    $beforeStart = mb_substr($line, 0, $cols[0], 'UTF-8');
                    $afterEnd = mb_substr($line, $cols[1] + 1, null, 'UTF-8');

                    $this->buffer[$row] = $beforeStart . $replacement . $afterEnd;
                } else {
                    // Replace the specified columns with zero
                    $this->buffer[$row] = array_replace(
                        $this->buffer[$row], array_fill_keys(range(...$cols), 0)
                    );
                }
            }
        }
    }

    protected function normalizeClearColumns(int $currentRow, int $startRow, int $startCol, int $endRow, int $endCol)
    {
        if ($startRow === $endRow) {
            return [$startCol, $endCol];
        } elseif ($currentRow === $startRow) {
            return [$startCol, PHP_INT_MAX];
        } elseif ($currentRow === $endRow) {
            return [0, $endCol];
        } else {
            return [0, PHP_INT_MAX];
        }
    }
}