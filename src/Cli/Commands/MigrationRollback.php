<?php

namespace Whis\Cli\Commands;

use Whis\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationRollback extends Command
{
    

    protected static $defaultName = 'migrate:rollback';

    protected static $defaultDescription = 'Rollback migrations';

    protected function configure()
    {
        $this->addArgument('steps',InputArgument::OPTIONAL,'The amount of migrations to rollback. Defaulting all');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        
        try {
            $migrator = app(Migrator::class);
            $migrator->rollback($input->getArgument('steps')??null);
            $output->writeln(date("Y-m-d H:i:s")." - Rolledback successfully ".($input->getArgument('steps')? $input->getArgument('steps')." step":""));
            return Command::SUCCESS;
        } catch (\PDOException $e) {
            $output->writeln("<error>".date("Y-m-d H:i:s")." - Migration failed: ". $e->getMessage()."</error>");
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
