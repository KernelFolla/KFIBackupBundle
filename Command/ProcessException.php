<?php

namespace KFI\BackupBundle\Command;

use Symfony\Component\Process\Process;
use Exception;

class ProcessException extends Exception{
    const HIDE_ALL = '{HIDEALL}';

    public static function create(Process $process, $hideCommandLine = null){
        $error   = $process->getErrorOutput();
        $command = $process->getCommandLine();

        if($hideCommandLine == self::HIDE_ALL)
            return new self($error);
        else if (isset($hideCommandLine))
            return new self(sprintf('%s -> %s', str_replace($hideCommandLine, '###', $command), $error));
        else
            return new self(sprintf('%s -> %s', $command, $error));
    }
}