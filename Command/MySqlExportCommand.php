<?php
namespace KFI\BackupBundle\Command;

use Symfony\Component\Process\Process;

/**
 * Command that dumps
 *
 * based on {@link https://github.com/ABMundi/DatabaseCommands/blob/master/Command/DatabaseDumpCommand.php ABMundi/DatabaseCommands}
 */
class MySqlExportCommand extends Base
{
    const COMMAND_NAME = 'kfi_backup:mysql_export';

    protected $filename;
    protected $link;


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
        $this->link     = $this->directory . "/current.sql.bz2";
        $this->filename = $this->directory . '/' . date('YmdHis') . '.sql.bz2';
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
            $this->prepareEnviroment();
            $this->mysqldump(
                $c->getParameter('database_host'),
                $c->getParameter('database_name'),
                $c->getParameter('database_user'),
                $c->getParameter('database_password'),
                $this->filename
            );
            $this->createLink($this->filename, $this->link);
            $o->writeln(sprintf(
                "<info>exported to %s in %s seconds</info>",
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
     * Create folder for dump
     *
     * @throws ProcessException
     */
    protected function prepareEnviroment()
    {
        if (is_dir($this->directory)) {
            $this->output->writeln(sprintf('<info>Directory %s already exists</info>', $this->directory));
        } else {
            $this->executeCode(sprintf('mkdir -p "%s"', $this->directory));
            $this->output->writeln(sprintf('<info>Directory %s succesfully  created</info>', $this->directory));
        }
    }

    /**
     * Run MysqlDump
     *
     * @throws ProcessException
     */
    protected function mysqldump($host, $name, $user, $password, $file)
    {
        $this->executeCode(sprintf(
            'mysqldump -h %s -u %s --password=%s %s | bzip2 -c > "%s"',
            $host, $user, $password, $name, $file
        ));
        $this->output->writeln(sprintf('<info>Database %s dumped succesfully</info>', $name));
    }

    /**
     * Create link to last dump
     *
     * @throws ProcessException
     */
    protected function createLink($from, $to)
    {
        $this->executeCode(sprintf('ln -f "%s" "%s"', $from, $to));
        $this->output->writeln(sprintf('<info>Link %s created succesfully</info>', $to));
    }
}