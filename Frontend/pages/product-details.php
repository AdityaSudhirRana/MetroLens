<?php
/**
 * AI Package Inspector - Single Product Profile & Inspection History Detail
 */
$pageTitle = 'Commodity Product Profile';
$activeNav = 'products';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: products.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div class='main-content'><div class='content-body'><div class='alert alert-danger'>Product not found.</div></div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch Inspections History
$historyStmt = $db->prepare("
    SELECT i.*, u.full_name AS inspector_name 
    FROM inspections i 
    LEFT JOIN users u ON i.inspector_id = u.id 
    WHERE i.product_id = ? 
    ORDER BY i.created_at DESC
");
$historyStmt->execute([$id]);
$inspections = $historyStmt->fetchAll();
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="margin-bottom: 24px;">
            <a href="products.php" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">
                <i class="fas fa-arrow-left"></i> Back to Product Database
            </a>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">
                <?php echo htmlspecialchars($product['product_name']); ?>
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Manufacturer: <strong><?php echo htmlspecialchars($product['manufacturer_name']); ?></strong> &bull; Category: <?php echo htmlspecialchars($product['category']); ?>
            </p>
        </div>

        <!-- Product Stat Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary"><i class="fas fa-clipboard-list"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($product['total_inspections']); ?></div>
                <div class="stat-label">Total Field Inspections</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" style="color: <?php echo ($product['total_violations'] > 0) ? '#dc2626' : '#16a34a'; ?>;">
                    <?php echo number_format($product['total_violations']); ?>
                </div>
                <div class="stat-label">Total Violations Flagged</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><i class="fas fa-percentage"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($product['compliance_rate'], 1); ?>%</div>
                <div class="stat-label">Product Compliance Rate</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warning"><i class="fas fa-barcode"></i></div>
                </div>
                <div class="stat-value" style="font-size: 1.2rem; font-family: monospace;">
                    <?php echo htmlspecialchars($product['gtin_barcode'] ?: 'N/A'); ?>
                </div>
                <div class="stat-label">GTIN / Barcode Identifier</div>
            </div>
        </div>

        <!-- History Table Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Inspection Log History for <?php echo htmlspecialchars($product['product_name']); ?></h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Report #</th>
                                <th>Inspection Type</th>
                                <th>Inspecting Officer</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Date & Time</th>
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
                                        <td><?php echo htmlspecialchars($row['inspection_type']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['inspector_name'] ?? 'Officer'); ?></strong></td>
                                        <td><?php echo get_compliance_badge($row['overall_status']); ?></td>
                                        <td>
                                            <strong style="color: <?php echo ($row['compliance_score'] >= 90) ? '#16a34a' : '#dc2626'; ?>;">
                                                <?php echo number_format($row['compliance_score'], 1); ?>%
                                            </strong>
                                        </td>
                                        <td style="font-size: 0.82rem; color: var(--text-muted);"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="inspection-result.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">
                                        No inspection history recorded for this commodity yet.
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
