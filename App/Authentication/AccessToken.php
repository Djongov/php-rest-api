<?php declare(strict_types=1);

namespace App\Authentication;

use Models\Core\DBCache;

class AccessToken
{
    public static function dbGet(string $username) : array
    {
        $cachedToken = DBCache::get('access_token', $username);
        return ($cachedToken) ? $cachedToken : [];
    }
    public static function get(string $username, $scope = 'https://graph.microsoft.com/user.read') : string
    {
        $cachedToken = self::dbGet($username);
        if ($cachedToken) {
            return $cachedToken['value'];
        } else {
            // If no token is present, let's go fetch one
            // This will go to a special endpoint where the user will be asked to consent and get an access token after which it will be saved to the DB
            $data = [
                'state' => $_SERVER['REQUEST_URI'],
                'username' => $username,
            ];
            if ($scope !== 'https://graph.microsoft.com/user.read') {
                $data['scope'] = $scope;
            }
            header('Location: /auth/azure/request-access-token?' . http_build_query($data));
            exit();
        }
    }
    public static function save(string $token, string $username) : string
    {
        $parsedToken = JWT::parseTokenPayLoad($token);
    
        // If it is a mslive token it will not be decoded
        if (!$parsedToken) {
            $parsedToken = [
                'exp' => time() + 3600, // 1 hour
                'aud' => 'https://graph.microsoft.com'
            ];
        }
        
        $expiration = date('Y-m-d H:i:s', $parsedToken['exp']);

        // If the username doesn't have an access token
        if (!self::dbGet($username)) {
            try {
                return DBCache::create($token, $expiration, 'access_token', $username);
            } catch (\Exception $e) {
                throw new \Exception('Error saving token to cache');
            }
        } else {
            // Let's check if the audience is the same
            $tokenInCache = self::dbGet($username);
            $parsedTokenInCache = JWT::parseTokenPayLoad($tokenInCache['value']);
            if (!$parsedToken['aud']) {
                $parsedToken['aud'] = 'https://graph.microsoft.com';
            }
            if ($parsedToken['aud'] === $parsedTokenInCache['aud']) {
                try {
                    return DBCache::update($token, $expiration, 'access_token', $username);
                } catch (\Exception $e) {
                    throw new \Exception('Error updating token in cache');
                }
            } else {
                try {
                    return DBCache::create($token, $expiration, 'access_token', $username);
                } catch (\Exception $e) {
                    throw new \Exception('Error saving token to cache');
                }
            }
        }
    }
}
