<?php declare(strict_types=1);

use Api\Response;
use Api\Checks;
use Controllers\HttpHandler;
use Controllers\AccessLog;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = $routeInfo[2]['request_id'] ?? null;

    HttpHandler::handleGetRequestWithPath($id, 'Controllers\AccessLog');
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Check if body is empty
    if ($_SERVER['CONTENT_LENGTH'] > 0) {
        Response::output('body must be empty in DELETE requests', 400);
    }

    // Also the router info should bring us the id
    if (!isset($routeInfo[2]['request_id'])) {
        Response::output('missing id parameter', 400);
    }

    $checks = new Checks();

    $apiKey = $checks->getApiKey();

    $id = (int) $routeInfo[2]['request_id'];

    $delete = new AccessLog();

    $response = $delete->delete($id, $apiKey);

    if ($response['status'] === 204) {
        Response::output('', 204);
    } else {
        Response::output($response['data'] ?? $response['error'], (int) $response['status']);
    }
}