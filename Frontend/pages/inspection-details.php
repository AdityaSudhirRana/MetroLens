<?php
/**
 * AI Package Inspector - Full Inspection Record Details View
 * File: pages/inspection-details.php
 */
$pageTitle = 'Full Inspection Record Details';
$activeNav = 'history';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: inspection-history.php');
    exit;
}

// Fetch Inspection Record with Joined Company, Product, and Inspector Data
$stmt = $db->prepare("
    SELECT i.*, 
           p.product_name, p.brand_name, p.category, p.gtin_barcode,
           c.id AS company_id, c.company_name, c.company_type, c.address AS company_address, c.phone AS company_phone, c.email AS company_email,
           u.full_name AS inspector_name, u.employee_id AS inspector_emp_id, u.department AS inspector_dept, u.designation AS inspector_desig, u.office_region
    FROM inspections i
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN companies c ON i.company_id = c.id
    LEFT JOIN users u ON i.inspector_id = u.id
    WHERE i.id = ? OR i.inspection_number = ?
    LIMIT 1
");
$stmt->execute([$id, $id]);
$inspection = $stmt->fetch();

if (!$inspection) {
    echo "<div class='main-content'><div class='content-body'><div class='alert alert-danger'>Inspection report not found.</div></div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Fetch Fields
$fieldsStmt = $db->prepare("SELECT * FROM inspection_fields WHERE inspection_id = ? ORDER BY id ASC");
$fieldsStmt->execute([$inspection['id']]);
$fields = $fieldsStmt->fetchAll();

// Fetch Violations
$violStmt = $db->prepare("SELECT * FROM violations WHERE inspection_id = ? ORDER BY id ASC");
$violStmt->execute([$inspection['id']]);
$violations = $violStmt->fetchAll();

// Fetch Linked Inquiry if any
$inqStmt = $db->prepare("SELECT * FROM inquiries WHERE inspection_id = ? LIMIT 1");
$inqStmt->execute([$inspection['id']]);
$inquiry = $inqStmt->fetch();

$isCompliant = (strtoupper($inspection['overall_status']) === 'COMPLIANT');
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <!-- Top Navigation & Action Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <a href="companies.php" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">
                    <i class="fas fa-arrow-left"></i> Back to Companies
                </a>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">
                    Inspection Record: <?php echo htmlspecialchars($inspection['inspection_number']); ?>
                </h2>
                <p style="font-size: 0.88rem; color: var(--text-muted);">
                    Company: <strong><a href="company-details.php?id=<?php echo $inspection['company_id']; ?>"><?php echo htmlspecialchars($inspection['company_name'] ?? 'Company'); ?></a></strong> &bull; Date: <?php echo date('F j, Y \a\t H:i', strtotime($inspection['inspection_date'])); ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button onclick="window.print();" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print Full Record
                </button>
                <?php if (!$isCompliant): ?>
                    <a href="inquiries.php" class="btn btn-danger">
                        <i class="fas fa-gavel"></i> View Statutory Notice
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Result Status Banner -->
        <div class="result-status-banner <?php echo $isCompliant ? 'compliant' : 'non-compliant'; ?>">
            <div class="banner-left">
                <div class="banner-status-icon">
                    <i class="fas <?php echo $isCompliant ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                </div>
                <div>
                    <div class="banner-title"><?php echo htmlspecialchars($inspection['overall_status']); ?> PACKAGE</div>
                    <div class="banner-sub">
                        Risk Level: <strong><?php echo htmlspecialchars($inspection['risk_level']); ?></strong> &bull; Type: <?php echo htmlspecialchars($inspection['inspection_type']); ?>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 2.8rem; font-weight: 800; line-height: 1;"><?php echo number_format($inspection['compliance_score'], 1); ?>%</div>
                <div style="font-size: 0.85rem; opacity: 0.9;">Compliance Rating Score</div>
            </div>
        </div>

        <!-- 2-Column Main Layout -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

            <div style="display: flex; flex-direction: column; gap: 24px;">

                <!-- Section 1: Package & Commodity Information -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-box" style="color: var(--primary);"></i> Commodity & Package Information</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div>
                                <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Product Name</span>
                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary-dark);">
                                    <?php echo htmlspecialchars($inspection['product_name'] ?? 'Packaged Commodity'); ?>
                                </div>
                            </div>
                            <div>
                                <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Brand Name</span>
                                <div style="font-size: 1.05rem; font-weight: 700; color: var(--text);">
                                    <?php echo htmlspecialchars($inspection['brand_name'] ?: 'N/A'); ?>
                                </div>
                            </div>
                            <div>
                                <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Category</span>
                                <div style="font-size: 0.95rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($inspection['category'] ?? 'General'); ?>
                                </div>
                            </div>
                            <div>
                                <span style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">GTIN Barcode</span>
                                <div style="font-family: monospace; font-weight: 700; color: var(--primary);">
                                    <?php echo htmlspecialchars($inspection['gtin_barcode'] ?: 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Mandatory Declarations Audit Breakdown Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-check-double" style="color: var(--primary);"></i> Mandatory Declarations Audit Breakdown</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Declaration Field</th>
                                        <th>Extracted / Declared Value</th>
                                        <th>Confidence</th>
                                        <th>Source / Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($fields)): ?>
                                        <?php foreach ($fields as $f): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($f['field_name']); ?></strong></td>
                                                <td>
                                                    <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; color: #0f172a; font-family: monospace;">
                                                        <?php echo htmlspecialchars($f['corrected_value'] ?: $f['extracted_value']); ?>
                                                    </code>
                                                </td>
                                                <td>
                                                    <span class="confidence-tag high"><?php echo number_format($f['confidence_score'], 0); ?>%</span>
                                                </td>
                                                <td><?php echo get_field_status_badge($f['field_status']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">No field breakdown records available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Violations (if any) -->
                <?php if (!empty($violations)): ?>
                    <div class="card" style="border-left: 5px solid var(--danger);">
                        <div class="card-header">
                            <h3 class="card-title" style="color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> Identified Statutory Violations</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($violations as $v): ?>
                                <div class="violation-card">
                                    <div class="violation-header">
                                        <span class="violation-title"><?php echo htmlspecialchars($v['rule_name']); ?></span>
                                        <span class="severity-tag <?php echo strtolower($v['severity']); ?>"><?php echo htmlspecialchars($v['severity']); ?> Severity</span>
                                    </div>
                                    <p style="font-size: 0.9rem; color: #7f1d1d; margin-bottom: 8px;">
                                        <strong>Declaration Field:</strong> <?php echo htmlspecialchars($v['field_name']); ?><br>
                                        <?php echo htmlspecialchars($v['notes']); ?>
                                    </p>
                                    <div style="font-size: 0.85rem; color: #991b1b; font-weight: 700;">
                                        Statutory Penalty: ₹<?php echo number_format($v['penalty_amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Section 4: Raw OCR Text & Structured JSON Viewers -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="card-title"><i class="fas fa-code"></i> Raw OCR Stream & Structured JSON Payload</h3>
                        <button class="btn btn-outline btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('jsonViewerContent').textContent); alert('Structured JSON copied to clipboard!');">
                            <i class="fas fa-copy"></i> Copy JSON
                        </button>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 16px;">
                            <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 6px;">Raw OCR Output Stream</h4>
                            <div class="ocr-raw-box"><?php echo htmlspecialchars($inspection['raw_ocr_text'] ?: 'No raw OCR text recorded.'); ?></div>
                        </div>
                        <div>
                            <h4 style="font-size: 0.9rem; font-weight: 700; margin-bottom: 6px;">Extracted Package JSON Schema</h4>
                            <div class="json-code-viewer" id="jsonViewerContent"><?php echo htmlspecialchars($inspection['extracted_json'] ? json_encode(json_decode($inspection['extracted_json']), JSON_PRETTY_PRINT) : '{}'); ?></div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Right Sidebar: Original Package Photo & Inspector Details -->
            <div style="display: flex; flex-direction: column; gap: 24px;">

                <!-- Original Package Captured Image -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-camera"></i> Original Package Image</h3>
                    </div>
                    <div class="card-body" style="text-align: center;">
                        <?php if (!empty($inspection['package_image_url']) && file_exists(__DIR__ . '/../' . $inspection['package_image_url'])): ?>
                            <img src="../<?php echo htmlspecialchars($inspection['package_image_url']); ?>" alt="Package Photograph" style="width: 100%; max-height: 280px; object-fit: contain; border-radius: 8px; border: 1px solid var(--border);">
                        <?php else: ?>
                            <img src="../assets/logo.svg" alt="Package Photograph" style="width: 100%; max-height: 200px; object-fit: contain; padding: 20px;">
                        <?php endif; ?>
                        <div style="font-size: 0.78rem; color: var(--text-muted); margin-top: 10px;">
                            Path: <code><?php echo htmlspecialchars($inspection['package_image_url'] ?: 'N/A'); ?></code>
                        </div>
                    </div>
                </div>

                <!-- Company Details Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-building"></i> Company Profile</h3>
                    </div>
                    <div class="card-body" style="font-size: 0.88rem;">
                        <div style="margin-bottom: 10px;">
                            <strong>Company Name:</strong><br>
                            <a href="company-details.php?id=<?php echo $inspection['company_id']; ?>" style="font-weight: 700; color: var(--primary);">
                                <?php echo htmlspecialchars($inspection['company_name'] ?? 'Company'); ?>
                            </a>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <strong>Postal Address:</strong><br>
                            <?php echo htmlspecialchars($inspection['company_address'] ?? 'N/A'); ?>
                        </div>
                        <div>
                            <strong>Contact Email / Phone:</strong><br>
                            <?php echo htmlspecialchars($inspection['company_email'] ?: $inspection['company_phone'] ?: 'N/A'); ?>
                        </div>
                    </div>
                </div>

                <!-- Inspecting Officer Badge Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-shield"></i> Inspecting Officer</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <div style="width: 46px; height: 46px; border-radius: 50%; background: #1e3a8a; color: #ffffff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;">
                                <?php echo strtoupper(substr($inspection['inspector_name'] ?? 'I', 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 1rem; color: var(--primary-dark);">
                                    <?php echo htmlspecialchars($inspection['inspector_name'] ?? 'Inspector'); ?>
                                </div>
                                <div style="font-size: 0.82rem; color: var(--text-muted);">
                                    ID: <?php echo htmlspecialchars($inspection['inspector_emp_id'] ?? 'N/A'); ?> &bull; <?php echo htmlspecialchars($inspection['office_region'] ?? 'Region'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inspection Audit Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-stream"></i> Audit Timeline</h3>
                    </div>
                    <div class="card-body">
                        <div class="inspection-timeline-list">
                            <div class="timeline-step">
                                <span class="timeline-time"><?php echo date('H:i:s', strtotime($inspection['created_at'])); ?></span>
                                <span>Package photo captured & AI OCR scan initiated.</span>
                            </div>
                            <div class="timeline-step">
                                <span class="timeline-time"><?php echo date('H:i:s', strtotime($inspection['created_at']) + 2); ?></span>
                                <span>Rule 6 compliance verification executed.</span>
                            </div>
                            <div class="timeline-step">
                                <span class="timeline-time"><?php echo date('H:i:s', strtotime($inspection['created_at']) + 5); ?></span>
                                <span>Inspection record saved to MySQL database.</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
