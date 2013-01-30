<?php
namespace KFI\BackupBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Process\Process;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Base class for other commands
 */
abstract class Base extends ContainerAwareCommand
{
    const DEFAULT_BASEDIR = "app/backup";

    abstract protected function init();

    abstract protected function dispatch();

    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;
    protected $directory = self::DEFAULT_BASEDIR;

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input   = $input;
        $this->output  = $output;
        $this->init();
        $this->dispatch();
    }

    protected function getRootDir()
    {
        /** @var $kernel \AppKernel */
        $kernel = $this->getContainer()->get('kernel');
        return $kernel->getRootDir();
    }

    /**
     * fast way to do a command with error handling
     *
     * @param $command
     * @param null $hideCommandLine
     * @throws ProcessException
     */
    protected function executeCode($command, $hideCommandLine = null)
    {
        $process = new Process($command);
        $process->run();
        if (!$process->isSuccessful()) {
            throw $this->createProcessException($process, $hideCommandLine);
        }
    }

    /**
     * create an exception that wraps the process exception
     *
     * @param Process $process
     * @param null $hideCommandLine
     * @return ProcessException
     */
    protected function createProcessException(Process $process, $hideCommandLine = null)
    {
        return ProcessException::create($process, $hideCommandLine);
    }
}