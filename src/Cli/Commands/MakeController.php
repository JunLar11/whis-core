<?php

namespace Whis\Cli\Commands;

use Whis\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeController extends Command {
    protected static $defaultName = "make:controller";

    protected static $defaultDescription = "Create a new controller";

    protected function configure() {
        $this->addArgument("name", InputArgument::REQUIRED, "Controller name");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getArgument("name");
        $directories = "";
        if(str_contains($name, "/")) {
            $directories = explode("/", $name);
            $name = array_pop($directories);
            $directories = implode("/", $directories);
            if (!is_dir(App::$root . "/app/Controllers/$directories")) {
                mkdir(App::$root . "/app/Controllers/$directories", 0744, true);
            }
        }
        $template = file_get_contents(resourcesDirectory() . "/resources/templates/controller.php");
        $template = str_replace("ControllerName", $name."Controller", $template);
        if (!file_exists(App::$root . "/app/Controllers/".$directories)) {
            mkdir(App::$root . "/app/Controllers/".$directories, 0744);
        }
        file_put_contents(App::$root . "/app/Controllers/$directories/$name"."Controller.php", $template);
        $output->writeln("<info>Controller created => $name"."Controller.php</info>");

        return Command::SUCCESS;
    }
}
