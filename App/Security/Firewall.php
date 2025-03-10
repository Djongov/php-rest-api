<?php declare(strict_types=1);

namespace App\Security;

use App\Database\DB;
use App\Api\Response;
use App\Logs\SystemLog;

class Firewall
{
    public static function cirdMatch ($ip, $range) {
        list($subnet, $bits) = explode('/', $range);
        if ($bits === null) {
            $bits = 32;
        }
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask; # nb: in case the supplied subnet wasn't correctly aligned
        return ($ip & $mask) == $subnet;
    }

    public static function activate() {
        // Find out the real client IP
        $client_ip = currentIP();
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM firewall");
        $stmt->execute();
        $firewall_array = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $allow_list = [];
        foreach ($firewall_array as $array) {
            foreach ($array as $name => $value) {
                if ($name === 'ip_cidr') {
                    array_push($allow_list, $value);
                }
            }
        }
        // Initiate validator variable
        $valid_ip = false;
        // Loop through the allow list
        foreach ($allow_list as $addr) {
            // If there is a match
            if (self::cirdMatch($client_ip, $addr)) {
                // Set the validator to true
                $valid_ip = true;
                // and break the loop
                break;
            }
        }
        if (!$valid_ip) {
            SystemLog::write('just tried to access the web app and got Unauthorized', 'Access');
            Response::output('Unauthorized access for IP Address ' . $client_ip . ' on uri ' . $_SERVER['REQUEST_URI'], 401);
        }
    }
}
