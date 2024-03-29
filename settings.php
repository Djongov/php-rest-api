<?php

declare(strict_types=1);

ini_set('display_errors', 1);

if (isset($_ENV['DB_SSL'])) {
    if (filter_var($_ENV['DB_SSL'], FILTER_VALIDATE_BOOLEAN)) {
        define("DB_SSL", $_ENV['DB_SSL']);
    } else {
        define("DB_SSL", false);
    }
}
define("DB_DRIVER", $_ENV['DB_DRIVER']);
define("DB_HOST", $_ENV['DB_HOST']);
define("DB_USER", $_ENV['DB_USER']);
define("DB_PASS", $_ENV['DB_PASS']);
define("DB_NAME", $_ENV['DB_NAME']);

// This is the DigiCertGlobalRootCA.crt.pem file that is used to verify the SSL connection to the DB. It's located in the .tools folder
define("CA_CERT", dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.tools' . DIRECTORY_SEPARATOR . 'DigiCertGlobalRootCA.crt.pem');
// This is used by the curl requests so you don't get SSL verification errors. It's located in the .tools folder
define("CURL_CERT", dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.tools' . DIRECTORY_SEPARATOR . 'cacert.pem');

// This needs to be set to what is set across the fetch requests in the javascript files. Default is the below
define('SECRET_HEADER', 'secretheader');
// Same as above
define('SECRET_HEADER_VALUE', 'badass');
// Name of the api key header
define("API_KEY_NAME", 'X-API-KEY');
// This is the path that the api key is checked against. If the path is in this array, the api key is not checked
define("SKIP_AUTH_PATHS", ['/v1/api-key', '/v1/api-key/*']);
/*

Mailer Settings (Sendgrid)

*/

define("SENDGRID", false);
if (SENDGRID) {
    define("SENDGRID_API_KEY", $_ENV['SENDGRID_API_KEY']);
    #define("SENDGRID_TEMPLATE_ID", 'd-381e01fdce2b44c48791d7a12683a9c3');
}

define("FROM", 'admin@gamerz-bg.com');
define("FROM_NAME", 'No Reply');

$missing_extensions = [];

$required_extensions = [
    'curl',
    'openssl'
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
