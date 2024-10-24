<?php declare(strict_types=1);

use Controllers\Api\Output;
use Api\Checks;
use App\Database\DB;
use App\Security\Firewall;

Firewall::activate();

$checks = new Checks($vars, $_POST);

// Perform the API checks
$checks->apiAdminChecks();

// Awaiting parameters
$allowedParams = ['domain', 'csrf_token'];

// Check if the required parameters are present
$checks->checkParams($allowedParams, $_POST);

// Check if domain is valid
if (!filter_var($_POST['domain'], FILTER_VALIDATE_DOMAIN)) {
    Output::error('Invalid domain');
}

// Strip the csrf_token from the POST array and check if we have such a column in the firewall table
unset($_POST['csrf_token']);

$db = new DB();

$pdo = $db->getConnection();

$stmt = $pdo->prepare('SELECT id FROM csp_approved_domains WHERE domain = ?');

$stmt->execute([$_POST['domain']]);

if ($stmt->rowCount() > 0) {
    echo Output::error('Domain already in DB');
    return;
}

$stmt = $pdo->prepare('INSERT INTO csp_approved_domains (domain, created_by) VALUES (?,?)');

$stmt->execute([$_POST['domain'], $vars['usernameArray']['username']]);

if ($stmt->rowCount() === 1) {
    echo Output::success('Domain added');
} else {
    echo Output::error('Domain not added');
}
