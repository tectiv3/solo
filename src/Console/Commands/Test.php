<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Console\Commands;

use AaronFrancis\Solo\Facades\Solo as SoloAlias;
use AaronFrancis\Solo\Support\PendingProcess;
use App\Providers\AppServiceProvider;
use Generator;
use Illuminate\Console\Command;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\Terminal;
use Laravel\SerializableClosure\SerializableClosure;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use function Orchestra\Testbench\package_path;

class Test extends Solo
{
    protected $signature = 'solo:test {class} {provider}';

    protected $description = 'Run solo with an ad-hoc service provider';

    public function handle(): void
    {
        AppServiceProvider::allowCommandsFromTest($this->argument('class'));

        $closure = $this->argument('provider');
        $closure = unserialize($closure)->getClosure();

        call_user_func($closure);

        parent::handle();
    }

}
