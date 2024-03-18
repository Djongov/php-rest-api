<?php

namespace Controllers;

use Controllers\Output;

class ApiChecks
{
    public function checkSecretHeader(): void
    {
        // get all headers
        $headers = getallheaders();
        $lowercaseHeaders = array_change_key_case($headers, CASE_LOWER);
        // Check if the secret header is set
        if (!isset($lowercaseHeaders[SECRET_HEADER])) {
            Output::error('Missing required header', 401);
        }
        // Check if the secret header is correct
        if ($lowercaseHeaders[SECRET_HEADER] !== SECRET_HEADER_VALUE) {
            Output::error('Invalid required header value', 401);
        }
    }
    public function apiKeyExist(): void
    {
        // The X-API-KEY header is required for all requests
        $headers = getallheaders();
        $lowercaseHeaders = array_change_key_case($headers, CASE_LOWER);
        if (!isset($lowercaseHeaders['x-api-key'])) {
            Output::error('Missing api key', 401);
        }
        // Now check if empty
        if (empty($lowercaseHeaders['x-api-key'])) {
            Output::error('Empty api key', 401);
        }
    }
    public function apiKeyHeaderGet(): string
    {
        // Check if the api key exists
        $this->apiKeyExist();
        $headers = getallheaders();
        $lowercaseHeaders = array_change_key_case($headers, CASE_LOWER);
        return $lowercaseHeaders['x-api-key'];
    }
    public function checkApiKeyHeader(string $uri): void
    {
        // Check if the api key exists
        $this->apiKeyExist();
        // Check if the api key is valid
        $apiKey = $this->apiKeyHeaderGet($uri);
        // Now check if the key exists in the database

    }
}
