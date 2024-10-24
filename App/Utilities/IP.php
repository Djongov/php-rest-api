<?php declare(strict_types=1);

namespace App\Utilities;

class IP
{
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
    public static function isPublicIp($ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            return false;
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)) {
            return false;
        }
        return true;
    }
    // This method will check if a string is a valid IP address
    public static function isValidIp($ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        return true;
    }
    // This method will check if a string is a private IP
    public static function isPrivateIp($ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
            return false;
        }
        return true;
    }
    // This method will check if a string is ipv6
    public static function isIpv6($ip): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return false;
        }
        return true;
    }
    // This method will check if an ip is from the CGNAT range (RFC 6598)
    public static function isCgnatIp($ip)
    {
        // Define the CGNAT range in CIDR notation
        $cgnat_range = '100.64.0.0/10';

        // Convert the IP and range to long integers
        $ip_long = ip2long($ip);
        list($range_ip, $subnet) = explode('/', $cgnat_range);
        $range_ip_long = ip2long($range_ip);

        // Calculate the mask
        $mask = -1 << (32 - $subnet);

        // Check if the IP is within the CGNAT range
        return (($ip_long & $mask) == ($range_ip_long & $mask)) ? true : false;
    }
}
