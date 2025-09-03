<?php
/**
 * Admin Dashboard
 */

require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use App\Auth;
use App\User;
use App\Template;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check authentication and admin role
if (!$auth->isAuthenticated()) {
    header('Location: /login.php');
    exit();
}

$current_user = $auth->getCurrentUser();
if ($current_user['role'] !== 'admin') {
    header('Location: /dashboard.php');
    exit();
}

$user = new User($db);
$template = new Template($db);

// Get admin statistics
$stats = [];

// Total users
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$stats['total_users'] = $stmt->fetch()['total'];

// Total invitations
$stmt = $db->prepare("SELECT COUNT(*) as total FROM invitations");
$stmt->execute();
$stats['total_invitations'] = $stmt->fetch()['total'];

// Total templates
$stmt = $db->prepare("SELECT COUNT(*) as total FROM templates WHERE is_active = 1");
$stmt->execute();
$stats['total_templates'] = $stmt->fetch()['total'];

// Total RSVPs
$stmt = $db->prepare("SELECT COUNT(*) as total FROM rsvps");
$stmt->execute();
$stats['total_rsvps'] = $stmt->fetch()['total'];

// Recent users
$recent_users = $user->getAllUsers(10, 0);

// Recent invitations
$stmt = $db->prepare("
    SELECT i.*, u.email, u.first_name, u.last_name, t.name as template_name
    FROM invitations i
    LEFT JOIN users u ON i.user_id = u.id
    LEFT JOIN templates t ON i.template_id = t.id
    ORDER BY i.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_invitations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .admin-sidebar {
            background: #2c3e50;
            min-height: 100vh;
        }
        .admin-nav-link {
            color: #ecf0f1;
            padding: 12px 20px;
            display: block;
            text-decoration: none;
            border-radius: 8px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        .admin-nav-link:hover, .admin-nav-link.active {
            background: #34495e;
            color: #3498db;
        }
        .stat-card {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border-radius: 15px;
        }
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
    </style>
</head>
<body class="bg-light">
    <div class="row g-0">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2">
            <div class="admin-sidebar p-3">
                <div class="text-center mb-4">
                    <h5 class="text-white">
                        <i class="fas fa-cog me-2"></i>Admin Panel
                    </h5>
                </div>
                
                <nav>
                    <a href="/admin/" class="admin-nav-link active">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="/admin/users.php" class="admin-nav-link">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                    <a href="/admin/templates.php" class="admin-nav-link">
                        <i class="fas fa-palette me-2"></i>Templates
                    </a>
                    <a href="/admin/invitations.php" class="admin-nav-link">
                        <i class="fas fa-envelope me-2"></i>Invitations
                    </a>
                    <a href="/admin/analytics.php" class="admin-nav-link">
                        <i class="fas fa-chart-line me-2"></i>Analytics
                    </a>
                    
                    <hr class="my-3 border-secondary">
                    
                    <a href="/dashboard.php" class="admin-nav-link">
                        <i class="fas fa-arrow-left me-2"></i>Back to App
                    </a>
                    <a href="#" class="admin-nav-link" onclick="logout()">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10">
            <div class="container-fluid p-4">
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col">
                        <h1 class="h3 mb-2">Admin Dashboard</h1>
                        <p class="text-muted">System overview and management</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card p-4 text-center">
                            <div class="display-6 mb-2">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="mb-1"><?= number_format($stats['total_users']) ?></h3>
                            <p class="mb-0 opacity-75">Total Users</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card p-4 text-center">
                            <div class="display-6 mb-2">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3 class="mb-1"><?= number_format($stats['total_invitations']) ?></h3>
                            <p class="mb-0 opacity-75">Total Invitations</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card p-4 text-center">
                            <div class="display-6 mb-2">
                                <i class="fas fa-palette"></i>
                            </div>
                            <h3 class="mb-1"><?= number_format($stats['total_templates']) ?></h3>
                            <p class="mb-0 opacity-75">Active Templates</p>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stat-card p-4 text-center">
                            <div class="display-6 mb-2">
                                <i class="fas fa-reply"></i>
                            </div>
                            <h3 class="mb-1"><?= number_format($stats['total_rsvps']) ?></h3>
                            <p class="mb-0 opacity-75">Total RSVPs</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Recent Users</h6>
                                    <a href="/admin/users.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_users)): ?>
                                <p class="text-muted text-center">No users found</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Plan</th>
                                                <th>Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($recent_users, 0, 5) as $user_item): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']) ?></strong>
                                                        <br><small class="text-muted"><?= htmlspecialchars($user_item['email']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary text-capitalize">
                                                        <?= $user_item['subscription_tier'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?= date('M j, Y', strtotime($user_item['created_at'])) ?></small>
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

                    <!-- Recent Invitations -->
                    <div class="col-lg-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">Recent Invitations</h6>
                                    <a href="/admin/invitations.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_invitations)): ?>
                                <p class="text-muted text-center">No invitations found</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Creator</th>
                                                <th>Views</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($recent_invitations, 0, 5) as $inv): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($inv['title']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($inv['template_name']) ?></small>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name']) ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($inv['email']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?= $inv['views_count'] ?></span>
                                                </td>
                                                <td>
                                                    <small><?= date('M j, Y', strtotime($inv['created_at'])) ?></small>
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
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h6 class="mb-0">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <a href="/admin/templates.php?action=create" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add Template
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="/admin/users.php" class="btn btn-info w-100">
                                            <i class="fas fa-users me-2"></i>Manage Users
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="/admin/analytics.php" class="btn btn-success w-100">
                                            <i class="fas fa-chart-line me-2"></i>View Analytics
                                        </a>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <a href="/admin/settings.php" class="btn btn-secondary w-100">
                                            <i class="fas fa-cog me-2"></i>Settings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
</body>
</html>