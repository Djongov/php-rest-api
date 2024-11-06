<?php declare(strict_types=1);

namespace Controllers;

use Api\Checks;
use Api\Response;

class HttpHandler
{
    public static function handleGetRequestWithParam(string|int $mainParam, string $controllerName) : void
    {
        // This endpoint is for creating a new local user. Cloud users are created in /auth-verify

        $checks = new Checks();

        // Let's check if the API key is present
        $checks->getApiKey();

        // Check sorting and filtering params
        $sortingParams = $checks->checkGetSortingAndFilteringParams();

        // Initialize paramValue as null
        $paramValue = null;

        // Check if any GET parameters are present
        if (!empty($_GET)) {
            // Check if the expected mainParam is set
            if (array_key_exists($mainParam, $_GET)) {
                // Check if the mainParam is not empty
                if (!empty($_GET[$mainParam])) {
                    $paramValue = $_GET[$mainParam];
                } else {
                    Response::output($mainParam . ' parameter cannot be empty', 400);
                }
            } else {
                // Return error if other parameters are present
                $unexpectedParams = array_diff_key($_GET, [$mainParam => null]);
                foreach ($sortingParams as $param => $value) {
                    unset($unexpectedParams[$param]);
                    unset($unexpectedParams['format']);
                }
                if (!empty($unexpectedParams) && $sortingParams !== array_keys($unexpectedParams)) {
                    Response::output('Unexpected parameters: ' . implode(', ', array_keys($unexpectedParams)), 400);
                }
            }
        }

        // Create an instance of the controller
        $controllerClass = new $controllerName();

        // Call the get method with the paramValue and sorting parameters
        $response = $controllerClass->get($paramValue, ...$sortingParams);

        // Output the response
        Response::output($response['data'] ?? $response['error'], (int) $response['status']);
    }
    public static function handleGetRequestWithPath(string|int $mainParam = null, string $controllerName) : void
    {
        // This endpoint is for creating a new local user. Cloud users are created in /auth-verify

        $checks = new Checks();

        // Let's check if the API key is present
        $checks->getApiKey();

        // Check sorting and filtering params
        $sortingParams = $checks->checkGetSortingAndFilteringParams();
    
        // Return error if other parameters are present
        $unexpectedParams = array_diff_key($_GET, [$mainParam => null]);
        foreach ($sortingParams as $param => $value) {
            unset($unexpectedParams[$param]);
            unset($unexpectedParams['format']);
        }
        if (!empty($unexpectedParams) && $sortingParams !== array_keys($unexpectedParams)) {
            Response::output('Unexpected parameters: ' . implode(', ', array_keys($unexpectedParams)), 400);
        }

        // Create an instance of the controller
        $controllerClass = new $controllerName();

        // Call the get method with the paramValue and sorting parameters
        $response = $controllerClass->get($mainParam, ...$sortingParams);

        // Output the response
        Response::output($response['data'] ?? $response['error'], (int) $response['status']);
    }
}
