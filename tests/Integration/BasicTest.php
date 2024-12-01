<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Tests\Integration;

use AaronFrancis\Solo\Facades\Solo as SoloAlias;
use PHPUnit\Framework\Attributes\Test;

use function Orchestra\Testbench\package_path;

class BasicTest extends Base
{
    #[Test]
    public function basic_test()
    {
        $actions = [
            $this->withSnapshot(function (string $ansi, string $plain) {
                $this->assertStringContainsString('About', $plain);
            }),
            's',
            $this->withSnapshot(function (string $ansi, string $plain) {
                $this->assertStringContainsString("\e[9mAbout\e[29m", $ansi);
            }),
        ];

        $this->runSolo($actions, function () {
            SoloAlias::useTheme('light');

            SoloAlias::addCommands([
                'About' => implode(' ', [
                    'php', package_path('vendor', 'bin', 'testbench'), 'solo:about'
                ]),
            ]);
        });
    }
}
