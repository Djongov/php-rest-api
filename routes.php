<?php

declare(strict_types=1);

use FastRoute\RouteCollector;

return function (RouteCollector $router) {
    $viewsFolder = dirname($_SERVER['DOCUMENT_ROOT']) . '/Routes';

    /* API Routes */
    $router->addRoute(['GET','POST','PUT','DELETE'], '/v1/api-key[/{api-key:\w+}]', [$viewsFolder . '/v1/api-key.php']);
};
