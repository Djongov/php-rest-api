<?php declare(strict_types=1);

// Path: Models/api key.php

// Called in /Controllers/api key.php

// Responsible for handling the api key table in the database CRUD operations

namespace Models;

use App\Database\DB;
use App\Exceptions\ApiKeyException;
use App\Logs\SystemLog;
use Models\BasicModel;

class ApiKey extends BasicModel
{
    private $table = 'api_keys';
    private $mainColumn = 'api_key';
    
    public function setter($table, $mainColumn) : void
    {
        $this->table = $table;
        $this->mainColumn = $mainColumn;
    }
    /**
     * Checks if an IP exists in the api key table, accepts an ID or an IP in CIDR notation
     * @category   Models - api key
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
     * Gets an IP from the api key table, accepts an ID or an IP in CIDR notation. If no parameter is provided, returns all IPs
     * @category   Models - api key
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string|int $param the id or the ip in CIDR notation
     * @return     array returns the IP data as an associative array and if no parameter is provided, returns fetch_all
     * @throws     api keyException, IPDoesNotExist, InvalidIP from formatIp
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
                throw (new ApiKeyException())->apiKeyDoesNotExist();
            }
            $stmt = $pdo->prepare("SELECT * FROM $this->table WHERE id = ?");
            $stmt->execute([$param]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            // Check if IP exists
            if (!$this->exists($param)) {
                throw (new ApiKeyException())->apiKeyDoesNotExist();
            }
            $stmt = $pdo->prepare("SELECT * FROM $this->table WHERE $this->mainColumn = ?");
            $stmt->execute([$param]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }
    }
    /**
     * Saves an IP to the api key table, accepts an IP in CIDR notation, the user who created the IP and an optional comment
     * @category   Models - api key
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string $ip the ip in optional CIDR notation
     * @param      string $createdBy the user who creates the IP
     * @param      string $comment optional comment for the IP
     * @return     string|int the last inserted id (string or int)
     * @throws     api keyException ipAlreadyExists, notSaved, InvalidIP from formatIp
     * @system_log       IP added to the api key table, by who and under which id
     */
    public function add(string $access, ?string $note) : array
    {
        $apiKey = randomString();
        if ($note === null) {
            $note = '';
        }
        $createdBy = getApiKeyFromHeaders();
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("INSERT INTO $this->table ($this->mainColumn, access, note, created_by) VALUES (?,?,?,?)");
        $stmt->execute([$apiKey, $access, $note, $createdBy]);
        // Let's check if lastid is not 0
        if ($pdo->lastInsertId() !== 0) {
            SystemLog::write('Api Key ' . $apiKey . ' added to the api key table by ' . $createdBy . ' under id ' . $pdo->lastInsertId(), 'Api Key');
            return [
                'id' => $pdo->lastInsertId(),
                'api_key' => $apiKey
            ];
        } else {
            throw (new ApiKeyException())->notSaved('Api Key ' . $apiKey . ' not saved');
        }
    }
    /**
     * Updates an IP in the api key table, accepts an associative array with the data to update, the id of the IP and the user who updates the IP, if the IP does not exist, throws an exception
     * @category   Models - api key
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      array $data an associative array with the data to update, needs to comply with the columns in the table
     * @param      int $id the id of the IP
     * @param      string $updatedBy the user who updates the IP
     * @return     bool
     * @throws     api keyException ipDoesNotExist
     * @system_log IP updated and by who and what data was passed
     */
    public function update(array $data, int $id) : bool
    {
        $db = new DB();
        // Check if the data matches the columns

        // Every table has a last_updated_by column. Let's add it to the update
        $apikey = getApiKeyFromHeaders();
        if (!$apikey) {
            throw (new ApiKeyException())->missingApiKey();
        }
        
        $data['last_updated_by'] = $apikey;

        $db->checkDBColumnsAndTypes($data, $this->table);

        if (!$this->exists($id)) {
            throw (new ApiKeyException())->apiKeyDoesNotExist();
        }

        $fields = '';
        $values = [];
        foreach ($data as $column => $value) {
            $fields .= "$column = ?, ";
            $values[] = $value;
        }
        $fields = rtrim($fields, ', ');

        $values[] = $id; // Add the ID to the values array for the WHERE clause

        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("UPDATE $this->table SET $fields WHERE id = ?");
        $stmt->execute($values);

        // Check if any rows were affected
        if ($stmt->rowCount() > 0) {
            SystemLog::write('IP with id ' . $id . ' updated with data ' . json_encode($data), 'Api Key');
            return true;
        } else {
            return false;
        }
    }
    /**
     * Deletes an IP in the api key table, accepts the id of the IP and the user who deletes the IP, if the IP does not exist, throws an exception
     * @category   Models - api key
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      int $id the id of the IP
     * @param      string $deletedBy the user who deletes the IP
     * @return     bool
     * @throws     api keyException ipDoesNotExist
     * @system_log IP deleted and by who
     */
    public function delete(int $id, string $deletedBy) : bool
    {
        // Check if IP exists
        if (!$this->exists($id)) {
            throw (new ApiKeyException())->apiKeyDoesNotExist();
        }
        // We only know the id, so just for logging purposes, we will pull the IP
        $ip = $this->get($id)[$this->mainColumn];
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM $this->table WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 1) {
            SystemLog::write('IP ' . $ip . ' (id ' . $id . ') deleted by ' . $deletedBy, 'Api Key');
            return true;
        } else {
            throw (new ApiKeyException())->notSaved('IP ' . $ip . ' not deleted');
        }
    }
}
