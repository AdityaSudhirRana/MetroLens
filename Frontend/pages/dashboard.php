<?php
/**
 * AI Package Inspector - Officer Control Dashboard
 */
$pageTitle = 'Control Dashboard';
$activeNav = 'dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();

// Fetch Dashboard Stats from MySQL
$totalInspections = 0;
$compliantCount = 0;
$nonCompliantCount = 0;
$avgScore = 0;

try {
    $statStmt = $db->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN overall_status = 'Compliant' THEN 1 ELSE 0 END) AS compliant,
            SUM(CASE WHEN overall_status = 'Non-Compliant' THEN 1 ELSE 0 END) AS non_compliant,
            AVG(compliance_score) AS avg_score
        FROM inspections
    ");
    $stats = $statStmt->fetch();

    $totalInspections = intval($stats['total'] ?? 0);
    $compliantCount = intval($stats['compliant'] ?? 0);
    $nonCompliantCount = intval($stats['non_compliant'] ?? 0);
    $avgScore = round(floatval($stats['avg_score'] ?? 0), 1);
} catch (Exception $e) {
    // Database table might be empty or fallback
}

$complianceRate = ($totalInspections > 0) ? round(($compliantCount / $totalInspections) * 100, 1) : 100;

// Fetch Recent Inspections
$recentInspections = [];
try {
    $recStmt = $db->query("
        SELECT i.*, p.product_name, p.manufacturer_name, u.full_name AS inspector_name
        FROM inspections i
        LEFT JOIN products p ON i.product_id = p.id
        LEFT JOIN users u ON i.inspector_id = u.id
        ORDER BY i.created_at DESC
        LIMIT 6
    ");
    $recentInspections = $recStmt->fetchAll();
} catch (Exception $e) {}

// Fetch Recent Audit Activity
$recentActivity = [];
try {
    $actStmt = $db->query("
        SELECT a.*, u.full_name 
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $recentActivity = $actStmt->fetchAll();
} catch (Exception $e) {}

?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h1>Welcome back, <?php echo htmlspecialchars($currentUser['name']); ?>!</h1>
                <p>Legal Metrology Officer &bull; Zone: <?php echo htmlspecialchars($currentUser['department']); ?> (ID: <?php echo htmlspecialchars($currentUser['employee_id']); ?>)</p>
            </div>
            <div class="date-pill">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
            </div>
        </div>

        <!-- KPI Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary"><i class="fas fa-clipboard-check"></i></div>
                    <span class="stat-change up"><i class="fas fa-arrow-up"></i> +12%</span>
                </div>
                <div class="stat-value"><?php echo number_format($totalInspections); ?></div>
                <div class="stat-label">Total Inspections Executed</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                    <span class="stat-change up"><i class="fas fa-arrow-up"></i> High</span>
                </div>
                <div class="stat-value"><?php echo number_format($compliantCount); ?></div>
                <div class="stat-label">Compliant Packages Verified</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                    <span class="stat-change down"><i class="fas fa-arrow-down"></i> Flagged</span>
                </div>
                <div class="stat-value"><?php echo number_format($nonCompliantCount); ?></div>
                <div class="stat-label">Violations Detected</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warning"><i class="fas fa-percentage"></i></div>
                    <span class="stat-change up"><i class="fas fa-shield-alt"></i> Legal</span>
                </div>
                <div class="stat-value"><?php echo $complianceRate; ?>%</div>
                <div class="stat-label">Overall Package Compliance Rate</div>
            </div>
        </div>

        <!-- Quick Action Grid -->
        <div class="quick-actions-grid">
            <a href="new-inspection.php" class="quick-action-btn">
                <div class="quick-action-icon"><i class="fas fa-camera"></i></div>
                <div class="quick-action-text">
                    <span class="quick-action-title">Start AI OCR Inspection</span>
                    <span class="quick-action-sub">Scan package photograph</span>
                </div>
            </a>
            <a href="inspection-history.php" class="quick-action-btn">
                <div class="quick-action-icon"><i class="fas fa-list-alt"></i></div>
                <div class="quick-action-text">
                    <span class="quick-action-title">Inspection Reports</span>
                    <span class="quick-action-sub">View history & PDF downloads</span>
                </div>
            </a>
            <a href="products.php" class="quick-action-btn">
                <div class="quick-action-icon"><i class="fas fa-boxes"></i></div>
                <div class="quick-action-text">
                    <span class="quick-action-title">Product Database</span>
                    <span class="quick-action-sub">Manage registered commodities</span>
                </div>
            </a>
            <a href="inquiries.php" class="quick-action-btn">
                <div class="quick-action-icon"><i class="fas fa-gavel"></i></div>
                <div class="quick-action-text">
                    <span class="quick-action-title">Legal Notices</span>
                    <span class="quick-action-sub">Issue violation inquiries</span>
                </div>
            </a>
        </div>

        <!-- Dashboard 2-Column Grid -->
        <div class="dashboard-grid-2">
            <!-- Left: Recent Inspections Table -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">Recent Inspection Log</h3>
                        <p class="card-subtitle">Latest package compliance verification records</p>
                    </div>
                    <a href="inspection-history.php" class="btn btn-outline btn-sm">View All Log</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Report #</th>
                                    <th>Product / Manufacturer</th>
                                    <th>Status</th>
                                    <th>Score</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentInspections)): ?>
                                    <?php foreach ($recentInspections as $row): ?>
                                        <tr>
                                            <td>
                                                <a href="inspection-result.php?id=<?php echo $row['id']; ?>" class="table-id-link">
                                                    <?php echo htmlspecialchars($row['inspection_number']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <strong style="color: var(--primary-dark); display: block;">
                                                    <?php echo htmlspecialchars($row['product_name'] ?? 'Packaged Commodity'); ?>
                                                </strong>
                                                <span style="font-size: 0.78rem; color: var(--text-muted);">
                                                    <?php echo htmlspecialchars($row['manufacturer_name'] ?? 'Manufacturer'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo get_compliance_badge($row['overall_status']); ?>
                                            </td>
                                            <td>
                                                <strong style="color: <?php echo ($row['compliance_score'] >= 90) ? '#16a34a' : (($row['compliance_score'] >= 60) ? '#d97706' : '#dc2626'); ?>;">
                                                    <?php echo number_format($row['compliance_score'], 1); ?>%
                                                </strong>
                                            </td>
                                            <td style="font-size: 0.82rem; color: var(--text-muted);">
                                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                            </td>
                                            <td>
                                                <a href="inspection-result.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm" title="View Full Report">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                            No inspections logged yet. <a href="new-inspection.php">Start your first AI package scan</a>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Compliance Gauge & Activity -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Gauge Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Enforcement Compliance</h3>
                    </div>
                    <div class="card-body gauge-card-body">
                        <div class="svg-donut-container">
                            <svg width="180" height="180" viewBox="0 0 36 36" class="donut">
                                <path class="donut-ring" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e2e8f0" stroke-width="3.8"/>
                                <path class="donut-segment" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#1e3a8a" stroke-width="3.8" stroke-dasharray="<?php echo $complianceRate; ?>, 100"/>
                            </svg>
                            <div class="donut-percentage-text">
                                <span class="donut-val"><?php echo $complianceRate; ?>%</span>
                                <span class="donut-lbl">Compliant</span>
                            </div>
                        </div>

                        <div class="gauge-breakdown-list">
                            <div class="breakdown-item">
                                <span><span class="breakdown-dot" style="background-color: #1e3a8a;"></span> Fully Compliant</span>
                                <strong><?php echo $compliantCount; ?></strong>
                            </div>
                            <div class="breakdown-item">
                                <span><span class="breakdown-dot" style="background-color: #dc2626;"></span> Violations Found</span>
                                <strong><?php echo $nonCompliantCount; ?></strong>
                            </div>
                            <div class="breakdown-item">
                                <span><span class="breakdown-dot" style="background-color: #f59e0b;"></span> Pending Review</span>
                                <strong><?php echo max(0, $totalInspections - $compliantCount - $nonCompliantCount); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Audit Activity Feed</h3>
                    </div>
                    <div class="card-body">
                        <div class="activity-feed">
                            <?php if (!empty($recentActivity)): ?>
                                <?php foreach ($recentActivity as $act): ?>
                                    <div class="activity-item">
                                        <div class="activity-avatar" style="background: #1e3a8a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.8rem;">
                                            <?php echo strtoupper(substr($act['full_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div class="activity-details">
                                            <div class="activity-title">
                                                <strong><?php echo htmlspecialchars($act['full_name'] ?? 'Officer'); ?></strong>: 
                                                <?php echo htmlspecialchars($act['details'] ?? $act['action']); ?>
                                            </div>
                                            <div class="activity-time"><?php echo date('M d, H:i', strtotime($act['created_at'])); ?> &bull; IP: <?php echo htmlspecialchars($act['ip_address']); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="font-size: 0.85rem; color: var(--text-muted);">No activity recorded yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
