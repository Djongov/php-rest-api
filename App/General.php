<?php

declare(strict_types=1);

namespace App;

class General
{
    public static function fullUri(): string
    {
        return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }
    public static function currentIP(): string
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $client_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $client_ip = str_replace(strstr($_SERVER['HTTP_CLIENT_IP'], ':'), '', $_SERVER['HTTP_CLIENT_IP']);
        } else {
            // or just use the normal remote addr
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $client_ip;
    }
    public static function currentBrowser()
    {
        return $_SERVER['HTTP_USER_AGENT'];
    }
    public static function matchRequestURI(array $uris) : bool
    {
        // Get the current request URI
        $currentURI = $_SERVER['REQUEST_URI'];

        // Loop through the array of URIs
        foreach ($uris as $uri) {
            // If the URI in the array ends with a wildcard character (*), use strncmp to check if the current URI matches the beginning part of the URI in the array
            if (substr($uri, -1) === '*' && strncmp($currentURI, rtrim($uri, '*'), strlen(rtrim($uri, '*'))) === 0) {
                return true; // Match found
            } elseif ($currentURI === $uri) {
                return true; // Exact match found
            }
        }

        return false; // No match found
    }
    // This method catches if current uri is in the array of uris including wildcards
    public static function matchRequestURIVsAccess(string $access) : bool
    {
        // Get the current request URI
        $uri = $_SERVER['REQUEST_URI'];

        // Escape special characters in the pattern
        $pattern = preg_quote($access, '/');

        // Replace '*' with a regex wildcard
        $pattern = str_replace('\*', '.*', $pattern);

        // Add regex delimiters and anchors
        $pattern = '/^' . $pattern . '$/';

        // Check if the URI matches the pattern
        return (bool) preg_match($pattern, $uri, $matches);
    }
    // Array to xml
    public static function arrayToXml(array $data, \SimpleXMLElement $xml_data): \SimpleXMLElement
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($key === 'data') {
                    // If the key is 'data', add its child elements without renaming it
                    self::arrayToXml($value, $xml_data);
                } else {
                    // Create a new child element for other arrays encountered
                    $subnode = $xml_data->addChild("$key");
                    self::arrayToXml($value, $subnode);
                }
            } else {
                // Add non-array values directly as child elements
                $xml_data->addChild("$key", htmlspecialchars("$value"));
            }
        }
        return $xml_data;
    }

}
