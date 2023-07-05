<?php

namespace Whis\Cli\Commands;

use Whis\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddleware extends Command 
{
    protected static $defaultName = "make:middleware";

    protected static $defaultDescription = "Create a new middleware";

    protected function configure() {
        $this->addArgument("name", InputArgument::REQUIRED, "middleware name");
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $name = $input->getArgument("name");
        $directories = "";
        if(str_contains($name, "/")) {
            $directories = explode("/", $name);
            $name = array_pop($directories);
            $directories = implode("/", $directories);
            if (!is_dir(App::$root . "/app/Middlewares/$directories")) {
                mkdir(App::$root . "/app/Middlewares/$directories", 0744, true);
            }
        }

        $template = file_get_contents(resourcesDirectory() . "/resources/templates/middleware.php");
        $template = str_replace("MiddlewareName", $name."Middleware", $template);
        $template = str_replace("\\extraDirectories", "\\".str_replace("/","\\",$directories), $template);
        if (!file_exists(App::$root . "/app/Middlewares/".$directories)) {
            mkdir(App::$root . "/app/Middlewares/".$directories, 0744);
        }
        file_put_contents(App::$root . "/app/Middlewares/$directories/$name"."Middleware.php", $template);
        $output->writeln("<info>Controller created => $name"."Middleware.php</info>");

        return Command::SUCCESS;
    }
}
