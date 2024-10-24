<?php declare(strict_types=1);

use Api\Response;
use Api\Checks;

$checks = new Checks();

$allowedParams = ['name'];

$checks->checkParams($allowedParams, $_GET);

$checks->getApiKey();

Response::output($_GET['name']);
