<?php
/**
 * AI Package Inspector - Real OCR API Endpoint
 * File: api/ocr.php
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../services/ocr_service.php';
require_once __DIR__ . '/../services/extraction_service.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed. Use POST.'], 405);
}

$imageInput = null;

// 1. Check for file upload (POST multipart/form-data)
if (isset($_FILES['package_image']) && $_FILES['package_image']['error'] === UPLOAD_ERR_OK) {
    $imageInput = $_FILES['package_image'];
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $imageInput = $_FILES['image'];
} else {
    // 2. Check for base64 JSON payload from Camera capture
    $inputData = json_decode(file_get_contents('php://input'), true);
    if (!empty($inputData['image'])) {
        $imageInput = $inputData['image'];
    } elseif (!empty($_POST['image'])) {
        $imageInput = $_POST['image'];
    }
}

if (!$imageInput) {
    json_response(['error' => 'No package photograph received. Please capture a camera photo or upload an image file.'], 400);
}

// Execute Real OCR Engine
$ocrResult = OCRService::processImage($imageInput);

if (!$ocrResult['success']) {
    json_response(['error' => $ocrResult['error']], 400);
}

// Perform Field Extraction
$extraction = ExtractionService::extractFields($ocrResult['raw_text']);

$annotatedImage = $_SESSION['last_annotated_image'] ?? null;
$complianceReport = $_SESSION['last_compliance_report'] ?? null;

json_response([
    'success' => true,
    'source' => $ocrResult['source'],
    'image_url' => $ocrResult['image_url'],
    'annotated_image_url' => $annotatedImage,
    'compliance_report' => $complianceReport,
    'raw_text' => $ocrResult['raw_text'],
    'quality' => $ocrResult['quality'],
    'fields' => $extraction['fields'],
    'summary' => $extraction['summary']
]);
