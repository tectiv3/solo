<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Commands;

use AaronFrancis\Solo\Facades\Solo;
use AaronFrancis\Solo\Hotkeys\Hotkey;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EnhancedTailCommand extends Command
{
    protected $hideVendor = true;

    protected string $file;

    public static function forFile($path)
    {
        return static::make('Logs', "tail -f -n 100 $path")->setFile($path);
    }

    public function setFile($path)
    {
        $this->file = $path;

        return $this;
    }

    /**
     * @return array<string, Hotkey>
     */
    public function hotkeys(): array
    {
        return [
            'vendor' => Hotkey::make('v', function () {
                $index = $this->findNonVendorFrame($this->scrollIndex + floor($this->scrollPaneHeight() / 2));

                dd($this->wrappedLines());
                //                if ($index !== false) {
                //                    $this->lines[$index] = '___scrollpos___';
                //                }
            }),
            'truncate' => true ? null : Hotkey::make('t', function () {
                if (!$this->file) {
                    return;
                }

                // Opening in write mode truncates (or creates.)
                $handle = fopen($this->file, 'w');

                if ($handle !== false) {
                    fclose($handle);
                }

                // Clear the logs held in memory.
                $this->clear();
            })
        ];
    }

    public function addOutput($text)
    {
        $text = explode(PHP_EOL, $text);

        $text = collect($text)
            ->map($this->formatLogLine(...))
            ->reject(fn($line) => is_null($line))
            ->implode(PHP_EOL);

        parent::addOutput($text);
    }

    public function findNonVendorFrame(int $start)
    {
        $linesCount = count($this->lines);
        $step = 0;

        while ($start + $step < $linesCount || $start - $step >= 0) {
            // Check forward index
            if ($start + $step < $linesCount) {
                $index = $start + $step;
                if (!$this->isVendorFrame($this->lines[$index])) {
                    return $index;
                }
            }

            // Check backward index, avoiding duplicate check at step 0
            if ($step !== 0 && $start - $step >= 0) {
                $index = $start - $step;
                if (!$this->isVendorFrame($this->lines[$index])) {
                    return $index;
                }
            }

            $step++;
        }

        // If no non-vendor frames are found, return false
        return false;
    }

    protected function formatLogLine($line): ?string
    {
        $theme = Solo::makeTheme();

        // A single trailing line that closes the JSON exception object.
        if (trim($line) === '"}') {
            return null;
        }

        if (str_contains($line, '{"exception":"[object] ')) {
            return $this->formatInitialException($line);
        }

        if (str_contains($line, '[stacktrace]')) {
            return '   ' . $theme->dim($line);
        }

        if (!Str::isMatch('/#[0-9]+ /', $line)) {
            return $line;
        }

        $base = function_exists('Orchestra\Testbench\package_path') ? \Orchestra\Testbench\package_path() : base_path();

        // Make the line shorter by removing the base path. Helps prevent wrapping.
        $line = str_replace($base, '…', $line);

        // Replace all vendor frame with a simple placeholder.
        if ($this->hideVendor && $this->isVendorFrame($line)) {
            return $theme->dim('   [Vendor frames]');
        }

        return (Str::isMatch('/#[0-9]+ /', $line) ? str_repeat(' ', 3) : str_repeat(' ', 7)) . $line;
    }

    public function isVendorFrame($line)
    {
        return str_contains($line, '/vendor/') && !Str::isMatch("/BoundMethod\.php\([0-9]+\): App/", $line)
            || str_contains($line, '[Vendor frames]');
    }

    public function formatInitialException($line): string
    {
        $lines = explode('{"exception":"[object] ', $line);

        $message = Solo::makeTheme()->red($lines[0]);

        return collect($this->wrapLine($lines[1], -3))
            ->map(fn($line) => '   ' . Solo::makeTheme()->exception($line))
            ->prepend($message)
            ->implode(PHP_EOL);

    }

    protected function modifyWrappedLines(Collection $lines): Collection
    {
        if (!$this->hideVendor) {
            return $lines;
        }

        $hasVendorFrame = false;

        // After all the lines have been wrapped, we look through them
        // to collapse consecutive vendor frames into a single line.
        return $lines->filter(function ($line) use (&$hasVendorFrame) {
            $isVendorFrame = $this->isVendorFrame($line);

            if ($isVendorFrame) {
                // Skip the line if a vendor frame has already been added.
                if ($hasVendorFrame) {
                    return false;
                }
                // Otherwise, mark that a vendor frame has been added.
                $hasVendorFrame = true;
            } else {
                // Reset the flag if the current line is not a vendor frame.
                $hasVendorFrame = false;
            }

            return true;
        });
    }
}
