<?php declare(strict_types=1);

namespace Api;

class Response
{
    public static string $dateFormat = 'Y-m-d H:i:s';
    public static string $contentType = 'application/json';
    // Calculate the response time, START_TIME is defined in the public index.php file
    public static function responseTime()
    {
        return round((microtime(true) - START_TIME) * 1000);
    }
    public static function responseJson(mixed $data, int $statusCode) : string
    {
        $responseStatus = 'success';
        if ($statusCode >= 400) {
            $responseStatus = 'error';
        }
        return json_encode(
            [
                'result' => $responseStatus,
                'timestampUTC' => gmdate(self::$dateFormat),
                'serverResponseTimeMs' => self::responseTime(),
                'data' => $data
            ],
            JSON_PRETTY_PRINT
        );
    }
    public static function responseXml(mixed $data, int $statusCode): string
    {
        $responseStatus = 'success';
        if ($statusCode >= 400) {
            $responseStatus = 'error';
        }
        // Create a new XML document
        $xml = new \SimpleXMLElement('<response/>');

        // Helper function to convert data to XML
        $arrayToXml = function($data, $xml) use (&$arrayToXml) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $subnode = $xml->addChild($key);
                    $arrayToXml($value, $subnode);
                } else {
                    $xml->addChild("$key", htmlspecialchars("$value"));
                }
            }
        };
        // Populate the XML document with data
        $arrayToXml([
            'result' => $responseStatus,
            'timestampUTC' => gmdate(self::$dateFormat),
            'serverResponseTimeMs' => self::responseTime(),
            'data' => $data
        ], $xml);

        return $xml->asXML(); // Return the XML as a string
    }
    public static function decideContentType() : string
    {
        $headerValue = self::$contentType; // Default to JSON
        if (isset($_GET['format']) && $_GET['format'] === 'xml') {
            $headerValue = 'application/xml'; // Directly set the XML header value
        }
        return $headerValue; // Return the correct header value
    }
    public static function output(mixed $data, int $statusCode = 200) : string
    {
        $contentType = self::decideContentType();
        header('Content-Type: ' . $contentType);
        http_response_code($statusCode);
        // Determine the response method to use
        $responseMethod = $contentType === 'application/xml' ? 'responseXml' : 'responseJson';
    
        // Call the response method dynamically
        echo self::$responseMethod($data, $statusCode);
        exit();
    }
}
