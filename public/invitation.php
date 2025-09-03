<?php
/**
 * Public invitation view and RSVP page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Invitation;
use App\Template;

// Get invitation code from URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));
$invitation_code = end($path_parts);

if (empty($invitation_code)) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

$database = new Database();
$db = $database->getConnection();
$invitation = new Invitation($db);
$template = new Template($db);

// Get invitation data
$invite_data = $invitation->getInvitationByCode($invitation_code);

if (!$invite_data) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit();
}

// Track view
$invitation->trackView($invitation_code);

$error_message = '';
$success_message = '';

// Handle RSVP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rsvp_data = [
        'guest_name' => sanitize_input($_POST['guest_name'] ?? ''),
        'guest_email' => sanitize_input($_POST['guest_email'] ?? ''),
        'guest_phone' => sanitize_input($_POST['guest_phone'] ?? ''),
        'response' => sanitize_input($_POST['response'] ?? ''),
        'guest_count' => (int)($_POST['guest_count'] ?? 1),
        'message' => sanitize_input($_POST['message'] ?? '')
    ];

    if (empty($rsvp_data['guest_name']) || empty($rsvp_data['response'])) {
        $error_message = 'Name and RSVP response are required';
    } else {
        $result = $invitation->addRSVP($invite_data['id'], $rsvp_data);
        if ($result['success']) {
            $success_message = 'Thank you for your RSVP!';
        } else {
            $error_message = $result['message'];
        }
    }
}

// Process invitation content
$custom_data = json_decode($invite_data['custom_data'], true) ?? [];
$processed_html = $invite_data['html_content'];

// Replace placeholders
foreach ($custom_data as $key => $value) {
    $processed_html = str_replace('{{' . $key . '}}', htmlspecialchars($value), $processed_html);
}

$processed_html = str_replace('{{event_date}}', format_date($invite_data['event_date']), $processed_html);
$processed_html = str_replace('{{event_location}}', htmlspecialchars($invite_data['event_location']), $processed_html);
$processed_html = str_replace('{{custom_message}}', htmlspecialchars($invite_data['custom_message']), $processed_html);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($invite_data['title']) ?> - Digital Invitation</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Template CSS -->
    <style>
        <?= $invite_data['css_content'] ?>
        
        .invitation-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .rsvp-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-top: 30px;
        }
        .invitation-display {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .social-share {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>

    <!-- Open Graph meta tags -->
    <meta property="og:title" content="<?= htmlspecialchars($invite_data['title']) ?>">
    <meta property="og:description" content="You're invited to <?= htmlspecialchars($invite_data['title']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= APP_URL ?>/invitation/<?= $invitation_code ?>">
</head>
<body class="bg-light">
    <div class="container">
        <div class="invitation-container">
            <!-- Invitation Display -->
            <div class="invitation-display">
                <?= $processed_html ?>
            </div>

            <!-- Social Share -->
            <div class="social-share text-center">
                <h6 class="mb-3">Share this invitation</h6>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button class="btn btn-primary btn-sm" onclick="shareViaEmail()">
                        <i class="fas fa-envelope me-1"></i>Email
                    </button>
                    <button class="btn btn-success btn-sm" onclick="shareViaWhatsApp()">
                        <i class="fab fa-whatsapp me-1"></i>WhatsApp
                    </button>
                    <button class="btn btn-info btn-sm" onclick="shareViaFacebook()">
                        <i class="fab fa-facebook me-1"></i>Facebook
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="copyLink()">
                        <i class="fas fa-copy me-1"></i>Copy Link
                    </button>
                </div>
            </div>

            <!-- RSVP Section -->
            <div class="rsvp-section">
                <h4 class="text-center mb-4">
                    <i class="fas fa-reply text-primary me-2"></i>RSVP
                </h4>

                <?php if ($success_message): ?>
                <div class="alert alert-success text-center" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
                <?php elseif ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
                <?php endif; ?>

                <?php if (!$success_message): ?>
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guest_name" class="form-label">Your Name *</label>
                                <input type="text" class="form-control" id="guest_name" name="guest_name" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guest_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="guest_email" name="guest_email">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guest_phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="guest_phone" name="guest_phone">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="guest_count" class="form-label">Number of Guests</label>
                                <select class="form-select" id="guest_count" name="guest_count">
                                    <option value="1">1 Guest</option>
                                    <option value="2">2 Guests</option>
                                    <option value="3">3 Guests</option>
                                    <option value="4">4 Guests</option>
                                    <option value="5">5+ Guests</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Will you attend? *</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="response" id="response_yes" value="yes" required>
                            <label class="btn btn-outline-success" for="response_yes">
                                <i class="fas fa-check me-1"></i>Yes, I'll be there
                            </label>

                            <input type="radio" class="btn-check" name="response" id="response_no" value="no" required>
                            <label class="btn btn-outline-danger" for="response_no">
                                <i class="fas fa-times me-1"></i>Sorry, can't make it
                            </label>

                            <input type="radio" class="btn-check" name="response" id="response_maybe" value="maybe" required>
                            <label class="btn btn-outline-warning" for="response_maybe">
                                <i class="fas fa-question me-1"></i>Maybe
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="message" class="form-label">Message (Optional)</label>
                        <textarea class="form-control" id="message" name="message" rows="3" 
                                  placeholder="Leave a message for the host..."></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <i class="fas fa-paper-plane me-2"></i>Submit RSVP
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="text-center mt-4">
                <p class="text-muted small">
                    This invitation was created with 
                    <a href="/" class="text-decoration-none">Digital Invitations</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        const invitationUrl = window.location.href;
        const invitationTitle = '<?= htmlspecialchars($invite_data['title']) ?>';

        function shareViaEmail() {
            const subject = encodeURIComponent(`You're invited: ${invitationTitle}`);
            const body = encodeURIComponent(`Hi! You're invited to ${invitationTitle}. View the invitation and RSVP here: ${invitationUrl}`);
            window.open(`mailto:?subject=${subject}&body=${body}`);
        }

        function shareViaWhatsApp() {
            const text = encodeURIComponent(`You're invited to ${invitationTitle}! View and RSVP: ${invitationUrl}`);
            window.open(`https://wa.me/?text=${text}`);
        }

        function shareViaFacebook() {
            const url = encodeURIComponent(invitationUrl);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`);
        }

        function copyLink() {
            navigator.clipboard.writeText(invitationUrl).then(function() {
                // Show temporary success message
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
                button.classList.remove('btn-secondary');
                button.classList.add('btn-success');
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-success');
                    button.classList.add('btn-secondary');
                }, 2000);
            });
        }
    </script>
</body>
</html>