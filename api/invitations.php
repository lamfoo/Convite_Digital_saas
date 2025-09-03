<?php
/**
 * Invitations API endpoints
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;
use App\Invitation;
use App\User;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);
    $invitation = new Invitation($db);
    $user = new User($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? null;

    // Check authentication for most endpoints
    if ($action !== 'view' && $action !== 'rsvp') {
        if (!$auth->isAuthenticated()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Authentication required']);
            exit();
        }
    }

    $current_user = $auth->getCurrentUser();

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $limit = (int)($_GET['limit'] ?? 20);
                    $offset = (int)($_GET['offset'] ?? 0);
                    
                    $invitations = $invitation->getUserInvitations($current_user['id'], $limit, $offset);
                    echo json_encode(['success' => true, 'invitations' => $invitations]);
                    break;

                case 'view':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                        exit();
                    }

                    $invite = $invitation->getInvitationByCode($id);
                    if (!$invite) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Invitation not found']);
                        exit();
                    }

                    // Track view
                    $invitation->trackView($id);
                    
                    echo json_encode(['success' => true, 'invitation' => $invite]);
                    break;

                case 'rsvps':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                        exit();
                    }

                    $result = $invitation->getInvitationRSVPs($id, $current_user['id']);
                    
                    if ($result['success']) {
                        echo json_encode($result);
                    } else {
                        http_response_code(403);
                        echo json_encode($result);
                    }
                    break;

                case 'analytics':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                        exit();
                    }

                    // Check if user has analytics access
                    if (!$user->checkSubscriptionLimits($current_user['id'], 'analytics')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Analytics not available in your subscription tier']);
                        exit();
                    }

                    $result = $invitation->getInvitationAnalytics($id, $current_user['id']);
                    
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

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input && $action !== 'rsvp') {
                throw new Exception('Invalid JSON input');
            }

            switch ($action) {
                case 'create':
                    // Validate CSRF token
                    if (!verify_csrf_token($input['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                        exit();
                    }

                    // Check subscription limits
                    if (!$user->checkSubscriptionLimits($current_user['id'], 'create_invitation')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Invitation limit reached for your subscription tier']);
                        exit();
                    }

                    // Validate required fields
                    if (empty($input['template_id']) || empty($input['title'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Template ID and title are required']);
                        exit();
                    }

                    $result = $invitation->createInvitation($current_user['id'], sanitize_input($input));
                    
                    if ($result['success']) {
                        http_response_code(201);
                    } else {
                        http_response_code(400);
                    }
                    echo json_encode($result);
                    break;

                case 'rsvp':
                    // Get invitation ID from URL or input
                    $invitation_id = $id ?? $input['invitation_id'] ?? null;
                    
                    if (!$invitation_id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                        exit();
                    }

                    // Validate required RSVP fields
                    $rsvp_data = $_POST; // Handle form data for RSVP
                    if (empty($rsvp_data)) {
                        $rsvp_data = $input;
                    }

                    if (empty($rsvp_data['guest_name']) || empty($rsvp_data['response'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Guest name and response are required']);
                        exit();
                    }

                    $result = $invitation->addRSVP($invitation_id, sanitize_input($rsvp_data));
                    
                    if ($result['success']) {
                        http_response_code(201);
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

        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                exit();
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }

            // Validate CSRF token
            if (!verify_csrf_token($input['csrf_token'] ?? '')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                exit();
            }

            $result = $invitation->updateInvitation($id, $current_user['id'], sanitize_input($input));
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;

        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invitation ID required']);
                exit();
            }

            $result = $invitation->deleteInvitation($id, $current_user['id']);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(403);
                echo json_encode($result);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    error_log("Invitations API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}