<?php
/**
 * All invitations management page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Invitation;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check authentication
if (!$auth->isAuthenticated()) {
    header('Location: /login.php');
    exit();
}

$invitation = new Invitation($db);
$current_user = $auth->getCurrentUser();

$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$invitations = $invitation->getUserInvitations($current_user['id'], $limit, $offset);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Invitations - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="/assets/css/style.css">
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
                        <a class="nav-link active" href="/invitations.php">My Invitations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/create-invitation.php">Create New</a>
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
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-2">My Invitations</h1>
                <p class="text-muted">Manage all your digital invitations</p>
            </div>
            <div class="col-auto">
                <a href="/create-invitation.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create New Invitation
                </a>
            </div>
        </div>

        <!-- Invitations List -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">All Invitations</h5>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="filterStatus" onchange="filterInvitations()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <select class="form-select form-select-sm" id="sortBy" onchange="sortInvitations()">
                            <option value="created_desc">Newest First</option>
                            <option value="created_asc">Oldest First</option>
                            <option value="views_desc">Most Views</option>
                            <option value="rsvps_desc">Most RSVPs</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($invitations)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-envelope-open text-muted" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-muted">No invitations yet</h5>
                    <p class="text-muted">Create your first invitation to get started!</p>
                    <a href="/create-invitation.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Your First Invitation
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Invitation</th>
                                <th>Template</th>
                                <th>Event Date</th>
                                <th>Views</th>
                                <th>RSVPs</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invitations as $inv): ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?= htmlspecialchars($inv['title']) ?></strong>
                                        <?php if ($inv['event_location']): ?>
                                        <br><small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?= htmlspecialchars($inv['event_location']) ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary text-capitalize">
                                        <?= htmlspecialchars(str_replace('-', ' ', $inv['template_category'])) ?>
                                    </span>
                                    <br><small class="text-muted"><?= htmlspecialchars($inv['template_name']) ?></small>
                                </td>
                                <td>
                                    <?php if ($inv['event_date']): ?>
                                        <?= format_date($inv['event_date'], 'M j, Y') ?>
                                        <br><small class="text-muted"><?= format_date($inv['event_date'], 'g:i A') ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $inv['views_count'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?= $inv['rsvp_count'] ?></span>
                                </td>
                                <td>
                                    <?php if ($inv['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= date('M j, Y', strtotime($inv['created_at'])) ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/invitation/<?= $inv['unique_code'] ?>" 
                                           class="btn btn-outline-primary" target="_blank" title="View Invitation">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/edit-invitation.php?id=<?= $inv['id'] ?>" 
                                           class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-info" 
                                                onclick="viewRSVPs(<?= $inv['id'] ?>)" title="View RSVPs">
                                            <i class="fas fa-users"></i>
                                        </button>
                                        <button class="btn btn-outline-warning" 
                                                onclick="shareInvitation('<?= $inv['unique_code'] ?>')" title="Share">
                                            <i class="fas fa-share"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" 
                                                onclick="deleteInvitation(<?= $inv['id'] ?>)" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if (count($invitations) === $limit): ?>
                <div class="card-footer bg-white">
                    <nav>
                        <ul class="pagination pagination-sm justify-content-center mb-0">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="page-item active">
                                <span class="page-link">Page <?= $page ?></span>
                            </li>
                            
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RSVP Modal -->
    <div class="modal fade" id="rsvpModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">RSVPs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="rsvpContent">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Invitation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Invitation Link</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareUrl" readonly>
                            <button class="btn btn-outline-secondary" onclick="copyShareUrl()">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary" onclick="shareViaEmail()">
                            <i class="fas fa-envelope me-2"></i>Share via Email
                        </button>
                        <button class="btn btn-success" onclick="shareViaWhatsApp()">
                            <i class="fab fa-whatsapp me-2"></i>Share via WhatsApp
                        </button>
                        <button class="btn btn-info" onclick="shareViaFacebook()">
                            <i class="fab fa-facebook me-2"></i>Share on Facebook
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/app.js"></script>
    
    <script>
        let currentShareUrl = '';

        // View RSVPs
        async function viewRSVPs(invitationId) {
            try {
                const response = await fetch(`/api/invitations.php?action=rsvps&id=${invitationId}`);
                const data = await response.json();
                
                if (data.success) {
                    let html = '';
                    
                    if (data.rsvps.length === 0) {
                        html = '<div class="text-center py-4"><p class="text-muted">No RSVPs yet</p></div>';
                    } else {
                        html = '<div class="table-responsive"><table class="table table-sm">';
                        html += '<thead><tr><th>Name</th><th>Response</th><th>Guests</th><th>Date</th></tr></thead><tbody>';
                        
                        data.rsvps.forEach(rsvp => {
                            const responseClass = {
                                'yes': 'success',
                                'no': 'danger',
                                'maybe': 'warning'
                            }[rsvp.response] || 'secondary';
                            
                            html += `<tr>
                                <td>${rsvp.guest_name}${rsvp.guest_email ? '<br><small class="text-muted">' + rsvp.guest_email + '</small>' : ''}</td>
                                <td><span class="badge bg-${responseClass}">${rsvp.response.toUpperCase()}</span></td>
                                <td>${rsvp.guest_count}</td>
                                <td><small>${new Date(rsvp.responded_at).toLocaleDateString()}</small></td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table></div>';
                    }
                    
                    document.getElementById('rsvpContent').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('rsvpModal')).show();
                } else {
                    App.showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('RSVP fetch error:', error);
                App.showNotification('Failed to load RSVPs', 'error');
            }
        }

        // Share invitation
        function shareInvitation(uniqueCode) {
            currentShareUrl = `${window.location.origin}/invitation/${uniqueCode}`;
            document.getElementById('shareUrl').value = currentShareUrl;
            new bootstrap.Modal(document.getElementById('shareModal')).show();
        }

        function copyShareUrl() {
            const input = document.getElementById('shareUrl');
            input.select();
            navigator.clipboard.writeText(input.value);
            App.showNotification('Link copied to clipboard!', 'success');
        }

        function shareViaEmail() {
            const subject = encodeURIComponent('You\'re invited!');
            const body = encodeURIComponent(`Please join me for my event. View the invitation here: ${currentShareUrl}`);
            window.open(`mailto:?subject=${subject}&body=${body}`);
        }

        function shareViaWhatsApp() {
            const text = encodeURIComponent(`You're invited! View the invitation: ${currentShareUrl}`);
            window.open(`https://wa.me/?text=${text}`);
        }

        function shareViaFacebook() {
            const url = encodeURIComponent(currentShareUrl);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`);
        }

        // Filter and sort functions
        function filterInvitations() {
            // This would typically trigger an AJAX request to filter results
            // For now, we'll reload the page with filter parameters
            const status = document.getElementById('filterStatus').value;
            const url = new URL(window.location);
            
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }
            
            window.location.href = url.toString();
        }

        function sortInvitations() {
            const sortBy = document.getElementById('sortBy').value;
            const url = new URL(window.location);
            url.searchParams.set('sort', sortBy);
            window.location.href = url.toString();
        }
    </script>
</body>
</html>