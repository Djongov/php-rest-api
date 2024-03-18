<?php

namespace App\Database;

use Controllers\Output;

class DB
{
    private $host;
    private $username;
    private $password;
    private $database;
    private $charset;
    private $pdo;

    public function __construct($host = DB_HOST, $username = DB_USER, $password = DB_PASS, $database = DB_NAME, $charset = 'utf8')
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
        $this->charset = $charset;

        $this->connect();
    }

    private function connect()
    {
        $dsn = "mysql:host=$this->host;dbname=$this->database;charset=$this->charset";

        if (defined("MYSQL_SSL") && MYSQL_SSL) {
            $dsn .= ";sslmode=REQUIRED";
            $options = [
                \PDO::MYSQL_ATTR_SSL_CA => CA_CERT,
                //PDO::MYSQL_ATTR_SSL_CERT => '/path/to/client_cert.pem',
                //PDO::MYSQL_ATTR_SSL_KEY => '/path/to/client_key.pem'
            ];
        }

        try {
            $this->pdo = new \PDO($dsn, $this->username, $this->password, $options ?? []);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            if (ini_get('display_errors') === '1') {
                Output::error("Connection failed: " . $e->getMessage(), 500);
            } else {
                Output::error("Connection failed", 500);
            }
        }
    }

    public function query($sql) : array
    {
        $result = $this->pdo->query($sql);
        $this->disconnect();
        // Now if the query has SELECT, SHOW, DESCRIBE or EXPLAIN, then return the result, otherwise return the statement
        if (preg_match('/^(SELECT|SHOW|DESCRIBE|EXPLAIN)/i', $sql)) {
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $result;
    }
    public function queryPrepared(string $sql, array $params)
    {
        $stmt = $this->pdo->prepare($sql);
        // Now let's prepare the array of parameters
        $stmt->execute($params);
        $this->disconnect();
        return $stmt;
    }
    public function updateTable(array $data, string $tableName) : void
    {
        self::checkDBColumns($data, $tableName);
        // Prepare SQL statement
        $sql = "UPDATE $tableName SET ";
        $setValues = [];
        foreach ($data as $key => $value) {
            if ($key !== 'id') { // Exclude id from SET clause
                $setValues[] = "$key = :$key";
            }
        }
        $sql .= implode(', ', $setValues);
        $sql .= " WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);

        // Bind parameters
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        var_dump($sql);

        // Execute statement
        $stmt->execute();
    }
    // This method will check if the columns in the array match the columns in the database
    public static function checkDBColumns(array $array, string $table)
    {
        $db = new DB();
        $columns = $db->query("SHOW COLUMNS FROM $table");
        $columnNames = array_column($columns, 'Field');
        $diff = array_diff(array_keys($array), $columnNames);
        if (!empty($diff)) {
            throw new \PDOException('The following columns do not exist in the database: ' . implode(', ', $diff));
        }
    }
    private function disconnect()
    {
        $this->pdo = null;
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
