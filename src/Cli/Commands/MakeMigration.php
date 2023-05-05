<?php

namespace Whis\Cli\Commands;

use Whis\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMigration extends Command
{
    

    protected static $defaultName = 'make:migration';

    protected static $defaultDescription = 'Create a new migration file';

    protected function configure()
    {
        $this->addArgument('name',InputArgument::REQUIRED,'The name of the migration');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrator = app(Migrator::class);
        $migrator->make($input->getArgument('name'));
        $output->writeln(date("Y-m-d H:i:s")." - Migration created successfully");
        return Command::SUCCESS;
    }
}
