<?php

namespace Controllers;

use Models\ApiKey as ModelsApiKey;

class ApiKey
{
    public function get(int|string $apiKey = null): array
    {
        // Decide whether the api key is an integer (id) or a string (api_key), because integers may be passed as strings
        if ($apiKey !== null) {
            ctype_digit((string) $apiKey) ? $apiKey = (int) $apiKey : $apiKey = (string) $apiKey;
        }
        $apiKeyModel = new ModelsApiKey();
        try {
            return $apiKeyModel->get($apiKey);
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
    public function create(string $username, string $access, string $note) : array
    {
        $apiKeyModel = new ModelsApiKey();
        try {
            return $apiKeyModel->create($username, $access, $note);
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
    public function update(array $data) : string
    {
        $apiKeyModel = new ModelsApiKey();
        try {
            $rowsAffected = $apiKeyModel->update($data);
            if ($rowsAffected === 0) {
                return 'No rows affected';
            } else {
                return $rowsAffected;
            }
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
    public function delete(int $id) : string
    {
        $apiKeyModel = new ModelsApiKey();
        try {
            if ($apiKeyModel->delete($id)) {
                return 'api key with id ' . $id . ' deleted';
            } else {
                return 'No rows affected';
            }
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
}
