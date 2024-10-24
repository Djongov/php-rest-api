<?php declare(strict_types=1);

use App\Database\Migrate;
use Api\Response;

try {
    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if (defined("DB_SSL") && DB_SSL) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = DB_CA_CERT;
        }
    } elseif (DB_DRIVER === 'pgsql') {
        $dsn = 'pgsql:host=' . DB_HOST . ';dbname=' . DB_NAME;

        if (defined("DB_SSL") && DB_SSL) {
            $dsn .= ';sslmode=require;sslrootcert=' . DB_CA_CERT;
        }

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
    } elseif (DB_DRIVER === 'sqlite') {
        $dbFile = dirname($_SERVER['DOCUMENT_ROOT']) . DIRECTORY_SEPARATOR . '.tools' . DIRECTORY_SEPARATOR . DB_NAME . '.db';
        
        // Check if the database file exists before proceeding
        if (!file_exists($dbFile)) {
            throw new Exception('Database file does not exist.');
        }
        
        $dsn = 'sqlite:' . $dbFile;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
    } else {
        throw new Exception('Unsupported DB_DRIVER: ' . DB_DRIVER);
    }

    $pdo = new PDO($dsn, DB_USER ?? null, DB_PASS ?? null, $options);
    Response::output('Successfully connected to the database. Nothing to do here.');
} catch (Exception | PDOException $e) {
    // Move to migration if the connection fails or the file doesn't exist
    try {
        $install = new Migrate();
        echo $install->start();
    } catch (PDOException $e) {
        Response::output($e->getMessage(), 400);
    }
}
