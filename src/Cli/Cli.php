<?php

namespace Whis\Cli;

use Whis\App;
use Whis\Cli\Commands\MakeController;
use Whis\Cli\Commands\MakeMigration;
use Whis\Cli\Commands\MakeModel;
use Whis\Cli\Commands\Migrate;
use Whis\Cli\Commands\MigrationRollback;
use Whis\Cli\Commands\Serve;
use Whis\Config\Config;
use Whis\Database\Drivers\DatabaseDriver;
use Whis\Database\Migrations\Migrator;
use Dotenv\Dotenv;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Whis\Cli\Commands\MakeMiddleware;

class Cli
{
    private static ConsoleOutput $output;
    public static function bootstrap(string $root):self
    {
        App::$root=$root;
        Dotenv::createImmutable($root)->load();
        Config::load($root."/config");
        foreach (config("providers.cli") as $provider) {
           ( new $provider())->registerServices();
        }
        app(DatabaseDriver::class)->connect(
            config("database.connection"),
            config("database.host"),
            config("database.port"),
            config("database.database"),
            config("database.username"),
            config("database.password")
        );

        singleton(
            Migrator::class,
            fn()=>new Migrator(
                "$root/Database/migrations",
                resourcesDirectory()."/resources/templates",
                app(DatabaseDriver::class)
            )
        );

        return new self;
    }

    public function run(){
        $cli = new Application("Whis");
        $cli->addCommands([
            new MakeMigration(),
            new Migrate(),
            new MigrationRollback(),
            new MakeModel(),
            new MakeController(),
            new Serve(),
            new MakeMiddleware()
        ]);
        $cli->run();
    }

    public static function log(string $message,string $format=null,array $extraSettings=[]) {
        if(is_null(self::$output)||!isset(self::$output)){
            self::$output=new ConsoleOutput();
        }

        switch($format){
            case "info":
                self::$output->writeln("<info>$message</info>");
                break;
            case "error":
                self::$output->writeln("<error>$message</error>");
                break;
            case "comment":
                self::$output->writeln("<comment>$message</comment>");
                break;
            case "question":
                self::$output->writeln("<question>$message</question>");
                break;
            default:
                $start="<";
                $end="</>";
                $options=self::unfoldArrayIntoString($extraSettings);
                if($options){
                    $start.=$options.">";
                    self::$output->writeln($start.$message.$end);
                }else{
                    self::$output->writeln($message);
                }
        }
    }
    private static function unfoldArrayIntoString(array $array, string $endOfOption=";"):string|false{
        $string = "";
        if(count($array)==0){
            return false;
        }elseif (count($array)==1) {
            return key($array)."=".$array[0];
        }else {
            $i=0;
            foreach($array as $key => $value){
                $string.= $key."=". $value;
                if($i<count($array)-1){
                    $string.=$endOfOption;
                }
            }
        }
        return $string;
    }
}
