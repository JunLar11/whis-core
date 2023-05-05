<?php

namespace Whis\Database\Migrations;

use Whis\Database\Drivers\DatabaseDriver;
use Symfony\Component\Console\Output\ConsoleOutput;

class Migrator
{
    private ConsoleOutput $output;
    public function __construct(
        private string $migrationsDirectory,
        private string $templatesDirectory,
        private DatabaseDriver $driver,
        private bool $logProgress = true,
    ) {
        $this->migrationsDirectory = $migrationsDirectory;
        $this->templatesDirectory = $templatesDirectory;
        $this->driver = $driver;
        $this->output = new ConsoleOutput();
    }

    private function log(string $message) {
        if ($this->logProgress) {
            $this->output->writeln($message);
        }
    }

    private function createMigrationsTableIfNotExists() {
        $this->driver->statement("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(256), migration_date DATETIME)");
    }
    public function migrate(){
        $this->createMigrationsTableIfNotExists();
        $migrated=$this->driver->statement("SELECT name FROM migrations");
        $migrations=glob("$this->migrationsDirectory/*.php");
        if(count($migrated)>=count($migrations)){
            $this->log("Nothing to migrate");
            return;
        }
        foreach (array_slice($migrations, count($migrated)) as $file) {
            $migration = require $file;
            $migration->up();
            $name = basename($file);
            $this->driver->statement("INSERT INTO migrations (name, migration_date) VALUES (?, ?)", [$name, date('Y-m-d H:i:s')]);
            $this->log("Migrated => $name");
        }
    }

    public function rollback(?int $steps=null){
        $this->createMigrationsTableIfNotExists();
        $migrated=$this->driver->statement("SELECT name FROM migrations");
        $pending=count($migrated);
        if($pending==0){
            $this->log("Nothing to rollback");
            return;
        }
        if(is_null($steps)||$steps>$pending){
            $steps=$pending;
        }
        $migrations=array_slice(array_reverse(glob("$this->migrationsDirectory/*.php")),-$pending);
        foreach ($migrations as $file) {
            $migration=require $file;
            $migration->down();
            $name=basename($file);
            $this->driver->statement("DELETE FROM migrations WHERE name=?",[$name]);
            $this->log("Rolled back => $name");
            if (--$steps==0) {
                break;
            }
        }
    }

    public function make(string $migrationName):string
    {
        $migrationName = snake_case($migrationName);
        $template = file_get_contents("$this->templatesDirectory/migration.php");
        if (preg_match("/create_.*_table/", $migrationName)) {
            $table = preg_replace_callback("/create_(.*)_(table)/", function ($matches) {
                return $matches[1];
            }, $migrationName);
            $template = str_replace('$UP', "CREATE TABLE $table (id INT AUTO_INCREMENT PRIMARY KEY)", $template);
            $template = str_replace('$DOWN', "DROP TABLE $table", $template);
        } elseif (preg_match("/.*(from|to)_(.*)_table/", $migrationName)) {
            $table = preg_replace_callback("/.*(from|to)_(.*)_table/", function ($matches) {
                return $matches[2];
            }, $migrationName);
            $template = preg_replace('/\$UP|\$DOWN/', "ALTER TABLE $table", $template);
        } else {
            $template = preg_replace_callback('/DB::statement.*/', fn ($match) => "// $match[0]", $template);
        }

        $date = date('Y_m_d');
        $id = 0;

        foreach (glob("$this->migrationsDirectory/*.php") as $file) {
            if (str_starts_with(basename($file), $date)) {
                $id++;
            }
        }


        $fileName = sprintf("%s_%06d_%s.php", $date, $id, $migrationName);
        //$this->log($this->migrationsDirectory);
        if (!file_exists($this->migrationsDirectory)) {
            mkdir($this->migrationsDirectory, 0744);
        }
        file_put_contents("$this->migrationsDirectory/$fileName", $template);
        $this->log("Created => $fileName on $date");
        return $fileName;
    }
}
