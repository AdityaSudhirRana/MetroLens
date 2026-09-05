<?php
/**
 * AI Package Inspector - Legal Inquiries REST API
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
            SELECT inq.*, 
                   i.inspection_number, i.overall_status AS inspection_status, i.created_at AS inspection_date,
                   p.product_name, p.manufacturer_name, p.gtin_barcode,
                   u.full_name AS inspector_name, u.employee_id AS inspector_emp_id
            FROM inquiries inq
            LEFT JOIN inspections i ON inq.inspection_id = i.id
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN users u ON inq.inspector_id = u.id
            WHERE inq.id = ? OR inq.inquiry_number = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $id]);
        $inquiry = $stmt->fetch();

        if (!$inquiry) {
            json_response(['error' => 'Legal inquiry record not found.'], 404);
        }

        json_response(['success' => true, 'inquiry' => $inquiry]);
    } else {
        $status = trim($_GET['status'] ?? '');
        $where = ["1=1"];
        $params = [];

        if (!empty($status)) {
            $where[] = "inq.status = ?";
            $params[] = $status;
        }

        $sql = "
            SELECT inq.*, 
                   i.inspection_number, 
                   p.product_name, p.manufacturer_name,
                   u.full_name AS inspector_name
            FROM inquiries inq
            LEFT JOIN inspections i ON inq.inspection_id = i.id
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN users u ON inq.inspector_id = u.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY inq.issued_at DESC, inq.id DESC
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $inquiries = $stmt->fetchAll();

        json_response(['success' => true, 'inquiries' => $inquiries]);
    }
} elseif ($method === 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $action = $inputData['action'] ?? 'create';

    if ($action === 'update_status') {
        $inquiryId = $inputData['inquiry_id'] ?? null;
        $newStatus = $inputData['status'] ?? 'Issued';
        $responseNotes = trim($inputData['response_notes'] ?? '');

        if (!$inquiryId) {
            json_response(['error' => 'Inquiry ID required.'], 400);
        }

        $stmt = $db->prepare("UPDATE inquiries SET status = ?, manufacturer_response = CONCAT(IFNULL(manufacturer_response,''), '\n', ?) WHERE id = ?");
        $stmt->execute([$newStatus, $responseNotes, $inquiryId]);

        log_audit($currentUser['id'], 'UPDATE_INQUIRY', "Inquiry #{$inquiryId} updated to status: {$newStatus}");

        json_response(['success' => true, 'message' => 'Inquiry status updated successfully.']);
    } else {
        // Create inquiry
        $inspectionId = $inputData['inspection_id'] ?? null;
        $subject = trim($inputData['subject'] ?? 'Notice of Legal Metrology Violation');
        $manufacturerName = trim($inputData['manufacturer_name'] ?? '');
        $description = trim($inputData['description'] ?? '');
        $deadlineDays = intval($inputData['deadline_days'] ?? 15);

        if (!$inspectionId || empty($manufacturerName)) {
            json_response(['error' => 'Inspection ID and Manufacturer Name are required.'], 400);
        }

        $inquiryNum = 'INQ-' . date('Y') . '-' . rand(1000, 9999);
        $responseDeadline = date('Y-m-d', strtotime("+{$deadlineDays} days"));

        $stmt = $db->prepare("
            INSERT INTO inquiries (inquiry_number, inspection_id, inspector_id, manufacturer_name, subject, description, response_deadline, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Issued')
        ");
        $stmt->execute([$inquiryNum, $inspectionId, $currentUser['id'], $manufacturerName, $subject, $description, $responseDeadline]);
        $newId = $db->lastInsertId();

        log_audit($currentUser['id'], 'CREATE_INQUIRY', "Notice {$inquiryNum} issued to {$manufacturerName}");

        json_response(['success' => true, 'message' => 'Legal notice issued successfully.', 'inquiry_id' => $newId, 'inquiry_number' => $inquiryNum], 201);
    }
} else {
    json_response(['error' => 'Method not allowed'], 405);
}
