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
        $apiKey = getApiKeyFromHeaders();
        if (!$apiKey) {
            Response::output('missing API key', 401);
        }
        $apiKeyModel = new ApiKey();
        $check = $apiKeyModel->exists($apiKey);
        if (!$check) {
            Response::output('invalid API key. Got:' . $apiKey, 401);
        }
        return $apiKey;
    }
    // Check GET sorting and filtering parameters
    public function getGetSortingAndFilteringParams(): array
    {
        $sort = (isset($_GET['sort'])) ? $_GET['sort'] : null;
        $limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int) $_GET['limit'] : null;
        $orderBy = (isset($_GET['orderBy'])) ? $_GET['orderBy'] : null;
        return [
            'sort' => $sort,
            'limit' => $limit,
            'orderBy' => $orderBy
        ];
    }
    // Check if sorting and filtering parameters are present and return what is present
    public function checkGetSortingAndFilteringParams() : array
    {
        $array = $this->getGetSortingAndFilteringParams();

        // Remove nulls
        $array = array_filter($array, function ($value) {
            return $value !== null;
        });
        return $array;

    }
}
