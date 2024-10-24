<?php declare(strict_types=1);

namespace App\Logs;

use App\Database\DB;

class SystemLog
{
    public static function write($message, $category) : void
    {
        $username = 'Dummy';
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("INSERT INTO system_log (text, client_ip, user_agent, created_by, category, uri, method) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $message, // Actual message
            currentIP(), // IP
            currentBrowser(), // User Agent
            $username, // who created the log
            $category, // category
            currentUrl(), // full current url
            $_SERVER['REQUEST_METHOD']
        ]);
    }
}
