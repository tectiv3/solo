<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Support;

use Illuminate\Process\PendingProcess as BasePendingProcess;
use Symfony\Component\Process\Process;

class PendingProcess extends BasePendingProcess
{
    public bool $pty = false;

    public function pty(bool $pty = true): static
    {
        $this->pty = $pty;

        return $this;
    }

    protected function toSymfonyProcess(array|string|null $command): \Symfony\Component\Process\Process
    {
        $process = parent::toSymfonyProcess($command);

        $process->setPty($this->pty);

        return $process;
    }
}