<?php declare(strict_types=1);

use App\Database\DB;
use Api\Checks;
use Api\Response;
use App\Security\Firewall;

Firewall::activate();

$checks = new Checks($vars, $_POST);

// Perform the API checks
$checks->apiAdminChecks();

// Awaiting parameters
$allowedParams = ['query', 'csrf_token'];

// Check if the required parameters are present
$checks->checkParams($allowedParams, $_POST);

$query = $_POST['query'];

if (str_contains($query, 'DROP') || str_contains($query, 'TRUNCATE')) {
    echo Response::error('You cannot execute DROP or TRUNCATE queries');
    return;
}

// JOIN not developed yet
if (str_contains($query, 'JOIN')) {
    echo Response::error('You cannot execute JOIN queries as they are in development');
    return;
}

$db = new DB();

$pdo = $db->getConnection();

try {
    $stmt = $pdo->prepare($query);
} catch (\PDOException $e) {
    echo Response::error('Error preparing query: ' . $e->getMessage());
    return;
}

// In this particular situation we will be catching the exception because we want to display the error message
try {
    $stmt->execute();
} catch (\PDOException $e) {
    echo Response::error('Error executing query: ' . $e->getMessage());
    return;
}

$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
$db->__destruct();

if (str_starts_with($query, 'SELECT')) {
    if (!$data) {
        echo Response::error('No data found for this SELECT query');
    }
    // Capture the table from the query
    echo Response::success($data);
} elseif (str_starts_with($query, 'DESCRIBE') || str_starts_with($query, 'SHOW')) {
    if (!$data) {
        Response::error('No data found for this DESCRIBE or SHOW query');
    } else {
        echo Response::success($data);
    }
} else {
    if (!$data) {
        echo Response::error('No rows changed');
    } else {
        echo Response::success('Query executed successfully. ' . $stmt->rowCount() . ' rows affected');
    }
}
