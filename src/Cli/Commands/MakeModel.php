<?php

namespace Whis\Cli\Commands;

use Whis\App;
use Whis\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModel extends Command {
    protected static $defaultName = "make:model";

    protected static $defaultDescription = "Create a new model";

    protected function configure() {
        $this
            ->addArgument("name", InputArgument::REQUIRED, "Migration name")
            ->addOption("migration", "m", InputOption::VALUE_OPTIONAL, "Also create migration file", false);
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getArgument("name");
        $migration = $input->getOption("migration");

        $directories = "";
        if(str_contains($name, "/")) {
            $directories = explode("/", $name);
            $name = array_pop($directories);
            $directories = implode("/", $directories);
            if (!is_dir(App::$root . "/app/Models/$directories")) {
                mkdir(App::$root . "/app/Models/$directories", 0744, true);
            }
        }
        

        $template = file_get_contents(resourcesDirectory() . "/resources/templates/model.php");
        $template = str_replace("ModelName", $name."Model", $template);
        if($directories != ""){
            $template = str_replace("\\extraDirectories","\\".str_replace("/","\\",$directories), $template);
        }else{
            $template = str_replace("\\extraDirectories", "", $template);
        }
        if (!file_exists(App::$root . "/app/Models/".$directories)) {
            mkdir(App::$root . "/app/Models/". $directories, 0744);
        }
        file_put_contents(App::$root . "/app/Models/$directories/$name"."Model.php", $template);
        $output->writeln("<info>Model created => $name"."Model.php</info>");

        if ($migration !== false) {
            app(Migrator::class)->make("create_{$name}s_table");
        }

        return Command::SUCCESS;
    }
}