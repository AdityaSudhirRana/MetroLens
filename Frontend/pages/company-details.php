<?php
/**
 * AI Package Inspector - Single Company Profile & Complete Inspection History
 * File: pages/company-details.php
 */
$pageTitle = 'Company Profile';
$activeNav = 'companies';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: companies.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM companies WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$company = $stmt->fetch();

if (!$company) {
    echo "<div class='main-content'><div class='content-body'><div class='alert alert-danger'>Company not found.</div></div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch products for this company
$pStmt = $db->prepare("SELECT * FROM products WHERE company_id = ? ORDER BY product_name ASC");
$pStmt->execute([$id]);
$products = $pStmt->fetchAll();

// Fetch inspection history for this company
$iStmt = $db->prepare("
    SELECT i.*, p.product_name, u.full_name AS inspector_name
    FROM inspections i
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN users u ON i.inspector_id = u.id
    WHERE i.company_id = ?
    ORDER BY i.inspection_date DESC
");
$iStmt->execute([$id]);
$inspections = $iStmt->fetchAll();

$totalInspections = count($inspections);
$compliantInspections = 0;
$nonCompliantInspections = 0;
foreach ($inspections as $ins) {
    if ($ins['overall_status'] === 'COMPLIANT') $compliantInspections++;
    else $nonCompliantInspections++;
}
$complianceRate = ($totalInspections > 0) ? round(($compliantInspections / $totalInspections) * 100, 1) : 100;
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="margin-bottom: 24px;">
            <a href="companies.php" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">
                <i class="fas fa-arrow-left"></i> Back to Companies Catalog
            </a>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">
                <?php echo htmlspecialchars($company['company_name']); ?>
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Type: <strong><?php echo htmlspecialchars($company['company_type']); ?></strong> &bull; Address: <?php echo htmlspecialchars($company['address']); ?>
            </p>
        </div>

        <!-- Company KPI Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary"><i class="fas fa-clipboard-check"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($totalInspections); ?></div>
                <div class="stat-label">Total Field Inspections</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($compliantInspections); ?></div>
                <div class="stat-label">Compliant Inspections</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
                <div class="stat-value" style="color: #dc2626;"><?php echo number_format($nonCompliantInspections); ?></div>
                <div class="stat-label">Violations Detected</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warning"><i class="fas fa-percentage"></i></div>
                </div>
                <div class="stat-value"><?php echo $complianceRate; ?>%</div>
                <div class="stat-label">Company Compliance Rate</div>
            </div>
        </div>

        <!-- Registered Commodities List -->
        <div class="card" style="margin-bottom: 28px;">
            <div class="card-header">
                <h3 class="card-title">Registered Commodities (<?php echo count($products); ?> Products)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>GTIN / Barcode</th>
                                <th>Product Name & Brand</th>
                                <th>Category</th>
                                <th>Inspections</th>
                                <th>Violations</th>
                                <th>Compliance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($p['gtin_barcode'] ?: 'N/A'); ?></code></td>
                                        <td><strong><?php echo htmlspecialchars($p['product_name']); ?></strong></td>
                                        <td><span class="badge badge-secondary"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                        <td><strong><?php echo number_format($p['total_inspections']); ?></strong></td>
                                        <td><strong style="color: <?php echo ($p['total_violations'] > 0) ? '#dc2626' : '#16a34a'; ?>;"><?php echo number_format($p['total_violations']); ?></strong></td>
                                        <td><strong><?php echo number_format($p['compliance_rate'], 1); ?>%</strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; padding: 20px; color: var(--text-muted);">No products registered under this company yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Complete Chronological Inspection History Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Chronological Inspection History</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Inspection Date</th>
                                <th>Report #</th>
                                <th>Product</th>
                                <th>Inspecting Officer</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($inspections)): ?>
                                <?php foreach ($inspections as $row): ?>
                                    <tr>
                                        <td><strong style="color: var(--primary-dark);"><?php echo date('M d, Y H:i', strtotime($row['inspection_date'])); ?></strong></td>
                                        <td>
                                            <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="table-id-link">
                                                <?php echo htmlspecialchars($row['inspection_number']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['product_name'] ?? 'Commodity'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['inspector_name'] ?? 'Officer'); ?></strong></td>
                                        <td><?php echo get_compliance_badge($row['overall_status']); ?></td>
                                        <td><strong><?php echo number_format($row['compliance_score'], 1); ?>%</strong></td>
                                        <td>
                                            <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center; padding: 24px; color: var(--text-muted);">No inspection history recorded.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
