<?php declare(strict_types=1);

namespace App\Authentication;

use App\Utilities\General;
use Controllers\Api\Output;
use App\Core\Session;
use App\Authentication\AuthToken;

class JWT
{
    // A method to generate a JWT token based off a private key and a set of claims
    public static function generateToken(array $claims, $expiration = JWT_TOKEN_EXPIRY): string
    {
        // First let's make sure that the claims structure is correct

        // Required claims
        $requiredClaims = [
            'iss',
            'username',
            'name',
            'roles',
            'last_ip'
        ];

        foreach ($requiredClaims as $claim) {
            if (!isset($claims[$claim])) {
                throw new \Exception('Missing required claim: ' . $claim . ' out of ' . implode(', ', $requiredClaims));
            }
        }

        // roles needs to be an array
        if (!is_array($claims['roles'])) {
            throw new \Exception('Roles claim needs to be an array');
        }

        $claims['exp'] = time() + $expiration;

        $claims['nbf'] = time() - 1;

        // now iat
        $claims['iat'] = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];

        // Base64url encode the header and payload
        $base64UrlHeader = General::base64url_encode(json_encode($header));
        $base64UrlPayload = General::base64url_encode(json_encode($claims));

        // Concatenate the base64url-encoded header and payload with a period
        $jwtUnsigned = $base64UrlHeader . '.' . $base64UrlPayload;

        // Sign the JWT with the private key
        $signature = '';
        openssl_sign($jwtUnsigned, $signature, base64_decode(JWT_PRIVATE_KEY), OPENSSL_ALGO_SHA256);

        // Base64url encode the signature
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        // Concatenate the JWT parts with periods
        return $jwtUnsigned . '.' . $base64UrlSignature;
    }
    // Check if token is set
    public static function isTokenSet(): bool
    {
        $tokenSet = AuthToken::get();
        return ($tokenSet !== null) ? true : false;
    }
    // Method to parse a JWT token and return an array with the header and payload
    public static function parse(string $token): array
    {
        $jwtParts = explode('.', $token);

        if (count($jwtParts) !== 3) {
            // Invalid JWT format
            return [];
        }

        // Extract the header and payload from the JWT
        $base64UrlHeader = $jwtParts[0];
        $base64UrlPayload = $jwtParts[1];
        // Also the signature
        $signature = $jwtParts[2];

        // Decode the base64url-encoded header and payload
        $header = General::base64url_decode($base64UrlHeader);
        $payload = General::base64url_decode($base64UrlPayload);

        if (!$header || !$payload) {
            // Invalid header or payload
            return [];
        }

        return [
            json_decode($header, true),
            json_decode($payload, true),
            $signature
        ];
    }
    // A method to validate a token that's been signed with our private key
    public static function validateToken(string $token): bool
    {
        $jwtParts = explode('.', $token);

        if (count($jwtParts) !== 3) {
            // Invalid JWT format
            return false;
        }

        // Extract the header and payload from the JWT
        $base64UrlHeader = $jwtParts[0];
        $base64UrlPayload = $jwtParts[1];
        $signature = $jwtParts[2];

        // Decode the base64url-encoded header and payload
        $header = General::base64url_decode($base64UrlHeader);
        $payload = General::base64url_decode($base64UrlPayload);

        if (!$header || !$payload) {
            // Invalid header or payload
            return false;
        }

        // Concatenate the base64url-encoded header and payload with a period
        $jwtUnsigned = $base64UrlHeader . '.' . $base64UrlPayload;

        // Verify the signature using the public key, base64url decode the signature first
        $signatureToVerify = General::base64url_decode($signature);

        try {
            $verified = openssl_verify($jwtUnsigned, $signatureToVerify, base64_decode(JWT_PUBLIC_KEY), OPENSSL_ALGO_SHA256);

            return ($verified === 1) ? true : false;
        } catch (\Exception $e) {
            // Handle the exception
            Output::error($e->getMessage());
        }
    }
    // A method to parse the payload of a token
    public static function parseTokenPayLoad(string $token): array
    {
        $jwtParts = explode('.', $token);

        if (count($jwtParts) !== 3) {
            // Invalid JWT format
            return [];
        }

        // Extract the header and payload from the JWT
        $base64UrlPayload = $jwtParts[1];

        // Decode the base64url-encoded header and payload
        $payload = General::base64url_decode($base64UrlPayload);

        if (!$payload) {
            // Invalid header or payload
            return [];
        }

        return json_decode($payload, true);
    }
    // A method to check expiration of a token
    public static function checkExpiration(string $token): bool
    {
        // Parse the payload
        $payload = self::parseTokenPayLoad($token);

        // We need both exp and nbf present in the payload
        if (isset($payload['exp'], $payload['nbf'])) {
            // If the token is expired or not yet valid, return false
            return ($payload['exp'] < time() || $payload['nbf'] > time()) ? false : true;
        // Google ID token doesn't have nbf
        } elseif (isset($payload['exp'])) {
            return ($payload['exp'] < time()) ? false : true;
        } else {
            return false;
        }
    }
    // A method to check the combined validity of a token
    public static function checkToken(string $token): bool
    {
        if (!self::validateToken($token)) {
            return self::handleValidationFailure();
        }

        if (!self::checkExpiration($token)) {
            return self::handleValidationFailure();
        }

        return true;
    }
    // This method will extract the username from the JWT token. The need and complexity of this method comes from the fact that we have different type of tokens, local and AzureAD
    public static function extractUserName(string $token): string
    {
        $payload = self::parseTokenPayLoad($token);

        if (isset($payload['username'])) {
            return $payload['username'];
        } elseif (isset($payload['preferred_username'])) {
            return $payload['preferred_username'];
        // Google ID token doesn't have preferred_username
        } elseif(isset($payload['email'])) {
            return $payload['email'];
        } else {
            return '';
        }
    }
    // Common method to handle validation failure to reduce code duplication
    public static function handleValidationFailure(): bool
    {
        if (self::isTokenSet()) {
            AuthToken::unset();
            Session::reset();
            return false;
        } else {
            return false;
        }
    }
}
