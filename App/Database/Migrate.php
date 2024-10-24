<?php declare(strict_types=1);

namespace App\Database;

use Components\Alerts;
use Api\Response;
use App\Utilities\IP;
use App\Utilities\General;
use PDO;
use PDOException;

class Migrate
{
    public function start() : void
    {
        // Connect to the database server without specifying the database
        try {
            $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
            if (DB_DRIVER === 'mysql') {
                $dsn = sprintf("mysql:host=%s;port=%d;charset=utf8mb4", DB_HOST, DB_PORT);
                if (defined("DB_SSL") && DB_SSL) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = DB_CA_CERT;
                }
            } elseif (DB_DRIVER === 'pgsql') {
                // Connect to a common default database, like 'postgres'
                $dsn = sprintf("pgsql:host=%s;port=%d;dbname=postgres", DB_HOST, DB_PORT);
                if (defined("DB_SSL") && DB_SSL) {
                    $dsn .= sprintf(";sslmode=require;sslrootcert=%s", DB_CA_CERT);
                }
            } elseif (DB_DRIVER === 'sqlite') {
                // Nothing to do, except pass the unsupported driver
            } else {
                throw new \Exception('Unsupported DB_DRIVER: ' . DB_DRIVER);
            }

            if (DB_DRIVER !== 'sqlite') {
                $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            Response::output('Database connection error: ' . $e->getMessage(), 400);
        }

        // Create the database if it doesn't exist
        try {
            if (DB_DRIVER === 'mysql') {
                $conn->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            } elseif (DB_DRIVER === 'pgsql') {
                // PostgreSQL does not support the 'IF NOT EXISTS' clause for CREATE DATABASE
                $query = $conn->query("SELECT 1 FROM pg_database WHERE datname = '" . DB_NAME . "'");
                if (!$query->fetch()) {
                    $conn->exec("CREATE DATABASE " . DB_NAME);
                }
            } elseif (DB_DRIVER === 'sqlite') {
                // For sqlite we need to create the database file
                $dbDir = dirname($_SERVER['DOCUMENT_ROOT']) . '/.tools';
                $dbFile = '/' . DB_NAME . '.db';
                $dbFullPath = $dbDir . $dbFile;

                if (!is_writable($dbDir)) {
                    Response::output("Error: directory $dbDir is not writable.", 400);
                }

                if (!file_exists($dbFullPath)) {
                    $conn = new PDO('sqlite:' . $dbFullPath);
                }
            }
        } catch (PDOException $e) {
            Response::output('Database creation error: ' . $e->getMessage(), 400);
        }
        // Reconnect to the newly created database
        try {
            if (DB_DRIVER === 'mysql') {
                $dsnWithDb = sprintf("mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4", DB_HOST, DB_PORT, DB_NAME);
            } elseif (DB_DRIVER === 'pgsql') {
                $dsnWithDb = sprintf("pgsql:host=%s;port=%d;dbname=%s", DB_HOST, DB_PORT, DB_NAME);
                if (defined("DB_SSL") && DB_SSL) {
                    $dsnWithDb .= sprintf(";sslmode=require;sslrootcert=%s", DB_CA_CERT);
                }
            } elseif (DB_DRIVER === 'sqlite') {
                $dsnWithDb = 'sqlite:' . dirname($_SERVER['DOCUMENT_ROOT']) . '/.tools/' . DB_NAME . '.db';
            }
            
            $conn = new PDO($dsnWithDb, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            Response::output('Database selection error: ' . $e->getMessage(), 400);
        }

        // Read and execute queries from the SQL file to create tables. We have a different migrate file for different database drivers
        $migrateFile = dirname($_SERVER['DOCUMENT_ROOT']) . '/.tools/migrate_' . DB_DRIVER . '.sql';
        $migrate = file_get_contents($migrateFile);

        try {
            // Execute multiple queries
            $conn->exec($migrate);
            

            // Let's use the current connection to create an api key in the api_keys table
            $stmt = $conn->prepare("INSERT INTO api_keys (api_key, access, created_by, note) VALUES (?,?,?,?)");

            // Generate a new API key
            try {
                $adminApiKey = randomString();
                $stmt->execute([$adminApiKey, '/*', 'System', 'System generated admin API key']);
            } catch (PDOException $e) {
                Response::output('Error creating Admin API key: ' . $e->getMessage(), 400);
            }

            Response::output('Database migration successful. Api key: ' . $adminApiKey);
        } catch (PDOException $e) {
            Response::output('Error in migrate file (' . $migrateFile . '): ' . $e->getMessage(), 400);
        }
    }
}