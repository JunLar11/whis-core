<?php

namespace Whis\Database\Drivers;

use PDO;

class PdoDriver implements DatabaseDriver
{
    protected ?PDO $pdo;

    public function connect(
        string $protocol,
        string $host,
        int    $port,
        string $database,
        string $username,
        string $password
    ) {
        $dsn = "$protocol:host=$host;port=$port;dbname=$database";
        $this->pdo = new PDO($dsn, $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function lastInsertedId()
    {
        return $this->pdo->lastInsertId();
    }

    public function close()
    {
        $this->pdo = null;
    }

    public function statement(string $query, array $bind = []): mixed
    {
        try{
            $statement = $this->pdo->prepare($query);
            //return [$bind, $query];
            //exit;
            $statement->execute($bind);
            //return "Errormessage: " . $statement->error;
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        }catch(\PDOException $e){
            http_response_code(500);
            echo "An error occured. Please try again later.";
            exit;
        }
        
    }
}
