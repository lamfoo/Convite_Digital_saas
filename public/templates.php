<?php
/**
 * Templates browsing page
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'autoload.php';

use App\Auth;
use App\Template;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$template = new Template($db);

$current_user = $auth->getCurrentUser();
$subscription_tier = $current_user['subscription_tier'] ?? 'free';

$selected_category = $_GET['category'] ?? '';
$categories = $template->getCategories();

if ($selected_category) {
    $templates = $template->getTemplatesByCategory($selected_category, $subscription_tier);
} else {
    $templates = $template->getAllTemplates($subscription_tier);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Templates - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .template-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .template-card:hover {
            transform: translateY(-5px);
            border-color: #007bff;
            box-shadow: 0 8px 25px rgba(0,123,255,0.15);
        }
        .template-preview {
            height: 200px;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        .template-content {
            transform: scale(0.6);
            transform-origin: top left;
            width: 166.67%;
            height: 166.67%;
        }
        .category-filter {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .premium-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #333;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary" href="/">
                <i class="fas fa-envelope-open-text me-2"></i>
                Digital Invitations
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/templates.php">Templates</a>
                    </li>
                    <?php if ($auth->isAuthenticated()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($auth->isAuthenticated()): ?>
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
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary ms-2" href="/register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-2">Invitation Templates</h1>
                <p class="text-muted">Choose from our collection of beautiful, professionally designed templates</p>
            </div>
            <?php if ($auth->isAuthenticated()): ?>
            <div class="col-auto">
                <a href="/create-invitation.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Invitation
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Category Filter -->
        <div class="category-filter mb-4">
            <h6 class="mb-3">Filter by Category</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="/templates.php" class="btn <?= empty($selected_category) ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm">
                    All Categories
                </a>
                <?php foreach ($categories as $category): ?>
                <a href="/templates.php?category=<?= urlencode($category) ?>" 
                   class="btn <?= $selected_category === $category ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm text-capitalize">
                    <?= htmlspecialchars(str_replace('-', ' ', $category)) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Templates Grid -->
        <div class="row">
            <?php if (empty($templates)): ?>
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-search text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 text-muted">No templates found</h5>
                    <p class="text-muted">Try selecting a different category or upgrade your subscription for more templates.</p>
                </div>
            </div>
            <?php else: ?>
            <?php foreach ($templates as $tmpl): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card template-card h-100" onclick="<?= $auth->isAuthenticated() ? "selectTemplate({$tmpl['id']})" : "showLoginPrompt()" ?>">
                    <?php if ($tmpl['is_premium']): ?>
                    <div class="premium-badge">
                        <i class="fas fa-crown me-1"></i>Premium
                    </div>
                    <?php endif; ?>
                    
                    <div class="template-preview">
                        <div class="template-content">
                            <style><?= $tmpl['css_content'] ?></style>
                            <?= $tmpl['html_content'] ?>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($tmpl['name']) ?></h6>
                        <p class="card-text text-muted small"><?= htmlspecialchars($tmpl['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars(str_replace('-', ' ', $tmpl['category'])) ?></span>
                            <?php if ($auth->isAuthenticated()): ?>
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); selectTemplate(<?= $tmpl['id'] ?>)">
                                Use Template
                            </button>
                            <?php else: ?>
                            <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); showLoginPrompt()">
                                Login to Use
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Upgrade Prompt for Free Users -->
        <?php if ($subscription_tier === 'free'): ?>
        <div class="alert alert-info mt-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-3 fs-4"></i>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">Want access to premium templates?</h6>
                    <p class="mb-2">Upgrade to Basic or Premium to unlock all templates and additional features.</p>
                </div>
                <a href="/subscription.php" class="btn btn-primary">Upgrade Now</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Template Preview Modal -->
    <div class="modal fade" id="templateModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Template Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="templatePreview"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="useTemplateBtn">Use This Template</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedTemplateId = null;

        function selectTemplate(templateId) {
            <?php if ($auth->isAuthenticated()): ?>
            window.location.href = `/create-invitation.php?template=${templateId}`;
            <?php else: ?>
            showLoginPrompt();
            <?php endif; ?>
        }

        function showLoginPrompt() {
            if (confirm('You need to be logged in to use templates. Would you like to sign in now?')) {
                window.location.href = '/login.php';
            }
        }

        function previewTemplate(templateId) {
            selectedTemplateId = templateId;
            
            fetch(`/api/templates.php?action=render&id=${templateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('templatePreview').innerHTML = `
                        <style>${data.css}</style>
                        ${data.html}
                    `;
                    
                    document.getElementById('useTemplateBtn').onclick = function() {
                        selectTemplate(templateId);
                    };
                    
                    new bootstrap.Modal(document.getElementById('templateModal')).show();
                }
            })
            .catch(error => {
                console.error('Preview error:', error);
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
                    csrf_token: '<?= csrf_token() ?>'
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