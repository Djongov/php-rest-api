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
    public function exists(int|string $apiKey): bool
    {
        // If it is an integer, we'll assume it's an id, otherwise we'll assume it's an api key
        $column = is_int($apiKey) ? 'id' : 'api_key';
        try {
            $db = new DB();
            $pdo = $db->getConnection();
            $stmt = $pdo->prepare("SELECT * FROM `api_keys` WHERE `$column`=?");
            $stmt->execute([$apiKey]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw (new ApiKeyException())->genericError($e->getMessage(), 500);
        }
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
                throw (new ApiKeyException())->noApiKeysFound();
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
            throw (new ApiKeyException())->genericError('createdBy, access, and note cannot be empty', 400);
        }
        $db = new DB();
        $pdo = $db->getConnection();
        $apiKey = bin2hex(random_bytes(32));
        try {
            $stmt = $pdo->prepare("INSERT INTO `api_keys` (`api_key`, `created_by`, `access`, `note`) VALUES (?, ?, ?, ?)");
            // bind params

            $stmt->execute([$apiKey, $createdBy, $access, $note]);
            // Let's find out the last inserted id
            
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
        // // Let's analyze the data passed
        // if (empty($data)) {
        //     throw (new ApiKeyException())->genericError('no data provided', 400);
        // }
        // if (!isset($data['id'])) {
        //     throw (new ApiKeyException())->genericError('no id provided', 400);
        // }
        // if (!is_int($data['id'])) {
        //     throw (new ApiKeyException())->genericError('id must be an integer', 400);
        // }
        if (!$this->exists($data['id'])) {
            throw (new ApiKeyException())->apiKeyNotFound();
        }
        //$db = new DB();
        //$pdo = $db->getConnection();

        // // Let's build the query
        // $query = "UPDATE `api_keys` SET ";
        // $query .= implode(', ', array_map(fn($key) => "$key=?", array_keys($data)));
        // $query .= " WHERE `id`=?";
        // try {
        //     $stmt = $pdo->prepare($query);
        //     $stmt->execute(array_values($data));
        //     return $stmt->rowCount();
        // } catch (\PDOException $e) {
        //     // Handle database error appropriately
        //     throw new ApiKeyException('Failed to update API key: ' . $e->getMessage(), 500);
        // }
    }
    /**
     * Delete an api key by its id
     *
     * @param string $apiKey
     * @return bool
     */
    public function delete(int $id) : mixed
    {
        $db = new DB();
        $pdo = $db->getConnection();

        // Let's build the query
        $query = "DELETE FROM `api_keys` WHERE `id`=?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}
