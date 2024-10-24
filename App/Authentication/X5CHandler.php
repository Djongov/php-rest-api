<?php declare(strict_types=1);

namespace App\Authentication;

use App\Authentication\Azure\AzureAD;
use App\Authentication\Google;
use App\Logs\SystemLog;
use Models\Core\DBCache;

class X5CHandler
{
    public static function load(string $appId, string $tenant, string $kid, string $provider) : bool|string
    {
        $providerValues = ['azure', 'mslive', 'google'];
        if (!in_array($provider, $providerValues)) {
            return null;
        }
        // Let's set some provider-specific variables
        if ($provider === 'azure') {
            $type = 'x5c';
        } elseif ($provider === 'google') {
            $type = 'x509';
        }
        $x5c = DBCache::get($type, $kid);

        // Check if we have a result
        if ($x5c) {
            // We have a result, let's check if it's expired
            if (strtotime($x5c['expiration']) > time()) {
                // We have a valid x5c, let's return it
                return $x5c['value'];
            } else {
                // We have an expired x5c, let's delete it
                DBCache::delete($type, $kid);
                // Let's fetch a new x5c
                $newX5c = self::fetch($appId, $tenant, $kid, $provider);
                SystemLog::write('Fetched new ' . $type, $type);
                // Let's return the x5c if not false
                return (!$newX5c) ? false : $newX5c;
            }
        // If no result, let's fetch a new x5c
        } else {
            // If we don't have a result, let's fetch a new x5c
            $newX5c = self::fetch($appId, $tenant, $kid, $provider);
            // Let's return the x5c if not false
            return (!$newX5c) ? false : $newX5c;
        }
    }
    public static function fetch(string $appId, string $tenant, string $kid, string $provider) : ?string
    {
        // We don't have a valid x5c, let's fetch it
        $providerValues = ['azure', 'google'];
        if (!in_array($provider, $providerValues)) {
            return null;
        }
        if ($provider === 'azure') {
            // Fetch the x5c from AzureAD
            $x5cResult = AzureAD::getSignatures($appId, $tenant, $kid);
            $type = 'x5c';
            $x5c = $x5cResult['x5c'][0];
        } elseif ($provider === 'google') {
            // Fetch the x5c from Google
            $x5cResult = Google::getSignatures($kid);
            $type = 'x509';
            $x5c = $x5cResult['n'] . ' ' . $x5cResult['e'];
        }
        DBCache::create($x5c, date('Y-m-d H:i:s', strtotime('+1 day')), $type, $kid);

        // Return the x5c
        SystemLog::write('Fetched new ' . $type . ' and wrote it to DB', $type);
        return $x5c;
    }
}
