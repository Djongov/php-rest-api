<?php declare(strict_types=1);

namespace Models\Core;

use App\Database\DB;
use App\Logs\SystemLog;

class DBCache implements DBCacheInterface
{
    private static string $table = 'cache';
    private static string $errorCategory = 'DBCache Error';

    /**
     * @param string $type The type column in the cache table
     * @param string $uniqueProperty The unique_property column in the cache table
     * @return array The row from the cache table, if no row is found, an empty array is returned
     * @throws \PDOException
     */
    public static function get(string $type, string $uniqueProperty) : array
    {
        $db = new DB();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("SELECT * FROM " . self::$table . " WHERE type=? AND unique_property=?");
        
        try {
            $stmt->execute([$type, $uniqueProperty]);
            $array = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$array) {
                return [];
            } else {
                return $array;
            }
        } catch (\PDOException $e) {
            SystemLog::write('DBCache get error: ' . $e->getMessage(), self::$errorCategory);
            if (ERROR_VERBOSE) {
                throw new \PDOException($e->getMessage());
            } else {
                throw new \PDOException('Error getting from cache');
            }
        }
    }
    /**
     * @param mixed $value The value to insert
     * @param string $expiration The expiration date (format - Y-m-d H:i:s)
     * @param string $type The type column in the cache table
     * @param string $uniqueProperty The unique_property column in the cache table
     * @return string The last inserted ID
     * @throws \PDOException
     */
    public static function create(mixed $value, string $expiration, string $type, string $uniqueProperty) : string
    {
        $db = new DB();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("INSERT INTO " . self::$table . " (value, expiration, type, unique_property) VALUES (?,?,?,?)");

        try {
            $stmt->execute([$value, $expiration, $type, $uniqueProperty]);
            return $pdo->lastInsertId();
        } catch (\PDOException $e) {
            SystemLog::write('DBCache create error: ' . $e->getMessage(), self::$errorCategory);
            if (ERROR_VERBOSE) {
                throw new \PDOException($e->getMessage());
            } else {
                throw new \PDOException('Error creating cache');
            }
        }
    }
    /**
     * @param mixed $value The value to update
     * @param string $expiration The expiration date (format - Y-m-d H:i:s)
     * @param string $type The type column in the cache table
     * @param string $uniqueProperty The unique_property column in the cache table
     * @return int The number of rows affected
     * @throws \PDOException
     */
    public static function update(mixed $value, string $expiration, string $type, string $uniqueProperty) : int
    {
        $db = new DB();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("UPDATE " . self::$table . " SET value=?, expiration=? WHERE type=? AND unique_property=?");

        try {
            $stmt->execute([$value, $expiration, $type, $uniqueProperty]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            SystemLog::write('DBCache update error: ' . $e->getMessage(), self::$errorCategory);
            if (ERROR_VERBOSE) {
                throw new \PDOException($e->getMessage());
            } else {
                throw new \PDOException('Error updating cache');
            }
        }
    }
    /**
    * @param string $type type column in the cache table
    * @param string $uniqueProperty unique_property column in the cache table
    * @return int The number of rows affected
    * @throws \PDOException
    */
    public static function delete(string $type, string $uniqueProperty) : int
    {
        $db = new DB();
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare("DELETE FROM " . self::$table . " WHERE type=? AND unique_property=?");

        try {
            $stmt->execute([$type, $uniqueProperty]);
            return $stmt->rowCount();
        } catch (\PDOException $e) {
            SystemLog::write('DBCache delete error: ' . $e->getMessage(), self::$errorCategory);
            if (ERROR_VERBOSE) {
                throw new \PDOException($e->getMessage());
            } else {
                throw new \PDOException('Error deleting from cache');
            }
        }
    }
}
