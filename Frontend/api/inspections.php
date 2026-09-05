<?php
/**
 * AI Package Inspector - Inspections REST API & Verification Action
 * File: api/inspections.php
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/compliance_service.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$currentUser = get_logged_user();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'dashboard_live') {
        $totalCompanies = intval($db->query("SELECT COUNT(*) FROM companies")->fetchColumn());
        $totalInspectors = intval($db->query("SELECT COUNT(*) FROM users WHERE role = 'inspector'")->fetchColumn());
        $totalInspections = intval($db->query("SELECT COUNT(*) FROM inspections")->fetchColumn());
        $todaysInspections = intval($db->query("SELECT COUNT(*) FROM inspections WHERE DATE(created_at) = CURDATE()")->fetchColumn());

        $statStmt = $db->query("
            SELECT 
                SUM(CASE WHEN overall_status = 'COMPLIANT' THEN 1 ELSE 0 END) AS compliant,
                SUM(CASE WHEN overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS non_compliant
            FROM inspections
        ");
        $stats = $statStmt->fetch();
        $compliantCount = intval($stats['compliant'] ?? 0);
        $nonCompliantCount = intval($stats['non_compliant'] ?? 0);
        $complianceRate = ($totalInspections > 0) ? round(($compliantCount / $totalInspections) * 100, 1) : 100.0;

        $recStmt = $db->query("
            SELECT i.*, p.product_name, c.company_name, u.full_name AS inspector_name
            FROM inspections i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN companies c ON i.company_id = c.id
            LEFT JOIN users u ON i.inspector_id = u.id
            ORDER BY i.created_at DESC
            LIMIT 6
        ");
        $recentInspections = $recStmt->fetchAll();

        $actStmt = $db->query("
            SELECT u.full_name, u.employee_id, COUNT(i.id) AS total_scans, MAX(i.created_at) AS last_scan
            FROM users u
            LEFT JOIN inspections i ON u.id = i.inspector_id
            WHERE u.role = 'inspector'
            GROUP BY u.id
            ORDER BY last_scan DESC
        ");
        $inspectorActivity = $actStmt->fetchAll();

        json_response([
            'success' => true,
            'stats' => [
                'total_companies' => $totalCompanies,
                'total_inspectors' => $totalInspectors,
                'total_inspections' => $totalInspections,
                'todays_inspections' => $todaysInspections,
                'compliant_count' => $compliantCount,
                'non_compliant_count' => $nonCompliantCount,
                'compliance_rate' => $complianceRate
            ],
            'recent_inspections' => $recentInspections,
            'inspector_activity' => $inspectorActivity
        ]);
    }

    $id = $_GET['id'] ?? null;

    if ($id) {
        // Single Inspection Details
        $stmt = $db->prepare("
            SELECT i.*, 
                   p.product_name, p.category, p.gtin_barcode, p.manufacturer_name, p.brand_name,
                   u.full_name AS inspector_name, u.employee_id AS inspector_emp_id, u.department
            FROM inspections i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN users u ON i.inspector_id = u.id
            WHERE i.id = ? OR i.inspection_number = ?
            LIMIT 1
        ");
        $stmt->execute([$id, $id]);
        $inspection = $stmt->fetch();

        if (!$inspection) {
            json_response(['error' => 'Inspection report not found.'], 404);
        }

        // Fetch fields
        $fieldsStmt = $db->prepare("SELECT * FROM inspection_fields WHERE inspection_id = ? ORDER BY id ASC");
        $fieldsStmt->execute([$inspection['id']]);
        $inspection['fields'] = $fieldsStmt->fetchAll();

        // Fetch violations
        $violStmt = $db->prepare("SELECT * FROM violations WHERE inspection_id = ? ORDER BY id ASC");
        $violStmt->execute([$inspection['id']]);
        $inspection['violations'] = $violStmt->fetchAll();

        json_response(['success' => true, 'inspection' => $inspection]);
    } else {
        // List inspections
        $q = trim($_GET['q'] ?? '');
        $status = trim($_GET['status'] ?? '');
        $limit = intval($_GET['limit'] ?? 50);

        $whereClause = ["1=1"];
        $params = [];

        if (!empty($q)) {
            $whereClause[] = "(i.inspection_number LIKE ? OR p.product_name LIKE ? OR p.manufacturer_name LIKE ? OR u.full_name LIKE ?)";
            $searchTerm = "%$q%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($status)) {
            $whereClause[] = "i.overall_status = ?";
            $params[] = $status;
        }

        $sql = "
            SELECT i.*, 
                   p.product_name, p.category, p.manufacturer_name, p.gtin_barcode,
                   u.full_name AS inspector_name
            FROM inspections i
            LEFT JOIN products p ON i.product_id = p.id
            LEFT JOIN users u ON i.inspector_id = u.id
            WHERE " . implode(" AND ", $whereClause) . "
            ORDER BY i.created_at DESC
            LIMIT $limit
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $inspections = $stmt->fetchAll();

        json_response(['success' => true, 'inspections' => $inspections]);
    }
} elseif ($method === 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true);
    if (!$inputData) {
        $inputData = $_POST;
    }

    // Action 1: Evaluate Compliance Scorecard
    if ($action === 'verify') {
        $fields = $inputData['fields'] ?? $inputData;
        $eval = ComplianceService::evaluateCompliance($fields);
        json_response([
            'success' => true,
            'evaluation' => $eval
        ]);
    }

    // Action 2: Save Inspection to MySQL
    $productId = $inputData['product_id'] ?? null;
    $productName = trim($inputData['product_name'] ?? 'Packaged Commodity');
    $manufacturer = trim($inputData['manufacturer_name'] ?? 'Unknown Manufacturer');
    $category = trim($inputData['category'] ?? 'Packaged Commodities');
    $brand = trim($inputData['brand_name'] ?? '');
    $gtin = trim($inputData['gtin_barcode'] ?? '');
    
    $inspectionType = $inputData['inspection_type'] ?? 'Routine Market Inspection';
    $ocrText = $inputData['ocr_extracted_text'] ?? '';
    $imageUrl = $inputData['image_url'] ?? '';
    $notes = trim($inputData['notes'] ?? '');
    $fieldsPayload = $inputData['fields'] ?? [];

    // Run compliance engine
    $evaluation = ComplianceService::evaluateCompliance($fieldsPayload);

    $overallStatus = $evaluation['overall_status'];
    $complianceScore = $evaluation['compliance_score'];
    $violations = $evaluation['violations'];

    try {
        $db->beginTransaction();

        // 1. Resolve Company ID from Manufacturer Name
        $companyId = null;
        if (!empty($manufacturer)) {
            $compStmt = $db->prepare("SELECT id FROM companies WHERE company_name LIKE ? LIMIT 1");
            $compStmt->execute(["%" . $manufacturer . "%"]);
            $comp = $compStmt->fetch();
            if ($comp) {
                $companyId = $comp['id'];
            } else {
                $insComp = $db->prepare("INSERT INTO companies (company_name, company_type, address, status) VALUES (?, 'Manufacturer & Packer', 'Address pending verification', 'Active')");
                $insComp->execute([$manufacturer]);
                $companyId = $db->lastInsertId();
            }
        }

        // 2. Resolve or Create Product Record in MySQL
        if (!$productId) {
            $prodStmt = $db->prepare("SELECT id, company_id FROM products WHERE (product_name = ? AND manufacturer_name = ?) OR (gtin_barcode != '' AND gtin_barcode = ?) LIMIT 1");
            $prodStmt->execute([$productName, $manufacturer, $gtin]);
            $existingProduct = $prodStmt->fetch();

            if ($existingProduct) {
                $productId = $existingProduct['id'];
                if (!$companyId && !empty($existingProduct['company_id'])) {
                    $companyId = $existingProduct['company_id'];
                }
            } else {
                $insProd = $db->prepare("
                    INSERT INTO products (company_id, gtin_barcode, product_name, brand_name, category, manufacturer_name, total_inspections, total_violations, compliance_rate)
                    VALUES (?, ?, ?, ?, ?, ?, 0, 0, 100.0)
                ");
                $insProd->execute([$companyId, $gtin, $productName, $brand, $category, $manufacturer]);
                $productId = $db->lastInsertId();
            }
        } else {
            $pCheck = $db->prepare("SELECT company_id FROM products WHERE id = ?");
            $pCheck->execute([$productId]);
            $pRes = $pCheck->fetch();
            if ($pRes && !empty($pRes['company_id'])) {
                $companyId = $pRes['company_id'];
            }
        }

        // Generate Unique Inspection Number
        $inspectionNum = 'INSP-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 5));

        // Insert Inspection Record
        $insStmt = $db->prepare("
            INSERT INTO inspections (
                inspection_number, company_id, inspector_id, product_id, inspection_type, 
                overall_status, compliance_score, ocr_extracted_text, package_image_url, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->execute([
            $inspectionNum,
            $companyId,
            $currentUser['id'],
            $productId,
            $inspectionType,
            $overallStatus,
            $complianceScore,
            $ocrText,
            $imageUrl,
            $notes
        ]);
        $inspectionId = $db->lastInsertId();

        // Insert Field Breakdown Records
        if (!empty($fieldsPayload) && is_array($fieldsPayload)) {
            $fieldStmt = $db->prepare("
                INSERT INTO inspection_fields (
                    inspection_id, field_name, expected_value, extracted_value, 
                    corrected_value, field_status, confidence_score
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($fieldsPayload as $fieldName => $fData) {
                $val = is_array($fData) ? ($fData['value'] ?? '') : (string)$fData;
                $conf = is_array($fData) ? floatval($fData['confidence'] ?? 95.0) : 95.0;
                $st = is_array($fData) ? ($fData['status'] ?? 'Verified') : 'Verified';
                $corrVal = is_array($fData) ? ($fData['corrected_value'] ?? null) : null;

                $fieldStmt->execute([
                    $inspectionId,
                    ucwords(str_replace('_', ' ', $fieldName)),
                    $val,
                    $val,
                    $corrVal,
                    $st,
                    $conf
                ]);
            }
        }

        // Insert Violation Records
        $hasViolation = false;
        if (!empty($violations) && is_array($violations)) {
            $violStmt = $db->prepare("
                INSERT INTO violations (
                    inspection_id, rule_name, field_name, severity, 
                    penalty_amount, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($violations as $v) {
                $hasViolation = true;
                $violStmt->execute([
                    $inspectionId,
                    $v['rule_name'],
                    $v['field_name'],
                    $v['severity'],
                    $v['penalty_amount'],
                    $v['notes'],
                    'Detected'
                ]);
            }
        }

        // Update Product Statistics in MySQL
        $violationInc = $hasViolation ? 1 : 0;
        $updProd = $db->prepare("
            UPDATE products 
            SET total_inspections = total_inspections + 1,
                total_violations = total_violations + ?,
                compliance_rate = ROUND(((total_inspections - total_violations) / total_inspections) * 100, 1),
                last_inspected_at = NOW()
            WHERE id = ?
        ");
        $updProd->execute([$violationInc, $productId]);

        // Audit Trail
        log_audit($currentUser['id'], 'CREATE_INSPECTION', "Inspection #{$inspectionNum} created for {$productName}. Status: {$overallStatus}");

        $db->commit();

        json_response([
            'success' => true,
            'message' => 'Inspection report saved to MySQL successfully.',
            'inspection_id' => $inspectionId,
            'inspection_number' => $inspectionNum
        ], 201);

    } catch (Exception $e) {
        $db->rollBack();
        json_response(['error' => 'Database error saving inspection: ' . $e->getMessage()], 500);
    }
} else {
    json_response(['error' => 'Method not allowed'], 405);
}
