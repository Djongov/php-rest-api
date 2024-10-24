<?php declare(strict_types=1);
/* Not supposed to be used */

declare(strict_types=1);

namespace App\Database;

use Api\Response;
use Exception;

class MYSQL
{
    public static function connect()
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = mysqli_init();

        if (defined("DB_SSL") && DB_SSL) {
            mysqli_ssl_set($conn, null, null, DB_CA_CERT, null, null);
            try {
                $conn->real_connect('p:' . DB_HOST, DB_USER, DB_PASS, DB_NAME, 3306, DB_SSL);
            } catch (\mysqli_sql_exception $e) {
                if (str_contains($e->getMessage(), "Unknown database") !== false) {
                    Response::output('Database "' . DB_NAME . '" does not exist, you need to go through the /install endpoint' . $_SERVER['REQUEST_URI'], 400);
                } else {
                    Response::output($e->getMessage(), 400);
                }
            }
        } else {
            try {
                $conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            } catch (\mysqli_sql_exception $e) {
                if (str_contains($e->getMessage(), "Unknown database") !== false) {
                    Response::output('Database "' . DB_NAME . '" does not exist, you need to go through the /install endpoint', 400);
                } else {
                    Response::output($e->getMessage(), 400);
                }
            }
        }

        return $conn;
    }

    public static function query($query)
    {
        $link = self::connect();
        try {
            $stmt = $link->prepare($query);
        } catch (\mysqli_sql_exception $e) {
            Response::output($e->getMessage(), 400);
        }
        $result = false;
        try {
            $stmt->execute();
            if (str_starts_with($query, 'SELECT') || str_starts_with($query, 'SHOW') || str_starts_with($query, 'DESCRIBE')) {
                $result = $stmt->get_result();
            } else {
                $result = $stmt;
            }
        } catch (\mysqli_sql_exception $e) {
            Response::output($e->getMessage(), 400);
        }

        $link->close();
        return $result;
    }

    // Prepared query, statement needs to be passed as array [] as in [$value1, $value2], returns a result
    public static function queryPrepared($query, $statement)
    {
        $link = self::connect();
        try {
            $stmt = $link->prepare($query);
        } catch (Exception $e) {
            Response::output($e->getMessage(), 400);
        }
        if (is_array($statement)) {
            $statementParams = '';
            foreach ($statement as $key => $param) {
                if ($key === 'id') {
                    $statementParams .= 'i';
                } else {
                    $statementParams .= 's';
                }
            }
            $stmt->bind_param($statementParams, ...$statement);
        } else {
            $stmt->bind_param("s", $statement);
        }
        try {
            if ($stmt->execute()) {
        if (stripos($query, "SELECT") !== false) {
            $result = $stmt->get_result();
            $link->close();
            return $result;
        } else {
            $link->close();
            return $stmt;
        }
    } else {
        $error = $stmt->error;
        // Debugging statement 3: Print the error message
        echo "Debug MySQL Error: $error\n";
        $link->close();
        Response::output($error, 400);
    }
        } catch (Exception $e) {
            $link->close();
            Response::output($e->getMessage(), 400);
        }
    }
    // Sometimes you may need to make a bundle of queries one after the other, returns a result
    public static function multiQuery($arrayOfQueries)
    {
        $link = self::connect();
        foreach ($arrayOfQueries as $sql) {
            try {
                $result = mysqli_query($link, $sql);
            } catch (Exception $e) {
                Response::output($e->getMessage(), 400);
            }
        }
        $link->close();
        return $result;
    }
    // This would apply mysqli_real_escape_string to an entire assoc array
    public static function mysqliEscapeAssoc($array)
    {
        $link = self::connect();
        foreach ($array as $column => $value) {
            $array[$column] = mysqli_real_escape_string($link, $array[$column]);
        }
        $link->close();
        return $array;
    }
    // This would apply mysqli_real_escape_string to a string
    public static function mysqliEscapeString($string)
    {
        $link = self::connect();
        // mysqli_real_escape_string does not accept nulls
        if ($string !== null) {
            return mysqli_real_escape_string($link, $string);
        } else {
            return $string;
        }
    }
    // This method will check if the columns in the array match the columns in the database
    public static function checkDBColumns(array $array, string $table)
    {
        $columns = self::query("SHOW COLUMNS FROM `$table`");
        $columns = $columns->fetch_all(MYSQLI_ASSOC);
        $columns = array_column($columns, 'Type', 'Field');
        // Check if all keys in $reports_array match the columns
        foreach ($array as $key => $value) {
            if (!array_key_exists($key, $columns)) {
                Response::output("Column '$key' does not exist in table '$table'", 400);
            }
        }
    }
    public static function describe(string $table)
    {
        $query = "DESCRIBE `$table`";
        $result = self::query($query);
        $resultArray = $result->fetch_all(MYSQLI_ASSOC);
        // Now we want to return an array of the column names and their types only
        $resultArray = array_column($resultArray, 'Type', 'Field');
        // Now go through the values and convert them to their respective types
        foreach ($resultArray as $key => $value) {
            $resultArray[$key] = self::mapDataTypesArray($value);
        }
        return $resultArray;
    }
    // This is used in the get-records datagrid API to present the data in the correct input type
    public static function mapDataTypesArray(string $value) {
        if (str_starts_with($value, 'tinyint')) {
            return 'bool';
        }
        if (str_starts_with($value, 'int')) {
            return 'int';
        }
        if (str_starts_with($value, 'decimal') || str_starts_with($value, 'float') || str_starts_with($value, 'double')) {
            return 'float';
        }
        if (str_starts_with($value, 'date') || str_starts_with($value, 'time') || str_starts_with($value, 'year')) {
            return 'datetime';
        }
        if (str_starts_with($value, 'varchar') || str_starts_with($value, 'text')) {
            return 'string';
        }
    }
    // This method will check columns but also data types
    public static function checkDBColumnsAndTypes(array $array, string $table)
    {
        // Fetch table structure from the database
        $dbTable = self::query("DESCRIBE `$table`");
        $dbTableArray = $dbTable->fetch_all(MYSQLI_ASSOC);

        // Extract column names and data types from the table structure
        $dbColumns = array_column($dbTableArray, 'Type', 'Field');

        // Check if all columns in $_POST exist in the database
        foreach ($array as $key => $value) {
            if (is_int($key)) {
                // This is a numeric array, so the column name is the value
                $key = $value;
            }
            if (!array_key_exists($key, $dbColumns)) {
                // Column does not exist in the database
                Response::output("Column '$key' does not exist in table '$table");
            } else {
                // Column exists, check data type
                $expectedType = self::normalizeDataType($dbColumns[$key]);
                $actualType = self::normalizeDataType(gettype($value));

                if (self::checkDataType($expectedType, $actualType)) {
                    Response::output("Column '$key' in table '$table' has incorrect data type. Expected '$expectedType', got '$actualType'");
                }
            }
        }
    }

    // Helper method to check data types
    private static function checkDataType($expectedType, $actualType)
    {
        // Implement your own logic for data type checking
        // This is a simple example, you may need to extend it based on your requirements
        return $expectedType === $actualType;
    }

    // Helper method to normalize data types
    private static function normalizeDataType($type)
    {
        // Adjust this based on your specific requirements
        // Convert common MySQL data types to PHP types
        $typeMap = [
            'tinyint' => 'int',
            'smallint' => 'int',
            'mediumint' => 'int',
            'int' => 'int',
            'bigint' => 'int',
            'decimal' => 'float',
            'float' => 'float',
            'double' => 'float',
            // ... add more mappings as needed
        ];

        return $typeMap[strtolower($type)] ?? $type;
    }
    // Helper function for quickly save the last login time for a user
    public static function recordLastLogin($username)
    {
        //$link = self::connect();
        //date("yyyy-MM-dd HH:mm",time())
        self::queryPrepared("UPDATE `users` SET `last_login`= NOW() WHERE `username` = ?", [$username]);
    }
}
