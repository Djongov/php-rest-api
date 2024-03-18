<?php
define("START_TIME", microtime(true));
// Load the autoloaders, local and composer
require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/vendor/autoload.php';

use Controllers\Output;
use Controllers\ApiChecks;

$dotenv = \Dotenv\Dotenv::createImmutable(dirname($_SERVER['DOCUMENT_ROOT']));
$dotenv->load();

require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/settings.php';

$routesDefinition = require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/routes.php';
// Ensure that $routesDefinition is a callable
if (!is_callable($routesDefinition)) {
    throw new \RuntimeException('Invalid routes definition');
}
$dispatcher = \FastRoute\simpleDispatcher($routesDefinition);

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case \FastRoute\Dispatcher::NOT_FOUND:
        Output::error('api endpoint (' . $uri . ') not found', 404);
        break;
    case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // Handle 405 Method Not Allowed
        Output::error('Method not allowed. Allowed methods are: ' . implode(',', $allowedMethods), 405);
        break;
    case \FastRoute\Dispatcher::FOUND:
        $controllerName = $routeInfo[1][0]; // the controller file
        if (file_exists($controllerName)) {
            $apiChecks = new ApiChecks();
            // Check if the secret header is set and if it's correct
            $apiChecks->checkSecretHeader();
            $apiChecks->checkApiKeyHeader($uri);
            // Check if Authorization

            include_once $controllerName;
        } else {
            throw new \Exception('Controller file (' . $controllerName . ') not found');
        }
        break;
    // Default case (should never happen)
    default:
        Output::error('Unknown error', 500);
        break;
}
