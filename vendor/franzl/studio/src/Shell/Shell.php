<?php

namespace Studio\Shell;

use RuntimeException;
use Symfony\Component\Process\Process;

class Shell
{
    public static function run($task, $directory = null)
    {
        $process = new Process("$task", $directory);
        $process->setTimeout(3600);

        $process->run();

        if (! $process->isSuccessful()) {
            $command = preg_replace('/ .+$/', '', $task);
            $error = $process->getErrorOutput();
            throw new RuntimeException("Error while running $command: $error");
        }

        return $process->getOutput();
    }
}
