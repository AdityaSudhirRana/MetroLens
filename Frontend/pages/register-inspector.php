<?php
/**
 * AI Package Inspector - Officer Onboarding Inspector Form
 * File: pages/register-inspector.php
 */
$pageTitle = 'Onboard New Inspector';
$activeNav = 'inspectors';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

requireOfficer(); // Backend RBAC guard

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $employeeId = trim($_POST['employee_id'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? 'Legal Metrology Enforcement');
    $designation = trim($_POST['designation'] ?? 'Field Inspector');
    $officeRegion = trim($_POST['office_region'] ?? 'West Region');
    $joiningDate = trim($_POST['joining_date'] ?? date('Y-m-d'));
    $password = trim($_POST['password'] ?? '');

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Full Name, Email, and Password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $db = getDB();
            $chk = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'An account with this email address already exists.';
            } else {
                if (empty($employeeId)) {
                    $employeeId = 'INS-' . date('Y') . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
                }
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $db->prepare("
                    INSERT INTO users (employee_id, full_name, email, password_hash, role, department, designation, office_region, joining_date, phone, status)
                    VALUES (?, ?, ?, ?, 'inspector', ?, ?, ?, ?, ?, 'Active')
                ");
                $stmt->execute([$employeeId, $fullName, $email, $hash, $department, $designation, $officeRegion, $joiningDate, $phone]);

                log_audit($_SESSION['user_id'], 'ONBOARD_INSPECTOR', "Officer onboarded new inspector {$fullName} ({$employeeId})");

                $success = "Inspector account created successfully! Employee ID: {$employeeId}";
            }
        } catch (Exception $e) {
            $error = 'Error onboarding inspector: ' . $e->getMessage();
        }
    }
}
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="margin-bottom: 24px;">
            <a href="manage-inspectors.php" class="btn btn-outline btn-sm" style="margin-bottom: 8px;">
                <i class="fas fa-arrow-left"></i> Back to Inspector Directory
            </a>
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">Onboard New Field Inspector</h2>
            <p style="font-size: 0.88rem; color: var(--text-muted);">Register field officer credentials and assign regional jurisdiction</p>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px 16px; background-color: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 6px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 800px;">
            <div class="card-header">
                <h3 class="card-title">Inspector Account Credentials</h3>
            </div>
            <div class="card-body">
                <form action="register-inspector.php" method="POST">
                    <div class="form-grid-2">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Full Name *</strong></label>
                            <input type="text" name="full_name" class="form-control" placeholder="Inspector Amit Verma" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Official Email *</strong></label>
                            <input type="email" name="email" class="form-control" placeholder="a.verma@gov.in" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Employee ID</strong> (Optional)</label>
                            <input type="text" name="employee_id" class="form-control" placeholder="INS-2026-104" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Contact Phone</strong></label>
                            <input type="tel" name="phone" class="form-control" placeholder="+91 98333 44556" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Office / Region Zone</strong></label>
                            <input type="text" name="office_region" class="form-control" value="West Region" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Joining Date</strong></label>
                            <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label><strong>Temporary Account Password *</strong></label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Register & Issue Credentials
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
