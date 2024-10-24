<?php declare(strict_types=1);
namespace App\Request;

class NativeHttp {
    public static function get(string $url, array $headers = [], bool $sslIgnore = false) : array
    {
        // Options
        $options = [
            'http' => [
                'method' => 'GET',
                'ignore_errors' => true
            ]
        ];
        if (!$sslIgnore) {
            self::sslOptions($url);
        }
        if (!empty($headers)) {
            $options['http']['header'] = $headers;
        }

        $context  = stream_context_create($options);

        $response = file_get_contents($url, false, $context);

        $responseCode = intval(self::getResponseCode($http_response_header[0]));

        return json_decode($response, true);
    }
    public static function post(string $url, array $data, bool $sendJson = false, array $headers = [], bool $sslIgnore = false) : array
    {
        // Pack data
        if ($sendJson) {
            $data = json_encode($data);
        } else {
            $data = http_build_query($data);
        }
        // Options
        $options = [
            'http' => [
                'method' => 'POST',
                'ignore_errors' => true,
                'content' =>$data
            ]
        ];
        if (!$sslIgnore) {
            self::sslOptions($url);
        }

        if (!empty($headers)) {
            $options['http']['header'] = $headers;
        }
        if ($sendJson) {
            $options['http']['header'][] = 'Content-Type: application/json';
        } else {
            $options['http']['header'][] = 'Content-Type: application/x-www-form-urlencoded';
        }

        $context  = stream_context_create($options);

        $response = file_get_contents($url, false, $context);

        $responseCode = intval(self::getResponseCode($http_response_header[0]));

        return json_decode($response, true);
    }
    
    private static function getResponseCode($responseHeader)
    {
        if ($responseHeader != null) {
            preg_match('/\d{3}/', $responseHeader, $matches);
            return $matches[0] ?? null;
        }
    }
    public static function sslOptions($url)
    {
        return [
            'ssl' => [
                'cafile'            => CURL_CERT,
                //'peer_fingerprint'  => openssl_x509_fingerprint(file_get_contents('/path/to/key.crt')),
                'verify_peer'       => true,
                'verify_peer_name'  => true,
                'allow_self_signed' => false,
                'verify_depth'      => 0,
                'CN_match'          => parse_url($url)['host']
            ]
        ];
    }
}
