<?php
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Security.php';

use MovieSuggestor\Database;
use MovieSuggestor\Security;

// Initialize session and require authentication
Security::initSession();
Security::requireAuth();

$userId = Security::getUserId();
$db = new Database();
$pdo = $db->connect();

// Fetch user data
$stmt = $pdo->prepare("
    SELECT id, email, username, created_at, is_verified
    FROM users 
    WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $newUsername = trim($_POST['username'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        
        // Validate inputs
        if (empty($newUsername) || empty($newEmail)) {
            $error = 'Username and email are required.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } else {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $userId]);
            if ($stmt->fetch()) {
                $error = 'Email is already taken by another user.';
            } else {
                // Update user profile
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET username = ?, email = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newUsername, $newEmail, $userId]);
                $message = 'Profile updated successfully!';
                
                // Refresh user data
                $user['username'] = $newUsername;
                $user['email'] = $newEmail;
            }
        }
    } elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate inputs
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $error = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'New password must be at least 8 characters long.';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($currentPassword, $userData['password_hash'])) {
                $error = 'Current password is incorrect.';
            } else {
                // Update password
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET password_hash = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$newPasswordHash, $userId]);
                $message = 'Password changed successfully!';
            }
        }
    } elseif ($action === 'delete_account') {
        $confirmPassword = $_POST['confirm_password_delete'] ?? '';
        
        if (empty($confirmPassword)) {
            $error = 'Password is required to delete account.';
        } else {
            // Verify password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($confirmPassword, $userData['password_hash'])) {
                $error = 'Password is incorrect.';
            } else {
                // Delete user data
                $pdo->beginTransaction();
                try {
                    $pdo->prepare("DELETE FROM favorites WHERE user_id = ?")->execute([$userId]);
                    $pdo->prepare("DELETE FROM watch_later WHERE user_id = ?")->execute([$userId]);
                    $pdo->prepare("DELETE FROM ratings WHERE user_id = ?")->execute([$userId]);
                    $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$userId]);
                    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
                    $pdo->commit();
                    
                    // Logout and redirect
                    Security::logout();
                    header('Location: ../index.php?message=account_deleted');
                    exit;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Failed to delete account. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Movie Suggestor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .settings-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        .section-title {
            color: #667eea;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        .danger-zone {
            border: 2px solid #dc3545;
            border-radius: 10px;
            padding: 20px;
            background: #fff5f5;
        }
        .btn-danger-custom {
            background: #dc3545;
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 25px;
            font-weight: 500;
        }
        .btn-danger-custom:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="settings-container">
        <!-- Header -->
        <div class="settings-card">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="mb-0"><i class="fas fa-cog"></i> Account Settings</h1>
                <a href="profile.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Profile Information -->
        <div class="settings-card">
            <h3 class="section-title"><i class="fas fa-user"></i> Profile Information</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </form>
        </div>

        <!-- Change Password -->
        <div class="settings-card">
            <h3 class="section-title"><i class="fas fa-lock"></i> Change Password</h3>
            <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password</label>
                    <input type="password" class="form-control" id="current_password" 
                           name="current_password" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="new_password" 
                           name="new_password" required minlength="8">
                    <small class="form-text text-muted">Must be at least 8 characters long</small>
                </div>
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="confirm_password" 
                           name="confirm_password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="settings-card">
            <h3 class="section-title text-danger"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
            <div class="danger-zone">
                <h5 class="text-danger mb-3">Delete Account</h5>
                <p class="mb-3">
                    Once you delete your account, there is no going back. This will permanently delete:
                </p>
                <ul class="mb-3">
                    <li>Your account and profile</li>
                    <li>All your favorites</li>
                    <li>Your watch later list</li>
                    <li>All your ratings and reviews</li>
                </ul>
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!');">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="mb-3">
                        <label for="confirm_password_delete" class="form-label">Confirm your password to delete account</label>
                        <input type="password" class="form-control" id="confirm_password_delete" 
                               name="confirm_password_delete" required>
                    </div>
                    <button type="submit" class="btn btn-danger-custom">
                        <i class="fas fa-trash"></i> Delete My Account
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
