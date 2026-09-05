<?php
/**
 * AI Package Inspector - Reports & Analytics REST API
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$db = getDB();

try {
    // 1. Overall Inspection KPI Totals
    $kpiStmt = $db->query("
        SELECT 
            COUNT(*) AS total_inspections,
            SUM(CASE WHEN overall_status = 'Compliant' THEN 1 ELSE 0 END) AS compliant_count,
            SUM(CASE WHEN overall_status = 'Non-Compliant' THEN 1 ELSE 0 END) AS non_compliant_count,
            SUM(CASE WHEN overall_status IN ('Needs Review', 'Warning') THEN 1 ELSE 0 END) AS review_count,
            ROUND(AVG(compliance_score), 1) AS avg_score
        FROM inspections
    ");
    $kpi = $kpiStmt->fetch();

    $total = max(1, intval($kpi['total_inspections']));
    $compliantRate = round((intval($kpi['compliant_count']) / $total) * 100, 1);

    // 2. Violation Types Breakdown
    $violTypeStmt = $db->query("
        SELECT rule_name, COUNT(*) AS count, SUM(penalty_amount) AS total_penalty
        FROM violations
        GROUP BY rule_name
        ORDER BY count DESC
        LIMIT 5
    ");
    $violationTypes = $violTypeStmt->fetchAll();

    // 3. Top Violator Manufacturers
    $mfrStmt = $db->query("
        SELECT manufacturer_name, COUNT(*) AS total_inspected, 
               SUM(total_violations) AS violations_count,
               compliance_rate
        FROM products
        WHERE total_violations > 0
        ORDER BY total_violations DESC
        LIMIT 5
    ");
    $topViolators = $mfrStmt->fetchAll();

    // 4. Category Breakdown
    $catStmt = $db->query("
        SELECT p.category, 
               COUNT(i.id) AS total_inspections,
               SUM(CASE WHEN i.overall_status = 'Compliant' THEN 1 ELSE 0 END) AS compliant,
               SUM(CASE WHEN i.overall_status = 'Non-Compliant' THEN 1 ELSE 0 END) AS non_compliant
        FROM inspections i
        JOIN products p ON i.product_id = p.id
        GROUP BY p.category
    ");
    $categories = $catStmt->fetchAll();

    // 5. Recent Inspections Activity
    $recentStmt = $db->query("
        SELECT i.inspection_number, i.overall_status, i.compliance_score, i.created_at,
               p.product_name, p.manufacturer_name, u.full_name AS inspector_name
        FROM inspections i
        LEFT JOIN products p ON i.product_id = p.id
        LEFT JOIN users u ON i.inspector_id = u.id
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    $recentInspections = $recentStmt->fetchAll();

    json_response([
        'success' => true,
        'kpi' => [
            'total_inspections' => intval($kpi['total_inspections']),
            'compliant_count' => intval($kpi['compliant_count']),
            'non_compliant_count' => intval($kpi['non_compliant_count']),
            'review_count' => intval($kpi['review_count']),
            'compliance_rate' => $compliantRate,
            'avg_score' => floatval($kpi['avg_score'] ?? 0.0)
        ],
        'violation_types' => $violationTypes,
        'top_violating_manufacturers' => $topViolators,
        'categories' => $categories,
        'recent_inspections' => $recentInspections
    ]);

} catch (Exception $e) {
    json_response(['error' => 'Database error generating report: ' . $e->getMessage()], 500);
}
