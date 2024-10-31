<?php declare(strict_types=1);

use FastRoute\RouteCollector;

return function (RouteCollector $router) {
    $routesDirectory = dirname($_SERVER['DOCUMENT_ROOT']) . '/routes';
    /* Main API Routes */
    $router->addRoute('GET', '/', [$routesDirectory . '/example.php']);
    $router->addRoute('GET', '/migrate', [$routesDirectory . '/migrate.php']);
    $router->addRoute(['GET','PUT','DELETE','POST'], '/user[/{id:[^/]+}]', [$routesDirectory . '/user.php']);
    /* Firewall API Routes */
    $router->addRoute(['GET','PATCH','DELETE','POST'], '/firewall[/{id:\d+}]', [$routesDirectory . '/firewall.php']);
    $router->addRoute('POST', '/mail/send', [$routesDirectory . '/mail/send.php']);
    /* Current IP Routes */
    $router->addRoute('GET', '/ip', [$routesDirectory . '/ip.php']);
};
