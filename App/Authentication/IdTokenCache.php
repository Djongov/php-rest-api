<?php declare(strict_types=1);

namespace App\Authentication;

use App\Authentication\JWT;
use Models\Core\DBCache;

class IdTokenCache
{
    // Check if there is a token in the cache for that username
    public static function exist(string $username) : bool
    {
        $cachedToken = DBCache::get('id_token', $username);
        return ($cachedToken) ? true : false;
    }
    public static function get(string $username) : array
    {
        $cachedToken = DBCache::get('id_token', $username);
        return ($cachedToken) ? $cachedToken : [];
    }
    public static function save(string $token) : void
    {
        if (!self::exist($token)) {
            $parsedToken = JWT::parseTokenPayLoad($token);
            // Set the expiration to the token's expiration but convert to mysql datetime
            $expiration = date('Y-m-d H:i:s', $parsedToken['exp']);
            if (isset($parsedToken['email'])) {
                $username = $parsedToken['email'];
            }
            if (isset($parsedToken['username'])) {
                $username = $parsedToken['username'];
            }
            if (isset($parsedToken['upn'])) {
                $username = $parsedToken['upn'];
            }
            DBCache::create($token, $expiration, 'id_token', $username);
        }
    }
    public static function update(string $token, string $username) : void
    {
        $parsedToken = JWT::parseTokenPayLoad($token);
        // Set the expiration to the token's expiration but convert to mysql datetime
        $expiration = date('Y-m-d H:i:s', $parsedToken['exp']);
        DBCache::update($token, $expiration, 'id_token', $username);
    }
}
