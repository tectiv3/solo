<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Tests\Integration;

use AaronFrancis\Solo\Facades\Solo as SoloAlias;
use AaronFrancis\Solo\Helpers\AnsiAware;
use AaronFrancis\Solo\Providers\SoloServiceProvider;
use AaronFrancis\Solo\Support\PendingProcess;
use AaronFrancis\Solo\Tests\Support\SoloTestServiceProvider;
use App\Providers\AppServiceProvider;
use Closure;
use Illuminate\Process\InvokedProcess;
use Laravel\Prompts\Key;
use Laravel\Prompts\Terminal;
use Laravel\SerializableClosure\SerializableClosure;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Process\InputStream;

abstract class Base extends TestCase
{
    protected InputStream $input;

    protected string $frame = '';

    protected string $previousFrame = '';

    protected bool $newBuffer = false;

    protected InvokedProcess $process;

    protected function getEnvironmentSetup($app)
    {
        //
    }

    protected function getPackageProviders($app)
    {
        return [
            SoloServiceProvider::class,
            SoloTestServiceProvider::class,
        ];
    }


    protected function runSolo(array $actions, ?Closure $provider = null)
    {
        // Number of spare lines at the top for status and stuff.
        $reservedLines = 4;
        $this->input = new InputStream;

        // PHPUnit captures output, so we need to put an
        // end to that so we can see Solo on screen.
        $flushed = ob_get_clean();

        $terminal = new Terminal;
        $terminal->initDimensions();

        $width = $terminal->cols();
        $height = $terminal->lines() - $reservedLines;

        $closure = new SerializableClosure($provider ?? function () {
            //
        });

        $this->process = app(PendingProcess::class)
            ->command([
                'php',
                'vendor/bin/testbench',
                'solo:test',
                static::class,
                serialize($closure)
            ])
            ->input($this->input)
            ->pty()
            ->forever()
            ->env([
                // Disable the underlying alt screen of Solo
                'NO_ALT_SCREEN' => '1',
                'FORCE_COLOR' => '1',
                'COLUMNS' => $width,
                // Leave some space for status at the top
                'LINES' => $height,
            ])
            ->start(null, function ($type, $buffer) use ($reservedLines, $height) {
                $this->newBuffer = true;

                // Move to top means that we're starting a new frame.
                $move = "\e[{$height}F";

                if (str_contains($buffer, $move)) {
                    $this->previousFrame = $this->frame;
                    // Move all the way up, but then down four lines.
                    $this->frame = "\e[1000F\e[{$reservedLines}B" . last(explode($move, $buffer));
                } else {
                    $this->frame .= $buffer;
                }
            });

        $millisecondsSinceLastAction = 0;
        $millisecondsBetweenLoops = 10;
        $millisecondsBetweenActions = 1_000;

        // Start an alt screen
//        echo "\e[?1049h";

        while ($this->process->running()) {
            // Move up 1000 rows to column 1, down 4 lines, clear up, move back up
            echo "\e[1000F" . "\e[4B" . "\e[1J" . "\e[4A";
            echo "Running tests...";
            // Four new lines
            echo "\n\n\n\n";

            if ($this->newBuffer) {
                echo $this->frame;
            }

            usleep($millisecondsBetweenLoops * 1000);
            $millisecondsSinceLastAction += $millisecondsBetweenLoops;

            if ($millisecondsSinceLastAction < $millisecondsBetweenActions) {
                continue;
            }

            $millisecondsSinceLastAction = 0;

            if (count($actions)) {
                $action = array_shift($actions);
            } else {
                $action = Key::CTRL_C;
            }

            if (is_string($action)) {
                $this->input->write($action);
            } elseif (is_int($action)) {
                $millisecondsSinceLastAction = -1 * $action;
            } else {
                call_user_func($action);
            }
        }

        $this->process->wait();

//        echo "\e[?1049l";

        ob_start();
        echo $flushed;
    }

    public function withSnapshot(Closure $callback)
    {
        return function () use ($callback) {
            $callback($this->previousFrame, AnsiAware::plain($this->previousFrame));
        };
    }

}