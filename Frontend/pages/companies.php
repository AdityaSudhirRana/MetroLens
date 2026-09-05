<?php
/**
 * AI Package Inspector - Centralized Companies Management & Expandable Inspection History
 * File: pages/companies.php
 */
$pageTitle = 'Companies & Inspection Dates Catalog';
$activeNav = 'companies';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$q = trim($_GET['q'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($q)) {
    $where[] = "(c.company_name LIKE ? OR c.address LIKE ? OR c.email LIKE ?)";
    $term = "%$q%";
    $params = array_merge($params, [$term, $term, $term]);
}

$sql = "
    SELECT c.*,
           COUNT(DISTINCT p.id) AS products_count,
           COUNT(DISTINCT i.id) AS inspections_count,
           SUM(CASE WHEN i.overall_status = 'NON_COMPLIANT' THEN 1 ELSE 0 END) AS violations_count
    FROM companies c
    LEFT JOIN products p ON c.id = p.company_id
    LEFT JOIN inspections i ON c.id = i.company_id
    WHERE " . implode(" AND ", $where) . "
    GROUP BY c.id
    ORDER BY c.company_name ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$companies = $stmt->fetchAll();
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">Registered Manufacturers & Company Catalog</h2>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Expand any company to view all recorded inspection dates and access full inspection details</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openAddCompanyModal();">
                <i class="fas fa-building"></i> Add New Company
            </button>
        </div>

        <!-- Search Bar -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form action="companies.php" method="GET" style="display: flex; gap: 16px; align-items: center;">
                    <div style="flex: 1;">
                        <input type="text" name="q" class="form-control" placeholder="Search company name, manufacturer address, or email..." value="<?php echo htmlspecialchars($q); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($q)): ?>
                        <a href="companies.php" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Companies Table with Expandable Inspection Dates Rows -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Companies List (<?php echo count($companies); ?> Registered)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table" id="companiesTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;"></th>
                                <th>Company Name & Type</th>
                                <th>Address & Contact</th>
                                <th>Products</th>
                                <th>Total Inspections</th>
                                <th>Violations</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($companies)): ?>
                                <?php foreach ($companies as $c): ?>
                                    <!-- Main Company Row -->
                                    <tr class="company-row" data-company-id="<?php echo $c['id']; ?>">
                                        <td>
                                            <button type="button" class="btn-expand-company" onclick="toggleCompanyExpand(<?php echo $c['id']; ?>, this);" style="background: none; border: none; font-size: 1.1rem; color: var(--primary); cursor: pointer; padding: 4px;">
                                                <i class="fas fa-chevron-down"></i>
                                            </button>
                                        </td>
                                        <td>
                                            <a href="company-details.php?id=<?php echo $c['id']; ?>" class="table-id-link">
                                                <strong style="font-size: 1.05rem;"><?php echo htmlspecialchars($c['company_name']); ?></strong>
                                            </a>
                                            <span style="display: block; font-size: 0.78rem; color: var(--text-muted);">
                                                Type: <?php echo htmlspecialchars($c['company_type']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 0.88rem;">
                                            <?php echo htmlspecialchars($c['address']); ?><br>
                                            <span style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($c['email'] ?: $c['phone']); ?></span>
                                        </td>
                                        <td><span class="badge badge-secondary"><?php echo number_format($c['products_count']); ?> Products</span></td>
                                        <td><strong style="color: var(--primary); font-size: 1.05rem;"><?php echo number_format($c['inspections_count']); ?></strong></td>
                                        <td>
                                            <strong style="color: <?php echo ($c['violations_count'] > 0) ? '#dc2626' : '#16a34a'; ?>;">
                                                <?php echo number_format($c['violations_count']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button type="button" class="btn btn-outline btn-sm" onclick="toggleCompanyExpand(<?php echo $c['id']; ?>, this.closest('tr').querySelector('.btn-expand-company'));">
                                                    <i class="fas fa-calendar-alt"></i> Inspection Dates
                                                </button>
                                                <a href="company-details.php?id=<?php echo $c['id']; ?>" class="btn btn-outline btn-sm">
                                                    <i class="fas fa-eye"></i> Profile
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Expandable Inspection Dates Child Sub-Row -->
                                    <tr class="company-expand-row" id="expandRow_<?php echo $c['id']; ?>" style="display: none; background-color: #f8fafc;">
                                        <td colspan="7" style="padding: 20px 28px;">
                                            <div style="border-left: 4px solid var(--primary); padding-left: 18px;">
                                                <h4 style="font-size: 1rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 12px;">
                                                    <i class="fas fa-history"></i> Inspection Dates History for <?php echo htmlspecialchars($c['company_name']); ?>
                                                </h4>
                                                <div class="company-dates-container" id="datesContainer_<?php echo $c['id']; ?>">
                                                    <div style="color: var(--text-muted); font-size: 0.88rem;"><i class="fas fa-spinner fa-spin"></i> Loading inspection dates...</div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" style="text-align: center; padding: 32px; color: var(--text-muted);">No companies found matching search criteria.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Add New Company -->
<div id="addCompanyModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 100%; max-width: 520px; border-radius: 12px; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
        <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 16px;">
            <i class="fas fa-building"></i> Register New Company
        </h3>
        <form id="addCompanyForm">
            <div class="form-group" style="margin-bottom: 14px;">
                <label><strong>Company Name *</strong></label>
                <input type="text" id="newCompanyName" class="form-control" placeholder="e.g. Parle Products Pvt. Ltd." required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div class="form-group" style="margin-bottom: 14px;">
                <label><strong>Company Type</strong></label>
                <select id="newCompanyType" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="Manufacturer & Packer">Manufacturer & Packer</option>
                    <option value="Manufacturer">Manufacturer</option>
                    <option value="Packer">Packer</option>
                    <option value="Importer">Importer</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 14px;">
                <label><strong>Full Postal Address *</strong></label>
                <textarea id="newCompanyAddress" class="form-control" rows="2" placeholder="Street, City, Pincode, State" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;"></textarea>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 18px;">
                <div>
                    <label><strong>Email Address</strong></label>
                    <input type="email" id="newCompanyEmail" class="form-control" placeholder="contact@company.com" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div>
                    <label><strong>Helpline Phone</strong></label>
                    <input type="text" id="newCompanyPhone" class="form-control" placeholder="1800-XX-XXXX" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeAddCompanyModal();">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveCompany">Save Company Record</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddCompanyModal() { document.getElementById('addCompanyModal').style.display = 'flex'; }
    function closeAddCompanyModal() { document.getElementById('addCompanyModal').style.display = 'none'; }

    // Toggle Expand Company Inspection Dates Sub-Row
    function toggleCompanyExpand(companyId, btn) {
        const row = document.getElementById('expandRow_' + companyId);
        const icon = btn.querySelector('i');

        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            icon.className = 'fas fa-chevron-up';
            fetchCompanyDates(companyId);
        } else {
            row.style.display = 'none';
            icon.className = 'fas fa-chevron-down';
        }
    }

    // Fetch Inspection Dates from MySQL for selected company
    function fetchCompanyDates(companyId) {
        const container = document.getElementById('datesContainer_' + companyId);
        
        fetch('../api/companies.php?id=' + companyId)
            .then(res => res.json())
            .then(data => {
                if (!data.success || !data.company.inspection_dates) {
                    container.innerHTML = '<div style="color: var(--text-muted); font-size: 0.88rem;">No inspection dates recorded for this company yet.</div>';
                    return;
                }

                const dates = data.company.inspection_dates;
                if (dates.length === 0) {
                    container.innerHTML = '<div style="color: var(--text-muted); font-size: 0.88rem;">No inspection dates recorded for this company yet.</div>';
                    return;
                }

                let html = '<div style="display: flex; flex-direction: column; gap: 10px;">';
                dates.forEach(d => {
                    const statusClass = (d.overall_status === 'COMPLIANT') ? 'badge-success' : 'badge-danger';
                    html += `
                        <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="font-size: 0.98rem; color: var(--primary-dark);">${d.inspection_date}</strong>
                                <span style="font-size: 0.82rem; color: var(--text-muted); margin-left: 12px;">Report #: <strong>${d.inspection_number}</strong> &bull; Product: ${d.product_name || 'Commodity'} &bull; Inspector: ${d.inspector_name || 'Officer'}</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 14px;">
                                <span class="badge ${statusClass}">${d.overall_status}</span>
                                <strong style="color: ${d.compliance_score >= 90 ? '#16a34a' : '#dc2626'};">${d.compliance_score}% Score</strong>
                                <a href="inspection-details.php?id=${d.id}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Full Inspection Details
                                </a>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            })
            .catch(err => {
                container.innerHTML = '<div style="color: #dc2626;">Error fetching inspection dates.</div>';
            });
    }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
