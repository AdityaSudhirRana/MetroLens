<?php
/**
 * AI Package Inspector - Common Helper Functions & RBAC Middleware
 * File: includes/functions.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_logged_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'employee_id' => $_SESSION['employee_id'] ?? '',
        'name' => $_SESSION['name'] ?? 'Officer',
        'email' => $_SESSION['email'] ?? '',
        'role' => strtolower($_SESSION['role'] ?? 'inspector'),
        'department' => $_SESSION['department'] ?? 'Legal Metrology'
    ];
}

function isOfficer() {
    $u = get_logged_user();
    return $u && strtolower($u['role']) === 'officer';
}

function isInspector() {
    $u = get_logged_user();
    return $u && strtolower($u['role']) === 'inspector';
}

function requireLogin() {
    if (!is_logged_in()) {
        if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) || isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            json_response(['error' => 'Unauthorized access. Please log in.'], 401);
        }
        header('Location: ../auth/login.php');
        exit;
    }
}

function requireOfficer() {
    requireLogin();
    if (!isOfficer()) {
        if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            json_response(['error' => 'Forbidden. Officer administration privileges required.'], 403);
        }
        header('Location: ../pages/inspector-dashboard.php');
        exit;
    }
}

function requireInspector() {
    requireLogin();
    if (!isInspector() && !isOfficer()) {
        if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            json_response(['error' => 'Forbidden. Inspector authorization required.'], 403);
        }
        header('Location: ../auth/login.php');
        exit;
    }
}

// Backward compatibility alias
function require_login() { requireLogin(); }
function require_admin() { requireOfficer(); }

function log_audit($user_id, $action, $details = '') {
    try {
        $db = getDB();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

function get_compliance_badge($status) {
    switch (strtoupper($status)) {
        case 'COMPLIANT':
            return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Compliant</span>';
        case 'NON_COMPLIANT':
        case 'NON-COMPLIANT':
            return '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Non-Compliant</span>';
        case 'PENDING_REVIEW':
        case 'NEEDS REVIEW':
            return '<span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> Pending Review</span>';
        default:
            return '<span class="badge badge-secondary"><i class="fas fa-clock"></i> ' . htmlspecialchars($status) . '</span>';
    }
}

function get_field_status_badge($status) {
    switch (strtolower($status)) {
        case 'compliant':
        case 'verified':
        case 'manually verified':
            return '<span class="badge-sm badge-success">✓ Verified</span>';
        case 'non_compliant':
        case 'missing':
        case 'incorrect':
            return '<span class="badge-sm badge-danger">✕ Non-Compliant</span>';
        default:
            return '<span class="badge-sm badge-warning">⚠ Warning</span>';
    }
}
