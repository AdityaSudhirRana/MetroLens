<?php
/**
 * AI Package Inspector - Inspector Details & Officer Performance Record
 */
$pageTitle = 'Inspector Profile Detail';
$activeNav = 'profile';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$id = $_GET['id'] ?? $_SESSION['user_id'];

$stmt = $db->prepare("
    SELECT u.*, 
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
    echo "<div class='main-content'><div class='content-body'><div class='alert alert-danger'>Inspector record not found.</div></div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch inspections done by this officer
$inspStmt = $db->prepare("
    SELECT i.*, p.product_name, p.manufacturer_name
    FROM inspections i
    LEFT JOIN products p ON i.product_id = p.id
    WHERE i.inspector_id = ?
    ORDER BY i.created_at DESC
    LIMIT 20
");
$inspStmt->execute([$inspector['id']]);
$inspections = $inspStmt->fetchAll();
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="margin-bottom: 24px;">
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">
                Officer Performance: <?php echo htmlspecialchars($inspector['full_name']); ?>
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Employee ID: <strong><?php echo htmlspecialchars($inspector['employee_id']); ?></strong> &bull; Department: <?php echo htmlspecialchars($inspector['department']); ?>
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary"><i class="fas fa-clipboard-check"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($inspector['total_inspections']); ?></div>
                <div class="stat-label">Inspections Executed</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($inspector['compliant_inspections']); ?></div>
                <div class="stat-label">Compliant Packages Verified</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger"><i class="fas fa-gavel"></i></div>
                </div>
                <div class="stat-value" style="color: #dc2626;"><?php echo number_format($inspector['non_compliant_inspections']); ?></div>
                <div class="stat-label">Violations Detected</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warning"><i class="fas fa-user-shield"></i></div>
                </div>
                <div class="stat-value" style="font-size: 1.2rem;"><?php echo htmlspecialchars($inspector['role']); ?></div>
                <div class="stat-label">System Role Authorization</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Recent Inspection Reports Logged by Officer</h3>
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
                            <?php if (!empty($inspections)): ?>
                                <?php foreach ($inspections as $row): ?>
                                    <tr>
                                        <td>
                                            <a href="inspection-result.php?id=<?php echo $row['id']; ?>" class="table-id-link">
                                                <?php echo htmlspecialchars($row['inspection_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['manufacturer_name']); ?></span>
                                        </td>
                                        <td><?php echo get_compliance_badge($row['overall_status']); ?></td>
                                        <td><strong><?php echo number_format($row['compliance_score'], 1); ?>%</strong></td>
                                        <td style="font-size: 0.82rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="inspection-result.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No inspections logged by this officer yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
