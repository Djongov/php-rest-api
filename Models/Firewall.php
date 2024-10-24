<?php declare(strict_types=1);

// Path: Models/Api/Firewall.php

// Called in /Controllers/Api/Firewall.php

// Responsible for handling the firewall table in the database CRUD operations

namespace Models;

use App\Database\DB;
use App\Exceptions\FirewallException;
use App\Logs\SystemLog;

class Firewall
{
    private $table = 'firewall';
    private $mainColumn = 'ip_cidr';
    
    public function setter($table, $mainColumn) : void
    {
        $this->table = $table;
        $this->mainColumn = $mainColumn;
    }
    /**
     * Checks if an IP exists in the firewall table, accepts an ID or an IP in CIDR notation
     * @category   Models - Firewall
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string|int $param the id or the ip in CIDR notation
     * @return     string bool
     */
    public function exists(string|int $param) : bool
    {
        $db = new DB();
        // If the parameter is an integer, we assume it's an ID
        if (is_int($param)) {
            $query = "SELECT $this->mainColumn FROM $this->table WHERE id = ?";
        } else {
            $query = "SELECT $this->mainColumn FROM $this->table WHERE $this->mainColumn = ?";
        }
        $stmt = $db->getConnection()->prepare($query);
        $stmt->execute([$param]);
        return ($stmt->rowCount() > 0) ? true : false;
    }
    /**
     * Gets an IP from the firewall table, accepts an ID or an IP in CIDR notation. If no parameter is provided, returns all IPs
     * @category   Models - Firewall
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string|int $param the id or the ip in CIDR notation
     * @return     array returns the IP data as an associative array and if no parameter is provided, returns fetch_all
     * @throws     FirewallException, IPDoesNotExist, InvalidIP from formatIp
     */
    public function get(string|int $param) : array
    {
        $db = new DB();
        $pdo = $db->getConnection();
        // if the parameter is empty, we assume we want all the IPs
        if (empty($param)) {
            $stmt = $pdo->query("SELECT * FROM $this->table");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }
        // If the parameter is an integer, we assume it's an ID
        if (is_int($param)) {
            if (!$this->exists($param)) {
                throw (new FirewallException())->ipDoesNotExist();
            }
            $stmt = $pdo->prepare("SELECT * FROM $this->table WHERE id = ?");
            $stmt->execute([$param]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } else {
            // Format the IP
            $param = $this->formatIp($param);
            // Check if IP exists
            if (!$this->exists($param)) {
                throw (new FirewallException())->ipDoesNotExist();
            }
            $stmt = $pdo->prepare("SELECT * FROM $this->table WHERE $this->mainColumn = ?");
            $stmt->execute([$param]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        }
    }
    /**
     * Saves an IP to the firewall table, accepts an IP in CIDR notation, the user who created the IP and an optional comment
     * @category   Models - Firewall
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string $ip the ip in optional CIDR notation
     * @param      string $createdBy the user who creates the IP
     * @param      string $comment optional comment for the IP
     * @return     bool
     * @throws     FirewallException ipAlreadyExists, notSaved, InvalidIP from formatIp
     * @system_log       IP added to the firewall table, by who and under which id
     */
    public function save(string $ip, string $createdBy, string $comment = '') : bool
    {
        // Format the IP
        $ip = $this->formatIp($ip);
        // Check if IP exists
        if ($this->exists($ip)) {
            throw (new FirewallException())->ipAlreadyExists();
        }
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("INSERT INTO $this->table ($this->mainColumn, created_by, comment) VALUES (?,?,?)");
        $stmt->execute([$ip, $createdBy, $comment]);
        if ($stmt->rowCount() === 1) {
            SystemLog::write('IP ' . $ip . ' added to the firewall table by ' . $createdBy . ' under the id ' . $pdo->lastInsertId(), 'Firewall');
            return true;
        } else {
            throw (new FirewallException())->notSaved('IP not saved');
        }
    }
    /**
     * Updates an IP in the firewall table, accepts an associative array with the data to update, the id of the IP and the user who updates the IP, if the IP does not exist, throws an exception
     * @category   Models - Firewall
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      array $data an associative array with the data to update, needs to comply with the columns in the table
     * @param      int $id the id of the IP
     * @param      string $updatedBy the user who updates the IP
     * @return     bool
     * @throws     FirewallException ipDoesNotExist
     * @system_log IP updated and by who and what data was passed
     */
    public function update(array $data, int $id, string $updatedBy) : bool
    {
        $db = new DB();
        // Check if the data matches the columns

        $db->checkDBColumnsAndTypes($data, $this->table);

        if (!$this->exists($id)) {
            throw (new FirewallException())->ipDoesNotExist();
        }
        // Check if data is correct
        $sql = "UPDATE $this->table SET ";
        $updates = [];
        // Check if all keys in $array match the columns
        foreach ($data as $key => $value) {
            // Add the column to be updated to the SET clause
            $updates[] = "$key = ?";
        }
        // Combine the SET clauses with commas
        $sql .= implode(', ', $updates);

        // Add a WHERE clause to specify which organization to update
        $sql .= " WHERE id = ?";

        // Prepare and execute the query using queryPrepared
        $values = array_values($data);
        $values[] = $id; // Add the id for the WHERE clause
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        if ($stmt->rowCount() === 1) {
            SystemLog::write('IP with id ' . $id . ' updated by ' . $updatedBy . ' with data ' . json_encode($data), 'Firewall');
            return true;
        } else {
            throw (new FirewallException())->notSaved('IP not saved');
        }
    }
    /**
     * Deletes an IP in the firewall table, accepts the id of the IP and the user who deletes the IP, if the IP does not exist, throws an exception
     * @category   Models - Firewall
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      int $id the id of the IP
     * @param      string $deletedBy the user who deletes the IP
     * @return     bool
     * @throws     FirewallException ipDoesNotExist
     * @system_log IP deleted and by who
     */
    public function delete(int $id, string $deletedBy) : bool
    {
        // Check if IP exists
        if (!$this->exists($id)) {
            throw (new FirewallException())->ipDoesNotExist();
        }
        // We only know the id, so just for logging purposes, we will pull the IP
        $ip = $this->get($id)[$this->mainColumn];
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM $this->table WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 1) {
            SystemLog::write('IP ' . $ip . ' (id ' . $id . ') deleted by ' . $deletedBy, 'Firewall');
            return true;
        } else {
            throw (new FirewallException())->notSaved('IP ' . $ip . ' not deleted');
        }
    }
    /**
     * Validates an IP in CIDR notation, if the IP is not valid or not in CIDR notation, throws an exception
     * @category   Models - Firewall
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string $ip the IP, needs to be in CIDR notation
     * @return     bool
     * @throws     FirewallException invalidIP
     */
    public function validateIp(string $ip) : bool
    {
        $ipExplode = explode('/', $ip);
        $ip = $ipExplode[0];
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            throw (new FirewallException())->invalidIP();
        }
        if (isset($ipExplode[1])) {
            $mask = $ipExplode[1];
            if ($mask < 0 || $mask > 32) {
                throw (new FirewallException())->invalidIP();
            }
        }
        return true;
    }
    /**
     * Formats an IP to CIDR notation, runs through the validation first, if the IP is not valid or not in CIDR notation, throws an exception
     * @category   Models - Firewall
     * @author     @Djongov <djongov@gamerz-bg.com>
     * @param      string $ip the IP, needs to be in CIDR notation
     * @return     string returns a formatted IP in CIDR notation
     * @throws     FirewallException invalidIP from validateIp
     */
    public function formatIp(string $ip) : string
    {
        // First run through the validation
        $this->validateIp($ip);

        // Now let's format the IP to CIDR notation
        $ipExplode = explode('/', $ip);
        $ip = $ipExplode[0];
        if (!isset($ipExplode[1])) {
            $mask = 32;
        } else {
            $mask = $ipExplode[1];
        }
        return $ip . '/' . $mask;
    }
}
