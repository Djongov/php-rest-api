<?php

declare(strict_types=1);

namespace Controllers;

use App\General;

class Output
{
    public static function error(string|array $data, int $status_code): void
    {
        // Let's decide what the expected response is. If Accept is not set, we'll default to JSON
        $response = self::responseType();
        header('Content-Type: application/' . $response);
        http_response_code($status_code);
        if ($response === 'json') {
            echo json_encode(
                [
                    'result' => 'error',
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
                    'result' => 'error',
                    'timestampUTC' => gmdate("Y-m-d H:i:s"),
                    'serverResponseTimeMs' => self::responseTime(),
                    'data' => $data
                ],
                $xml_data
            );
            echo $xml_data->asXML();
        }
        self::logRequest();
        die();
    }
    public static function success(string|array $data) : mixed
    {
        $response = self::responseType();
        header('Content-Type: application/' . $response);
        self::logRequest();
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
    public static function responseTime()
    {
        return round((microtime(true) - START_TIME) * 1000);
    }
    public static function responseType() : string
    {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return 'json';
        } else {
            if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
                return 'json';
            } elseif ($_SERVER['HTTP_ACCEPT'] === 'application/xml') {
                return 'xml';
            } else {
                return 'json';
            }
        }
    }
    public static function logRequest()
    {
        $apiChecks = new \Controllers\ApiChecks();
        $apiKey = $apiChecks->apiKeyHeaderGet();
        $ip = General::currentIP();
        $browser = General::currentBrowser();
        $method = $_SERVER['REQUEST_METHOD'];
        $status = http_response_code();
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
