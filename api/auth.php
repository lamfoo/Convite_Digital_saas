<?php
/**
 * Authentication API endpoints
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../vendor/autoload.php';

use App\Auth;

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

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }

            switch ($action) {
                case 'register':
                    // Validate CSRF token
                    if (!verify_csrf_token($input['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                        exit();
                    }

                    // Validate required fields
                    $required_fields = ['email', 'password', 'first_name', 'last_name'];
                    foreach ($required_fields as $field) {
                        if (empty($input[$field])) {
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => "Field {$field} is required"]);
                            exit();
                        }
                    }

                    // Validate email format
                    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                        exit();
                    }

                    // Validate password strength
                    if (strlen($input['password']) < 6) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
                        exit();
                    }

                    $result = $auth->register(
                        sanitize_input($input['email']),
                        $input['password'],
                        sanitize_input($input['first_name']),
                        sanitize_input($input['last_name'])
                    );

                    if ($result['success']) {
                        http_response_code(201);
                    } else {
                        http_response_code(400);
                    }
                    echo json_encode($result);
                    break;

                case 'login':
                    // Validate CSRF token
                    if (!verify_csrf_token($input['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                        exit();
                    }

                    if (empty($input['email']) || empty($input['password'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Email and password are required']);
                        exit();
                    }

                    $result = $auth->login(
                        sanitize_input($input['email']),
                        $input['password']
                    );

                    if ($result['success']) {
                        http_response_code(200);
                    } else {
                        http_response_code(401);
                    }
                    echo json_encode($result);
                    break;

                case 'logout':
                    $result = $auth->logout();
                    echo json_encode($result);
                    break;

                case 'reset-password-request':
                    if (empty($input['email'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Email is required']);
                        exit();
                    }

                    $result = $auth->requestPasswordReset(sanitize_input($input['email']));
                    echo json_encode($result);
                    break;

                case 'reset-password':
                    if (empty($input['token']) || empty($input['password'])) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Token and password are required']);
                        exit();
                    }

                    $result = $auth->resetPassword($input['token'], $input['password']);
                    
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
                case 'me':
                    if (!$auth->isAuthenticated()) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
                        exit();
                    }

                    $user = $auth->getCurrentUser();
                    echo json_encode(['success' => true, 'user' => $user]);
                    break;

                case 'csrf-token':
                    echo json_encode(['success' => true, 'token' => csrf_token()]);
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
    error_log("Auth API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}