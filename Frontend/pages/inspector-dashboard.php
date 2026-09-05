<?php
/**
 * AI Package Inspector - Field Inspector Personal Dashboard
 * File: pages/inspector-dashboard.php
 */
$pageTitle = 'Field Inspector Dashboard';
$activeNav = 'inspector-dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

requireInspector(); // PHP backend RBAC guard

$db = getDB();
$userId = $_SESSION['user_id'];

$myTotalInspections = 0;
$myTodaysInspections = 0;
$myCompliantCount = 0;
$myNonCompliantCount = 0;
$myOpenInquiries = 0;

try {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) AS today,
            SUM(CASE WHEN overall_status = 'COMPLIANT' THEN 1 ELSE 0 END) AS compliant,
            SUM(CASE WHEN overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS non_compliant
        FROM inspections
        WHERE inspector_id = ?
    ");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();

    $myTotalInspections = intval($stats['total'] ?? 0);
    $myTodaysInspections = intval($stats['today'] ?? 0);
    $myCompliantCount = intval($stats['compliant'] ?? 0);
    $myNonCompliantCount = intval($stats['non_compliant'] ?? 0);

    $inqStmt = $db->prepare("SELECT COUNT(*) FROM inquiries WHERE inspector_id = ? AND status IN ('Issued', 'Responded', 'Under Review')");
    $inqStmt->execute([$userId]);
    $myOpenInquiries = intval($inqStmt->fetchColumn());

} catch (Exception $e) {}

// Fetch Inspections done by this inspector
$myRecentInspections = [];
try {
    $recStmt = $db->prepare("
        SELECT i.*, p.product_name, c.company_name
        FROM inspections i
        LEFT JOIN products p ON i.product_id = p.id
        LEFT JOIN companies c ON i.company_id = c.id
        WHERE i.inspector_id = ?
        ORDER BY i.created_at DESC
        LIMIT 10
    ");
    $recStmt->execute([$userId]);
    $myRecentInspections = $recStmt->fetchAll();
} catch (Exception $e) {}
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <!-- Welcome Banner -->
        <div class="welcome-banner" style="background: linear-gradient(135deg, #0d9488 0%, #115e59 100%); flex-wrap: wrap; gap: 16px;">
            <div class="welcome-text">
                <h1>Field Inspector Workspace</h1>
                <p>Welcome, <?php echo htmlspecialchars($currentUser['name']); ?> &bull; Role: <strong>FIELD INSPECTOR</strong> (ID: <?php echo htmlspecialchars($currentUser['employee_id']); ?>)</p>
            </div>
            <div class="date-pill">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <!-- Personal Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary"><i class="fas fa-camera"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($myTotalInspections); ?></div>
                <div class="stat-label">My Total Package Scans</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><i class="fas fa-calendar-check"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($myTodaysInspections); ?></div>
                <div class="stat-label">Scans Completed Today</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($myCompliantCount); ?></div>
                <div class="stat-label">Compliant Packages Verified</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" style="color: #dc2626;"><?php echo number_format($myNonCompliantCount); ?></div>
                <div class="stat-label">Violations Detected</div>
            </div>
        </div>

        <!-- Quick Action Hero Tile -->
        <div style="background: #ffffff; border-radius: 12px; border: 1px solid var(--border); padding: 24px 28px; margin-bottom: 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: var(--shadow-sm);">
            <div>
                <h3 style="font-size: 1.25rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 4px;">
                    <i class="fas fa-camera" style="color: var(--primary);"></i> Perform New Package Scan
                </h3>
                <p style="font-size: 0.9rem; color: var(--text-muted);">Capture commodity packaging using device camera or upload image for AI OCR text extraction</p>
            </div>
            <a href="new-inspection.php" class="btn btn-primary btn-lg" style="padding: 12px 24px;">
                <i class="fas fa-camera"></i> Launch Camera Scan
            </a>
        </div>

        <!-- My Inspection History Table -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h3 class="card-title">My Inspection History</h3>
                <a href="inspection-history.php" class="btn btn-outline btn-sm">View Full History</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Report #</th>
                                <th>Company &amp; Product</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Date &amp; Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($myRecentInspections)): ?>
                                <?php foreach ($myRecentInspections as $row): ?>
                                    <tr>
                                        <td>
                                            <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="table-id-link">
                                                <?php echo htmlspecialchars($row['inspection_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong style="color: var(--primary-dark); display: block;">
                                                <?php echo htmlspecialchars($row['company_name'] ?? 'Company'); ?>
                                            </strong>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($row['product_name'] ?? 'Product'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo get_compliance_badge($row['overall_status']); ?></td>
                                        <td>
                                            <strong style="color: <?php echo ($row['compliance_score'] >= 90) ? '#16a34a' : '#dc2626'; ?>;">
                                                <?php echo number_format($row['compliance_score'], 1); ?>%
                                            </strong>
                                        </td>
                                        <td style="font-size: 0.82rem; color: var(--text-muted);"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">
                                                <i class="fas fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        You have not performed any package inspections yet. <a href="new-inspection.php">Start your first scan</a>.
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
