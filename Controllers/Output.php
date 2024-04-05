<?php

declare(strict_types=1);

namespace Controllers;

use App\General;

class Output
{
    public static function error(string|array $data, int $status_code): void
    {
        // Check if error has already been handled
        if (defined('ERROR_HANDLED')) {
            // If it has, exit to prevent infinite loop
            exit;
        }

        // Define a constant to mark that error is being handled
        define('ERROR_HANDLED', true);

        $response = self::responseType();
        header('Content-Type: application/' . $response);
        http_response_code($status_code);

        $reponseArray = [
            'result' => 'error',
            'timestampUTC' => gmdate("Y-m-d H:i:s"),
            'serverResponseTimeMs' => self::responseTime(),
            'data' => $data
        ];

        // Handle error response based on response type
        if ($response === 'json') {
            echo json_encode($reponseArray, JSON_PRETTY_PRINT);
        } else {
            $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
            General::arrayToXml($reponseArray, $xml_data);
            echo $xml_data->asXML();
        }

        // Exit after handling error
        exit;
    }
    public static function success(string|array $data, int $status_code = 200) : string
    {
        $response = self::responseType();
        header('Content-Type: application/' . $response);
        http_response_code($status_code);
        self::logRequest($status_code);
        if ($response === 'json') {
            return json_encode(
                [
                    'result' => 'success',
                    'timestampUTC' => gmdate("Y-m-d H:i:s"),
                    'serverResponseTimeMs' => self::responseTime(),
                    'data' => $data
                ],
                JSON_PRETTY_PRINT
            );
        } else {
            // We'll add the XML output here
            $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><data></data>');
            General::arrayToXml(
                [
                    'result' => 'success',
                    'timestampUTC' => gmdate("Y-m-d H:i:s"),
                    'serverResponseTimeMs' => self::responseTime(),
                    'data' => $data
                ],
                $xml_data
            );
            return $xml_data->asXML();
        }
    }
    public static function responseTime() : float
    {
        return round((microtime(true) - START_TIME) * 1000);
    }
    public static function responseType() : string
    {
        $type = 'json';
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return $type;
        }
        if ($_SERVER['HTTP_ACCEPT'] === 'application/xml') {
            $type = 'xml';
        }
        return $type;
    }
    public static function logRequest($status)
    {
        $apiChecks = new \Controllers\ApiChecks();
        $apiKey = $apiChecks->apiKeyHeaderGet();
        $ip = General::currentIP();
        $browser = General::currentBrowser();
        $method = $_SERVER['REQUEST_METHOD'];
        $queryString = $_SERVER['QUERY_STRING'];
        $path = strtok($_SERVER['REQUEST_URI'], '?');

        $db = new \App\Database\DB();
        $pdo = $db->getConnection();
        $query = "INSERT INTO `request_log` (`api_key`, `path`, `query_string`, `user_agent`, `status`, `method`, `client_ip`) VALUES (?,?,?,?,?,?,?)";
        $stmt = $pdo->prepare($query);
        try {
            $stmt->execute([$apiKey, $path, $queryString, $browser, $status, $method, $ip]);
        } catch (\PDOException $e) {
            if (ini_get('display_errors') === '1') {
                self::error($e->getMessage(), 500);
            } else {
                self::error('internal server error', 500);
            }
        }
    }
}
