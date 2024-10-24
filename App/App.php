<?php declare(strict_types=1);

namespace App;

use App\Core\Session;
use Api\Response;

class App
{
    public function init() : void
    {
        // Start session
        Session::start();
        
        if (!isset($_SESSION['nonce'])) {
            $_SESSION['nonce'] = \App\Utilities\General::randomString(24);
        }

        // Now that we've loaded the env, let's get the site settings
        require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/config.php';

        // Include the generic functions file
        require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/functions.php';
        
        /*
            Now Routing
        */
        // Location of the routes definition
        $routesDefinition = require_once dirname($_SERVER['DOCUMENT_ROOT']) . '/routes/routes.php';
        // Ensure that $routesDefinition is a callable
        if (!is_callable($routesDefinition)) {
            throw new \RuntimeException('Invalid routes definition');
        }
        $dispatcher = \FastRoute\simpleDispatcher($routesDefinition);

        // Fetch method and URI from somewhere
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        // Strip query string (?foo=bar) and decode URI
        if ($pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);
        switch ($routeInfo[0]) {
            case \FastRoute\Dispatcher::NOT_FOUND:
                // For non-GET requests, provide an API response
                Response::output('api endpoint (' . $uri . ') not found', 404);
                break;
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                // Handle 405 Method Not Allowed
                Response::output('Method not allowed. Allowed methods are: ' . implode(',', $allowedMethods), 405);
                break;

            case \FastRoute\Dispatcher::FOUND:
                $controllerName = $routeInfo[1][0]; // the controller file
                // Include and execute the PHP file
                if (file_exists($controllerName)) {
                    include_once $controllerName;
                } else {
                    // Extract only the filename without the extension
                    $controllerName = pathinfo($controllerName, PATHINFO_FILENAME);
                    Response::output('controller file (' . $controllerName . ') not found', 404);
                }
                break;
        }
    }
}
