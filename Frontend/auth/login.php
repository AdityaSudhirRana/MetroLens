<?php
/**
 * AI Package Inspector - Unified Role-Based Login Page & Controller
 * File: auth/login.php
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . (isOfficer() ? '../pages/officer-dashboard.php' : '../pages/inspector-dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email address and password.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['status'] !== 'Active') {
                    $error = 'Account status is ' . htmlspecialchars($user['status']) . '. Contact administrator.';
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['employee_id'] = $user['employee_id'];
                    $_SESSION['name'] = $user['full_name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = strtolower($user['role']);
                    $_SESSION['department'] = $user['department'] ?? 'Legal Metrology';

                    $upd = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $upd->execute([$user['id']]);

                    log_audit($user['id'], 'LOGIN', "Logged in as " . strtoupper($user['role']));

                    if (strtolower($user['role']) === 'officer') {
                        header('Location: ../pages/officer-dashboard.php');
                    } else {
                        header('Location: ../pages/inspector-dashboard.php');
                    }
                    exit;
                }
            } else {
                $error = 'Invalid email address or password.';
            }
        } catch (Exception $e) {
            $error = 'System error during login: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Sign In | AI Package Inspector</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <img src="../assets/logo.svg" alt="Legal Metrology Logo" class="auth-logo">
                <h1 class="auth-title">AI Package Inspector</h1>
                <p class="auth-subtitle">Legal Metrology Officer & Field Inspector Portal</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px; font-size: 0.88rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="demo-accounts-box">
                <div class="demo-title"><i class="fas fa-key"></i> Quick Demo Sign-In:</div>
                <div class="demo-account-item">
                    <span>Officer Role:</span>
                    <a href="#" onclick="fillLogin('admin@example.com', 'admin123'); return false;"><code>admin@example.com</code> / <code>admin123</code></a>
                </div>
                <div class="demo-account-item">
                    <span>Inspector Role:</span>
                    <a href="#" onclick="fillLogin('officer@example.com', '123456'); return false;"><code>officer@example.com</code> / <code>123456</code></a>
                </div>
            </div>

            <form action="login.php" method="POST">
                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Official Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" required placeholder="officer@gov.in" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 18px;">
                    <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 0.88rem;">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••" style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>

                <button type="submit" class="btn-auth-submit">
                    <i class="fas fa-sign-in-alt"></i> Access Verification System
                </button>
            </form>

            <div class="auth-footer" style="margin-top: 24px; font-size: 0.88rem; text-align: center;">
                Need an account?<br>
                <a href="register-officer.php"><i class="fas fa-user-shield"></i> Register Officer</a> &bull; 
                <a href="register-inspector.php"><i class="fas fa-user-check"></i> Register Inspector</a>
            </div>
        </div>
    </div>

    <script>
        function fillLogin(email, pass) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>
