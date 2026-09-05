<?php
/**
 * AI Package Inspector - Officer Registration Controller & Page
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    $department = trim($_POST['department'] ?? 'Legal Metrology Enforcement');
    $designation = trim($_POST['designation'] ?? 'Inspector');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please complete all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        try {
            $db = getDB();

            // Check if email already registered
            $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = 'An inspector account with this email address already exists.';
            } else {
                // Generate employee ID
                $employeeId = 'LM-' . date('Y') . '-' . str_pad(rand(100, 9999), 4, '0', STR_PAD_LEFT);
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $db->prepare("
                    INSERT INTO users (employee_id, full_name, email, password_hash, role, department, designation, phone, status)
                    VALUES (?, ?, ?, ?, 'Inspector', ?, ?, ?, 'Active')
                ");

                $insertStmt->execute([
                    $employeeId,
                    $fullName,
                    $email,
                    $passwordHash,
                    $department,
                    $designation,
                    $phone
                ]);

                $newUserId = $db->lastInsertId();

                // Log audit
                log_audit($newUserId, 'REGISTER', 'New inspector registration created');

                // Auto login
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['employee_id'] = $employeeId;
                $_SESSION['name'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'Inspector';
                $_SESSION['department'] = $department;

                header('Location: ../pages/dashboard.php');
                exit;
            }
        } catch (Exception $e) {
            $error = 'Registration failed due to a system error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Registration | AI Package Inspector</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <img src="../assets/logo.svg" alt="Legal Metrology Logo" class="auth-logo">
                <h1 class="auth-title">Register Legal Metrology Officer</h1>
                <p class="auth-subtitle">Join the AI-powered package compliance enforcement network</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; font-size: 0.88rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" id="registerForm">
                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="full_name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Officer Rajesh Sharma" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="email" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Official Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="r.sharma@gov.in" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="department" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Department / Zone</label>
                        <input type="text" id="department" name="department" class="form-control" placeholder="Legal Metrology Zone 1" value="<?php echo htmlspecialchars($_POST['department'] ?? 'Legal Metrology Enforcement'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 16px;">
                        <label for="designation" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Official Designation</label>
                        <input type="text" id="designation" name="designation" class="form-control" placeholder="Senior Inspector" value="<?php echo htmlspecialchars($_POST['designation'] ?? 'Inspector'); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label for="phone" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Contact Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="+91 98765 43210" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="form-grid-2">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="password" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label for="confirm_password" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="••••••••" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    </div>
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fas fa-user-plus"></i> Complete Officer Registration
                </button>
            </form>

            <div class="auth-footer">
                Already registered? <a href="login.php">Sign In Here</a>
            </div>
        </div>
    </div>
</body>
</html>
