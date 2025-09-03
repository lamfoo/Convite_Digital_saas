<?php
/**
 * Email API endpoints
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\EmailService;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $emailService = new EmailService($db);

    // Check authentication
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit();
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? null;

    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }

            switch ($action) {
                case 'send':
                    // Validate CSRF token
                    if (!verify_csrf_token($input['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                        exit();
                    }

                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                        exit();
                    }

                    if (empty($input['recipient_emails']) || !is_array($input['recipient_emails'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Recipient emails are required']);
                        exit();
                    }

                    // Validate email addresses
                    foreach ($input['recipient_emails'] as $email) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => "Invalid email address: {$email}"]);
                            exit();
                        }
                    }

                    $custom_subject = sanitize_input($input['custom_subject'] ?? '');
                    
                    $result = $emailService->sendInvitation($id, $input['recipient_emails'], $custom_subject);
                    
                    if ($result['success']) {
                        http_response_code(200);
                    } else {
                        http_response_code(400);
                    }
                    echo json_encode($result);
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Action not found']);
                    break;
            }
            break;

        case 'GET':
            switch ($action) {
                case 'logs':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                        exit();
                    }

                    $current_user = $auth->getCurrentUser();
                    $result = $emailService->getEmailLogs($id, $current_user['id']);
                    
                    if ($result['success']) {
                        echo json_encode($result);
                    } else {
                        http_response_code(403);
                        echo json_encode($result);
                    }
                    break;

                default:
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Action not found']);
                    break;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    error_log("Email API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}