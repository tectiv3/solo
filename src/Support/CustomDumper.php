<?php

namespace AaronFrancis\Solo\Support;

use AaronFrancis\Solo\Facades\Solo as SoloFacade;
use Illuminate\Foundation\Console\CliDumper;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\VarDumper;
use Throwable;

class CustomDumper
{
    protected VarCloner $cloner;

    /**
     * The dumper that wraps our output (to the named pipe).
     */
    protected ?CliDumper $dumper = null;

    /**
     * We’ll store the file handle separately so we can close it
     * if something goes wrong (and we set $this->dumper to null).
     */
    protected $pipeHandle = null;

    public static function register($basePath, $compiledViewPath)
    {
        return new static($basePath, $compiledViewPath);
    }

    public static function namedDumpPipe(): string
    {
        return '/tmp/solo_dumps_' . SoloFacade::uniqueId();
    }

    public function __construct(public readonly string $basePath, public readonly string $compiledViewPath)
    {
        $this->cloner = new VarCloner;
        $this->cloner->addCasters(ReflectionCaster::UNSET_CLOSURE_FILE_INFO);

        // Stash the original handler so we can use it if the named pipe doesn't exist.
        $original = VarDumper::setHandler(null);

        VarDumper::setHandler(function ($value) use ($original) {
            // If we can dump to the custom command, we’re done. But the command
            // may not be running, or we may not be able to open the pipe.
            if ($this->dumpToCommand($value)) {
                return;
            }

            // If the custom dumper failed for any reason, we need to blow it away.
            $this->closePipeAndDumper();

            // And then defer to the original.
            if ($original !== null) {
                $original($value);
            } else {
                // In a rare case that $original was null, fallback.
                print_r($value);
            }
        });
    }

    protected function dumpToCommand($value): bool
    {
        // This means that the solo:dumps command is not running (or the FIFO is gone).
        if (!file_exists(static::namedDumpPipe())) {
            return false;
        }

        // If we don't yet have a dumper, try to create one.
        if (!$this->dumper) {
            $this->closePipeAndDumper();
            $this->dumper = $this->makeDumper();

            // If we still have no dumper, fail out.
            if (!$this->dumper) {
                return false;
            }
        }

        try {
            $this->dumper->dumpWithSource($this->cloner->cloneVar($value));

            return true;
        } catch (Throwable $e) {
            // If an exception occurred (e.g., writing failed),
            // close everything and fail out.
            $this->closePipeAndDumper();

            return false;
        }
    }

    protected function makeDumper(): ?CliDumper
    {
        $namedPipe = static::namedDumpPipe();

        $handle = @fopen($namedPipe, 'r+');
        if (!$handle) {
            return null;
        }

        // Save the handle at the class level so we can close it later if needed.
        $this->pipeHandle = $handle;

        // Non-blocking is often helpful with named pipes.
        stream_set_blocking($this->pipeHandle, false);

        $output = new StreamOutput(stream: $this->pipeHandle, decorated: true);

        return new CliDumper($output, $this->basePath, $this->compiledViewPath);
    }

    /**
     * Close the existing handle and reset the dumper.
     */
    protected function closePipeAndDumper(): void
    {
        if ($this->pipeHandle !== null) {
            @fclose($this->pipeHandle);
            $this->pipeHandle = null;
        }

        // This also ensures no calls to $this->dumper if the pipe is invalid.
        $this->dumper = null;
    }
}
