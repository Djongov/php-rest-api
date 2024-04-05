<?php

declare(strict_types=1);

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
    public function update(array $data): bool
    {
        $apiKeyModel = new ModelsApiKey();
        try {
            return (bool) $apiKeyModel->update($data) ? true : false;
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
    public function delete(int|string $apiKey) : bool
    {
        $apiKeyModel = new ModelsApiKey();
        try {
            return (bool) $apiKeyModel->delete($apiKey) ? true : false;
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
}
