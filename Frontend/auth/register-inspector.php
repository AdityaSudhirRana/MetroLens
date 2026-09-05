<?php
/**
 * AI Package Inspector - Inspector Registration Page & Controller
 * File: auth/register-inspector.php
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . (isOfficer() ? '../pages/officer-dashboard.php' : '../pages/inspector-dashboard.php'));
    exit;
}

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
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid official email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $db = getDB();

            // Check existing email
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
                $newId = $db->lastInsertId();

                log_audit($newId, 'REGISTER_INSPECTOR', 'New Field Inspector account registered');

                // Auto Login
                $_SESSION['user_id'] = $newId;
                $_SESSION['employee_id'] = $employeeId;
                $_SESSION['name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'inspector';
                $_SESSION['department'] = $department;

                header('Location: ../pages/inspector-dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspector Registration | AI Package Inspector</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <img src="../assets/logo.svg" alt="Legal Metrology Logo" class="auth-logo">
                <h1 class="auth-title"><i class="fas fa-search" style="color: var(--primary);"></i> Field Inspector Registration</h1>
                <p class="auth-subtitle">Legal Metrology Field Verification & Commodity Inspection Portal</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register-inspector.php" method="POST">
                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label><strong>Full Name *</strong></label>
                        <input type="text" name="full_name" class="form-control" placeholder="Inspector Rajesh Sharma" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label><strong>Official Email *</strong></label>
                        <input type="email" name="email" class="form-control" placeholder="r.sharma@gov.in" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label><strong>Employee ID</strong> (Optional - Auto-generated if blank)</label>
                        <input type="text" name="employee_id" class="form-control" placeholder="INS-2026-101" value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label><strong>Contact Phone</strong></label>
                        <input type="tel" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label><strong>Department / Zone</strong></label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($_POST['department'] ?? 'Legal Metrology Enforcement'); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label><strong>Joining Date</strong></label>
                        <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d')); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label><strong>Password *</strong></label>
                        <input type="password" name="password" class="form-control" required placeholder="••••••••" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label><strong>Confirm Password *</strong></label>
                        <input type="password" name="confirm_password" class="form-control" required placeholder="••••••••" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fas fa-user-check"></i> Complete Field Inspector Registration
                </button>
            </form>

            <div class="auth-footer">
                Already registered? <a href="login.php">Sign In Here</a> | <a href="register-officer.php">Register as Officer</a>
            </div>
        </div>
    </div>
</body>
</html>
