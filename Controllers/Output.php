<?php

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
        die();
    }
    public static function success(string|array $data) : mixed
    {
        $response = self::responseType();
        header('Content-Type: application/' . $response);
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
}
