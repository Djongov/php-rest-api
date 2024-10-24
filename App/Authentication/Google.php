<?php declare(strict_types=1);

namespace App\Authentication;

use App\Request\HttpClient;
use App\Authentication\JWT;
use Controllers\Api\Output;
use Google\Client;
use App\Authentication\IdTokenCache;
use App\Logs\SystemLog;

class Google
{
    public static function getSignatures(string $kid) : array
    {
        // Fetch the x5c from Google
        $client = new HttpClient('https://www.googleapis.com/oauth2/v3/certs');

        $response = $client->call('GET', '', [], null, false, ['User-Agent' => SYSTEM_USER_AGENT]);

        // We expect the response to have a keys array

        if (!isset($response['keys'])) {
            return [];
        }

        // Now let's search for the kid
        foreach ($response['keys'] as $key) {
            if ($key['kid'] === $kid) {
                // Return the entire key
                return $key;
            }
        }
    }
    public static function checkTokenIntegrity(string $idToken)
    {
        $tokenParts = JWT::parse($idToken);
        // We are expecting 3 parts
        if (count($tokenParts) !== 3) {
            return false;
        }

        // Extract the header and payload from the JWT
        $header = $tokenParts[0];
        $payload = $tokenParts[1];
        $signature = $tokenParts[2];

        // Header must have an iss, aud, typ
        if (!isset($header['alg'], $header['kid'], $header['typ'])) {
            return false;
        }

        // Payload must have an iss, sub, aud, exp, iat
        if (!isset($payload['iss'], $payload['sub'], $payload['aud'], $payload['exp'], $payload['iat'])) {
            return false;
        }

        // Signature must be present
        if (empty($signature)) {
            return false;
        }
        return true;

    }
    public static function verifyIdToken(string $idToken)
    {
        $payload = JWT::parseTokenPayLoad($idToken);

        // Check the token integrity for basic malformations
        if (!self::checkTokenIntegrity($idToken)) {
            return false;
        }

        // Check the claims we need and if they are present
        $expectedClaims = ['email', 'name', 'exp', 'iat', 'iss', 'aud'];

        // There are more than the expected claims, let's check if the expected claims are present
        if (count(array_intersect($expectedClaims, array_keys($payload))) !== count($expectedClaims)) {
            return false;
        }

        // First check the issuer
        if ($payload['iss'] !== 'https://accounts.google.com') {
            return false;
        }

        // Now check the audience
        if ($payload['aud'] !== GOOGLE_CLIENT_ID) {
            return false;
        }

        // Now check the expiration
        if ($payload['exp'] < time()) {
            return false;
        }

        // We don't want to verify the signature on every request as it's expensive, it calls the Google verification endpoint so it adds delay. So we will only be verifying once and if for the duration of the token (1h) and if the token is still valid, we will be using the cached token. We will be using the email as the unique property to store the token in the cache.

        // If the token is not in the token cache, we will verify it
        if (!IdTokenCache::exist($payload['email'])) {
            // Verify the token
            $client = new Client(['client_id' => GOOGLE_CLIENT_ID]);
            $client->setHttpClient(new \GuzzleHttp\Client(['verify' => CURL_CERT, 'timeout' => 60, 'http_errors' => false]));
            $payload = $client->verifyIdToken($idToken);
            // If token is not valid, return false
            if (!$payload) {
                JWT::handleValidationFailure();
                exit();
            }
            // Save the token in the cache
            IdTokenCache::save($idToken);
            SystemLog::write('Token for '. $payload['email'] . ' verified and saved', 'Google Auth');
        } else {
            // If it exists, let's check if it's the same token, if not we will save it
            //echo 'Pulling token';
            $cachedToken = IdTokenCache::get(JWT::parseTokenPayLoad($idToken)['email']);
            // Let's check if the expiration time of the token is different from the cached one, if not we need to update it, however the expiration in the cached token needs to eb converted to timestamp
            $cachedTokenExpiration = strtotime($cachedToken['expiration']);
            // Check if the Token's expiration is different from the cached one
            $tokenExpiration = JWT::parseTokenPayLoad($idToken)['exp'];
            if ($tokenExpiration !== $cachedTokenExpiration) {

                // Replace the token with the current one but verify it first
                $client = new Client(['client_id' => GOOGLE_CLIENT_ID]);
                $client->setHttpClient(new \GuzzleHttp\Client(['verify' => CURL_CERT, 'timeout' => 60, 'http_errors' => false]));
                $payload = $client->verifyIdToken($idToken);
                IdTokenCache::update($idToken, JWT::parseTokenPayLoad($idToken)['email']);
                SystemLog::write('Token updated because there was a different between token expiration (' . $tokenExpiration . ') and cached token expiration (' . $cachedTokenExpiration . ')', 'Google Auth');
                //echo 'Token updated';
            }
        }

        if (!$payload) {
            return false;
        }

        // // Now check the signature
        // //$x5c = self::getSignatures($header['kid']);
        // $x5c = X5CHandler::load(GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, $header['kid'], 'google');
        // // We've saved the n and e in the x5c value but with a space in between
        // $x5cArray = explode(" ", $x5c);

        // if (!isset($x5cArray[0], $x5cArray[1])) {
        //     return false;
        // }

        // $modulus = base64_decode($x5cArray[0]);
        // $exponent = base64_decode($x5cArray[1]);

        // // Construct RSA public key in DER format
        // $der = "305C300D06092A864886F70D0101010500034B003048024100" . bin2hex($modulus) . "0203" . bin2hex($exponent);

        // // Convert DER to PEM
        // $pem = "-----BEGIN RSA PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END RSA PUBLIC KEY-----\n";

        // // Convert the cert to OpenSSL format
        // $pkey = openssl_pkey_get_public($pem);

        // $signature = General::base64url_decode($base64UrlSignature);

        // // Explode the token
        // $jwtParts = explode('.', $idToken);


        // $verified = openssl_verify($jwtParts[0] . '.' . $jwtParts[1], $signature, $pkey, OPENSSL_ALGO_SHA256);

        // var_dump($verified);

        // if ($verified !== 1) {
        //     // Invalid signature
        //     return false;
        // }

        // Signature is valid
        return true;
    }
    public static function check(string $idToken)
    {
        $expiration = JWT::checkExpiration($idToken);
        if (!$expiration) {
            JWT::handleValidationFailure();
            header('Location:' . GOOGLE_LOGIN_BUTTON_URL);
            exit();
        }
        // Now check the token
        $check = self::verifyIdToken($idToken);
        if (!$check) {
            SystemLog::write('Token verification failed for ' . JWT::parseTokenPayLoad($idToken)['email'], 'Google Auth');
            JWT::handleValidationFailure();
            Output::error('Invalid token', 400);
            return false;
        }
        return true;
    }
}
