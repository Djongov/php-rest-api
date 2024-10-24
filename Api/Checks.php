<?php declare(strict_types=1);

namespace Api;

use Api\Response;
use Models\ApiKey;

class Checks
{
    public function checkParams(array $allowedParams, array $providedParams): void
    {
        foreach ($allowedParams as $name) {
            if (!array_key_exists($name, $providedParams)) {
                Response::output('missing parameter \'' . $name . '\'', 400);
            }
            // need to check if the parameter is empty but not use empty() as it returns incorrect for value 0
            if ($providedParams[$name] === null || $providedParams[$name] === '') {
                Response::output('parameter \'' . $name . '\' cannot be empty', 400);
            }
        }
    }
    // Api Keys check
    public function getApiKey() : string
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        if ($apiKey === null) {
            Response::output('missing API key', 401);
        }
        $apiKeyModel = new ApiKey();
        $check = $apiKeyModel->exists($apiKey);
        var_dump($check);
        if (!$check) {
            Response::output('invalid API key', 401);
        }
        return $apiKey;
    }
}
