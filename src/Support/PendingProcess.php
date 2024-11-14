<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace AaronFrancis\Solo\Support;

use Illuminate\Process\PendingProcess as BasePendingProcess;

class PendingProcess extends BasePendingProcess
{
    public $pty = false;

    public function pty(bool $pty = true)
    {
        $this->pty = $pty;

        return $this;
    }

    protected function toSymfonyProcess(array|string|null $command)
    {
        $process = parent::toSymfonyProcess($command);
        $process->setPty($this->pty);

        return $process;
    }
}