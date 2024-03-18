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
        $db = new DB();
        if (is_int($apiKey)) {
            try {
                $result = $db->queryPrepared("SELECT * FROM `api_keys` WHERE `id` = :id", ['id' => $apiKey]);
            } catch (\PDOException $e) {
                throw (new ApiKeyException())->genericError($e->getMessage(), 500);
            }
            return $result->rowCount() === 1;
        }
        try {
            $result = $db->queryPrepared("SELECT * FROM `api_keys` WHERE `api_key` = :api_key", ['api_key' => $apiKey]);
        } catch (\PDOException $e) {
            throw (new ApiKeyException())->genericError($e->getMessage(), 500);
        }
        return $result->rowCount() === 1;
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
        if ($apiKey === null) {
            // Let's pull all the api keys
            try {
                $result = $db->query("SELECT * FROM `api_keys`");
            } catch (\PDOException $e) {
                throw (new ApiKeyException())->genericError($e->getMessage(), 500);
            }
            if (empty($result)) {
                throw (new ApiKeyException())->noApiKeysFound();
            } else {
                return $result;
            }
        }
        if (is_int($apiKey)) {
            try {
                $result = $db->queryPrepared("SELECT * FROM `api_keys` WHERE `id` = :id", ['id' => $apiKey]);
            } catch (\PDOException $e) {
                throw (new ApiKeyException())->genericError($e->getMessage(), 500);
            }
            return $result->fetchAll(\PDO::FETCH_ASSOC);
        }
        // Finally, let's pull the api key by the api key
        try {
            $result = $db->queryPrepared("SELECT * FROM `api_keys` WHERE `api_key` = :api_key", ['api_key' => $apiKey]);
        } catch (\PDOException $e) {
            throw (new ApiKeyException())->genericError($e->getMessage(), 500);
        }

        if ($result->rowCount() === 0) {
            throw (new ApiKeyException())->apiKeyNotFound();
        }
        return $result->fetchAll(\PDO::FETCH_ASSOC);
    }
    /**
     * Create an api key
     *
     * @param string $apiKey
     * @return int
     */
    public function create(string $createdBy, string $access, string $note) : string
    {
        // Let's not allow empty or null values
        if (empty($createdBy) || empty($access) || empty($note)) {
            throw (new ApiKeyException())->genericError('createdBy, access, and note cannot be empty', 400);
        }
        $db = new DB();
        $apiKey = bin2hex(random_bytes(32));
        try {
            $result = $db->queryPrepared("INSERT INTO `api_keys` (`api_key`, `created_by`, `note`, `access`) VALUES (:api_key, :created_by, :note, :access)", ['api_key' => $apiKey, 'created_by' => $createdBy, 'note' => $note, 'access' => $access]);
        } catch (\PDOException $e) {
            throw (new ApiKeyException())->genericError($e->getMessage(), 500);
        }
        // If affected rows are 0, throw an exception
        if ($result->rowCount() === 0) {
            throw (new ApiKeyException())->apiKeyNotCreated();
        } else {
            return $apiKey;
        }
    }
    /**
     * Update an api key by its id
     *
     * @param string $apiKey
     * @return bool
     */
    public function update(array $data) : mixed
    {
        // Let's analyze the data passed
        if (empty($data)) {
            throw (new ApiKeyException())->genericError('no data provided', 400);
        }
        if (!isset($data['id'])) {
            throw (new ApiKeyException())->genericError('no id provided', 400);
        }
        if (!is_int($data['id'])) {
            throw (new ApiKeyException())->genericError('id must be an integer', 400);
        }
        $db = new DB();

        // Let's check if the api key exists
        if (!$this->exists($data['id'])) {
            throw (new ApiKeyException())->apiKeyNotFound();
        }

        return $data;
    }
}
