<?php

/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Commands;

use AaronFrancis\Solo\Facades\Solo;
use AaronFrancis\Solo\Hotkeys\Hotkey;
use AaronFrancis\Solo\Support\Screen;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Themes\Default\Concerns\InteractsWithStrings;

class EnhancedTailCommand extends Command
{
    use Colors, InteractsWithStrings;

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

    protected function makeNewScreen()
    {
        // Disable wrapping by setting the width to 1000 characters. We'll wrap it ourselves.
        return new Screen(1000, $this->scrollPaneHeight());
    }

    protected function modifyWrappedLines(Collection $lines): Collection
    {
        if (!$this->hideVendor) {
            return $lines;
        }

        $hasVendorFrame = false;

        // After all the lines have been wrapped, we look through them
        // to collapse consecutive vendor frames into a single line.
        return $lines
            ->map($this->formatLogLine(...))
            ->flatten()
            ->reject(fn($line) => is_null($line))
            ->filter(function ($line) use (&$hasVendorFrame) {
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

    protected function formatInitialException($line): array
    {
        $lines = explode('{"exception":"[object] ', $line);
        $message = array_map(
            fn($line) => Solo::makeTheme()->exception($line),
            $this->wrapLine($lines[0])
        );

        $exception = array_map(
            fn($line) => '   ' . Solo::makeTheme()->exception($line),
            $this->wrapLine($lines[1], -3)
        );

        return [
            ...$message,
            ...$exception
        ];
    }

    protected function formatLogLine($line): null|array|string
    {
        $theme = Solo::makeTheme();

        // 1 space outside of each border.
        $traceBoxWidth = $this->scrollPaneWidth() - 2;

        // 1 border + 1 space on each side
        $traceContentWidth = $traceBoxWidth - 4;

        // A single trailing line that closes the JSON exception object.
        if (trim($line) === '"}') {
            return $theme->dim(' ╰' . str_repeat('═', $traceBoxWidth - 2) . '╯');
        }

        if (str_contains($line, '{"exception":"[object] ')) {
            return $this->formatInitialException($line);
        }

        if (str_contains($line, '[stacktrace]')) {
            return $theme->dim(' ╭─Trace' . str_repeat('─', $traceContentWidth - 4) . '╮');
        }

        if (!Str::isMatch('/#[0-9]+ /', $line)) {
            return $this->wrapLine($line);
        }

        $base = function_exists('Orchestra\Testbench\package_path') ? \Orchestra\Testbench\package_path() : base_path();

        // Make the line shorter by removing the base path. Helps prevent wrapping.
        $line = str_replace($base, '', $line);

        // Replace all vendor frame with a simple placeholder.
        if ($this->hideVendor && $this->isVendorFrame($line)) {
            return $this->dim(' │ ' . $this->pad('#…', $traceContentWidth) . ' │ ');
        }

        // Extract the file so that we can keep that part not dim.
        $file = Str::match('/^#\d+ (.*?): /', $line);

        $line = explode($file, $line);
        $line = $theme->dim($line[0]) . $this->reset($file) . $this->dim($line[1]);

        return array_map(
            fn($line) => $this->dim(' │ ') . $this->pad($line, $traceContentWidth) . $this->dim(' │ '),
            $this->wrapLine($line, $traceContentWidth)
        );
    }

    protected function findNonVendorFrame(int $start)
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

        // No non-vendor frames were found.
        return false;
    }

    protected function isVendorFrame($line)
    {
        return
            (
                str_contains($line, '/vendor/') && !Str::isMatch("/BoundMethod\.php\([0-9]+\): App/", $line)
            )
            ||
            str_ends_with($line, '{main}')
            ||
            str_contains($line, '#…');
    }
}
