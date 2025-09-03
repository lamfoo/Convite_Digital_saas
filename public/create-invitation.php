<?php
/**
 * Create invitation page
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Template;
use App\Invitation;
use App\User;

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check authentication
if (!$auth->isAuthenticated()) {
    header('Location: /login.php');
    exit();
}

$template = new Template($db);
$invitation = new Invitation($db);
$user = new User($db);

$current_user = $auth->getCurrentUser();
$selected_template_id = $_GET['template'] ?? null;
$selected_template = null;

if ($selected_template_id) {
    $selected_template = $template->getTemplateById($selected_template_id);
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid CSRF token';
    } else {
        // Check subscription limits
        if (!$user->checkSubscriptionLimits($current_user['id'], 'create_invitation')) {
            $error_message = 'You have reached your monthly invitation limit. Please upgrade your subscription.';
        } else {
            $invitation_data = [
                'template_id' => (int)$_POST['template_id'],
                'title' => sanitize_input($_POST['title']),
                'event_date' => $_POST['event_date'] ? date('Y-m-d H:i:s', strtotime($_POST['event_date'])) : null,
                'event_location' => sanitize_input($_POST['event_location']),
                'custom_message' => sanitize_input($_POST['custom_message']),
                'custom_data' => [
                    'bride_name' => sanitize_input($_POST['bride_name'] ?? ''),
                    'groom_name' => sanitize_input($_POST['groom_name'] ?? ''),
                    'celebrant_name' => sanitize_input($_POST['celebrant_name'] ?? ''),
                    'company_name' => sanitize_input($_POST['company_name'] ?? ''),
                    'event_title' => sanitize_input($_POST['event_title'] ?? ''),
                    'baby_name' => sanitize_input($_POST['baby_name'] ?? ''),
                    'graduate_name' => sanitize_input($_POST['graduate_name'] ?? ''),
                ]
            ];

            $result = $invitation->createInvitation($current_user['id'], $invitation_data);
            
            if ($result['success']) {
                header('Location: /dashboard.php?created=' . $result['invitation_id']);
                exit();
            } else {
                $error_message = $result['message'];
            }
        }
    }
}

// Get available templates
$templates = $template->getAllTemplates($current_user['subscription_tier']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Invitation - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .template-selector {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }
        .template-option {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .template-option:hover {
            border-color: #007bff;
        }
        .template-option.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
        .preview-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            min-height: 400px;
        }
        .dynamic-fields {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
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
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Create New Invitation
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="/create-invitation.php" id="invitationForm">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="template_id" id="selectedTemplateId" value="<?= $selected_template_id ?>">
                            
                            <!-- Template Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Template</label>
                                <div class="template-selector">
                                    <?php foreach ($templates as $tmpl): ?>
                                    <div class="template-option <?= $tmpl['id'] == $selected_template_id ? 'selected' : '' ?>" 
                                         onclick="selectTemplate(<?= $tmpl['id'] ?>, '<?= htmlspecialchars($tmpl['category']) ?>')">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-palette text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?= htmlspecialchars($tmpl['name']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($tmpl['description']) ?></small>
                                                <div class="mt-1">
                                                    <span class="badge bg-secondary text-capitalize"><?= htmlspecialchars(str_replace('-', ' ', $tmpl['category'])) ?></span>
                                                    <?php if ($tmpl['is_premium']): ?>
                                                    <span class="badge bg-warning text-dark">Premium</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Basic Information -->
                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">Invitation Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       placeholder="e.g., Sarah & John's Wedding" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_date" class="form-label">Event Date & Time</label>
                                        <input type="datetime-local" class="form-control" id="event_date" name="event_date">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="event_location" class="form-label">Event Location</label>
                                        <input type="text" class="form-control" id="event_location" name="event_location" 
                                               placeholder="e.g., Central Park, New York">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="custom_message" class="form-label">Custom Message</label>
                                <textarea class="form-control" id="custom_message" name="custom_message" rows="3" 
                                          placeholder="Add a personal message to your guests..."></textarea>
                            </div>

                            <!-- Dynamic Fields Based on Template -->
                            <div id="dynamicFields"></div>

                            <div class="d-flex justify-content-between">
                                <a href="/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Create Invitation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Live Preview -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Live Preview
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="preview-container" id="livePreview">
                            <div class="text-center text-muted py-5">
                                <i class="fas fa-image fs-1"></i>
                                <p class="mt-2">Select a template to see preview</p>
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
        const templates = <?= json_encode($templates) ?>;
        let currentTemplate = null;

        // Template selection
        function selectTemplate(templateId, category) {
            // Update UI
            document.querySelectorAll('.template-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
            
            // Update hidden input
            document.getElementById('selectedTemplateId').value = templateId;
            
            // Find template data
            currentTemplate = templates.find(t => t.id == templateId);
            
            // Update dynamic fields
            updateDynamicFields(category);
            
            // Update preview
            updatePreview();
        }

        // Update dynamic fields based on template category
        function updateDynamicFields(category) {
            const dynamicFields = document.getElementById('dynamicFields');
            let fieldsHTML = '<div class="dynamic-fields"><h6 class="mb-3">Template-Specific Fields</h6>';
            
            switch (category) {
                case 'wedding':
                    fieldsHTML += `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bride_name" class="form-label">Bride's Name</label>
                                    <input type="text" class="form-control" id="bride_name" name="bride_name" oninput="updatePreview()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="groom_name" class="form-label">Groom's Name</label>
                                    <input type="text" class="form-control" id="groom_name" name="groom_name" oninput="updatePreview()">
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'birthday':
                    fieldsHTML += `
                        <div class="mb-3">
                            <label for="celebrant_name" class="form-label">Birthday Person's Name</label>
                            <input type="text" class="form-control" id="celebrant_name" name="celebrant_name" oninput="updatePreview()">
                        </div>
                    `;
                    break;
                case 'corporate':
                    fieldsHTML += `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" oninput="updatePreview()">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_title" class="form-label">Event Title</label>
                                    <input type="text" class="form-control" id="event_title" name="event_title" oninput="updatePreview()">
                                </div>
                            </div>
                        </div>
                    `;
                    break;
                case 'baby-shower':
                    fieldsHTML += `
                        <div class="mb-3">
                            <label for="baby_name" class="form-label">Baby's Name (if known)</label>
                            <input type="text" class="form-control" id="baby_name" name="baby_name" placeholder="Leave blank if not known yet" oninput="updatePreview()">
                        </div>
                    `;
                    break;
                case 'graduation':
                    fieldsHTML += `
                        <div class="mb-3">
                            <label for="graduate_name" class="form-label">Graduate's Name</label>
                            <input type="text" class="form-control" id="graduate_name" name="graduate_name" oninput="updatePreview()">
                        </div>
                    `;
                    break;
            }
            
            fieldsHTML += '</div>';
            dynamicFields.innerHTML = fieldsHTML;
        }

        // Update live preview
        function updatePreview() {
            if (!currentTemplate) return;
            
            const formData = new FormData(document.getElementById('invitationForm'));
            const customData = {};
            
            // Collect all form data
            for (let [key, value] of formData.entries()) {
                customData[key] = value;
            }
            
            let html = currentTemplate.html_content;
            
            // Replace placeholders
            Object.keys(customData).forEach(key => {
                const placeholder = '{{' + key + '}}';
                html = html.replaceAll(placeholder, customData[key] || '');
            });
            
            // Format event date if provided
            if (customData.event_date) {
                const date = new Date(customData.event_date);
                const formatted = date.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit'
                });
                html = html.replaceAll('{{event_date}}', formatted);
            }
            
            html = html.replaceAll('{{event_location}}', customData.event_location || '');
            html = html.replaceAll('{{custom_message}}', customData.custom_message || '');
            
            // Update preview
            document.getElementById('livePreview').innerHTML = `
                <style>${currentTemplate.css_content}</style>
                ${html}
            `;
        }

        // Initialize if template is pre-selected
        <?php if ($selected_template): ?>
        document.addEventListener('DOMContentLoaded', function() {
            selectTemplate(<?= $selected_template['id'] ?>, '<?= $selected_template['category'] ?>');
        });
        <?php endif; ?>

        // Add event listeners for real-time preview updates
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = ['title', 'event_date', 'event_location', 'custom_message'];
            inputs.forEach(inputId => {
                const element = document.getElementById(inputId);
                if (element) {
                    element.addEventListener('input', updatePreview);
                }
            });
        });
    </script>
</body>
</html>