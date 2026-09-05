<?php
/**
 * AI Package Inspector - Top Header Navbar Component
 */
$currentUser = get_logged_user();
$headerTitle = $headerTitle ?? $pageTitle ?? 'Officer Control Dashboard';
$userInitial = strtoupper(substr($currentUser['name'] ?? 'F', 0, 1));
?>
<header class="top-navbar">
    <div class="navbar-left">
        <button class="mobile-sidebar-toggle" id="sidebarToggleBtn" aria-label="Toggle Navigation">
            <i class="fas fa-bars"></i>
        </button>
        <h1 class="top-navbar-title"><?php echo htmlspecialchars($headerTitle); ?></h1>
    </div>

    <div class="navbar-right">
        <div class="ocr-engine-pill">
            <span class="ocr-dot"></span>
            <span class="ocr-text">OCR AI Engine: <strong>Online</strong></span>
        </div>

        <a href="new-inspection.php" class="btn btn-primary top-cta-btn">
            <i class="fas fa-camera"></i> New Inspection
        </a>

        <a href="profile.php" class="top-user-pill">
            <span class="top-user-avatar"><?php echo $userInitial; ?></span>
            <span class="top-user-name"><?php echo htmlspecialchars($currentUser['name'] ?? 'User'); ?></span>
            <?php if (strtolower($currentUser['role'] ?? '') === 'officer'): ?>
                <span class="badge" style="background-color: #2563eb; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; margin-left: 6px;">
                    <i class="fas fa-shield-alt"></i> OFFICER
                </span>
            <?php else: ?>
                <span class="badge" style="background-color: #059669; color: #ffffff; padding: 2px 8px; border-radius: 4px; font-size: 0.72rem; font-weight: 700; margin-left: 6px;">
                    <i class="fas fa-search-location"></i> INSPECTOR
                </span>
            <?php endif; ?>
        </a>
    </div>
</header>
