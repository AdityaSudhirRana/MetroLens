<?php
/**
 * AI Package Inspector - Inspector Profile & Settings
 */
$pageTitle = 'Officer Profile & Settings';
$activeNav = 'profile';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (empty($fullName)) {
        $error = 'Full Name is required.';
    } else {
        try {
            if (!empty($newPassword)) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $upd = $db->prepare("UPDATE users SET full_name = ?, phone = ?, department = ?, designation = ?, password_hash = ? WHERE id = ?");
                $upd->execute([$fullName, $phone, $department, $designation, $hash, $userId]);
            } else {
                $upd = $db->prepare("UPDATE users SET full_name = ?, phone = ?, department = ?, designation = ? WHERE id = ?");
                $upd->execute([$fullName, $phone, $department, $designation, $userId]);
            }

            $_SESSION['name'] = $fullName;
            $_SESSION['department'] = $department;

            log_audit($userId, 'UPDATE_PROFILE', 'Officer profile information updated');
            $message = 'Profile updated successfully.';

            // Refresh user array
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}
?>
<div class="main-content">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>

    <div class="content-body">

        <div style="margin-bottom: 24px;">
            <h2 style="font-size: 1.6rem; font-weight: 800; color: var(--primary-dark);">Officer Profile & Account Settings</h2>
            <p style="font-size: 0.88rem; color: var(--text-muted);">Manage your credentials, officer credentials, and department assignment</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px 16px; background-color: #dcfce7; border: 1px solid #86efac; color: #166534; border-radius: 6px;">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 6px;">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Officer Credentials</h3>
                </div>
                <div class="card-body">
                    <form action="profile.php" method="POST">
                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Full Name *</strong></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <label><strong>Email Address (Official)</strong></label>
                                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; background: #f1f5f9; border-radius: 6px;">
                            </div>
                            <div>
                                <label><strong>Employee / Inspector ID</strong></label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['employee_id']); ?>" disabled style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; background: #f1f5f9; border-radius: 6px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <label><strong>Department / Zone</strong></label>
                                <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($user['department']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <div>
                                <label><strong>Official Designation</strong></label>
                                <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($user['designation']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 16px;">
                            <label><strong>Contact Phone</strong></label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label><strong>Change Password (leave blank to keep current)</strong></label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Profile Changes
                        </button>
                    </form>
                </div>
            </div>

            <!-- Profile Summary Card -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Inspector Card</h3>
                </div>
                <div class="card-body" style="text-align: center;">
                    <div style="width: 80px; height: 80px; border-radius: 50%; background: #1e3a8a; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 2.2rem; font-weight: 800; margin: 0 auto 16px auto;">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--primary-dark); margin-bottom: 4px;">
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </h3>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 12px;">
                        <?php echo htmlspecialchars($user['designation']); ?> &bull; <?php echo htmlspecialchars($user['employee_id']); ?>
                    </div>
                    <span class="badge badge-success" style="font-size: 0.85rem; padding: 6px 14px;">
                        <i class="fas fa-user-check"></i> <?php echo htmlspecialchars($user['role']); ?> Access
                    </span>
                </div>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
