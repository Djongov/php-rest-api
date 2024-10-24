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
$fileName = $type . "-logs-data-" . date('Y-m-d-H-i-s') . ".tsv";

$excelData = '';
$unique_heads = [];


$unique_heads = array_keys(reset($data));


// Display column names as first row
$excelData = implode("\t", $unique_heads) . "\n";

// And the actual data under the columns
foreach ($data as $value) {
    $row = [];
    foreach ($unique_heads as $key) {
        $row[] = $value[$key] ?? '';
    }
    $excelData .= implode("\t", $row) . "\n";
}

// Headers for download
header("Content-Type: text/tab-separated-values");
header("Content-Disposition: attachment; filename=\"$fileName\"");

// Render excel data
echo $excelData;
exit;
