<?php
/**
 * AI Package Inspector - Role-Based Sidebar Component
 * File: includes/sidebar.php
 */
$currentUser = get_logged_user();
$role = strtolower($currentUser['role'] ?? 'inspector');
$activeNav = $activeNav ?? 'dashboard';

$userInitial = strtoupper(substr($currentUser['name'] ?? 'F', 0, 1));
$userEmpId = $currentUser['employee_id'] ?? 'OFF-2026-979';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo ($role === 'officer') ? 'officer-dashboard.php' : 'inspector-dashboard.php'; ?>" class="sidebar-brand-link">
            <div class="brand-emblem-box">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#eab308" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-5.45 9-12V7l-9-5z"></path>
                    <path d="M12 8v8M8 12h8"></path>
                </svg>
            </div>
            <div class="brand-title-box">
                <span class="brand-title">Legal Metrology</span>
                <span class="brand-sub">Package Inspector</span>
                <span class="brand-tag">Legal Metrology System</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <?php if ($role === 'officer'): ?>
            <!-- OFFICER CONTROL SECTION -->
            <div class="nav-section-title">OFFICER CONTROL</div>
            <ul class="nav-list">
                <li>
                    <a href="officer-dashboard.php" class="nav-item <?php echo ($activeNav === 'dashboard' || $activeNav === 'officer-dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-th-large nav-icon"></i>
                        <span>Officer Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="companies.php" class="nav-item <?php echo ($activeNav === 'companies') ? 'active' : ''; ?>">
                        <i class="fas fa-building nav-icon"></i>
                        <span>Companies Catalog</span>
                    </a>
                </li>
                <li>
                    <a href="inspection-history.php" class="nav-item <?php echo ($activeNav === 'history') ? 'active' : ''; ?>">
                        <i class="fas fa-clipboard-list nav-icon"></i>
                        <span>All Inspections</span>
                    </a>
                </li>
                <li>
                    <a href="new-inspection.php" class="nav-item <?php echo ($activeNav === 'new-inspection') ? 'active' : ''; ?>">
                        <i class="fas fa-camera nav-icon"></i>
                        <span>New Inspection</span>
                        <span class="ai-badge-pill">AI</span>
                    </a>
                </li>
            </ul>

            <!-- PERSONNEL & RECORDS SECTION -->
            <div class="nav-section-title">PERSONNEL &amp; RECORDS</div>
            <ul class="nav-list">
                <li>
                    <a href="manage-inspectors.php" class="nav-item <?php echo ($activeNav === 'inspectors') ? 'active' : ''; ?>">
                        <i class="fas fa-users-cog nav-icon"></i>
                        <span>Manage Inspectors</span>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="nav-item <?php echo ($activeNav === 'products') ? 'active' : ''; ?>">
                        <i class="fas fa-boxes nav-icon"></i>
                        <span>Product Database</span>
                    </a>
                </li>
                <li>
                    <a href="inquiries.php" class="nav-item <?php echo ($activeNav === 'inquiries') ? 'active' : ''; ?>">
                        <i class="fas fa-gavel nav-icon"></i>
                        <span>Legal Inquiries</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="nav-item <?php echo ($activeNav === 'reports') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie nav-icon"></i>
                        <span>Reports &amp; Analytics</span>
                    </a>
                </li>
            </ul>

            <!-- ACCOUNT SECTION -->
            <div class="nav-section-title">ACCOUNT</div>
            <ul class="nav-list">
                <li>
                    <a href="profile.php" class="nav-item <?php echo ($activeNav === 'profile') ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle nav-icon"></i>
                        <span>Officer Profile</span>
                    </a>
                </li>
            </ul>

        <?php else: ?>

            <!-- FIELD WORKSPACE SECTION -->
            <div class="nav-section-title">FIELD WORKSPACE</div>
            <ul class="nav-list">
                <li>
                    <a href="inspector-dashboard.php" class="nav-item <?php echo ($activeNav === 'dashboard' || $activeNav === 'inspector-dashboard') ? 'active' : ''; ?>">
                        <i class="fas fa-th-large nav-icon"></i>
                        <span>My Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="new-inspection.php" class="nav-item <?php echo ($activeNav === 'new-inspection') ? 'active' : ''; ?>">
                        <i class="fas fa-camera nav-icon"></i>
                        <span>Scan Package (AI OCR)</span>
                        <span class="ai-badge-pill">AI</span>
                    </a>
                </li>
                <li>
                    <a href="inspection-history.php" class="nav-item <?php echo ($activeNav === 'history') ? 'active' : ''; ?>">
                        <i class="fas fa-history nav-icon"></i>
                        <span>My Inspections</span>
                    </a>
                </li>
            </ul>

            <!-- CATALOGS & INQUIRIES SECTION -->
            <div class="nav-section-title">CATALOGS &amp; RECORDS</div>
            <ul class="nav-list">
                <li>
                    <a href="companies.php" class="nav-item <?php echo ($activeNav === 'companies') ? 'active' : ''; ?>">
                        <i class="fas fa-building nav-icon"></i>
                        <span>Companies Directory</span>
                    </a>
                </li>
                <li>
                    <a href="products.php" class="nav-item <?php echo ($activeNav === 'products') ? 'active' : ''; ?>">
                        <i class="fas fa-boxes nav-icon"></i>
                        <span>Products Catalog</span>
                    </a>
                </li>
                <li>
                    <a href="inquiries.php" class="nav-item <?php echo ($activeNav === 'inquiries') ? 'active' : ''; ?>">
                        <i class="fas fa-envelope-open-text nav-icon"></i>
                        <span>Legal Notices</span>
                    </a>
                </li>
                <li>
                    <a href="reports.php" class="nav-item <?php echo ($activeNav === 'reports') ? 'active' : ''; ?>">
                        <i class="fas fa-file-invoice nav-icon"></i>
                        <span>Compliance Reports</span>
                    </a>
                </li>
            </ul>

            <!-- ACCOUNT SECTION -->
            <div class="nav-section-title">ACCOUNT</div>
            <ul class="nav-list">
                <li>
                    <a href="profile.php" class="nav-item <?php echo ($activeNav === 'profile') ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle nav-icon"></i>
                        <span>My Profile</span>
                    </a>
                </li>
            </ul>
        <?php endif; ?>
    </nav>

    <!-- Sidebar Profile Footer Card -->
    <div class="sidebar-footer-profile">
        <a href="profile.php" class="sidebar-user-card">
            <div class="sidebar-avatar-circle">
                <?php echo $userInitial; ?>
            </div>
            <div class="sidebar-user-meta">
                <div class="sidebar-user-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'Officer'); ?></div>
                <div class="sidebar-user-role"><?php echo strtoupper($currentUser['role'] ?? 'OFFICER'); ?></div>
                <div class="sidebar-user-id">ID: <?php echo htmlspecialchars($userEmpId); ?></div>
                <div class="sidebar-user-status"><span class="status-dot-green"></span> Online</div>
            </div>
            <div class="sidebar-user-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>
    </div>
</aside>
