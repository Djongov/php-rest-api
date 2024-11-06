<?php declare(strict_types=1);

use Api\Response;
use Api\Checks;
use Api\Input;
use Controllers\ApiKey;
use Controllers\HttpHandler;

// This is the API view for the firewall. It allows to add, update, delete and get IPs from the firewall

// api/firewall GET, accepts a "api-key" parameter in the query string. If no query string provided, returns all IPs in the firewall table.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // $mainParam = 'api-key';
    // // This endpoint is for creating a new local user. Cloud users are create in /auth-verify

    // $checks = new Checks();

    // // Let's check if API key is present
    // $checks->getApiKey();

    // // check if api-key has been passed, if not pass empty string (not null)
    // if (!$_GET) {
    //     $paramValue = null;
    // } elseif (isset($_GET[$mainParam])) {
    //     if (!$_GET[$mainParam]) {
    //         Response::output($mainParam . ' parameter cannot be empty', 400);
    //     }
    //     $paramValue = $_GET[$mainParam];
    // } else {
    //     Response::output('missing ' . $mainParam . ' parameter', 400);
    // }

    // $controllerClass = new ApiKey();

    // // Let's see if we have any optional sorting and filtering parameters
    // $optionalParams = $checks->getGetSortingAndFilteringParams();

    // $response = $controllerClass->get($paramValue, ...$optionalParams);

    // Response::output($response['data'] ?? $response['error'], (int) $response['status']);

    $id = $routeInfo[2]['id'] ?? null;

    HttpHandler::handleGetRequestWithPath($id, 'Controllers\ApiKey');
}

// api/firewall POST, accepts form data with the "api-key" parameter and optional "comment". The user making the request is taken from the router data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $checks = new Checks();

    // Let's check if API key is present
    $apiKey = $checks->getApiKey();
    // Let's catch php input stream as we expect json
    $data = (new Input())->getJsonInput();

    $checks->checkParams(['access', 'note'], $data);

    $addController = new ApiKey();

    $response = $addController->add($data);

    Response::output($response['data'] ?? $response['error'], (int) $response['status']);
}

// api/firewall/{id} PATCH, accepts json body wit the data to update, id in the path. The user making the request is taken from the router data
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    $checks = new Checks();

    $apiKey = $checks->getApiKey();

    // Let's catch php input stream
    $data = (new Input())->getJsonInput();

    // Also the router info should bring us the id
    if (!isset($routeInfo[2]['id'])) {
        Response::output('missing id paramter', 400);
    }

    $id = (int) $routeInfo[2]['id'];

    $update = new ApiKey();

    $response = $update->update($data, $id, $apiKey);

    Response::output($response['data'] ?? $response['error'], (int) $response['status']);
}

// api/firewall/{id} DELETE, empty body, param in path
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Check if body is empty
    if ($_SERVER['CONTENT_LENGTH'] > 0) {
        Response::output('body must be empty in DELETE requests', 400);
    }

    // Also the router info should bring us the id
    if (!isset($routeInfo[2]['id'])) {
        Response::output('missing id parameter', 400);
    }

    $checks = new Checks();

    $apiKey = $checks->getApiKey();

    $id = (int) $routeInfo[2]['id'];

    $delete = new ApiKey();

    $response = $delete->delete($id, $apiKey);

    if ($response['status'] === 204) {
        Response::output('', 204);
    } else {
        Response::output($response['data'] ?? $response['error'], (int) $response['status']);
    }
}
