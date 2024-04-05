<?php

declare(strict_types=1);

namespace Models;

use App\Database\DB;
use App\Exceptions\ApiKeyException;

class ApiKey
{
    /**
     * Check if the api key exists
     *
     * @param string $apiKey
     * @return bool
     */
    public function exist(int|string $apiKey): bool
    {
        // If it is an integer, we'll assume it's an id, otherwise we'll assume it's an api key
        $column = is_int($apiKey) ? 'id' : 'api_key';
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `api_keys` WHERE `$column`=?");
        $stmt->execute([$apiKey]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        return count($result) > 0;
    }

    /**
     * Get the api key
     *
     * @param string $apiKey
     * @return array
     * @throws ApiKeyException
     */
    public function get(string|int $apiKey = null): array {
        $db = new DB();
        $pdo = $db->getConnection();
        if ($apiKey === null) {
            // Let's pull all the api keys
            try {
                $result = $pdo->query("SELECT * FROM `api_keys`");
                $array = $result->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                throw (new ApiKeyException())->genericError($e->getMessage(), 500);
            }
            if (empty($array)) {
                throw (new ApiKeyException())->noApiKeyFound();
            } else {
                return $array;
            }
        }
        if (is_int($apiKey)) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM `api_keys` WHERE `id`=?");
                $stmt->execute([$apiKey]);
                $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                throw (new ApiKeyException())->genericError($e->getMessage(), 500);
            }
            return $result;
        }
        // Finally, let's pull the api key by the api key
        try {
            $stmt = $pdo->prepare("SELECT * FROM `api_keys` WHERE `api_key`=?");
            $stmt->execute([$apiKey]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw (new ApiKeyException())->genericError($e->getMessage() . ' and query is: ', 500);
        }

        if (count($result) === 0) {
            throw (new ApiKeyException())->apiKeyNotFound();
        }
        return $result;
    }
    /**
     * Create an api key
     *
     * @param string $createdBy The user who created the api key
     * @param string $access The access level of the api key
     * @param string $note A note about the api key
     * @return array
     */
    public function create(string $createdBy, string $access, string $note) : array
    {
        // Let's not allow empty or null values
        if (empty($createdBy) || empty($access) || empty($note)) {
            throw (new ApiKeyException())->emptyParameter('createdBy, access, or note');
        }
        $db = new DB();
        $pdo = $db->getConnection();
        $apiKey = bin2hex(random_bytes(32));
        // bind params
        $stmt = $pdo->prepare("INSERT INTO `api_keys` (`api_key`, `created_by`, `access`, `note`) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$apiKey, $createdBy, $access, $note]);
        } catch (\PDOException $e) {
            throw (new ApiKeyException())->genericError($e->getMessage(), 500);
        }
        // If affected rows are 0, throw an exception
        if (!$stmt) {
            throw (new ApiKeyException())->apiKeyNotCreated();
        } else {
            return [
                'id' => $pdo->lastInsertId(),
                'api_key' => $apiKey
            ];
        }
    }
    /**
     * Update an api key by its id
     *
     * @param array $data The data to update
     * @return int The number of rows affected
     */
    public function update(array $data) : int
    {
        // Let's analyze the data passed
        if (empty($data)) {
            throw (new ApiKeyException())->emptyData();
        }
        if (!isset($data['id'])) {
            throw (new ApiKeyException())->noParamter('id');
        }
        if (empty($data['id'])) {
            throw (new ApiKeyException())->emptyParameter('id');
        }
        if (!is_int($data['id'])) {
            throw (new ApiKeyException())->parameterNoInt('id');
        }
        if (!$this->exist($data['id'])) {
            throw (new ApiKeyException())->apiKeyNotFound();
        }

        $db = new DB();
        $pdo = $db->getConnection();

        $query = 'UPDATE `api_keys` SET ';
        $updates = [];
        // Check if all keys in $reports_array match the columns
        foreach ($data as $key => $value) {
            // Add the column to be updated to the SET clause
            $updates[] = "`$key` = ?";
        }
        // Combine the SET clauses with commas
        $query .= implode(', ', $updates);

        // Add a WHERE clause to specify which organization to update
        $query .= " WHERE `id` = ?";

        // Prepare and execute the query using queryPrepared
        $values = array_values($data);
        $values[] = $data['id']; // Add the username for the WHERE clause
        $stmt = $pdo->prepare($query);
        try {
            $stmt->execute(array_values($values));
        } catch (\PDOException $e) {
            if (ini_get('display_errors') === '1') {
                throw (new ApiKeyException())->genericError($e->getMessage(), 500);
            }
            throw new ApiKeyException('failed to update API key', 500);
        }
        return $stmt->rowCount();
    }
    /**
     * Delete an api key by its id or api key
     *
     * @param string|int $apiKey The api key or id to delete
     * @return int
     */
    public function delete(string|int $apiKey) : int
    {
        $column = is_int($apiKey) ? 'id' : 'api_key';
        $db = new DB();
        $pdo = $db->getConnection();
        // Let's build the query
        $query = "DELETE FROM `api_keys` WHERE `$column`=?";
        $stmt = $pdo->prepare($query);
        try {
            $stmt->execute([$apiKey]);
        } catch (\PDOException $e) {
            if (ini_get('display_errors') === '1') {
                throw (new ApiKeyException())->genericError($e->getMessage(), 500);
            } else {
                throw (new ApiKeyException())->genericError('failed to delete api key', 500);
            }
        }
        if ($stmt->rowCount() === 0) {
            throw (new ApiKeyException())->apiKeyNotFound();
        }
        return $stmt->rowCount();
    }
}
