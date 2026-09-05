<?php
/**
 * AI Package Inspector - Officers / Inspectors REST API
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$currentUser = get_logged_user();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $db->prepare("
            SELECT u.id, u.employee_id, u.full_name, u.email, u.role, u.department, u.designation, u.phone, u.status, u.created_at, u.last_login,
                   COUNT(i.id) AS total_inspections,
                   SUM(CASE WHEN i.overall_status = 'Compliant' THEN 1 ELSE 0 END) AS compliant_inspections,
                   SUM(CASE WHEN i.overall_status = 'Non-Compliant' THEN 1 ELSE 0 END) AS non_compliant_inspections
            FROM users u
            LEFT JOIN inspections i ON u.id = i.inspector_id
            WHERE u.id = ? OR u.employee_id = ?
            GROUP BY u.id
            LIMIT 1
        ");
        $stmt->execute([$id, $id]);
        $inspector = $stmt->fetch();

        if (!$inspector) {
            json_response(['error' => 'Inspector record not found.'], 404);
        }

        json_response(['success' => true, 'inspector' => $inspector]);
    } else {
        $stmt = $db->query("
            SELECT u.id, u.employee_id, u.full_name, u.email, u.role, u.department, u.designation, u.status, u.last_login,
                   COUNT(i.id) AS total_inspections
            FROM users u
            LEFT JOIN inspections i ON u.id = i.inspector_id
            GROUP BY u.id
            ORDER BY total_inspections DESC, u.full_name ASC
        ");
        $inspectors = $stmt->fetchAll();

        json_response(['success' => true, 'inspectors' => $inspectors]);
    }
} else {
    json_response(['error' => 'Method not allowed'], 405);
}
