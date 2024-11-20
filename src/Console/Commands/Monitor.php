<?php

namespace AaronFrancis\Solo\Console\Commands;

use Illuminate\Console\Command;
use RuntimeException;

class Monitor extends Command
{
    protected $signature = 'solo:monitor {pid}';

    protected $description = 'Watch for the stray processes and clean them up.';

    public function handle()
    {
        $parent = $this->argument('pid');
        $children = [];

        $this->info("Monitoring parent process PID: {$parent}");

        while (true) {
            $children = array_unique([
                ...$children,
                ...$this->getChildProcesses($parent)
            ]);

            sleep(1);

            if ($this->isProcessRunning($parent)) {
                continue;
            }

            $this->warn("Parent process {$parent} has died.");

            // Give them a chance to die on their own.
            sleep(2);

            // Recursively kill all tracked child processes
            foreach ($children as $child) {
                $this->killProcess($child);
            }

            $this->info('All tracked child processes cleaned up. Exiting.');

            break;
        }
    }

    public function isProcessRunning($pid)
    {
        // Check if the process with the given PID exists
        $output = [];
        exec("ps -p {$pid}", $output);
        return count($output) > 1; // If the output has more than the header line, the process is running
    }

    public function getChildProcesses($pid)
    {
        // Detect the operating system
        $os = PHP_OS_FAMILY;

        // Get the list of all processes with their PID and PPID
        $output = [];
        if ($os === 'Darwin') { // macOS
            exec("ps -eo pid,ppid | tail -n +2", $output);
        } elseif ($os === 'Linux') { // Linux
            exec("ps -eo pid,ppid --no-headers", $output);
        } else {
            throw new RuntimeException("Unsupported operating system: $os");
        }

        // Parse the output into an array of processes
        $processes = [];
        foreach ($output as $line) {
            list($childPid, $parentPid) = preg_split('/\s+/', trim($line));
            $processes[] = ['pid' => $childPid, 'ppid' => $parentPid];
        }

        // Recursive function to find children of the given PID
        $children = [];
        foreach ($processes as $process) {
            if ($process['ppid'] == $pid) {
                $children[] = $process['pid'];
                // Recurse to find the children of this child
                $children = array_merge($children, $this->getChildProcesses($process['pid']));
            }
        }

        return $children;
    }

    public function killProcess($pid)
    {
        if ($pid === getmypid()) {
            return;
        }

        if ($this->isProcessRunning($pid)) {
            exec("kill -9 {$pid}");
        }

        $this->warn("Killed process PID: {$pid}");
    }
}