<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com>
 * @link https://aaronfrancis.com
 * @link https://twitter.com/aarondfrancis
 */

namespace AaronFrancis\Solo\Commands\Concerns;

use AaronFrancis\Solo\Support\PendingProcess;
use AaronFrancis\Solo\Support\ProcessTracker;
use AaronFrancis\Solo\Support\SafeBytes;
use Closure;
use Illuminate\Process\InvokedProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use PHPUnit\Event\Runtime\PHP;
use Symfony\Component\Process\InputStream;

trait ManagesProcess
{
    public ?InvokedProcess $process = null;

    protected array $afterTerminateCallbacks = [];

    protected bool $stopping = false;

    protected ?Carbon $stopInitiatedAt;

    protected ?Closure $processModifier = null;

    public InputStream $input;

    protected string $multibyteBuffer = '';

    protected $children = [];

    public function createPendingProcess(): PendingProcess
    {
        $this->input ??= new InputStream;

        $command = explode(' ', $this->command);

        // We have to make our own so that we can control pty if needed.
        $process = app(PendingProcess::class)
            ->command($command)
            ->forever()
            ->timeout(0)
            ->idleTimeout(0);

        if ($this->interactive) {
            $process->pty();
            $process->input($this->input);
        }

        if ($this->processModifier) {
            call_user_func($this->processModifier, $process);
        }

        // Add some default env variables to hopefully
        // make output more manageable.
        return $process->env([
            'FORCE_COLOR' => '1',
            'COLUMNS' => $this->scrollPaneWidth(),
            'LINES' => $this->scrollPaneHeight(),
            ...$process->environment
        ]);
    }

    public function sendInput(mixed $input)
    {
        if (!$this->input->isClosed()) {
            $this->input->write($input);
        }
    }

    public function withProcess(Closure $cb)
    {
        $this->processModifier = $cb;

        return $this;
    }

    public function autostart(): static
    {
        if ($this->autostart && $this->processStopped()) {
            $this->start();
        }

        return $this;
    }

    public function start(): void
    {
        $this->process = $this->createPendingProcess()->start(
            null, fn($type, $buffer) => $this->addOutput($buffer)
        );
    }

    public function stop(): void
    {
        $this->addLine('Stopping process...');

        $this->stopping = true;

        if ($this->processRunning()) {
            $this->children = ProcessTracker::children($this->process->id());

            // Keep track of when we tried to stop.
            $this->stopInitiatedAt ??= Carbon::now();

            // Ask for a graceful shutdown. If it isn't
            // respected, we'll force kill it later.
            $this->process->signal(SIGTERM);
        }
    }

    public function restart(): void
    {
        $this->afterTerminate(function () {
            $this->start();
        });

        $this->stop();
    }

    public function toggle(): void
    {
        $this->processRunning() ? $this->stop() : $this->start();
    }

    public function afterTerminate($cb): static
    {
        $this->afterTerminateCallbacks[] = $cb;

        return $this;
    }

    public function processRunning(): bool
    {
        return $this->process?->running() ?? false;
    }

    public function processStopped(): bool
    {
        return !$this->processRunning();
    }

    protected function marshalRogueProcess(): void
    {
        // If we're trying to stop and the process isn't running, then we
        // succeeded. We'll reset some state and call the callbacks.
        if ($this->stopping && $this->processStopped()) {
            $this->stopping = false;
            $this->stopInitiatedAt = null;

            ProcessTracker::kill($this->children);

            $this->addLine('Stopped.');

            return;
        }

        // If we're not stopping or it's not running,
        // then it doesn't qualify as rogue.
        if (!$this->stopping || $this->processStopped()) {
            return;
        }

        // We'll give it five seconds to terminate.
        if ($this->stopInitiatedAt->copy()->addSeconds(5)->isFuture()) {
            if (Carbon::now()->microsecond < 25_000) {
                $this->addLine('Waiting...');
            }

            return;
        }

        if ($this->processRunning()) {
            $this->addLine('Force killing!');

            // @TODO clean up orphans? Looking at you, pail
            $this->process->signal(SIGKILL);
        }
    }

    protected function callAfterTerminateCallbacks()
    {
        foreach ($this->afterTerminateCallbacks as $cb) {
            if ($cb instanceof Closure) {
                $cb = $cb->bindTo($this, static::class);
            }

            $cb();
        }

        $this->afterTerminateCallbacks = [];
    }

    protected function collectIncrementalOutput()
    {
        // A bit of a hack, but there's no other way in. Process is a Laravel InvokedProcess.
        // Calling `running` on it defers to the Symfony process `isRunning` method. That
        // method calls a protected method `updateStatus` which calls a private method
        // `readPipes` which invokes the output callback, adding it to our buffer.
        $this->process->running();
    }
}
