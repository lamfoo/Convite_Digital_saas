<?php
/**
 * Subscription management page
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
$user_stats = $user->getUserStats($current_user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .pricing-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }
        .pricing-card:hover {
            border-color: #007bff;
            box-shadow: 0 8px 25px rgba(0,123,255,0.15);
        }
        .pricing-card.current {
            border-color: #28a745;
            background: #f8fff8;
        }
        .pricing-card.popular {
            border-color: #007bff;
            background: #f8f9ff;
            position: relative;
        }
        .popular-badge {
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #007bff;
            color: white;
            padding: 5px 20px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: bold;
        }
        .usage-stats {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
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
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/dashboard.php">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Current Subscription -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3 mb-2">Subscription Management</h1>
                <p class="text-muted">Manage your subscription and billing</p>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Current Usage</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="usage-stats">
                                    <h4 class="text-primary"><?= $user_stats['total_invitations'] ?></h4>
                                    <small class="text-muted">Invitations Created</small>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="usage-stats">
                                    <h4 class="text-success"><?= number_format($user_stats['total_views']) ?></h4>
                                    <small class="text-muted">Total Views</small>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="usage-stats">
                                    <h4 class="text-info"><?= $user_stats['total_rsvps'] ?></h4>
                                    <small class="text-muted">RSVPs Received</small>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="usage-stats">
                                    <h4 class="text-warning"><?= $user_stats['active_invitations'] ?></h4>
                                    <small class="text-muted">Active Invitations</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">Current Plan</h6>
                    </div>
                    <div class="card-body">
                        <h4 class="text-capitalize"><?= $current_user['subscription_tier'] ?> Plan</h4>
                        <span class="badge bg-success mb-3">Active</span>
                        
                        <?php 
                        $limits = SUBSCRIPTION_LIMITS[$current_user['subscription_tier']];
                        ?>
                        
                        <div class="small">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Monthly Invitations:</span>
                                <span><?= $limits['invitations_per_month'] == -1 ? 'Unlimited' : $limits['invitations_per_month'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Templates Access:</span>
                                <span class="text-capitalize"><?= $limits['templates_access'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Analytics:</span>
                                <span><?= $limits['analytics'] ? 'Yes' : 'No' ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Custom Domain:</span>
                                <span><?= $limits['custom_domain'] ? 'Yes' : 'No' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing Plans -->
        <div class="row mb-4">
            <div class="col">
                <h3 class="text-center mb-4">Choose Your Plan</h3>
            </div>
        </div>

        <div class="row g-4">
            <!-- Free Plan -->
            <div class="col-lg-4">
                <div class="pricing-card <?= $current_user['subscription_tier'] === 'free' ? 'current' : '' ?>">
                    <?php if ($current_user['subscription_tier'] === 'free'): ?>
                    <div class="badge bg-success mb-3">Current Plan</div>
                    <?php endif; ?>
                    
                    <h5 class="card-title">Free</h5>
                    <div class="mb-4">
                        <span class="display-4 fw-bold">$0</span>
                        <span class="text-muted">/month</span>
                    </div>
                    
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>5 invitations per month</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Basic templates</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email sharing</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>RSVP tracking</li>
                        <li class="mb-2"><i class="fas fa-times text-muted me-2"></i>Analytics dashboard</li>
                        <li class="mb-2"><i class="fas fa-times text-muted me-2"></i>Premium templates</li>
                    </ul>
                    
                    <?php if ($current_user['subscription_tier'] !== 'free'): ?>
                    <button class="btn btn-outline-secondary w-100" onclick="changePlan('free')">
                        Downgrade to Free
                    </button>
                    <?php else: ?>
                    <button class="btn btn-secondary w-100" disabled>Current Plan</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Basic Plan -->
            <div class="col-lg-4">
                <div class="pricing-card popular <?= $current_user['subscription_tier'] === 'basic' ? 'current' : '' ?>">
                    <?php if ($current_user['subscription_tier'] !== 'basic'): ?>
                    <div class="popular-badge">Most Popular</div>
                    <?php else: ?>
                    <div class="badge bg-success mb-3">Current Plan</div>
                    <?php endif; ?>
                    
                    <h5 class="card-title">Basic</h5>
                    <div class="mb-4">
                        <span class="display-4 fw-bold">$9</span>
                        <span class="text-muted">/month</span>
                    </div>
                    
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>50 invitations per month</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All templates</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email sharing</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>RSVP tracking</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Analytics dashboard</li>
                        <li class="mb-2"><i class="fas fa-times text-muted me-2"></i>Custom domain</li>
                    </ul>
                    
                    <?php if ($current_user['subscription_tier'] === 'basic'): ?>
                    <button class="btn btn-success w-100" disabled>Current Plan</button>
                    <?php else: ?>
                    <button class="btn btn-primary w-100" onclick="changePlan('basic')">
                        <?= $current_user['subscription_tier'] === 'free' ? 'Upgrade' : 'Change' ?> to Basic
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Premium Plan -->
            <div class="col-lg-4">
                <div class="pricing-card <?= $current_user['subscription_tier'] === 'premium' ? 'current' : '' ?>">
                    <?php if ($current_user['subscription_tier'] === 'premium'): ?>
                    <div class="badge bg-success mb-3">Current Plan</div>
                    <?php endif; ?>
                    
                    <h5 class="card-title">Premium</h5>
                    <div class="mb-4">
                        <span class="display-4 fw-bold">$29</span>
                        <span class="text-muted">/month</span>
                    </div>
                    
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Unlimited invitations</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>All templates</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Email sharing</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>RSVP tracking</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Advanced analytics</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Custom domain</li>
                    </ul>
                    
                    <?php if ($current_user['subscription_tier'] === 'premium'): ?>
                    <button class="btn btn-success w-100" disabled>Current Plan</button>
                    <?php else: ?>
                    <button class="btn btn-warning w-100" onclick="changePlan('premium')">
                        <?= $current_user['subscription_tier'] === 'free' ? 'Upgrade' : 'Upgrade' ?> to Premium
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Billing History (placeholder) -->
        <div class="row mt-5">
            <div class="col">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Billing History</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center py-4">
                            <i class="fas fa-receipt text-muted fs-1"></i>
                            <h6 class="mt-3 text-muted">No billing history yet</h6>
                            <p class="text-muted">Your billing history will appear here once you upgrade to a paid plan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Modal (Stripe simulation) -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upgrade Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h4 id="planName"></h4>
                        <h2 id="planPrice"></h2>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Demo Mode:</strong> This is a simulation of Stripe payment integration. 
                        In production, this would connect to Stripe's secure payment processing.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Card Number</label>
                        <input type="text" class="form-control" placeholder="4242 4242 4242 4242" disabled>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Expiry Date</label>
                                <input type="text" class="form-control" placeholder="MM/YY" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">CVC</label>
                                <input type="text" class="form-control" placeholder="123" disabled>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="processPayment()">
                        <i class="fas fa-credit-card me-2"></i>Subscribe Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let selectedPlan = null;
        
        function changePlan(planType) {
            selectedPlan = planType;
            
            const planData = {
                'free': { name: 'Free Plan', price: '$0/month' },
                'basic': { name: 'Basic Plan', price: '$9/month' },
                'premium': { name: 'Premium Plan', price: '$29/month' }
            };
            
            document.getElementById('planName').textContent = planData[planType].name;
            document.getElementById('planPrice').textContent = planData[planType].price;
            
            if (planType === 'free') {
                if (confirm('Are you sure you want to downgrade to the free plan? You will lose access to premium features.')) {
                    // Simulate downgrade
                    alert('Downgrade successful! Please refresh the page.');
                    location.reload();
                }
            } else {
                new bootstrap.Modal(document.getElementById('paymentModal')).show();
            }
        }
        
        function processPayment() {
            // Simulate payment processing
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            modal.hide();
            
            // Show success message
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show';
            successAlert.innerHTML = `
                <i class="fas fa-check-circle me-2"></i>
                <strong>Success!</strong> Your subscription has been upgraded to ${selectedPlan}.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('.container').insertBefore(successAlert, document.querySelector('.container').firstElementChild.nextElementSibling);
            
            // In a real implementation, this would:
            // 1. Process payment with Stripe
            // 2. Update user's subscription in database
            // 3. Redirect to success page
        }
    </script>
</body>
</html>