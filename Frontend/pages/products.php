<?php
/**
 * AI Package Inspector - Products Database Page
 */
$pageTitle = 'Product Database';
$activeNav = 'products';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');

$where = ["1=1"];
$params = [];

if (!empty($q)) {
    $where[] = "(product_name LIKE ? OR manufacturer_name LIKE ? OR brand_name LIKE ? OR gtin_barcode LIKE ?)";
    $term = "%$q%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

if (!empty($category)) {
    $where[] = "category = ?";
    $params[] = $category;
}

$sql = "SELECT * FROM products WHERE " . implode(" AND ", $where) . " ORDER BY last_inspected_at DESC, id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">Packaged Commodity Database</h2>
                <p style="font-size: 0.88rem; color: var(--text-muted);">Registered manufacturers, brands, and statutory package declarations history</p>
            </div>
            <button class="btn btn-primary" onclick="openAddProductModal();">
                <i class="fas fa-plus"></i> Register New Commodity
            </button>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-body" style="padding: 16px 20px;">
                <form action="products.php" method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
                    <div style="flex: 1; min-width: 240px;">
                        <input type="text" name="q" class="form-control" placeholder="Search by Product Name, Manufacturer, Barcode..." value="<?php echo htmlspecialchars($q); ?>" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div style="width: 200px;">
                        <select name="category" class="form-control" style="width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="this.form.submit();">
                            <option value="">All Categories</option>
                            <option value="Packaged Foods & Biscuits" <?php echo ($category === 'Packaged Foods & Biscuits') ? 'selected' : ''; ?>>Packaged Foods & Biscuits</option>
                            <option value="Personal Care & Cosmetics" <?php echo ($category === 'Personal Care & Cosmetics') ? 'selected' : ''; ?>>Personal Care & Cosmetics</option>
                            <option value="Household Cleaning" <?php echo ($category === 'Household Cleaning') ? 'selected' : ''; ?>>Household Cleaning</option>
                            <option value="Beverages & Juices" <?php echo ($category === 'Beverages & Juices') ? 'selected' : ''; ?>>Beverages & Juices</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <?php if (!empty($q) || !empty($category)): ?>
                        <a href="products.php" class="btn btn-outline">Reset</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Product Table Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Registered Commodities (<?php echo count($products); ?> Records)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>GTIN / Barcode</th>
                                <th>Product Name & Brand</th>
                                <th>Manufacturer / Packer</th>
                                <th>Category</th>
                                <th>Inspections</th>
                                <th>Violations</th>
                                <th>Compliance Rate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)): ?>
                                <?php foreach ($products as $row): ?>
                                    <tr>
                                        <td>
                                            <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; color: var(--primary); font-family: monospace; font-weight: 700;">
                                                <?php echo htmlspecialchars($row['gtin_barcode'] ?: 'N/A'); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <a href="product-details.php?id=<?php echo $row['id']; ?>" class="table-id-link">
                                                <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                                            </a>
                                            <span style="display: block; font-size: 0.78rem; color: var(--text-muted);">
                                                Brand: <?php echo htmlspecialchars($row['brand_name'] ?: 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; font-size: 0.88rem;">
                                                <?php echo htmlspecialchars($row['manufacturer_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary" style="font-size: 0.78rem;">
                                                <?php echo htmlspecialchars($row['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong style="color: var(--primary);"><?php echo number_format($row['total_inspections']); ?></strong>
                                        </td>
                                        <td>
                                            <strong style="color: <?php echo ($row['total_violations'] > 0) ? '#dc2626' : '#16a34a'; ?>;">
                                                <?php echo number_format($row['total_violations']); ?>
                                            </strong>
                                        </td>
                                        <td>
                                            <strong style="color: <?php echo ($row['compliance_rate'] >= 90) ? '#16a34a' : (($row['compliance_rate'] >= 60) ? '#d97706' : '#dc2626'); ?>;">
                                                <?php echo number_format($row['compliance_rate'], 1); ?>%
                                            </strong>
                                        </td>
                                        <td>
                                            <a href="product-details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline btn-sm" title="View History">
                                                <i class="fas fa-history"></i> History
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 32px; color: var(--text-muted);">
                                        No commodities found matching filter.
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

<!-- Modal: Add New Product -->
<div id="addProductModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 100%; max-width: 500px; border-radius: 12px; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,0.3);">
        <h3 style="font-size: 1.3rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 16px;">
            <i class="fas fa-box-open"></i> Register New Commodity Product
        </h3>

        <form id="addProductForm">
            <div class="form-group" style="margin-bottom: 14px;">
                <label><strong>Product Name *</strong></label>
                <input type="text" id="newProdName" class="form-control" required placeholder="e.g. Parle-G Original Glucose Biscuits" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label><strong>Manufacturer / Packer Name *</strong></label>
                <input type="text" id="newMfrName" class="form-control" required placeholder="e.g. Parle Products Pvt Ltd" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label><strong>Category</strong></label>
                <select id="newCategory" class="form-control" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <option value="Packaged Foods & Biscuits">Packaged Foods & Biscuits</option>
                    <option value="Personal Care & Cosmetics">Personal Care & Cosmetics</option>
                    <option value="Household Cleaning">Household Cleaning</option>
                    <option value="Beverages & Juices">Beverages & Juices</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div>
                    <label><strong>GTIN Barcode</strong></label>
                    <input type="text" id="newGtin" class="form-control" placeholder="8901030000000" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div>
                    <label><strong>Standard MRP (₹)</strong></label>
                    <input type="number" step="0.01" id="newMrp" class="form-control" placeholder="80.00" style="width: 100%; padding: 9px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" class="btn btn-outline" onclick="closeAddProductModal();">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSaveNewProduct">
                    <i class="fas fa-check"></i> Register Commodity
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAddProductModal() {
        document.getElementById('addProductModal').style.display = 'flex';
    }
    function closeAddProductModal() {
        document.getElementById('addProductModal').style.display = 'none';
    }

    document.getElementById('btnSaveNewProduct').addEventListener('click', function() {
        const name = document.getElementById('newProdName').value.trim();
        const mfr = document.getElementById('newMfrName').value.trim();
        if (!name || !mfr) { alert('Please enter Product Name and Manufacturer.'); return; }

        const payload = {
            product_name: name,
            manufacturer_name: mfr,
            category: document.getElementById('newCategory').value,
            gtin_barcode: document.getElementById('newGtin').value.trim(),
            standard_mrp: document.getElementById('newMrp').value
        };

        fetch('../api/products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to add product.'));
            }
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
