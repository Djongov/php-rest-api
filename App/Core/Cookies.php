<?php declare(strict_types=1);

namespace App\Core;

use App\Authentication\JWT;

class Cookies
{
    public static function set(string $name, string $value, int $expiration = 86400, string $path = '/', string $domain = '', bool $secure = false, bool $httponly = true, string $samesite = 'Lax') : void
    {
        setcookie($name, $value, time() + $expiration, $path, $domain, $secure, $httponly);
    }
    public static function setAuthCookie($token, $expiry = AUTH_COOKIE_EXPIRY) : void
    {
        // Let's decide whether the connection is over HTTP or HTTPS (later for setting up the cookie)
        $secure = (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '[::1]')) ? false : true;
        $domainCookie = $_SERVER['HTTP_HOST'];
        $colonPosition = strstr(
            $domainCookie,
            ':'
        ) ?? '';

        if ($colonPosition === false) {
            $colonPosition = '';
        }

        $domainCookie = str_replace($colonPosition, '', $domainCookie);
        setcookie(AUTH_COOKIE_NAME, $token, [
            'expires' => JWT::parseTokenPayLoad($token)['exp'] + $expiry,
            'path' => '/',
            'domain' => $domainCookie, // strip : from HOST in cases where localhost:8080 is used
            'secure' => $secure, // This needs to be true for most scenarios, we leave the option to be false for local environments
            'httponly' =>  true, // Prevent JavaScript from accessing the cookie
            'samesite' => 'Lax' // This unlike the session cookie can be Lax
        ]);
    }
    public static function get(string $name) : ?string
    {
        return $_COOKIE[$name] ?? null;
    }
    public static function delete(string $name) : void
    {
        setcookie($name, '', time() - 3600);
    }
    public static function exists(string $name) : bool
    {
        return isset($_COOKIE[$name]);
    }
    public static function clear() : void
    {
        foreach ($_COOKIE as $name => $value) {
            setcookie($name, '', time() - 3600);
        }
    }
}
