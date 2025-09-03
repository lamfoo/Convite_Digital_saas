<?php
/**
 * User Dashboard
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\User;
use App\Invitation;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check authentication
if (!$auth->isAuthenticated()) {
    header('Location: /login.php');
    exit();
}

$user = new User($db);
$invitation = new Invitation($db);

$current_user = $auth->getCurrentUser();
$user_stats = $user->getUserStats($current_user['id']);
$recent_invitations = $invitation->getUserInvitations($current_user['id'], 5, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        .invitation-card {
            transition: transform 0.2s ease;
        }
        .invitation-card:hover {
            transform: translateY(-2px);
        }
    </style>
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
                        <a class="nav-link active" href="/dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/create-invitation.php">Create Invitation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/templates.php">Templates</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?= htmlspecialchars($current_user['first_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="/subscription.php">Subscription</a></li>
                            <?php if ($current_user['role'] === 'admin'): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/admin/">Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-2">Welcome back, <?= htmlspecialchars($current_user['first_name']) ?>!</h1>
                <p class="text-muted">Here's what's happening with your invitations</p>
            </div>
            <div class="col-auto">
                <a href="/create-invitation.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Invitation
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="mb-1"><?= $user_stats['total_invitations'] ?></h3>
                    <p class="mb-0 opacity-75">Total Invitations</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="mb-1"><?= number_format($user_stats['total_views']) ?></h3>
                    <p class="mb-0 opacity-75">Total Views</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-reply"></i>
                    </div>
                    <h3 class="mb-1"><?= $user_stats['total_rsvps'] ?></h3>
                    <p class="mb-0 opacity-75">Total RSVPs</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-1"><?= $user_stats['active_invitations'] ?></h3>
                    <p class="mb-0 opacity-75">Active Invitations</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Invitations -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Invitations</h5>
                            <a href="/invitations.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_invitations)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope-open text-muted" style="font-size: 3rem;"></i>
                            <h6 class="mt-3 text-muted">No invitations yet</h6>
                            <p class="text-muted">Create your first invitation to get started!</p>
                            <a href="/create-invitation.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Invitation
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Template</th>
                                        <th>Views</th>
                                        <th>RSVPs</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_invitations as $inv): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($inv['title']) ?></strong>
                                            <?php if (!$inv['is_active']): ?>
                                            <span class="badge bg-secondary ms-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($inv['template_category']) ?></span>
                                            <?= htmlspecialchars($inv['template_name']) ?>
                                        </td>
                                        <td><?= $inv['views_count'] ?></td>
                                        <td><?= $inv['rsvp_count'] ?></td>
                                        <td><?= date('M j, Y', strtotime($inv['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="/invitation/<?= $inv['unique_code'] ?>" class="btn btn-outline-primary" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/edit-invitation.php?id=<?= $inv['id'] ?>" class="btn btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-outline-danger" onclick="deleteInvitation(<?= $inv['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Info -->
            <div class="col-lg-4">
                <!-- Subscription Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Subscription Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-capitalize fw-bold"><?= $current_user['subscription_tier'] ?> Plan</span>
                            <span class="badge bg-success">Active</span>
                        </div>
                        
                        <?php 
                        $limits = SUBSCRIPTION_LIMITS[$current_user['subscription_tier']];
                        $monthly_limit = $limits['invitations_per_month'];
                        ?>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Monthly Invitations</span>
                                <span>
                                    <?php if ($monthly_limit == -1): ?>
                                        Unlimited
                                    <?php else: ?>
                                        <?= $user_stats['total_invitations'] ?> / <?= $monthly_limit ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <?php if ($monthly_limit != -1): ?>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar" style="width: <?= min(100, ($user_stats['total_invitations'] / $monthly_limit) * 100) ?>%"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <a href="/subscription.php" class="btn btn-outline-primary btn-sm w-100">
                            Manage Subscription
                        </a>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="/create-invitation.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>New Invitation
                            </a>
                            <a href="/templates.php" class="btn btn-outline-secondary">
                                <i class="fas fa-palette me-2"></i>Browse Templates
                            </a>
                            <a href="/invitations.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>All Invitations
                            </a>
                            <?php if ($current_user['role'] === 'admin'): ?>
                            <a href="/admin/" class="btn btn-outline-warning">
                                <i class="fas fa-cog me-2"></i>Admin Panel
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // CSRF token for AJAX requests
        window.csrfToken = '<?= csrf_token() ?>';
        
        // Delete invitation function
        function deleteInvitation(invitationId) {
            if (!confirm('Are you sure you want to delete this invitation? This action cannot be undone.')) {
                return;
            }

            fetch(`/api/invitations.php?id=${invitationId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                alert('An error occurred while deleting the invitation.');
            });
        }
        
        // Logout function
        function logout() {
            fetch('/api/auth.php?action=logout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: window.csrfToken
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/';
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
            });
        }
    </script>
</body>
</html>