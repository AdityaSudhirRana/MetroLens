<?php
/**
 * AI Package Inspector - Reports & Analytics Suite
 */
$pageTitle = 'Reports & Analytics';
$activeNav = 'reports';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();

// Fetch report stats
$kpiStmt = $db->query("
    SELECT 
        COUNT(*) AS total_inspections,
        SUM(CASE WHEN overall_status = 'Compliant' THEN 1 ELSE 0 END) AS compliant_count,
        SUM(CASE WHEN overall_status = 'Non-Compliant' THEN 1 ELSE 0 END) AS non_compliant_count,
        ROUND(AVG(compliance_score), 1) AS avg_score
    FROM inspections
");
$kpi = $kpiStmt->fetch();

$total = max(1, intval($kpi['total_inspections']));
$compliantCount = intval($kpi['compliant_count']);
$nonCompliantCount = intval($kpi['non_compliant_count']);
$complianceRate = round(($compliantCount / $total) * 100, 1);

// Top Violators
$violatorsStmt = $db->query("
    SELECT manufacturer_name, COUNT(*) AS total_inspected, 
           SUM(total_violations) AS violations_count,
           compliance_rate
    FROM products
    WHERE total_violations > 0
    ORDER BY total_violations DESC
    LIMIT 10
");
$topViolators = $violatorsStmt->fetchAll();

// Top Violation Types
$violTypesStmt = $db->query("
    SELECT rule_name, COUNT(*) AS count, SUM(penalty_amount) AS total_penalty
    FROM violations
    GROUP BY rule_name
    ORDER BY count DESC
    LIMIT 5
");
$violationTypes = $violTypesStmt->fetchAll();
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">Legal Metrology Analytics & Reports</h2>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Macro enforcement trends, manufacturer compliance indices, and violation statistics</p>
            </div>
            <button onclick="window.print();" class="btn btn-outline">
                <i class="fas fa-print"></i> Export Analytics Report
            </button>
        </div>

        <!-- KPI Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon primary"><i class="fas fa-chart-pie"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($total); ?></div>
                <div class="stat-label">Total Inspections Audited</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon success"><i class="fas fa-check-double"></i></div>
                </div>
                <div class="stat-value"><?php echo number_format($compliantCount); ?></div>
                <div class="stat-label">Compliant Package Declarations</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon danger"><i class="fas fa-gavel"></i></div>
                </div>
                <div class="stat-value" style="color: #dc2626;"><?php echo number_format($nonCompliantCount); ?></div>
                <div class="stat-label">Non-Compliant Violations Flagged</div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon warning"><i class="fas fa-percentage"></i></div>
                </div>
                <div class="stat-value"><?php echo $complianceRate; ?>%</div>
                <div class="stat-label">Zone Compliance Index</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">

            <!-- Top Violating Manufacturers Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-building" style="color: var(--danger);"></i> Top Violator Manufacturers</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Manufacturer / Packer</th>
                                    <th>Inspections</th>
                                    <th>Violations</th>
                                    <th>Compliance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($topViolators)): ?>
                                    <?php foreach ($topViolators as $mfr): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: var(--primary-dark);">
                                                    <?php echo htmlspecialchars($mfr['manufacturer_name']); ?>
                                                </strong>
                                            </td>
                                            <td><?php echo number_format($mfr['total_inspected']); ?></td>
                                            <td><strong style="color: #dc2626;"><?php echo number_format($mfr['violations_count']); ?></strong></td>
                                            <td>
                                                <strong style="color: <?php echo ($mfr['compliance_rate'] >= 90) ? '#16a34a' : '#dc2626'; ?>;">
                                                    <?php echo number_format($mfr['compliance_rate'], 1); ?>%
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">No major violator manufacturers logged.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Top Violation Types Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i> Frequent Legal Metrology Rule Violations</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>LM Act Rule / Declaration</th>
                                    <th>Occurrences</th>
                                    <th>Statutory Fine</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($violationTypes)): ?>
                                    <?php foreach ($violationTypes as $v): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: var(--primary-dark);"><?php echo htmlspecialchars($v['rule_name']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge badge-danger"><?php echo number_format($v['count']); ?> Cases</span>
                                            </td>
                                            <td>
                                                <strong style="color: var(--text);">₹<?php echo number_format($v['total_penalty'], 2); ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align: center; padding: 20px; color: var(--text-muted);">No statutory violations recorded yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
