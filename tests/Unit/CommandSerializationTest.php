<?php

namespace Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase;
use SoloTerm\Solo\Commands\Command;
use Illuminate\Support\Facades\Artisan;

class CommandSerializationTest extends TestCase
{

    public function test_command_config_is_properly_cached()
    {
        $this->artisan('config:clear');
        $fileSystem = app(Filesystem::class);

        $config = $fileSystem->getRequire(__DIR__ . '/../../workbench/config/solo.php');
        $this->assertUnserializedConfig($config);

        $cachedConfigPath = $this->app->getCachedConfigPath();
        $this->assertFileDoesNotExist($cachedConfigPath);

        $result = Artisan::call('config:cache');
        $this->assertEquals(0, $result);
        $this->assertFileExists($cachedConfigPath);
        $this->assertFileIsReadable($cachedConfigPath);
        $cachedConfig = $fileSystem->getRequire($cachedConfigPath);
        $this->assertEquals($config, $cachedConfig['solo']);

        $this->assertSerializedConfig($fileSystem->get($cachedConfigPath));
        
        $this->artisan('config:clear');
        $this->assertFileDoesNotExist($cachedConfigPath, 'Config cache file still exists');
    }

    /**
     * Assert that the serialized string has the expected format
     */
    private function assertSerializedConfig(string $serialized): void
    {
        $this->assertNotEmpty($serialized);
        $this->assertStringContainsString('solo', $serialized);
        $this->assertStringContainsString('commands', $serialized);

        $this->assertStringContainsString(Command::class, $serialized);
    }

    /**
     * Assert that the config line contains command instances to be serialised
     */
    protected function assertUnserializedConfig($config): void
    {
        $this->assertIsArray($config);
        $this->assertArrayHasKey('commands', $config);
        $this->assertNotEmpty($config['commands']);

        foreach ($config['commands'] as $configCommand) {
            $this->assertUnserializedCommands($configCommand);
        }
    }

    /**
     * Recursively check that there is command instances
     */
    private function assertUnserializedCommands($command): void
    {
        if ($command instanceof Command) {
            $this->assertInstanceOf(Command::class, $command);
        } elseif (is_array($command)) {
            foreach ($command as $item) {
                if ($item instanceof Command) {
                    $this->assertInstanceOf(Command::class, $item);
                }
            }
        }
    }
}