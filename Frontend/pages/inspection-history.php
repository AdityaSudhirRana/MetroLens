<?php
/**
 * AI Package Inspector - Role-Based Inspection History Table Page
 */
$pageTitle = 'Inspection History Log';
$activeNav = 'history';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$currentUser = get_logged_user();
$isOfficer = (strtolower($currentUser['role'] ?? '') === 'officer');
$currentUserId = $currentUser['id'] ?? 0;

$db = getDB();

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$inspectorIdFilter = trim($_GET['inspector_id'] ?? '');

$where = ["1=1"];
$params = [];

// Role-Based Access Control: Inspector ONLY sees their own inspections
if (!$isOfficer) {
    $where[] = "i.inspector_id = ?";
    $params[] = $currentUserId;
} else if (!empty($inspectorIdFilter)) {
    // Officer filtering by a specific inspector
    $where[] = "i.inspector_id = ?";
    $params[] = $inspectorIdFilter;
}

if (!empty($q)) {
    $where[] = "(i.inspection_number LIKE ? OR p.product_name LIKE ? OR p.manufacturer_name LIKE ? OR u.full_name LIKE ? OR c.company_name LIKE ?)";
    $term = "%$q%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

if (!empty($status)) {
    $where[] = "i.overall_status = ?";
    $params[] = $status;
}

$sql = "
    SELECT i.*, 
           p.product_name, p.category, p.manufacturer_name, p.gtin_barcode,
           c.company_name,
           u.full_name AS inspector_name, u.employee_id AS inspector_emp_id, u.role AS inspector_role
    FROM inspections i
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN companies c ON i.company_id = c.id
    LEFT JOIN users u ON i.inspector_id = u.id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY i.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$inspections = $stmt->fetchAll();

// Fetch all inspectors list for officer filter dropdown
$allInspectors = [];
if ($isOfficer) {
    try {
        $inspStmt = $db->query("SELECT id, full_name, employee_id FROM users WHERE role = 'inspector' ORDER BY full_name ASC");
        $allInspectors = $inspStmt->fetchAll();
    } catch (Exception $e) {}
}
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <!-- Role Banner & Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                    <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark); margin: 0;">
                        <?php echo $isOfficer ? 'System-Wide Inspection Audit Log' : 'My Inspection History Log'; ?>
                    </h2>
                    <span class="badge" style="background-color: <?php echo $isOfficer ? '#2563eb' : '#059669'; ?>; color: #ffffff; padding: 4px 10px; border-radius: 6px; font-weight: 700; font-size: 0.78rem;">
                        <i class="fas <?php echo $isOfficer ? 'fa-shield-alt' : 'fa-search-location'; ?>"></i>
                        <?php echo $isOfficer ? 'OFFICER VIEW (ALL INSPECTORS)' : 'FIELD INSPECTOR VIEW (MY RECORDS ONLY)'; ?>
                    </span>
                </div>
                <p style="font-size: 0.88rem; color: var(--text-muted);">
                    <?php if ($isOfficer): ?>
                        Review all legal metrology inspections executed across all field inspectors and manufacturers.
                    <?php else: ?>
                        Showing package compliance inspections executed exclusively by <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong> (ID: <?php echo htmlspecialchars($currentUser['employee_id'] ?? 'INS-101'); ?>).
                    <?php endif; ?>
                </p>
            </div>
            <a href="new-inspection.php" class="btn btn-primary">
                <i class="fas fa-camera"></i> <?php echo $isOfficer ? 'Start New Inspection' : 'Scan Package (AI OCR)'; ?>
            </a>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form action="inspection-history.php" method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 240px;">
                        <input type="text" name="q" class="form-control" placeholder="Search Report #, Product, Manufacturer, Inspector Name..." value="<?php echo htmlspecialchars($q); ?>" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <?php if ($isOfficer && !empty($allInspectors)): ?>
                        <div style="width: 200px;">
                            <select name="inspector_id" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="this.form.submit();">
                                <option value="">All Field Inspectors</option>
                                <?php foreach ($allInspectors as $insp): ?>
                                    <option value="<?php echo $insp['id']; ?>" <?php echo ($inspectorIdFilter == $insp['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($insp['full_name'] . ' (' . ($insp['employee_id'] ?: 'INS') . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div style="width: 180px;">
                        <select name="status" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="this.form.submit();">
                            <option value="">All Statuses</option>
                            <option value="COMPLIANT" <?php echo ($status === 'COMPLIANT') ? 'selected' : ''; ?>>COMPLIANT</option>
                            <option value="NON_COMPLIANT" <?php echo ($status === 'NON_COMPLIANT') ? 'selected' : ''; ?>>NON-COMPLIANT</option>
                            <option value="NEEDS_REVIEW" <?php echo ($status === 'NEEDS_REVIEW') ? 'selected' : ''; ?>>NEEDS REVIEW</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter Records
                    </button>
                    <?php if (!empty($q) || !empty($status) || !empty($inspectorIdFilter)): ?>
                        <a href="inspection-history.php" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Inspection Table Card -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Inspection Reports (<?php echo count($inspections); ?> Records)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Report #</th>
                                <th>Commodity &amp; Manufacturer</th>
                                <th>Category</th>
                                <th>Field Inspector</th>
                                <th>Status</th>
                                <th>Compliance Score</th>
                                <th>Date &amp; Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inspections)): ?>
                                <?php foreach ($inspections as $row): ?>
                                    <tr>
                                        <td>
                                            <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="table-id-link">
                                                <strong><?php echo htmlspecialchars($row['inspection_number']); ?></strong>
                                            </a>
                                        </td>
                                        <td>
                                            <strong style="color: var(--primary-dark); display: block; font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($row['product_name'] ?? 'Packaged Commodity'); ?>
                                            </strong>
                                            <span style="font-size: 0.8rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($row['company_name'] ?? $row['manufacturer_name'] ?? 'Manufacturer'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary" style="font-size: 0.78rem;">
                                                <?php echo htmlspecialchars($row['category'] ?? 'General'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column;">
                                                <span style="font-weight: 700; font-size: 0.88rem; color: var(--primary-dark);">
                                                    <i class="fas fa-user-check" style="color: #2563eb; margin-right: 4px;"></i>
                                                    <?php echo htmlspecialchars($row['inspector_name'] ?? 'Officer'); ?>
                                                </span>
                                                <?php if (!empty($row['inspector_emp_id'])): ?>
                                                    <span style="font-size: 0.74rem; color: var(--text-muted);">
                                                        ID: <?php echo htmlspecialchars($row['inspector_emp_id']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo get_compliance_badge($row['overall_status']); ?>
                                        </td>
                                        <td>
                                            <strong style="color: <?php echo ($row['compliance_score'] >= 90) ? '#16a34a' : (($row['compliance_score'] >= 60) ? '#d97706' : '#dc2626'); ?>;">
                                                <?php echo number_format($row['compliance_score'], 1); ?>%
                                            </strong>
                                        </td>
                                        <td style="font-size: 0.82rem; color: var(--text-muted); white-space: nowrap;">
                                            <?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm" title="View Full Inspection Details">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 36px; color: var(--text-muted);">
                                        <?php if ($isOfficer): ?>
                                            No inspection reports recorded in the system yet.
                                        <?php else: ?>
                                            You have not logged any package inspections under your account yet. <a href="new-inspection.php">Start a new scan</a>.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
