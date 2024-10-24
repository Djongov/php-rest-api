<?php declare(strict_types=1);

namespace App\Authentication\Azure;

use App\Authentication\X5CHandler;
use App\Authentication\JWT;
use App\Request\HttpClient;
use App\Utilities\General;

class AzureAD
{
    public static function validateJWTSignature(string $token): bool
    {
        $jwtParts = explode('.', $token);

        if (count($jwtParts) !== 3) {
            // Invalid JWT format
            return false;
        }

        // Extract the header and payload from the JWT
        $base64UrlHeader = $jwtParts[0];
        $base64UrlPayload = $jwtParts[1];
        $base64UrlSignature = $jwtParts[2];

        // Decode the base64url-encoded header and payload
        $header = General::base64url_decode($base64UrlHeader);
        $payload = General::base64url_decode($base64UrlPayload);
        $signature = General::base64url_decode($base64UrlSignature);

        if (!$header || !$payload) {
            // Invalid header or payload
            return false;
        }

        if (!isset(json_decode($header, true)['kid'])) {
            // Invalid header or payload
            return false;
        }

        $x5c = X5CHandler::load(AZURE_AD_CLIENT_ID, AZURE_AD_TENANT_ID, json_decode($header, true)['kid'], 'azure');

        if ($x5c === false) {
            // Invalid signature
            return false;
        }

        // Let's palce the x5c property in a certificate object
        $certText = '-----BEGIN CERTIFICATE-----' . "\n" . chunk_split($x5c, 64) . '-----END CERTIFICATE-----';
        // Let's place the certificate object in a public key object(OpenSSLCertificate)
        $certObject = openssl_x509_read($certText);
        // Extract the public key from the certificate object into a public key object(OpenSSLAsymmetricKey)
        $pkeyObject = openssl_pkey_get_public($certObject);
        // Extract the public key from the public key object into a public key array, ['bits'], ['key'], ['rsa']['n'], ['rsa']['e'] keys. We are interested in the ['key'] key
        $pkeyArray = openssl_pkey_get_details($pkeyObject);
        // Let's place the public key in a public key string
        $pkeyString = $pkeyArray['key'];

        $token_valid = openssl_verify($base64UrlHeader . '.' . $base64UrlPayload, $signature, $pkeyString, OPENSSL_ALGO_SHA256);

        if ($token_valid !== 1) {
            // Invalid signature
            return false;
        }

        return true;
    }
    public static function check(string $token) : bool
    {
        // Check validity of the token
        if (!JWT::checkExpiration($token)) {
            // unse the cookie but do not return false, we want to redirect to MS login to get a new token
            JWT::handleValidationFailure();
            header('Location:' . AZURE_AD_LOGIN_BUTTON_URL);
            exit();
        }
        // First validate signature of the token
        if (!self::validateJWTSignature($token)) {
            return JWT::handleValidationFailure();
        }
        // Parse the token
        $payloadArray = JWT::parseTokenPayLoad($token);
        // Let's do some other possible checks that are specific to Azure AD
        if ($payloadArray['aud'] !== AZURE_AD_CLIENT_ID) {
            return JWT::handleValidationFailure();
        }
        // Disable these 2 methods if app is multi-tenant and the issuer is different based on the incoming tenant
        if ($payloadArray['iss'] !== 'https://login.microsoftonline.com/' . AZURE_AD_TENANT_ID . '/v2.0') {
            return JWT::handleValidationFailure();
        }
        if ($payloadArray['tid'] !== AZURE_AD_TENANT_ID) {
            return JWT::handleValidationFailure();
        }

        return true;
        
    }
    public static function getSignatures(string $appId, string $tenant, string $header_kid): ?array
    {
        $url = "https://login.microsoftonline.com/$tenant/discovery/keys?appid=$appId";

        $request = new HttpClient($url);

        $result = $request->call('GET', $url, null, null, false, [
            'Accept' => 'application/json'
        ]);

        $kid_array = [];

        if ($result !== null) {

            foreach ($result as $keys) {
                foreach ($keys as $props) {
                    if (in_array($header_kid, $props)) {
                        return $props;
                    }
                    array_push($kid_array, $props['kid']);
                }
            }
        } else {
            return null;
        }

        return $kid_array;
    }
}
