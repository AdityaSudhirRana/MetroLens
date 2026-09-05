<?php
/**
 * AI Package Inspector - Companies & Expandable Dates REST API
 * File: api/companies.php
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        // Single Company Profile & all Inspection Dates from MySQL
        $stmt = $db->prepare("SELECT * FROM companies WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $company = $stmt->fetch();

        if (!$company) {
            json_response(['error' => 'Company record not found.'], 404);
        }

        // Fetch products for this company
        $pStmt = $db->prepare("SELECT * FROM products WHERE company_id = ? ORDER BY product_name ASC");
        $pStmt->execute([$id]);
        $company['products'] = $pStmt->fetchAll();

        // Fetch all Inspection Dates for this company
        $iStmt = $db->prepare("
            SELECT i.id, i.inspection_number, i.inspection_date, i.overall_status, i.risk_level, i.compliance_score,
                   p.product_name, u.full_name AS inspector_name
            FROM inspections i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN users u ON i.inspector_id = u.id
            WHERE i.company_id = ?
            ORDER BY i.inspection_date DESC
        ");
        $iStmt->execute([$id]);
        $company['inspection_dates'] = $iStmt->fetchAll();

        json_response(['success' => true, 'company' => $company]);
    } else {
        // List all Companies with total products and total inspections count
        $q = trim($_GET['q'] ?? '');
        $where = ["1=1"];
        $params = [];

        if (!empty($q)) {
            $where[] = "(c.company_name LIKE ? OR c.address LIKE ? OR c.email LIKE ?)";
            $term = "%$q%";
            $params = array_merge($params, [$term, $term, $term]);
        }

        $sql = "
            SELECT c.*,
                   COUNT(DISTINCT p.id) AS products_count,
                   COUNT(DISTINCT i.id) AS inspections_count,
                   SUM(CASE WHEN i.overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS violations_count
            FROM companies c
            LEFT JOIN products p ON c.id = p.company_id
            LEFT JOIN inspections i ON c.id = i.company_id
            WHERE " . implode(" AND ", $where) . "
            GROUP BY c.id
            ORDER BY c.company_name ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $companies = $stmt->fetchAll();

        json_response(['success' => true, 'companies' => $companies]);
    }
} else {
    json_response(['error' => 'Method not allowed'], 405);
}
