<?php declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start() : void
    {
        $secure = (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '[::1]')) ? false : true;
        $sesstionName = $secure ? '__Secure-SSID' : 'SSID';
        $domain = (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '[::1]')) ? 'localhost' : $_SERVER['HTTP_HOST'];
        session_name($sesstionName);
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => ($secure) ? 'None' : 'Lax' // Set to None because of trip to MS Azure AD authentication endpoint and back but None cannot be used with secure false.
        ]);
        session_start();
    }
    // Reset the session
    public static function reset() : void
    {
        session_unset();
        session_destroy();
        $_SESSION = [];
    }
}
