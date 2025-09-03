<?php
/**
 * Simplified configuration for shared hosting
 */

// Load environment variables if .env exists
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Default configuration
$config = [
    'DB_HOST' => $_ENV['DB_HOST'] ?? 'localhost',
    'DB_NAME' => $_ENV['DB_NAME'] ?? 'sql_fileserver_c',
    'DB_USER' => $_ENV['DB_USER'] ?? 'sql_fileserver_c',
    'DB_PASS' => $_ENV['DB_PASS'] ?? '7f80c627e749b8',
    'DB_PORT' => $_ENV['DB_PORT'] ?? '3306',
    'APP_URL' => $_ENV['APP_URL'] ?? 'http://' . $_SERVER['HTTP_HOST'],
    'APP_SECRET_KEY' => $_ENV['APP_SECRET_KEY'] ?? 'default-secret-key-change-this',
];

// Set as constants
foreach ($config as $key => $value) {
    if (!defined($key)) {
        define($key, $value);
    }
}

// Application constants
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? 5242880);
define('UPLOAD_PATH', __DIR__ . '/uploads/');

// Subscription limits
define('SUBSCRIPTION_LIMITS', [
    'free' => [
        'invitations_per_month' => 5,
        'templates_access' => 'basic',
        'custom_domain' => false,
        'analytics' => false
    ],
    'basic' => [
        'invitations_per_month' => 50,
        'templates_access' => 'all',
        'custom_domain' => false,
        'analytics' => true
    ],
    'premium' => [
        'invitations_per_month' => -1,
        'templates_access' => 'all',
        'custom_domain' => true,
        'analytics' => true
    ]
]);

// Error reporting
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Helper functions
function csrf_token() {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generate_invitation_code($length = 10) {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

function format_date($date, $format = 'F j, Y g:i A') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

// Simple database class
class SimpleDatabase {
    private $conn;
    
    public function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
}

// Alias for compatibility
class_alias('SimpleDatabase', 'Database');