<?php
/**
 * AI Package Inspector - Statutory Legal Inquiries & Notices
 */
$pageTitle = 'Legal Metrology Inquiries';
$activeNav = 'inquiries';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
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
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">Statutory Legal Notices & Inquiries</h2>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Enforcement notices issued under Section 18 of Legal Metrology Act 2009</p>
            </div>
        </div>

        <!-- Inquiries Table Card -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title">Notice Records (<?php echo count($inquiries); ?> Issued)</h3>
                <div style="display: flex; gap: 8px;">
                    <a href="inquiries.php" class="btn btn-outline btn-sm <?php echo empty($status) ? 'active' : ''; ?>">All</a>
                    <a href="inquiries.php?status=Issued" class="btn btn-outline btn-sm <?php echo ($status === 'Issued') ? 'active' : ''; ?>">Issued</a>
                    <a href="inquiries.php?status=Responded" class="btn btn-outline btn-sm <?php echo ($status === 'Responded') ? 'active' : ''; ?>">Responded</a>
                    <a href="inquiries.php?status=Resolved" class="btn btn-outline btn-sm <?php echo ($status === 'Resolved') ? 'active' : ''; ?>">Resolved</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Notice #</th>
                                <th>Manufacturer / Packer</th>
                                <th>Subject</th>
                                <th>Issuing Officer</th>
                                <th>Issued Date</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inquiries)): ?>
                                <?php foreach ($inquiries as $row): ?>
                                    <tr>
                                        <td>
                                            <a href="inquiry-details.php?id=<?php echo $row['id']; ?>" class="table-id-link">
                                                <?php echo htmlspecialchars($row['inquiry_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <strong style="color: var(--primary-dark); display: block;">
                                                <?php echo htmlspecialchars($row['manufacturer_name']); ?>
                                            </strong>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);">
                                                Ref Inspection: <?php echo htmlspecialchars($row['inspection_number'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td style="max-width: 260px; font-size: 0.88rem;">
                                            <?php echo htmlspecialchars($row['subject']); ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($row['inspector_name'] ?? 'Officer'); ?></strong></td>
                                        <td style="font-size: 0.82rem; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($row['issued_at'])); ?></td>
                                        <td>
                                            <span style="font-size: 0.85rem; font-weight: 700; color: <?php echo (strtotime($row['response_deadline']) < time()) ? '#dc2626' : '#d97706'; ?>;">
                                                <?php echo date('M d, Y', strtotime($row['response_deadline'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                                $st = $row['status'];
                                                $badgeClass = ($st === 'Resolved') ? 'badge-success' : (($st === 'Issued') ? 'badge-warning' : (($st === 'Escalated') ? 'badge-danger' : 'badge-primary'));
                                            ?>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($st); ?></span>
                                        </td>
                                        <td>
                                            <a href="inquiry-details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">
                                                <i class="fas fa-eye"></i> Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        No legal inquiries found.
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
