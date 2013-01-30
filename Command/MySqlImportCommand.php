<?php
namespace KFI\BackupBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Command that dumps
 *
 * based on {@link https://github.com/ABMundi/DatabaseCommands/blob/master/Command/DatabaseDumpCommand.php ABMundi/DatabaseCommands}
 */
class MySqlImportCommand extends Base
{
    const COMMAND_NAME = 'kfi_backup:mysql_import';

    protected $filename;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('This task dump the database in a file');
    }

    /**
     * {@inheritDoc}
     */
    protected function init()
    {
        $this->directory = $this->directory .'/mysql';
        $this->filename  = $this->directory . '/import.sql.bz2';
    }

    /**
     * {@inheritDoc}
     */
    protected function dispatch()
    {
        $time = new \DateTime();
        $o    = $this->output;
        $c    = $this->getContainer();
        try {
            $this->mysqlimport(
                $c->getParameter('database_host'),
                $c->getParameter('database_name'),
                $c->getParameter('database_user'),
                $c->getParameter('database_password'),
                $this->filename
            );
            $o->writeln(sprintf(
                "<info>imported from %s in %s seconds</info>",
                $this->filename,
                $time->diff($time = new \DateTime())->s
            ));
            $o->writeln('<info>MISSION ACCOMPLISHED</info>');
        } catch (ProcessException $e) {
            $o->writeln('<error>Nasty error happened :\'-(</error>');
            $o->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }
    }


    /**
     * Run MysqlDump
     *
     * @throws ProcessException
     */
    protected function mysqlimport($host, $name, $user, $password, $file)
    {
        if (!file_exists($file)) {
            throw new ProcessException(sprintf('<info>File %s don\'t exists</info>', $file));
        }
        $this->executeCode(sprintf(
            'bunzip2 < %s | mysql -h %s -u %s --password=%s %s',
            $file, $host, $user, $password, $name
        ));
        $this->output->writeln(sprintf('<info>Database %s imported succesfully</info>', $name));
    }
}