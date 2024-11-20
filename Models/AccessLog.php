<?php declare(strict_types=1);

// Path: Models/AccessLog.php

// Called in /Controllers/AccessLog.php

// Responsible for handling the AccessLog table in the database CRUD operations

namespace Models;

use App\Database\DB;
use App\Exceptions\AccessLogException;
use App\Logs\SystemLog;
use Models\BasicModel;

class AccessLog extends BasicModel
{
    private $table = 'api_access_log';
    private $mainColumn = 'request_id';
    
    public function setter($table, $mainColumn) : void
    {
        $this->table = $table;
        $this->mainColumn = $mainColumn;
    }
    /**
     * Checks if an IP exists in the AccessLog table, accepts an ID or an IP in CIDR notation
     * @category   Models - AccessLog
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string|int $param the id or the ip in CIDR notation
     * @return     string bool
     */
    public function exists(string|int $param) : bool
    {
        $db = new DB();

        // Determine if we're querying by ID or column
        $query = is_int($param)
            ? "SELECT 1 FROM $this->table WHERE id = ? LIMIT 1"
            : "SELECT 1 FROM $this->table WHERE $this->mainColumn = ? LIMIT 1";

        // Prepare and execute the statement
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$param]);

        // Fetch a single row and check if it exists
        return $stmt->fetch() !== false;
    }
    /**
     * Gets an IP from the AccessLog table, accepts an ID or an IP in CIDR notation. If no parameter is provided, returns all IPs
     * @category   Models - AccessLog
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string|int $param the id or the ip in CIDR notation
     * @return     array returns the IP data as an associative array and if no parameter is provided, returns fetch_all
     * @throws     AccessLogException
     */
    public function get(string|int $param = null, ?string $sort = null, ?int $limit = null, ?string $orderBy = null) : array
    {
        $db = new DB();
        $pdo = $db->getConnection();
        // if the parameter is empty, we assume we want all the IPs
        if (!$param) {
            $query = "SELECT * FROM $this->table";
            // If limit is set, we will limit the results
            if ($orderBy === null) {
                $query .= " ORDER BY $orderBy";
            } else {
                $query .= " ORDER BY $orderBy $sort";
            }
            if ($sort === null) {
                $query .= " ASC";
            }
            if ($limit) {
                $query .= " LIMIT $limit";
            }
            $stmt = $pdo->query($query);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        // If the parameter is an integer, we assume it's an ID
        if (is_int($param)) {
            if (!$this->exists($param)) {
                throw (new AccessLogException())->generic('access log ' . $param . ' does not exist', 404);
            }
            $stmt = $pdo->prepare("SELECT * FROM $this->table WHERE id = ?");
            $stmt->execute([$param]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            // Check if IP exists
            if (!$this->exists($param)) {
                throw (new AccessLogException())->generic('access log ' . $param . ' does not exist', 404);
            }
            $stmt = $pdo->prepare("SELECT * FROM $this->table WHERE $this->mainColumn = ?");
            $stmt->execute([$param]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }
    }
    public function add(array $data) : void
    {
        $db = new DB();
        $pdo = $db->getConnection();
        $dataValues = array_values($data);
        $query = 'INSERT INTO ' . $this->table . ' (' . implode(',', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')';
        $stmt = $pdo->prepare($query);
        try {
            $stmt->execute($dataValues);
        } catch (\Exception $e) {
            throw (new AccessLogException())->generic($e->getMessage(), 400);
        }
    }
    public function delete(int $id, string $deletedBy) : bool
    {
        // Check if IP exists
        if (!$this->exists($id)) {
            throw (new AccessLogException())->generic('ID ' . $id . ' does not exist', 404);
        }
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM $this->table WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 1) {
            SystemLog::write('API access log with id ' . $id . ' deleted', 'API Access Log');
            return true;
        } else {
            throw (new AccessLogException())->notDeleted();
        }
    }
    public function deleteAll() : bool
    {
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM $this->table");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            SystemLog::write('All API access logs deleted', 'API Access Log');
            return true;
        } else {
            throw (new AccessLogException())->notDeleted();
        }
    }
}
