<?php declare(strict_types=1);

namespace Models\ContentSecurityPolicy;

use App\Database\DB;
use App\Logs\SystemLog;
use App\Exceptions\ContentSecurityPolicyExceptions;

class CSPApprovedDomains
{
    public function domainExist(string $domain) : bool
    {
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM csp_approved_domains WHERE domain=?");
        try {
            $stmt->execute([$domain]);
            $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            return count($result) > 0;
        } catch (\PDOException $e) {
            SystemLog::write($e->getMessage(), 'CSP Approved Domains');
            throw (new ContentSecurityPolicyExceptions())->genericError($e->getMessage(), 500);
        }
    }
}
