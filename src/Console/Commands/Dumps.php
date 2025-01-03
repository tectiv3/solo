<?php

namespace AaronFrancis\Solo\Console\Commands;

use AaronFrancis\Solo\Support\CustomDumper;
use AaronFrancis\Solo\Support\ProcessTracker;
use Exception;
use Illuminate\Console\Command;

class Dumps extends Command
{
    protected $signature = 'solo:dumps';

    protected $description = 'Collect dumps from your Laravel application.';

    protected bool $shouldContinue = true;

    protected string $pidFilePath;

    protected string $pipePath;

    public function handle(): void
    {
        $this->pidFilePath = storage_path('solo_dumps.pid');
        $this->pipePath = CustomDumper::namedDumpPipe();

        $this->touchPidFile();
        $pipe = $this->openNamedPipe();

        // Have to register the signal listeners this way so that
        // they work when using the workbench command runner.
        pcntl_signal(SIGINT, fn() => $this->shouldContinue = false);
        pcntl_signal(SIGTERM, fn() => $this->shouldContinue = false);
        pcntl_signal(SIGQUIT, fn() => $this->shouldContinue = false);
        pcntl_signal(SIGHUP, fn() => $this->shouldContinue = false);

        while ($this->shouldContinue) {
            $read = [$pipe];
            $write = [];
            $except = [];

            // Block for 100 milliseconds waiting for data. If data is available,
            // stream_select() returns the number of streams ready to read.
            $changedStreams = @stream_select($read, $write, $except, 0, 100_000);

            // Error
            if ($changedStreams === false) {
                break;
            }

            // New input
            if ($changedStreams > 0) {
                $line = fgets($pipe);

                if ($line !== false) {
                    echo $line;
                }
            }

            if (!$this->isLeaderProcess()) {
                $this->error('Another process has taken over. Exiting.');
                break;
            }
        }

        @fclose($pipe);

        if ($this->isLeaderProcess() || $this->leaderIsDead()) {
            $this->info('Cleaning up...');
            @unlink($this->pipePath);
            @unlink($this->pidFilePath);
        }
    }

    protected function isLeaderProcess(): bool
    {
        return file_exists($this->pidFilePath) && (int) file_get_contents($this->pidFilePath) === getmypid();
    }

    protected function leaderIsDead(): bool
    {
        $pid = file_exists($this->pidFilePath) ? (int) file_get_contents($this->pidFilePath) : null;

        return is_null($pid) || !ProcessTracker::isRunning($pid);
    }

    /**
     * @return resource
     *
     * @throws Exception
     */
    protected function openNamedPipe()
    {
        if (!file_exists($this->pipePath)) {
            if (!posix_mkfifo($this->pipePath, 0600)) {
                throw new Exception("Failed to create FIFO at: {$this->pipePath}");
            }
        }

        $pipe = fopen($this->pipePath, 'r+');

        if (!$pipe) {
            throw new Exception("Failed to open FIFO at: {$this->pipePath}");
        }

        stream_set_blocking($pipe, false);

        return $pipe;
    }

    protected function touchPidFile(): void
    {
        file_put_contents($this->pidFilePath, getmypid());
    }
}
