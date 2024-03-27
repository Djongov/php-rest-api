<?php

declare(strict_types=1);

namespace Controllers;

use Controllers\Output;
use Models\ApiKey;
use App\General;

class ApiChecks
{
    public function checkSecretHeader(): void
    {
        // get all headers
        $headers = getallheaders();
        $lowercaseHeaders = array_change_key_case($headers, CASE_LOWER);
        // Check if the secret header is set
        if (!isset($lowercaseHeaders[SECRET_HEADER])) {
            Output::error('missing required header', 401);
        }
        // Check if the secret header is correct
        if ($lowercaseHeaders[SECRET_HEADER] !== SECRET_HEADER_VALUE) {
            Output::error('invalid required header value', 401);
        }
    }
    public function apiKeyExist(): bool
    {
        $headers = getallheaders();
        $lowercaseHeaders = array_change_key_case($headers, CASE_LOWER);
        $apiKey = strtolower(API_KEY_NAME);
        return isset($lowercaseHeaders[$apiKey]);
    }
    public function apiKeyHeaderGet(): string
    {
        // Check if the api key exists
        if (!$this->apiKeyExist()) {
            Output::error('missing api key header ' . API_KEY_NAME, 401);
        }
        $headers = getallheaders();
        $lowercaseHeaders = array_change_key_case($headers, CASE_LOWER);
        $apiKey = strtolower(API_KEY_NAME);
        return $lowercaseHeaders[$apiKey];
    }
    public function checkApiKeyHeader(): void
    {
        // Check if the api key is valid
        $apiKey = $this->apiKeyHeaderGet();
        // Now check if the key exists in the database
        $apiKeyModel = new ApiKey();
        try {
            $apiKeyDb = $apiKeyModel->get($apiKey);
        } catch (\Exception $e) {
            Output::error($e->getMessage(), 401);
        }
        if (empty($apiKey)) {
            Output::error('empty api key', 401);
        }
        // check if the access is allowed on this uri
        $allowedPath = $apiKeyDb[0]['access'];
        if (!General::matchRequestURIVsAccess($allowedPath)) {
            Output::error('api key is not allowed on this path', 401);
        }
    }
}
