<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 *
 * @link https://aaronfrancis.com
 * @link https://x.com/aarondfrancis
 */

namespace SoloTerm\Solo\Tests\Integration;

use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\Test;
use SoloTerm\Solo\Commands\EnhancedTailCommand;
use Str;

use function Orchestra\Testbench\package_path;

class EnhancedTailCommandTest extends Base
{
    #[Test]
    public function wrapping_and_unwrapping()
    {
        $traceIsFirstLine = function (string $ansi, string $plain) {
            $lines = explode("\n", $plain);
            $trace = $lines[3];
            $this->assertStringContainsString('─Trace─', $trace);
        };

        $actions = [
            // Disallow wrapping so we can scroll up faster
            'w',
            // Assert it's off. (We might change the default in the future.)
            function (string $ansi, string $plain) {
                $this->assertStringContainsString('Allow wrapping ', $plain);
            },
            // Look for the right line.
            function (string $ansi, string $plain) {
                while (!Str::contains($plain, '#00 /src/Commands/EnhancedTailCommand.php(121):')) {
                    yield Key::UP_ARROW;
                }
            },
            // Then arrow up one more time, to get to the Trace line.
            Key::UP_ARROW,
            $traceIsFirstLine,
            // Allow wrapping
            'w',
            $traceIsFirstLine,
            'w',
            $traceIsFirstLine,
        ];

        $this->runSolo($actions, function () {
            config()->set('solo.commands', [
                'Logs' => EnhancedTailCommand::file(package_path('tests/Fixtures/enhance-log-wrap-vendor-test.log'))
            ]);
        });
    }

    #[Test]
    public function collapse_and_expand_vendor()
    {
        $actions = [
            // Time for the tail to catch up
            1000,
            Key::SHIFT_UP,
            Key::SHIFT_UP,
            Key::UP_ARROW,
            function (string $ansi, string $plain) {
                $this->assertStringNotContainsString('#08 /vendor/joetannenbaum', $plain);
                $this->assertStringContainsString('#09 /src/Prompt/Dashboard.php(211): SoloTer', $plain);
                $this->assertStringContainsString('#10 /vendor/joetannenbaum/chewie/src/Concer', $plain);

                $this->assertStringContainsString('Hide Vendor', $plain);
            },
            'v',
            function (string $ansi, string $plain) {
                $this->assertStringContainsString('#09 /src/Prompt/Dashboard.php(211): SoloTer', $plain);
                $this->assertStringNotContainsString('#10 /vendor/joetannenbaum/chewie/src/Concer', $plain);

                $this->assertStringContainsString('Show Vendor', $plain);
            },
            'v',
            function (string $ansi, string $plain) {
                $this->assertStringNotContainsString('#08 /vendor/joetannenbaum', $plain);
                $this->assertStringContainsString('#09 /src/Prompt/Dashboard.php(211): SoloTer', $plain);
                $this->assertStringContainsString('#10 /vendor/joetannenbaum/chewie/src/Concer', $plain);

                $this->assertStringContainsString('Hide Vendor', $plain);
            },
        ];

        $this->runSolo($actions, function () {
            config()->set('solo.commands', [
                'Logs' => EnhancedTailCommand::file(package_path('tests/Fixtures/enhance-log-wrap-vendor-test.log'))
            ]);
        });
    }
}
