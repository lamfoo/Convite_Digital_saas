<?php
/**
 * Main application entry point
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$current_user = $auth->getCurrentUser();
$is_authenticated = $auth->isAuthenticated();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Invitations - Create Beautiful Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .template-preview {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .template-preview:hover {
            border-color: #007bff;
            box-shadow: 0 4px 15px rgba(0,123,255,0.2);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
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
                        <a class="nav-link" href="/templates.php">Templates</a>
                    </li>
                    <?php if ($is_authenticated): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">Dashboard</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($is_authenticated): ?>
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

    <?php if (!$is_authenticated): ?>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Create Stunning Digital Invitations</h1>
            <p class="lead mb-5">Design beautiful, professional invitations for any occasion. Track RSVPs, send via email, and manage your events effortlessly.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/register.php" class="btn btn-light btn-lg px-4">Get Started Free</a>
                <a href="/templates.php" class="btn btn-outline-light btn-lg px-4">View Templates</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold">Everything You Need</h2>
                    <p class="lead text-muted">Professional invitation tools for every occasion</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-palette text-primary fs-4"></i>
                            </div>
                            <h5 class="card-title">Beautiful Templates</h5>
                            <p class="card-text">Choose from professionally designed templates for weddings, birthdays, corporate events, and more.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-paper-plane text-success fs-4"></i>
                            </div>
                            <h5 class="card-title">Easy Sharing</h5>
                            <p class="card-text">Send invitations via email or share unique links. Generate QR codes for quick access.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card feature-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-chart-line text-info fs-4"></i>
                            </div>
                            <h5 class="card-title">Track RSVPs</h5>
                            <p class="card-text">Monitor invitation views, track RSVP responses, and get detailed analytics for your events.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold">Simple Pricing</h2>
                    <p class="lead text-muted">Choose the plan that's right for you</p>
                </div>
            </div>
            
            <div class="row g-4 justify-content-center">
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title text-center">Free</h5>
                            <div class="text-center mb-4">
                                <span class="display-4 fw-bold">$0</span>
                                <span class="text-muted">/month</span>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>5 invitations per month</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Basic templates</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email sharing</li>
                                <li class="mb-2"><i class="fas fa-times text-muted me-2"></i>Analytics</li>
                            </ul>
                            <a href="/register.php" class="btn btn-outline-primary w-100">Get Started</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card border-primary shadow">
                        <div class="card-header bg-primary text-white text-center">
                            <span class="badge bg-warning text-dark">Popular</span>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="card-title text-center">Basic</h5>
                            <div class="text-center mb-4">
                                <span class="display-4 fw-bold">$9</span>
                                <span class="text-muted">/month</span>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>50 invitations per month</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All templates</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email sharing</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Analytics</li>
                            </ul>
                            <a href="/register.php" class="btn btn-primary w-100">Choose Basic</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title text-center">Premium</h5>
                            <div class="text-center mb-4">
                                <span class="display-4 fw-bold">$29</span>
                                <span class="text-muted">/month</span>
                            </div>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Unlimited invitations</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All templates</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Custom domain</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Advanced analytics</li>
                            </ul>
                            <a href="/register.php" class="btn btn-outline-primary w-100">Choose Premium</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php else: ?>
    <!-- Dashboard Preview for Authenticated Users -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="mb-4">Welcome back, <?= htmlspecialchars($current_user['first_name']) ?>!</h2>
                    <p class="lead text-muted mb-4">Ready to create your next invitation?</p>
                    
                    <div class="d-flex gap-3 mb-4">
                        <a href="/create-invitation.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Invitation
                        </a>
                        <a href="/dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Your Subscription</h6>
                            <h4 class="text-capitalize"><?= $current_user['subscription_tier'] ?> Plan</h4>
                            <a href="/subscription.php" class="btn btn-sm btn-outline-primary">Manage</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h6>Digital Invitations</h6>
                    <p class="text-muted">Create beautiful digital invitations for any occasion.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-flex justify-content-md-end gap-3">
                        <a href="#" class="text-muted">Privacy Policy</a>
                        <a href="#" class="text-muted">Terms of Service</a>
                        <a href="#" class="text-muted">Contact</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // CSRF token for AJAX requests
        window.csrfToken = '<?= csrf_token() ?>';
        
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