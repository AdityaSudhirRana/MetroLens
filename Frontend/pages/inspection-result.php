<?php
/**
 * AI Package Inspector - Inspection Report Scorecard & Result Detail
 */
$pageTitle = 'Inspection Scorecard Report';
$activeNav = 'history';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: inspection-history.php');
    exit;
}

// Fetch Inspection
$stmt = $db->prepare("
    SELECT i.*, 
           p.product_name, p.category, p.gtin_barcode, p.manufacturer_name, p.brand_name,
           u.full_name AS inspector_name, u.employee_id AS inspector_emp_id, u.department, u.designation
    FROM inspections i
    LEFT JOIN products p ON i.product_id = p.id
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

$isCompliant = (strtolower($inspection['overall_status']) === 'compliant');
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <!-- Top Actions Bar -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <a href="inspection-history.php" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">
                    <i class="fas fa-arrow-left"></i> Back to History
                </a>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">
                    Inspection Report: <?php echo htmlspecialchars($inspection['inspection_number']); ?>
                </h2>
                <p style="font-size: 0.88rem; color: var(--text-muted);">
                    Logged on <?php echo date('F j, Y \a\t H:i', strtotime($inspection['created_at'])); ?> &bull; Type: <?php echo htmlspecialchars($inspection['inspection_type']); ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button onclick="window.print();" class="btn btn-outline">
                    <i class="fas fa-print"></i> Print Official Report
                </button>
                <?php if (!$isCompliant): ?>
                    <button class="btn btn-danger" onclick="openLegalNoticeModal();">
                        <i class="fas fa-gavel"></i> Issue Legal Notice
                    </button>
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
                    <div class="banner-title"><?php echo strtoupper(htmlspecialchars($inspection['overall_status'])); ?> PACKAGE</div>
                    <div class="banner-sub">
                        <?php echo $isCompliant ? 'Passed all Legal Metrology (Packaged Commodities) Rules 2011 declarations.' : 'Non-compliance detected under Rule 6 of Legal Metrology Act 2009.'; ?>
                    </div>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 2.8rem; font-weight: 800; line-height: 1;"><?php echo number_format($inspection['compliance_score'], 1); ?>%</div>
                <div style="font-size: 0.85rem; opacity: 0.9;">Compliance Rating Score</div>
            </div>
        </div>

        <!-- 2 Column Layout -->
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

            <!-- Left: Mandatory Declarations Table & Violations -->
            <div style="display: flex; flex-direction: column; gap: 24px;">

                <!-- Declarations Breakdown Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Mandatory Declarations Audit Breakdown</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Declaration Field</th>
                                        <th>Extracted / Declared Value</th>
                                        <th>Confidence</th>
                                        <th>Compliance Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($fields)): ?>
                                        <?php foreach ($fields as $f): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($f['field_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; color: #0f172a; font-family: monospace;">
                                                        <?php echo htmlspecialchars($f['corrected_value'] ?: $f['extracted_value']); ?>
                                                    </code>
                                                </td>
                                                <td>
                                                    <span class="confidence-tag high"><?php echo number_format($f['confidence_score'], 0); ?>%</span>
                                                </td>
                                                <td>
                                                    <?php echo get_field_status_badge($f['field_status']); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">No field breakdown available.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Violations Card (if any) -->
                <?php if (!empty($violations)): ?>
                    <div class="card" style="border-left: 5px solid var(--danger);">
                        <div class="card-header">
                            <h3 class="card-title" style="color: var(--danger);"><i class="fas fa-exclamation-triangle"></i> Identified Legal Violations</h3>
                        </div>
                        <div class="card-body">
                            <?php foreach ($violations as $v): ?>
                                <div class="violation-card">
                                    <div class="violation-header">
                                        <span class="violation-title"><?php echo htmlspecialchars($v['rule_name']); ?></span>
                                        <span class="severity-tag <?php echo strtolower($v['severity']); ?>"><?php echo htmlspecialchars($v['severity']); ?> Severity</span>
                                    </div>
                                    <p style="font-size: 0.9rem; color: #7f1d1d; margin-bottom: 8px;">
                                        <strong>Affected Field:</strong> <?php echo htmlspecialchars($v['field_name']); ?><br>
                                        <?php echo htmlspecialchars($v['notes']); ?>
                                    </p>
                                    <div style="font-size: 0.85rem; color: #991b1b; font-weight: 700;">
                                        Statutory Fine / Penalty: ₹<?php echo number_format($v['penalty_amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Raw OCR Text Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-alt"></i> Raw Computer Vision OCR Output</h3>
                    </div>
                    <div class="card-body">
                        <div class="ocr-raw-box"><?php echo htmlspecialchars($inspection['ocr_extracted_text'] ?: 'No raw OCR text recorded.'); ?></div>
                    </div>
                </div>

            </div>

            <!-- Right: Commodity & Officer Summary Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 24px;">

                <!-- Product Profile Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Commodity Profile</h3>
                    </div>
                    <div class="card-body">
                        <div style="margin-bottom: 14px;">
                            <span style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Product Name</span>
                            <div style="font-weight: 800; font-size: 1.1rem; color: var(--primary-dark);">
                                <?php echo htmlspecialchars($inspection['product_name'] ?? 'Commodity'); ?>
                            </div>
                        </div>
                        <div style="margin-bottom: 14px;">
                            <span style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Manufacturer / Packer</span>
                            <div style="font-weight: 600; font-size: 0.95rem; color: var(--text);">
                                <?php echo htmlspecialchars($inspection['manufacturer_name'] ?? 'Unspecified'); ?>
                            </div>
                        </div>
                        <div style="margin-bottom: 14px;">
                            <span style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Category</span>
                            <div style="font-weight: 600; font-size: 0.95rem; color: var(--text);">
                                <?php echo htmlspecialchars($inspection['category'] ?? 'Packaged Commodities'); ?>
                            </div>
                        </div>
                        <?php if (!empty($inspection['gtin_barcode'])): ?>
                            <div style="margin-bottom: 14px;">
                                <span style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">GTIN Barcode</span>
                                <div style="font-family: monospace; font-weight: 700; color: var(--primary);">
                                    <?php echo htmlspecialchars($inspection['gtin_barcode']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Inspector Seal Card -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Inspecting Officer Seal</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 16px;">
                            <div style="width: 48px; height: 48px; border-radius: 50%; background: #1e3a8a; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.2rem;">
                                <?php echo strtoupper(substr($inspection['inspector_name'] ?? 'O', 0, 1)); ?>
                            </div>
                            <div>
                                <div style="font-weight: 800; font-size: 1.05rem; color: var(--primary-dark);">
                                    <?php echo htmlspecialchars($inspection['inspector_name'] ?? 'Officer'); ?>
                                </div>
                                <div style="font-size: 0.82rem; color: var(--text-muted);">
                                    <?php echo htmlspecialchars($inspection['designation'] ?? 'Inspector'); ?> &bull; ID: <?php echo htmlspecialchars($inspection['inspector_emp_id'] ?? 'LM-2026'); ?>
                                </div>
                            </div>
                        </div>
                        <div style="border-top: 1px dashed var(--border); padding-top: 12px; font-size: 0.85rem; color: var(--text-muted);">
                            <strong>Department:</strong> <?php echo htmlspecialchars($inspection['department'] ?? 'Legal Metrology'); ?>
                        </div>
                    </div>
                </div>

                <!-- Inspection Timeline -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Audit Trail Timeline</h3>
                    </div>
                    <div class="card-body">
                        <div class="inspection-timeline-list">
                            <div class="timeline-step">
                                <span class="timeline-time"><?php echo date('H:i:s', strtotime($inspection['created_at'])); ?></span>
                                <span>Image uploaded & AI OCR initiated.</span>
                            </div>
                            <div class="timeline-step">
                                <span class="timeline-time"><?php echo date('H:i:s', strtotime($inspection['created_at']) + 2); ?></span>
                                <span>Rule 6 compliance engine executed.</span>
                            </div>
                            <div class="timeline-step">
                                <span class="timeline-time"><?php echo date('H:i:s', strtotime($inspection['created_at']) + 5); ?></span>
                                <span>Officer approved report & saved to database.</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

<!-- Modal: Issue Legal Notice -->
<div id="legalNoticeModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 100%; max-width: 540px; border-radius: 12px; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
        <h3 style="font-size: 1.3rem; font-weight: 800; color: #991b1b; margin-bottom: 8px;">
            <i class="fas fa-gavel"></i> Issue Legal Metrology Violation Notice
        </h3>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 20px;">
            Draft statutory notice under Section 18 of LM Act 2009 for manufacturer <strong><?php echo htmlspecialchars($inspection['manufacturer_name']); ?></strong>
        </p>

        <form id="issueNoticeForm">
            <div class="form-group" style="margin-bottom: 16px;">
                <label><strong>Notice Subject</strong></label>
                <input type="text" id="noticeSubject" class="form-control" value="Notice under Rule 6 of Legal Metrology (Packaged Commodities) Rules 2011" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label><strong>Response Deadline</strong></label>
                <select id="noticeDeadline" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="15">15 Days Statutory Notice</option>
                    <option value="30">30 Days Standard Notice</option>
                    <option value="7">7 Days Urgent Show Cause</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label><strong>Violation Details & Notice Body</strong></label>
                <textarea id="noticeBody" class="form-control" rows="4" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeLegalNoticeModal();">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnSubmitLegalNotice">
                    <i class="fas fa-paper-plane"></i> Dispatch Statutory Notice
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openLegalNoticeModal() {
        const modal = document.getElementById('legalNoticeModal');
        const body = document.getElementById('noticeBody');
        body.value = `Notice is hereby issued to ${<?php echo json_encode($inspection['manufacturer_name']); ?>} regarding non-compliant mandatory declarations found on commodity package "${<?php echo json_encode($inspection['product_name']); ?>}" during inspection #${<?php echo json_encode($inspection['inspection_number']); ?>}.\n\nYou are directed to submit written justification within the specified deadline failing which legal prosecution under Section 36 of Legal Metrology Act 2009 shall be initiated.`;
        modal.style.display = 'flex';
    }

    function closeLegalNoticeModal() {
        document.getElementById('legalNoticeModal').style.display = 'none';
    }

    document.getElementById('btnSubmitLegalNotice').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dispatching...';

        const payload = {
            inspection_id: <?php echo json_encode($inspection['id']); ?>,
            manufacturer_name: <?php echo json_encode($inspection['manufacturer_name']); ?>,
            subject: document.getElementById('noticeSubject').value,
            deadline_days: document.getElementById('noticeDeadline').value,
            description: document.getElementById('noticeBody').value
        };

        fetch('../api/inquiries.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Statutory notice dispatched successfully! Notice ID: ' + data.inquiry_number);
                window.location.href = 'inquiries.php';
            } else {
                alert('Error issuing notice: ' + (data.error || 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Dispatch Statutory Notice';
            }
        })
        .catch(err => {
            alert('Network error: ' + err);
            btn.disabled = false;
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
