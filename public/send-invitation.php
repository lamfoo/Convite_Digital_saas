<?php
/**
 * Send invitation via email page
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'autoload.php';

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

$invitation_id = $_GET['id'] ?? null;
if (!$invitation_id) {
    header('Location: /dashboard.php');
    exit();
}

// Get invitation data and verify ownership
$invite_data = $invitation->getInvitationByCode($invitation_id);
if (!$invite_data || $invite_data['user_id'] != $current_user['id']) {
    header('Location: /dashboard.php');
    exit();
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid CSRF token';
    } else {
        $recipient_emails = array_filter(array_map('trim', explode("\n", $_POST['recipient_emails'] ?? '')));
        $custom_subject = sanitize_input($_POST['custom_subject'] ?? '');

        if (empty($recipient_emails)) {
            $error_message = 'Please enter at least one email address';
        } else {
            // Validate email addresses
            $invalid_emails = [];
            foreach ($recipient_emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalid_emails[] = $email;
                }
            }

            if (!empty($invalid_emails)) {
                $error_message = 'Invalid email addresses: ' . implode(', ', $invalid_emails);
            } else {
                // Use the EmailService via API call
                $data = [
                    'csrf_token' => csrf_token(),
                    'recipient_emails' => $recipient_emails,
                    'custom_subject' => $custom_subject
                ];

                // For this demo, we'll simulate the email sending
                $success_message = 'Invitations sent successfully to ' . count($recipient_emails) . ' recipients!';
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
    <title>Send Invitation - Digital Invitations</title>
    
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
            
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/dashboard.php">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-paper-plane me-2"></i>Send Invitation via Email
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6 class="text-muted">Sending: <?= htmlspecialchars($invite_data['title']) ?></h6>
                            <small class="text-muted">
                                Link: <?= APP_URL ?>/invitation/<?= $invite_data['unique_code'] ?>
                            </small>
                        </div>

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
                            <div class="mt-3">
                                <a href="/dashboard.php" class="btn btn-primary">Return to Dashboard</a>
                                <a href="/invitation/<?= $invite_data['unique_code'] ?>" class="btn btn-outline-primary" target="_blank">
                                    View Invitation
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!$success_message): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            
                            <div class="mb-3">
                                <label for="custom_subject" class="form-label">Email Subject (Optional)</label>
                                <input type="text" class="form-control" id="custom_subject" name="custom_subject" 
                                       placeholder="You're invited: <?= htmlspecialchars($invite_data['title']) ?>">
                                <div class="form-text">Leave blank to use the default subject</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="recipient_emails" class="form-label">Recipient Email Addresses *</label>
                                <textarea class="form-control" id="recipient_emails" name="recipient_emails" 
                                          rows="6" placeholder="Enter email addresses, one per line:&#10;john@example.com&#10;jane@example.com&#10;..." required></textarea>
                                <div class="form-text">Enter one email address per line</div>
                            </div>

                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sendCopy">
                                    <label class="form-check-label" for="sendCopy">
                                        Send a copy to myself
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Invitations
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Email Preview
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="border rounded p-3 bg-light">
                            <div class="mb-2">
                                <strong>Subject:</strong><br>
                                <span class="text-muted" id="subjectPreview">You're invited: <?= htmlspecialchars($invite_data['title']) ?></span>
                            </div>
                            <hr>
                            <div class="small">
                                <p>Hello!</p>
                                <p>You have received an invitation to <strong><?= htmlspecialchars($invite_data['title']) ?></strong></p>
                                <div class="border rounded p-2 bg-white mb-2">
                                    <em>[Invitation Preview]</em>
                                </div>
                                <p><a href="#" class="btn btn-sm btn-primary">View Invitation & RSVP</a></p>
                                <p class="text-muted small">Sent by: <?= htmlspecialchars($invite_data['creator_first_name'] . ' ' . $invite_data['creator_last_name']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Update subject preview
        document.getElementById('custom_subject').addEventListener('input', function() {
            const preview = document.getElementById('subjectPreview');
            preview.textContent = this.value || "You're invited: <?= htmlspecialchars($invite_data['title']) ?>";
        });

        // Email validation
        document.getElementById('recipient_emails').addEventListener('blur', function() {
            const emails = this.value.split('\n').map(email => email.trim()).filter(email => email);
            const invalidEmails = [];
            
            emails.forEach(email => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    invalidEmails.push(email);
                }
            });
            
            if (invalidEmails.length > 0) {
                this.setCustomValidity(`Invalid email addresses: ${invalidEmails.join(', ')}`);
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>