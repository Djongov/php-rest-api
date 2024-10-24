<?php declare(strict_types=1);

// Define the start time of the request, it can be used to calculate the time it took to process the request later
define("START_TIME", microtime(true));

// Path to the composer autoload file
$path = dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

if (file_exists($path)) {
    require_once $path;
} else {
    die('<b>' . $path . '</b> file not found. You need to run <b>composer update</b>');
}

function dd()
{
    array_map(function ($x) {
        var_dump($x);
    }, func_get_args());
    die;
}

use App\App;

// Initialize the app
$app = new App();

// Run the app
$app->init();
