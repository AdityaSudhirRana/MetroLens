<?php
/**
 * AI Package Inspector - Legal Notice Detail & Manufacturer Response Page
 */
$pageTitle = 'Legal Notice Detail';
$activeNav = 'inquiries';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$id = $_GET['id'] ?? null;

if (!$id) {
    header('Location: inquiries.php');
    exit;
}

$stmt = $db->prepare("
    SELECT inq.*, 
           i.inspection_number, i.overall_status AS inspection_status, i.compliance_score, i.id AS inspection_ref_id,
           p.product_name, p.category, p.gtin_barcode,
           u.full_name AS inspector_name, u.employee_id AS inspector_emp_id, u.department
    FROM inquiries inq
    LEFT JOIN inspections i ON inq.inspection_id = i.id
    LEFT JOIN products p ON i.product_id = p.id
    LEFT JOIN users u ON inq.inspector_id = u.id
    WHERE inq.id = ? OR inq.inquiry_number = ?
    LIMIT 1
");
$stmt->execute([$id, $id]);
$inquiry = $stmt->fetch();

if (!$inquiry) {
    echo "<div class='main-content'><div class='content-body'><div class='alert alert-danger'>Notice record not found.</div></div></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="margin-bottom: 24px;">
            <a href="inquiries.php" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">
                <i class="fas fa-arrow-left"></i> Back to Legal Notices
            </a>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">
                Notice: <?php echo htmlspecialchars($inquiry['inquiry_number']); ?>
            </h2>
            <p style="font-size: 0.88rem; color: var(--text-muted);">
                Issued To: <strong><?php echo htmlspecialchars($inquiry['manufacturer_name']); ?></strong> &bull; Date: <?php echo date('F j, Y', strtotime($inquiry['issued_at'])); ?>
            </p>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Notice Content Card -->
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="card-title"><?php echo htmlspecialchars($inquiry['subject']); ?></h3>
                        <span class="badge badge-warning" style="font-size: 0.88rem;"><?php echo htmlspecialchars($inquiry['status']); ?></span>
                    </div>
                    <div class="card-body">
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid var(--primary); font-family: sans-serif; font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap; margin-bottom: 20px; color: #0f172a;"><?php echo htmlspecialchars($inquiry['description']); ?></div>

                        <div style="border-top: 1px solid var(--border); padding-top: 20px;">
                            <h4 style="font-size: 1rem; font-weight: 700; margin-bottom: 10px;">Manufacturer Response Log</h4>
                            <?php if (!empty($inquiry['manufacturer_response'])): ?>
                                <div style="background: #f1f5f9; padding: 16px; border-radius: 8px; font-size: 0.9rem; white-space: pre-wrap; color: #334155;"><?php echo htmlspecialchars($inquiry['manufacturer_response']); ?></div>
                            <?php else: ?>
                                <p style="font-size: 0.88rem; color: var(--text-muted); font-style: italic;">No official written response received from manufacturer yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Update Status Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Record Manufacturer Response & Legal Status</h3>
                    </div>
                    <div class="card-body">
                        <form id="updateInquiryForm">
                            <div class="form-group" style="margin-bottom: 14px;">
                                <label><strong>Notice Status</strong></label>
                                <select id="updateStatusSelect" class="form-control" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    <option value="Issued" <?php echo ($inquiry['status'] === 'Issued') ? 'selected' : ''; ?>>Issued / Awaiting Response</option>
                                    <option value="Responded" <?php echo ($inquiry['status'] === 'Responded') ? 'selected' : ''; ?>>Responded / Under Review</option>
                                    <option value="Resolved" <?php echo ($inquiry['status'] === 'Resolved') ? 'selected' : ''; ?>>Resolved & Closed</option>
                                    <option value="Escalated" <?php echo ($inquiry['status'] === 'Escalated') ? 'selected' : ''; ?>>Escalated to Court Prosecution</option>
                                </select>
                            </div>

                            <div class="form-group" style="margin-bottom: 16px;">
                                <label><strong>Officer Notes / Manufacturer Response Entry</strong></label>
                                <textarea id="updateResponseNotes" class="form-control" rows="3" placeholder="Enter details of manufacturer written reply, compounding fee payment, or enforcement decision..." style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
                            </div>

                            <button type="button" class="btn btn-primary" id="btnSaveInquiryUpdate">
                                <i class="fas fa-save"></i> Save Legal Record Update
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div style="display: flex; flex-direction: column; gap: 24px;">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Statutory Metadata</h3>
                    </div>
                    <div class="card-body" style="font-size: 0.88rem;">
                        <div style="margin-bottom: 12px;">
                            <strong>Response Deadline:</strong><br>
                            <span style="font-size: 1.1rem; font-weight: 800; color: #dc2626;">
                                <?php echo date('F j, Y', strtotime($inquiry['response_deadline'])); ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <strong>Linked Inspection:</strong><br>
                            <a href="inspection-result.php?id=<?php echo $inquiry['inspection_ref_id']; ?>" class="table-id-link">
                                <?php echo htmlspecialchars($inquiry['inspection_number'] ?? 'View Inspection'); ?>
                            </a>
                        </div>
                        <div style="margin-bottom: 12px;">
                            <strong>Issuing Officer:</strong><br>
                            <?php echo htmlspecialchars($inquiry['inspector_name']); ?> (<?php echo htmlspecialchars($inquiry['inspector_emp_id']); ?>)
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
    document.getElementById('btnSaveInquiryUpdate').addEventListener('click', function() {
        const btn = this;
        btn.disabled = true;

        const payload = {
            action: 'update_status',
            inquiry_id: <?php echo json_encode($inquiry['id']); ?>,
            status: document.getElementById('updateStatusSelect').value,
            response_notes: document.getElementById('updateResponseNotes').value.trim()
        };

        fetch('../api/inquiries.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating inquiry: ' + data.error);
                btn.disabled = false;
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
