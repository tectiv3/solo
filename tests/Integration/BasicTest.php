<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Tests\Integration;

use AaronFrancis\Solo\Facades\Solo as SoloAlias;
use AaronFrancis\Solo\Providers\SoloServiceProvider;
use AaronFrancis\Solo\Support\PendingProcess;
use AaronFrancis\Solo\Support\SafeBytes;
use AaronFrancis\Solo\Tests\Support\SoloTestServiceProvider;
use Generator;
use Laravel\Prompts\Key;
use Laravel\Prompts\Terminal;
use Laravel\SerializableClosure\SerializableClosure;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
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
            SoloAlias::clearCommands();
            SoloAlias::useTheme('light');

            SoloAlias::addCommands([
                'About' => implode(' ', [
                    'php', package_path('vendor', 'bin', 'testbench'), 'solo:about'
                ]),
            ]);
        });
    }
}