<?php

namespace Whis\Cli\Commands;

use Whis\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends Command
{
    protected static $defaultName = 'migrate';

    protected static $defaultDescription = 'Migrates all the migration files';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $migrator = app(Migrator::class);
            $migrator->migrate();
            $output->writeln(date("Y-m-d H:i:s")." - Migrated successfully");
            return Command::SUCCESS;
        } catch (\PDOException $e) {
            $output->writeln("<error>".date("Y-m-d H:i:s")." - Migration failed: ". $e->getMessage()."</error>");
            $output->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }

        
    }
}
