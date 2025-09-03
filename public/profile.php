<?php
/**
 * User profile management page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\User;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check authentication
if (!$auth->isAuthenticated()) {
    header('Location: /login.php');
    exit();
}

$user = new User($db);
$current_user = $auth->getCurrentUser();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid CSRF token';
    } else {
        $update_data = [
            'first_name' => sanitize_input($_POST['first_name'] ?? ''),
            'last_name' => sanitize_input($_POST['last_name'] ?? ''),
            'email' => sanitize_input($_POST['email'] ?? '')
        ];

        // Validate required fields
        if (empty($update_data['first_name']) || empty($update_data['last_name']) || empty($update_data['email'])) {
            $error_message = 'All fields are required';
        } elseif (!filter_var($update_data['email'], FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Invalid email format';
        } else {
            $result = $user->updateProfile($current_user['id'], $update_data);
            if ($result['success']) {
                $success_message = 'Profile updated successfully!';
                // Refresh current user data
                $current_user = $auth->getCurrentUser();
            } else {
                $error_message = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="fas fa-envelope-open-text me-2"></i>
                Digital Invitations
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/invitations.php">My Invitations</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($current_user['first_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="/profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="/subscription.php">Subscription</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-edit me-2"></i>Profile Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="/profile.php">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?= htmlspecialchars($current_user['first_name']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?= htmlspecialchars($current_user['last_name']) ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($current_user['email']) ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Account Information</label>
                                <div class="bg-light p-3 rounded">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <strong>Role:</strong> 
                                            <span class="badge bg-primary text-capitalize"><?= $current_user['role'] ?></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <strong>Subscription:</strong> 
                                            <span class="badge bg-success text-capitalize"><?= $current_user['subscription_tier'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-key me-2"></i>Change Password
                        </h6>
                    </div>
                    <div class="card-body">
                        <form id="passwordForm">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       minlength="6" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" 
                                       minlength="6" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Change Password
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Account Statistics -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-bar me-2"></i>Account Statistics
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php $user_stats = $user->getUserStats($current_user['id']); ?>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total Invitations:</span>
                            <strong><?= $user_stats['total_invitations'] ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total Views:</span>
                            <strong><?= number_format($user_stats['total_views']) ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <span>Total RSVPs:</span>
                            <strong><?= $user_stats['total_rsvps'] ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <span>Active Invitations:</span>
                            <strong><?= $user_stats['active_invitations'] ?></strong>
                        </div>
                        
                        <hr>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                Member since <?= date('F Y', strtotime($current_user['created_at'] ?? 'now')) ?>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Account Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/subscription.php" class="btn btn-outline-primary">
                                <i class="fas fa-credit-card me-2"></i>Manage Subscription
                            </a>
                            <a href="/dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                            </a>
                            <button class="btn btn-outline-danger" onclick="confirmAccountDeletion()">
                                <i class="fas fa-user-times me-2"></i>Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
    
    <script>
        // Password change form handling
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_new_password').value;
            
            if (newPassword !== confirmPassword) {
                App.showNotification('New passwords do not match', 'error');
                return;
            }
            
            // In a real implementation, this would make an API call
            App.showNotification('Password change functionality would be implemented here', 'info');
        });

        function confirmAccountDeletion() {
            if (confirm('Are you sure you want to delete your account? This action cannot be undone and will permanently delete all your invitations and data.')) {
                if (confirm('This is your final warning. Are you absolutely sure you want to delete your account?')) {
                    // In a real implementation, this would make an API call to delete the account
                    App.showNotification('Account deletion would be implemented here', 'warning');
                }
            }
        }
    </script>
</body>
</html>