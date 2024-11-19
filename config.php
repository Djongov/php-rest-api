<?php declare(strict_types=1);

// set the display errors to 1
ini_set('display_errors', 1);
define('ERROR_VERBOSE', (ini_get('display_errors') == 1) ? true : false);

// version is the version={version} in the version.txt file in the root of the project
$version = trim(file_get_contents(dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . 'version.txt'));

// Now we have version={version}, let's take the value after =
$version = explode('=', $version)[1];

define('APP_NAME', 'PHP REST API');

define('API_KEY_NAME', 'X-API-KEY');

define('SYSTEM_USER_AGENT', APP_NAME . '/' . $version . ' (+https://' . $_SERVER['HTTP_HOST'] . ')');

// Do a check here if .env file exists
if (!file_exists(dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.env')) {
    die('The .env file is missing. Please create one in the root of the project or use /migrate endpoint');
}

// Load the environment variables from the .env file which resides in the root of the project
$dotenv = \Dotenv\Dotenv::createImmutable(dirname($_SERVER['DOCUMENT_ROOT']));

try {
    $dotenv->load();
} catch (\Exception $e) {
    die($e->getMessage());
}

/*

DB Settings

$_ENV is taking values from the .env file in the root of the project. If you are not using .env, hardcode them or pass them as env variables in your server

*/
$requiredEnvConstants = [
    'DB_NAME',
    'DB_DRIVER',
    'SENDGRID_ENABLED'
];

foreach ($requiredEnvConstants as $constant) {
    if (!isset($_ENV[$constant])) {
        die($constant . ' must be set in the .env file');
    }
}

define("DB_NAME", $_ENV['DB_NAME']);
define("DB_DRIVER", $_ENV['DB_DRIVER']);

if (DB_DRIVER !== 'sqlite') {
    $dbRelatedConstants = [
        'DB_SSL',
        'DB_HOST',
        'DB_USER',
        'DB_PASS',
        'DB_PORT',
    ];
    $dbRelatedConstants[] = 'DB_PORT';
    foreach ($dbRelatedConstants as $constant) {
        if (!isset($_ENV[$constant])) {
            die($constant . ' must be set in the .env file');
        }
    }
    define("DB_SSL", filter_var($_ENV['DB_SSL'], FILTER_VALIDATE_BOOLEAN));
    define("DB_HOST", $_ENV['DB_HOST']);
    define("DB_USER", $_ENV['DB_USER']);
    define("DB_PASS", $_ENV['DB_PASS']);
    define("DB_PORT", (int) $_ENV['DB_PORT']);
} else {
    // For sqlite, we only need DB_NAME and DB_DRIVER so the rest will be empty
    define("DB_SSL", false);
    define("DB_HOST", '');
    define("DB_USER", '');
    define("DB_PASS", '');
    define("DB_PORT", 0);
}


// This is the DigiCertGlobalRootCA.crt.pem file that is used to verify the SSL connection to the DB. It's located in the .tools folder
define("DB_CA_CERT", dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.tools' . DIRECTORY_SEPARATOR . 'DigiCertGlobalRootCA.crt.pem');
// This is used by the curl requests so you don't get SSL verification errors. It's located in the .tools folder
define("CURL_CERT", dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.tools' . DIRECTORY_SEPARATOR . 'cacert.pem');

// This needs to be set to what is set across the fetch requests in the javascript files. Default is the below
define('SECRET_HEADER', 'secretheader');
// Same as above
define('SECRET_HEADER_VALUE', 'badass');

/*

Mailer Settings (Sendgrid)

*/

define("SENDGRID", filter_var($_ENV['SENDGRID_ENABLED'], FILTER_VALIDATE_BOOLEAN));

if (SENDGRID) {
    if (!isset($_ENV['SENDGRID_API_KEY'])) {
        die('SENDGRID_API_KEY must be set in the .env file');
    }
    define("SENDGRID_API_KEY", $_ENV['SENDGRID_API_KEY']);
    #define("SENDGRID_TEMPLATE_ID", 'd-381e01fdce2b44c48791d7a12683a9c3');
}

define("FROM", 'admin@gamerz-bg.com');
define("FROM_NAME", 'No Reply');

/*

Charts

For displaying non-JS charts we utilize Quickchart.io. It's a free service that allows you to generate charts from a simple URL. We use it to generate the charts in the form of images which are suited for emailing them safely or display charts from the backend. However, we introduce QUICKCHART_HOST so you can host your own instance of Quickchart.io and use it instead of the public one. This is useful if you want to keep your data private and not send it to a third party service. If you want to host your own instance, you need an app hosting the docker image of Quickchart.io. You can find it here: ianw/quickchart:latest

*/

define("QUICKCHART_HOST", "quickchart.io");

// /* App checks */
$missing_extensions = [];

$required_extensions = [
    'curl',
    'openssl',
    'intl'
];

if (DB_DRIVER === 'pgsql') {
    $required_extensions[] = 'pdo_pgsql';
}

if (DB_DRIVER === 'sqlsrv') {
    $required_extensions[] = 'pdo_sqlsrv';
}

if (DB_DRIVER === 'sqlite') {
    $required_extensions[] = 'pdo_sqlite';
}

if (DB_DRIVER === 'mysql') {
    $required_extensions[] = 'pdo_mysql';
}

foreach ($required_extensions as $extension) {
    if (!extension_loaded($extension)) {
        $missing_extensions[] = $extension;
    }
}

if (!empty($missing_extensions)) {
    die('The following extensions are missing: ' . implode(', ', $missing_extensions));
}
