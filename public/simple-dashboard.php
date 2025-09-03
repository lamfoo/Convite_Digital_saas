<?php
/**
 * Simplified dashboard for shared hosting
 */

require_once 'simple-config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: simple-login.php');
    exit();
}

$db = new SimpleDatabase();
$conn = $db->getConnection();

// Get user stats
$user_id = $_SESSION['user_id'];

$stats = [];
try {
    // Total invitations
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM invitations WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_invitations'] = $stmt->fetch()['total'] ?? 0;

    // Total views
    $stmt = $conn->prepare("SELECT SUM(views_count) as total_views FROM invitations WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_views'] = $stmt->fetch()['total_views'] ?? 0;

    // Total RSVPs
    $stmt = $conn->prepare("SELECT COUNT(*) as total_rsvps FROM rsvps r JOIN invitations i ON r.invitation_id = i.id WHERE i.user_id = ?");
    $stmt->execute([$user_id]);
    $stats['total_rsvps'] = $stmt->fetch()['total_rsvps'] ?? 0;

    // Active invitations
    $stmt = $conn->prepare("SELECT COUNT(*) as active FROM invitations WHERE user_id = ? AND is_active = 1");
    $stmt->execute([$user_id]);
    $stats['active_invitations'] = $stmt->fetch()['active'] ?? 0;

    // Recent invitations
    $stmt = $conn->prepare("
        SELECT i.*, t.name as template_name, t.category as template_category, COUNT(r.id) as rsvp_count
        FROM invitations i
        LEFT JOIN templates t ON i.template_id = t.id
        LEFT JOIN rsvps r ON i.id = r.invitation_id
        WHERE i.user_id = ?
        GROUP BY i.id
        ORDER BY i.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_invitations = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $stats = ['total_invitations' => 0, 'total_views' => 0, 'total_rsvps' => 0, 'active_invitations' => 0];
    $recent_invitations = [];
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-envelope-open-text me-2"></i>
                Digital Invitations
            </a>
            
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>!
                </span>
                <a class="nav-link" href="?logout=1">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-2">Dashboard</h1>
                <p class="text-muted">Welcome to your Digital Invitations platform!</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $stats['total_invitations']; ?></h3>
                    <p class="mb-0 opacity-75">Total Invitations</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="mb-1"><?php echo number_format($stats['total_views']); ?></h3>
                    <p class="mb-0 opacity-75">Total Views</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-reply"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $stats['total_rsvps']; ?></h3>
                    <p class="mb-0 opacity-75">Total RSVPs</p>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="stat-card p-4 text-center">
                    <div class="display-6 mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $stats['active_invitations']; ?></h3>
                    <p class="mb-0 opacity-75">Active Invitations</p>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Invitations -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Recent Invitations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_invitations)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-envelope-open text-muted" style="font-size: 3rem;"></i>
                            <h6 class="mt-3 text-muted">No invitations yet</h6>
                            <p class="text-muted">Create your first invitation to get started!</p>
                            <a href="create-simple-invitation.php" class="btn btn-primary">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_invitations as $inv): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($inv['title']); ?></strong>
                                            <?php if (!$inv['is_active']): ?>
                                            <span class="badge bg-secondary ms-2">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($inv['template_category'] ?? 'Unknown'); ?></span>
                                            <?php echo htmlspecialchars($inv['template_name'] ?? 'Unknown'); ?>
                                        </td>
                                        <td><?php echo $inv['views_count']; ?></td>
                                        <td><?php echo $inv['rsvp_count']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($inv['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Quick Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="create-simple-invitation.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>New Invitation
                            </a>
                            <a href="simple-templates.php" class="btn btn-outline-secondary">
                                <i class="fas fa-palette me-2"></i>Browse Templates
                            </a>
                            <a href="simple-invitations.php" class="btn btn-outline-secondary">
                                <i class="fas fa-list me-2"></i>All Invitations
                            </a>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="simple-admin.php" class="btn btn-outline-warning">
                                <i class="fas fa-cog me-2"></i>Admin Panel
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Subscription Info -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Subscription</h6>
                    </div>
                    <div class="card-body">
                        <h5 class="text-capitalize"><?php echo $_SESSION['subscription_tier'] ?? 'free'; ?> Plan</h5>
                        <span class="badge bg-success">Active</span>
                        
                        <div class="mt-3">
                            <?php 
                            $tier = $_SESSION['subscription_tier'] ?? 'free';
                            $limits = SUBSCRIPTION_LIMITS[$tier];
                            ?>
                            <small class="text-muted">
                                Monthly limit: <?php echo $limits['invitations_per_month'] == -1 ? 'Unlimited' : $limits['invitations_per_month']; ?> invitations<br>
                                Templates: <?php echo ucfirst($limits['templates_access']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>