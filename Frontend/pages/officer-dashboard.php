<?php
/**
 * AI Package Inspector - Officer Control Dashboard
 * File: pages/officer-dashboard.php
 */
$pageTitle = 'Officer Control Dashboard';
$activeNav = 'officer-dashboard';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

requireOfficer(); // PHP backend RBAC guard

$db = getDB();

// Fetch System-wide Statistics
$totalCompanies = 0;
$totalInspectors = 0;
$totalInspections = 0;
$todaysInspections = 0;
$compliantCount = 0;
$nonCompliantCount = 0;

try {
    $cStmt = $db->query("SELECT COUNT(*) FROM companies");
    $totalCompanies = intval($cStmt->fetchColumn());

    $uStmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'inspector'");
    $totalInspectors = intval($uStmt->fetchColumn());

    $iStmt = $db->query("SELECT COUNT(*) FROM inspections");
    $totalInspections = intval($iStmt->fetchColumn());

    $statStmt = $db->query("
        SELECT 
            SUM(CASE WHEN overall_status = 'COMPLIANT' THEN 1 ELSE 0 END) AS compliant,
            SUM(CASE WHEN overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS non_compliant
        FROM inspections
    ");
    $stats = $statStmt->fetch();
    $compliantCount = intval($stats['compliant'] ?? 0);
    $nonCompliantCount = intval($stats['non_compliant'] ?? 0);
} catch (Exception $e) {}

// Fetch Recent System Inspections
$recentInspections = [];
try {
    $recStmt = $db->query("
        SELECT i.*, p.product_name, c.company_name, u.full_name AS inspector_name
        FROM inspections i
        LEFT JOIN products p ON i.product_id = p.id
        LEFT JOIN companies c ON i.company_id = c.id
        LEFT JOIN users u ON i.inspector_id = u.id
        ORDER BY i.created_at DESC
        LIMIT 5
    ");
    $recentInspections = $recStmt->fetchAll();
} catch (Exception $e) {}

// Fetch Inspector Activity Stream
$inspectorActivity = [];
try {
    $actStmt = $db->query("
        SELECT u.id, u.full_name, u.employee_id, COUNT(i.id) AS total_scans, MAX(i.created_at) AS last_scan
        FROM users u
        LEFT JOIN inspections i ON u.id = i.inspector_id
        WHERE u.role = 'inspector'
        GROUP BY u.id
        ORDER BY last_scan DESC
    ");
    $inspectorActivity = $actStmt->fetchAll();
} catch (Exception $e) {}

$userEmpId = $currentUser['employee_id'] ?? 'OFF-2026-979';
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <!-- Officer System Overview Header -->
        <div class="dashboard-welcome-header">
            <div class="welcome-header-left">
                <h2 class="overview-title">Officer System Overview</h2>
                <p class="overview-sub">
                    Welcome back, <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong> &bull; Role: <strong>CHIEF OFFICER</strong> (ID: <?php echo htmlspecialchars($userEmpId); ?>)
                </p>
            </div>
            <div class="welcome-header-right">
                <span class="date-badge"><i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?></span>
            </div>
        </div>

        <!-- 4 KPI Stat Cards Row -->
        <div class="kpi-cards-grid">
            <div class="kpi-card kpi-blue">
                <div class="kpi-icon-box">
                    <i class="fas fa-building"></i>
                </div>
                <div class="kpi-data">
                    <div class="kpi-value" id="statTotalCompanies"><?php echo number_format($totalCompanies); ?></div>
                    <div class="kpi-label">Total Companies Tracked</div>
                </div>
            </div>

            <div class="kpi-card kpi-green">
                <div class="kpi-icon-box">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="kpi-data">
                    <div class="kpi-value" id="statTotalInspectors"><?php echo number_format($totalInspectors); ?></div>
                    <div class="kpi-label">Active Field Inspectors</div>
                </div>
            </div>

            <div class="kpi-card kpi-purple">
                <div class="kpi-icon-box">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="kpi-data">
                    <div class="kpi-value" id="statTotalInspections"><?php echo number_format($totalInspections); ?></div>
                    <div class="kpi-label">Total System Inspections</div>
                </div>
            </div>

            <div class="kpi-card kpi-orange">
                <div class="kpi-icon-box">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="kpi-data">
                    <div class="kpi-value kpi-val-danger" id="statNonCompliant"><?php echo number_format($nonCompliantCount); ?></div>
                    <div class="kpi-label">Non-Compliant Violations</div>
                </div>
            </div>
        </div>

        <!-- 4 Quick Action Cards Row -->
        <div class="quick-action-cards-grid">
            <a href="companies.php" class="action-card">
                <div class="action-icon-circle action-circle-blue">
                    <i class="fas fa-building"></i>
                </div>
                <div class="action-text-box">
                    <span class="action-title">Company Catalog &amp; Dates</span>
                    <span class="action-sub">Drill down company inspection history</span>
                </div>
                <i class="fas fa-chevron-right action-chevron"></i>
            </a>

            <a href="manage-inspectors.php" class="action-card">
                <div class="action-icon-circle action-circle-green">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="action-text-box">
                    <span class="action-title">Manage Inspectors</span>
                    <span class="action-sub">Onboard &amp; view inspector activity</span>
                </div>
                <i class="fas fa-chevron-right action-chevron"></i>
            </a>

            <a href="new-inspection.php" class="action-card">
                <div class="action-icon-circle action-circle-purple">
                    <i class="fas fa-camera"></i>
                </div>
                <div class="action-text-box">
                    <span class="action-title">Start AI OCR Inspection</span>
                    <span class="action-sub">Run live camera package scan</span>
                </div>
                <i class="fas fa-chevron-right action-chevron"></i>
            </a>

            <a href="reports.php" class="action-card">
                <div class="action-icon-circle action-circle-orange">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="action-text-box">
                    <span class="action-title">Reports &amp; Analytics</span>
                    <span class="action-sub">Executive compliance summary</span>
                </div>
                <i class="fas fa-chevron-right action-chevron"></i>
            </a>
        </div>

        <!-- Main 2-Column Section (Log Table Left, Inspector Stream Right) -->
        <div class="main-dashboard-grid">
            
            <!-- System-Wide Inspection Log Card (62% width) -->
            <div class="dashboard-panel-card">
                <div class="panel-card-header">
                    <div>
                        <h3 class="panel-card-title">System-Wide Inspection Log</h3>
                        <p class="panel-card-sub">Recent field inspection records across all zones</p>
                    </div>
                    <a href="inspection-history.php" class="panel-header-btn">
                        View All Log <i class="fas fa-arrow-right"></i>
                    </a>
                </div>

                <div class="panel-card-body p-0">
                    <div class="table-responsive">
                        <table class="mockup-data-table">
                            <thead>
                                <tr>
                                    <th>Report #</th>
                                    <th>Company &amp; Product</th>
                                    <th>Inspector</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="officerRecentTbody">
                                <?php if (!empty($recentInspections)): ?>
                                    <?php foreach ($recentInspections as $row): ?>
                                        <tr>
                                            <td class="col-report-id">
                                                <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="report-id-link">
                                                    <?php echo htmlspecialchars($row['inspection_number']); ?>
                                                </a>
                                            </td>
                                            <td class="col-company-prod">
                                                <div class="company-name-text"><?php echo htmlspecialchars($row['company_name'] ?? 'Company'); ?></div>
                                                <div class="product-name-text"><?php echo htmlspecialchars($row['product_name'] ?? 'Product'); ?></div>
                                            </td>
                                            <td class="col-inspector-name">
                                                <?php echo htmlspecialchars($row['inspector_name'] ?? 'Inspector'); ?>
                                            </td>
                                            <td class="col-status-badge">
                                                <?php if ($row['overall_status'] === 'COMPLIANT'): ?>
                                                    <span class="badge-pill-compliant">Compliant</span>
                                                <?php else: ?>
                                                    <span class="badge-pill-non-compliant">Non-Compliant</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="col-date-text">
                                                <?php echo date('M d, Y', strtotime($row['created_at'])); ?>
                                            </td>
                                            <td class="col-action-btn">
                                                <a href="inspection-details.php?id=<?php echo $row['id']; ?>" class="btn-table-details">
                                                    <i class="far fa-file-alt"></i> Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="empty-table-cell">No inspections logged yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Inspector Activity Record Card (38% width) -->
            <div class="dashboard-panel-card">
                <div class="panel-card-header">
                    <h3 class="panel-card-title">Inspector Activity Record</h3>
                    <a href="manage-inspectors.php" class="panel-header-btn-manage">
                        <i class="fas fa-cog"></i> Manage
                    </a>
                </div>

                <div class="panel-card-body p-0">
                    <div class="table-responsive">
                        <table class="mockup-inspector-table">
                            <thead>
                                <tr>
                                    <th>Field Inspector</th>
                                    <th>Total Scans</th>
                                    <th>Last Active</th>
                                </tr>
                            </thead>
                            <tbody id="officerActivityTbody">
                                <?php if (!empty($inspectorActivity)): ?>
                                    <?php foreach ($inspectorActivity as $ins): ?>
                                        <?php 
                                            $insInitial = strtoupper(substr($ins['full_name'], 0, 1));
                                            $lastActive = $ins['last_scan'] ? date('M d, H:i', strtotime($ins['last_scan'])) : 'Never';
                                            $hasScan = !empty($ins['last_scan']);
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="inspector-profile-item">
                                                    <div class="inspector-avatar-circle">
                                                        <?php echo $insInitial; ?>
                                                    </div>
                                                    <div class="inspector-meta-box">
                                                        <div class="inspector-full-name"><?php echo htmlspecialchars($ins['full_name']); ?></div>
                                                        <div class="inspector-code-id"><?php echo htmlspecialchars($ins['employee_id']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="col-scans-count">
                                                <strong><?php echo number_format($ins['total_scans']); ?></strong> Scans
                                            </td>
                                            <td class="col-last-active">
                                                <span class="<?php echo $hasScan ? 'active-dot-green' : 'active-dot-grey'; ?>"></span>
                                                <?php echo $lastActive; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="empty-table-cell">No active inspectors found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    // Live Dynamic Reflection Script: Polls backend so Inspector actions reflect live
    function fetchLiveDashboardData() {
        fetch('../api/inspections.php?action=dashboard_live')
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                // Update Stats
                if (data.stats) {
                    const elC = document.getElementById('statTotalCompanies');
                    const elIns = document.getElementById('statTotalInspectors');
                    const elT = document.getElementById('statTotalInspections');
                    const elNC = document.getElementById('statNonCompliant');

                    if (elC) elC.innerText = Number(data.stats.total_companies).toLocaleString();
                    if (elIns) elIns.innerText = Number(data.stats.total_inspectors).toLocaleString();
                    if (elT) elT.innerText = Number(data.stats.total_inspections).toLocaleString();
                    if (elNC) elNC.innerText = Number(data.stats.non_compliant_count).toLocaleString();
                }

                // Update Recent Inspections Feed
                if (data.recent_inspections && data.recent_inspections.length > 0) {
                    const tbody = document.getElementById('officerRecentTbody');
                    if (tbody) {
                        let html = '';
                        data.recent_inspections.forEach(row => {
                            const badgePill = row.overall_status === 'COMPLIANT' 
                                ? '<span class="badge-pill-compliant">Compliant</span>' 
                                : '<span class="badge-pill-non-compliant">Non-Compliant</span>';
                            
                            const createdDate = row.created_at 
                                ? new Date(row.created_at).toLocaleString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) 
                                : '';

                            html += `
                                <tr>
                                    <td class="col-report-id">
                                        <a href="inspection-details.php?id=${row.id}" class="report-id-link">
                                            ${escapeHtml(row.inspection_number)}
                                        </a>
                                    </td>
                                    <td class="col-company-prod">
                                        <div class="company-name-text">${escapeHtml(row.company_name || 'Company')}</div>
                                        <div class="product-name-text">${escapeHtml(row.product_name || 'Product')}</div>
                                    </td>
                                    <td class="col-inspector-name">${escapeHtml(row.inspector_name || 'Inspector')}</td>
                                    <td class="col-status-badge">${badgePill}</td>
                                    <td class="col-date-text">${createdDate}</td>
                                    <td class="col-action-btn">
                                        <a href="inspection-details.php?id=${row.id}" class="btn-table-details">
                                            <i class="far fa-file-alt"></i> Details
                                        </a>
                                    </td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    }
                }

                // Update Inspector Activity Feed
                if (data.inspector_activity && data.inspector_activity.length > 0) {
                    const actBody = document.getElementById('officerActivityTbody');
                    if (actBody) {
                        let actHtml = '';
                        data.inspector_activity.forEach(ins => {
                            const initial = (ins.full_name || 'I').charAt(0).toUpperCase();
                            const lastActive = ins.last_scan ? new Date(ins.last_scan).toLocaleString('en-US', { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }) : 'Never';
                            const dotClass = ins.last_scan ? 'active-dot-green' : 'active-dot-grey';

                            actHtml += `
                                <tr>
                                    <td>
                                        <div class="inspector-profile-item">
                                            <div class="inspector-avatar-circle">${initial}</div>
                                            <div class="inspector-meta-box">
                                                <div class="inspector-full-name">${escapeHtml(ins.full_name)}</div>
                                                <div class="inspector-code-id">${escapeHtml(ins.employee_id)}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-scans-count">
                                        <strong>${Number(ins.total_scans).toLocaleString()}</strong> Scans
                                    </td>
                                    <td class="col-last-active">
                                        <span class="${dotClass}"></span>
                                        ${lastActive}
                                    </td>
                                </tr>
                            `;
                        });
                        actBody.innerHTML = actHtml;
                    }
                }
            })
            .catch(err => console.error(err));
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    setInterval(fetchLiveDashboardData, 8000);
</script>

    </div><!-- end content-body -->
</div><!-- end main-content -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
