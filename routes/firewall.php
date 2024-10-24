<?php declare(strict_types=1);

use Api\Response;
use Api\Checks;
use Api\Input;
use Controllers\Firewall;

// This is the API view for the firewall. It allows to add, update, delete and get IPs from the firewall

// api/firewall GET, accepts a "cidr" parameter in the query string. If no query string provided, returns all IPs in the firewall table.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // This endpoint is for creating a new local user. Cloud users are create in /auth-verify

    // We only allow either an empty GET or a GET with a "cidr" parameter
    if (!empty($_GET) && !isset($_GET['cidr'])) {
        Response::output('parameters accepted are "cidr" or empty GET', 400);
    }

    $checks = new Checks();

    //$checks->getApiKey();

    // check if cidr has been passed
    if (!isset($_GET['cidr'])) {
        $ip = '';
    } else {
        $ip = $_GET['cidr'];
    }

    $firewall = new Firewall();

    echo $firewall->get($ip);
}

// api/firewall POST, accepts form data with the "cidr" parameter and optional "comment". The user making the request is taken from the router data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $checks = new Checks();

    $apiKey = ''; // $checks->getApiKey();

    $data = (new Input())->getJsonInput();

    $checks->checkParams(['cidr'], $data);

    $comment = $data['comment'] ?? '';

    $ip = $data['cidr'];

    $firewall = new Firewall();

    echo $firewall->add($ip, $apiKey, $comment);
}

// api/firewall/{id} PUT, accepts json body wit the data to update, id in the path. The user making the request is taken from the router data
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Check if content type is json
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        Response::output('content type must be application/json', 400);
        exit();
    }

    $apiKey = $checks->getApiKey();

    // Let's catch php input stream
    $data = (new Input())->getJsonInput();

    // Also the router info should bring us the id
    if (!isset($routeInfo[2]['id'])) {
        Response::output('missing id paramter', 400);
        exit();
    }

    $id = $routeInfo[2]['id'];

    $update = new Firewall();

    echo $update->update($data, $id, $apiKey);
}

// api/firewall/{id}?csrf_token={} DELETE, empty body, param in path, csrf token in query string. The user making the request is taken from the router data
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Check if body is empty
    if ($_SERVER['CONTENT_LENGTH'] > 0) {
        Response::output('body must be empty in DELETE requests', 400);
        exit();
    }
    // Let's check if the csrf token is passed as a query string in the DELETE request
    if (!isset($_GET['csrf_token'])) {
        Response::output('missing csrf token', 401);
        exit();
    }

    // Also the router info should bring us the id
    if (!isset($routeInfo[2]['id'])) {
        Response::output('missing id parameter', 400);
        exit();
    }

    $checks = new Checks();

    $apiKey = $checks->getApiKey();

    $id = $routeInfo[2]['id'];

    $delete = new Firewall();

    echo $delete->delete($id, $apiKey);
}
