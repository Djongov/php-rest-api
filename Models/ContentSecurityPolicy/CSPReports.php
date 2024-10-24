<?php declare(strict_types=1);

namespace Models\ContentSecurityPolicy;

use App\Database\DB;
use App\Exceptions\ContentSecurityPolicyExceptions;

class CSPReports
{
    /**
     * Create a report in the DB
     *
     * @param array $data. Array of the CSP report originally sent
     * @return array
     */
    public function create(array $data) : array
    {
        $jsonData = json_encode($data) ?? null;
        $url = $data['csp-report']['document-uri'] ?? null;
        $referrer = $data['csp-report']['referrer'] ?? null;
        $violatedDirective = $data['csp-report']['violated-directive'] ?? null;
        $effectiveDirective = $data['csp-report']['effective-directive'] ?? null;
        $originalPolicy = $data['csp-report']['original-policy'] ?? null;
        $disposition = $data['csp-report']['disposition'] ?? null;
        $blockedUri = $data['csp-report']['blocked-uri'] ?? null;
        $lineNumber = $data['csp-report']['line-number'] ?? null;
        $columnNumber = $data['csp-report']['column-number'] ?? null;
        $sourceFile = $data['csp-report']['source-file'] ?? null;
        $statusCode = $data['csp-report']['status-code'] ?? null;
        $scriptSample = $data['csp-report']['script-sample'] ?? null;
        $domain = parse_url($data['csp-report']['document-uri'], PHP_URL_HOST);
        $db = new DB();
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare("INSERT INTO csp_reports (data, domain, url, referrer, violated_directive, effective_directive, original_policy, disposition, blocked_uri, line_number, column_number, source_file, status_code, script_sample) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        try {
            $stmt->execute([$jsonData, $domain, $url, $referrer, $violatedDirective, $effectiveDirective, $originalPolicy, $disposition, $blockedUri, $lineNumber, $columnNumber, $sourceFile, $statusCode, $scriptSample]);
        } catch (\PDOException $e) {
            throw (new ContentSecurityPolicyExceptions())->genericError($e->getMessage(), 500);
        }
        if (!$stmt) {
            throw (new ContentSecurityPolicyExceptions())->CSPNotCreated();
        } else {
            return [
                'id' => $pdo->lastInsertId(),
                'message' => 'CSP report created successfully'
            ];
        }
    }
}
