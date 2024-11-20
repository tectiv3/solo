<?php

namespace AaronFrancis\Solo\Support;

use RuntimeException;

class ProcessTracker
{
    public static function children($pid, $processes = null)
    {
        if (is_null($processes)) {
            $processes = static::getProcessList();
        }

        $children = [];

        foreach ($processes as $process) {
            if ($process['ppid'] == $pid) {
                $children[] = $process['pid'];
                $children = array_merge(
                    $children,
                    static::children($process['pid'], $processes)
                );
            }
        }

        return $children;
    }

    public static function kill(array $pids)
    {
        if (empty($pids)) {
            return;
        }

        $pidList = implode(' ', $pids);

        exec("kill -9 {$pidList} > /dev/null 2>&1");
    }

    public static function isRunning($pid)
    {
        if (!is_numeric($pid)) {
            throw new RuntimeException("Invalid PID: {$pid}");
        }

        $output = [];
        exec("ps -p {$pid} | grep {$pid}", $output);

        return count($output) > 0;
    }

    public static function getProcessList()
    {
        $output = [];
        $os = PHP_OS_FAMILY;

        if ($os === 'Darwin') {
            exec('ps -eo pid,ppid | tail -n +2', $output);
        } elseif ($os === 'Linux') {
            exec('ps -eo pid,ppid --no-headers', $output);
        } else {
            throw new RuntimeException("Unsupported operating system: $os");
        }

        $processes = [];
        foreach ($output as $line) {
            [$childPid, $parentPid] = explode(' ', trim(preg_replace('/\s+/', ' ', $line)), 2);
            $processes[] = ['pid' => $childPid, 'ppid' => $parentPid];
        }

        return $processes;
    }
}
