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
    public function create(string $username, string $access, string $note) : string
    {
        $apiKeyModel = new ModelsApiKey();
        try {
            return $apiKeyModel->create($username, $access, $note);
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
    public function update(array $data) : array
    {
        $apiKeyModel = new ModelsApiKey();
        try {
            $update = $apiKeyModel->update($data);
            return $update;
        } catch (\Exception $e) {
            Output::error($e->getMessage(), $e->getCode());
        }
    }
}
