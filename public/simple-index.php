<?php
/**
 * Simplified homepage for shared hosting
 */

require_once 'simple-config.php';

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Invitations - Create Beautiful Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s ease;
            border: none;
            border-radius: 15px;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand text-primary fw-bold" href="simple-index.php">
                <i class="fas fa-envelope-open-text me-2"></i>
                Digital Invitations
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if ($is_logged_in): ?>
                    <a class="nav-link" href="simple-dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                    <a class="nav-link" href="?logout=1">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="simple-login.php">Login</a>
                    <a class="btn btn-primary ms-2" href="simple-login.php">Get Started</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (!$is_logged_in): ?>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-4">Create Stunning Digital Invitations</h1>
            <p class="lead mb-5">Design beautiful, professional invitations for any occasion. Track RSVPs, send via email, and manage your events effortlessly.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="simple-login.php" class="btn btn-light btn-lg px-4">Get Started</a>
                <a href="#features" class="btn btn-outline-light btn-lg px-4">Learn More</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5" id="features">
        <div class="container">
            <div class="row text-center mb-5">
                <div class="col-lg-8 mx-auto">
                    <h2 class="display-5 fw-bold">Everything You Need</h2>
                    <p class="lead text-muted">Professional invitation tools for every occasion</p>
                </div>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card h-100 shadow-sm">
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
                    <div class="card feature-card h-100 shadow-sm">
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
                    <div class="card feature-card h-100 shadow-sm">
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
    <?php else: ?>
    <!-- Dashboard Preview for Logged Users -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <h2 class="mb-4">Welcome back!</h2>
                    <p class="lead text-muted mb-4">Ready to create your next invitation?</p>
                    
                    <div class="d-flex gap-3 mb-4">
                        <a href="create-simple-invitation.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Invitation
                        </a>
                        <a href="simple-dashboard.php" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Platform Status</h6>
                            <h4 class="text-success">✅ Running</h4>
                            <small class="text-muted">All systems operational</small>
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
                        <small class="text-muted">SaaS Platform v1.0</small>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>