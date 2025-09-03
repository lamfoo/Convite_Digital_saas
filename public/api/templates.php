<?php
/**
 * Templates API endpoints
 */

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../autoload.php';

use App\Auth;
use App\Template;
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
    $template = new Template($db);
    $user = new User($db);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? null;

    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    $category = $_GET['category'] ?? null;
                    $subscription_tier = 'free';
                    
                    if ($auth->isAuthenticated()) {
                        $current_user = $auth->getCurrentUser();
                        $subscription_tier = $current_user['subscription_tier'];
                    }

                    if ($category) {
                        $templates = $template->getTemplatesByCategory($category, $subscription_tier);
                    } else {
                        $templates = $template->getAllTemplates($subscription_tier);
                    }
                    
                    echo json_encode(['success' => true, 'templates' => $templates]);
                    break;

                case 'categories':
                    $categories = $template->getCategories();
                    echo json_encode(['success' => true, 'categories' => $categories]);
                    break;

                case 'view':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Template ID required']);
                        exit();
                    }

                    $template_data = $template->getTemplateById($id);
                    if (!$template_data) {
                        http_response_code(404);
                        echo json_encode(['success' => false, 'message' => 'Template not found']);
                        exit();
                    }

                    echo json_encode(['success' => true, 'template' => $template_data]);
                    break;

                case 'render':
                    if (!$id) {
                        http_response_code(400);
                        echo json_encode(['success' => false, 'message' => 'Template ID required']);
                        exit();
                    }

                    $custom_data = $_GET['data'] ?? [];
                    if (is_string($custom_data)) {
                        $custom_data = json_decode($custom_data, true) ?? [];
                    }

                    $result = $template->renderTemplate($id, $custom_data);
                    
                    if ($result['success']) {
                        echo json_encode($result);
                    } else {
                        http_response_code(404);
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
            // Check authentication
            if (!$auth->isAuthenticated()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                exit();
            }

            $current_user = $auth->getCurrentUser();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new Exception('Invalid JSON input');
            }

            switch ($action) {
                case 'create':
                    // Check admin permission
                    if (!$auth->hasPermission('manage_templates')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Admin permission required']);
                        exit();
                    }

                    // Validate CSRF token
                    if (!verify_csrf_token($input['csrf_token'] ?? '')) {
                        http_response_code(403);
                        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                        exit();
                    }

                    // Validate required fields
                    $required_fields = ['name', 'category', 'html_content'];
                    foreach ($required_fields as $field) {
                        if (empty($input[$field])) {
                            http_response_code(400);
                            echo json_encode(['success' => false, 'message' => "Field {$field} is required"]);
                            exit();
                        }
                    }

                    $input['created_by'] = $current_user['id'];
                    $result = $template->createTemplate(sanitize_input($input));
                    
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
            // Check authentication
            if (!$auth->isAuthenticated()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                exit();
            }

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Template ID required']);
                exit();
            }

            // Check admin permission
            if (!$auth->hasPermission('manage_templates')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin permission required']);
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

            $result = $template->updateTemplate($id, sanitize_input($input));
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;

        case 'DELETE':
            // Check authentication
            if (!$auth->isAuthenticated()) {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                exit();
            }

            if (!$id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Template ID required']);
                exit();
            }

            // Check admin permission
            if (!$auth->hasPermission('manage_templates')) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Admin permission required']);
                exit();
            }

            $result = $template->deleteTemplate($id);
            
            if ($result['success']) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    error_log("Templates API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}