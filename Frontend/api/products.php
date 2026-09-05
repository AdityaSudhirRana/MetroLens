<?php
/**
 * AI Package Inspector - Products REST API
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
        // Single Product
        $stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            json_response(['error' => 'Product not found.'], 404);
        }

        // Inspections history for this product
        $historyStmt = $db->prepare("
            SELECT i.*, u.full_name AS inspector_name 
            FROM inspections i 
            LEFT JOIN users u ON i.inspector_id = u.id 
            WHERE i.product_id = ? 
            ORDER BY i.created_at DESC
        ");
        $historyStmt->execute([$id]);
        $product['inspections'] = $historyStmt->fetchAll();

        json_response(['success' => true, 'product' => $product]);
    } else {
        // List products
        $q = trim($_GET['q'] ?? '');
        $category = trim($_GET['category'] ?? '');

        $where = ["1=1"];
        $params = [];

        if (!empty($q)) {
            $where[] = "(product_name LIKE ? OR manufacturer_name LIKE ? OR brand_name LIKE ? OR gtin_barcode LIKE ?)";
            $term = "%$q%";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($category)) {
            $where[] = "category = ?";
            $params[] = $category;
        }

        $sql = "SELECT * FROM products WHERE " . implode(" AND ", $where) . " ORDER BY last_inspected_at DESC, id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        json_response(['success' => true, 'products' => $products]);
    }
} elseif ($method === 'POST') {
    $inputData = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $productName = trim($inputData['product_name'] ?? '');
    $manufacturer = trim($inputData['manufacturer_name'] ?? '');
    $category = trim($inputData['category'] ?? 'Packaged Commodities');
    $brand = trim($inputData['brand_name'] ?? '');
    $gtin = trim($inputData['gtin_barcode'] ?? '');
    $standardQuantity = trim($inputData['standard_net_quantity'] ?? '');
    $standardMrp = floatval($inputData['standard_mrp'] ?? 0.0);
    $notes = trim($inputData['notes'] ?? '');

    if (empty($productName) || empty($manufacturer)) {
        json_response(['error' => 'Product Name and Manufacturer Name are required.'], 400);
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO products (gtin_barcode, product_name, brand_name, category, manufacturer_name, standard_net_quantity, standard_mrp, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$gtin, $productName, $brand, $category, $manufacturer, $standardQuantity, $standardMrp, $notes]);
        $id = $db->lastInsertId();

        log_audit($currentUser['id'], 'CREATE_PRODUCT', "New product registered: {$productName} (#{$id})");

        json_response(['success' => true, 'message' => 'Product registered successfully.', 'product_id' => $id], 201);
    } catch (Exception $e) {
        json_response(['error' => 'Database error: ' . $e->getMessage()], 500);
    }
} else {
    json_response(['error' => 'Method not allowed'], 405);
}
