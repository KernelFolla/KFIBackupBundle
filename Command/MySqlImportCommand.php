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
    const DEFAULT_FILENAME = 'import.sql.bz2';
    const DEFAULT_DIRECTORY = 'app/backup/mysql';

    protected $filename;
    protected $download;
    protected $clear;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('This task dump the database in a file')
            ->addOption(
                'clear',
                'c',
                InputOption::VALUE_NONE,
                'clear the existing tables'
            )->addOption(
                'download',
                'w',
                InputOption::VALUE_REQUIRED,
                'download the file'
            )->addOption(
                'filename',
                'f',
                InputArgument::OPTIONAL,
                'define a filename',
                self::DEFAULT_FILENAME
            )->addOption(
                'directory',
                'd',
                InputArgument::OPTIONAL,
                'define a directory',
                self::DEFAULT_DIRECTORY
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function init()
    {
        $this->download = $this->input->getOption('download');
        $this->directory = $this->input->getOption('directory');
        $this->filename  = $this->input->getOption('filename');
        $this->clear = $this->input->getOption('clear');
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
            if(!empty($this->download))
                $this->downloadFile($this->download,$this->filename);
            if(!empty($this->clear))
                $this->clearTables(
                        $c->getParameter('database_host'),
                        $c->getParameter('database_name'),
                        $c->getParameter('database_user'),
                        $c->getParameter('database_password')
                );
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

    protected function clearTables($host, $name, $user, $password)
    {
        $this->output->writeln(sprintf('<info>clearing tables of %s</info>', $name));
        $command = 'mysql -h {host} --user={user} --password="{password}" -Nse "show tables" {name} | while read table; do mysql -h {host} --user={user} --password={password}  -e "SET FOREIGN_KEY_CHECKS = 0; drop table $table" {name}; done';
        foreach(array('host','name','user','password') as $k){
            $command = str_replace(sprintf('{%s}',$k),$$k,$command);
        }
        $this->executeCode($command);
        $this->output->writeln(sprintf('<info>database %s cleared succesfully</info>', $name));
    }

    protected function downloadFile($from, $to){
        $this->output->writeln(sprintf('<info>downloading %s, please wait</info>', $from));
        $this->executeCode(sprintf('wget -O %s %s', $to, $from));
    }
}