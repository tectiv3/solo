<?php

/**
 * @author Aaron Francis <aaron@tryhardstudios.com>
 *
 * @link https://aaronfrancis.com
 * @link https://x.com/aarondfrancis
 */

namespace SoloTerm\Solo\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use SoloTerm\Solo\Facades\Solo as SoloAlias;
use SoloTerm\Solo\Hotkeys\VimHotkeys;

class HotkeyTest extends Base
{
    #[Test]
    public function vim_hotkeys()
    {
        $actions = [
            function (string $ansi, string $plain) {
                $this->assertStringContainsString('h Previous', $plain);
                $this->assertStringContainsString('l Next', $plain);
                $this->assertStringContainsString('Solo for Laravel is a package to run multiple', $plain);
            },
            'l',
            function (string $ansi, string $plain) {
                $this->assertStringNotContainsString('Solo for Laravel is a package to run multiple', $plain);
            },
        ];

        $this->runSolo($actions, function () {
            config()->set('solo.hotkeys', VimHotkeys::class);

            SoloAlias::addCommands([
                'About' => 'php artisan solo:about',
                'Logs' => 'tail -f -n 100 ' . storage_path('logs/laravel.log')
            ]);
        });
    }
}
