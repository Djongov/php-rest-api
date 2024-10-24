<?php declare(strict_types=1);

use Api\Checks;
use Controllers\Api\Output;

$checks = new Checks($vars, $_POST);

$checks->apiChecksNoCSRFHeader(false);

if (isset($_POST['data'])) {
    $data = $_POST['data'];
} else {
    $data = '';
}

$data = json_decode($_POST['data'], true); // Decode JSON string into associative array

if ($data === null) {
    // Handle JSON decoding error
    error_log('JSON Decode Error: ' . json_last_error_msg());
    Output::error('JSON Decode Error: ' . json_last_error_msg());
}

if (count($data) === 0) {
    Output::error('No data to export');
}


if (isset($_POST['type'])) {
    $type = htmlspecialchars($_POST['type']);
} else {
    $type = '';
}

// file name for download
$fileName = $type . "-data-" . date('Y-m-d-H-i-s') . ".csv";

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $fileName . '"');

$output = fopen('php://output', 'w');

// Assuming that the first element of $data contains the keys (column names)

$columnNames = array_keys(reset($data));
fputcsv($output, $columnNames, ";");

foreach ($data as $row) {
    fputcsv($output, $row, ";");
}


fclose($output);
exit;
