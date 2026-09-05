<?php
/**
 * AI Package Inspector - Officer Management of Field Inspectors
 * File: pages/manage-inspectors.php
 */
$pageTitle = 'Manage Field Inspectors';
$activeNav = 'inspectors';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

requireOfficer(); // Backend RBAC guard

$db = getDB();

// Handle Status Toggle (Active / Inactive)
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $toggleId = intval($_POST['toggle_id']);
    $newStatus = ($_POST['current_status'] === 'Active') ? 'Inactive' : 'Active';
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'inspector'");
    $stmt->execute([$newStatus, $toggleId]);
    log_audit($_SESSION['user_id'], 'TOGGLE_INSPECTOR_STATUS', "Inspector #{$toggleId} set to {$newStatus}");
    $msg = "Inspector status updated to {$newStatus}.";
}

// Fetch all inspectors
$stmt = $db->query("
    SELECT u.*, 
           COUNT(i.id) AS total_inspections,
           SUM(CASE WHEN i.overall_status = 'COMPLIANT' THEN 1 ELSE 0 END) AS compliant_count,
           SUM(CASE WHEN i.overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS non_compliant_count,
           MAX(i.created_at) AS last_inspection_date
    FROM users u
    LEFT JOIN inspections i ON u.id = i.inspector_id
    WHERE u.role = 'inspector'
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$inspectors = $stmt->fetchAll();
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">Field Inspector Personnel Management</h2>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Manage field officer accounts, onboarding, and performance activity</p>
            </div>
            <a href="register-inspector.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Onboard New Inspector
            </a>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px 16px; background-color: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 6px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- Inspector Directory Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Registered Field Inspectors (<?php echo count($inspectors); ?> Officers)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Inspector Name & ID</th>
                                <th>Email & Phone</th>
                                <th>Region / Zone</th>
                                <th>Total Scans</th>
                                <th>Compliant vs Violations</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inspectors)): ?>
                                <?php foreach ($inspectors as $row): ?>
                                    <tr>
                                        <td>
                                            <strong style="color: var(--primary-dark); display: block;">
                                                <?php echo htmlspecialchars($row['full_name']); ?>
                                            </strong>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);">
                                                ID: <?php echo htmlspecialchars($row['employee_id']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.88rem;">
                                            <?php echo htmlspecialchars($row['email']); ?><br>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($row['phone'] ?: 'N/A'); ?></span>
                                        </td>
                                        <td style="font-size: 0.88rem;"><?php echo htmlspecialchars($row['office_region']); ?></td>
                                        <td><strong style="color: var(--primary);"><?php echo number_format($row['total_inspections']); ?></strong></td>
                                        <td>
                                            <span style="color: #16a34a; font-weight: 700;"><?php echo number_format($row['compliant_count']); ?> Compliant</span> / 
                                            <span style="color: #dc2626; font-weight: 700;"><?php echo number_format($row['non_compliant_count']); ?> Violations</span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($row['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <a href="inspector-details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm" title="View Activity">
                                                    <i class="fas fa-eye"></i> Activity
                                                </a>
                                                <form action="manage-inspectors.php" method="POST" style="display: inline;">
                                                    <input type="hidden" name="toggle_id" value="<?php echo $row['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $row['status']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Change status for <?php echo htmlspecialchars($row['full_name']); ?>?');">
                                                        <i class="fas fa-power-off"></i> <?php echo ($row['status'] === 'Active') ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">No field inspectors registered yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
