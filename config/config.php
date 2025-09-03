<?php
/**
 * Application Configuration
 */

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Application constants
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_SECRET_KEY', $_ENV['APP_SECRET_KEY'] ?? 'default-secret-key');

// File upload settings
define('MAX_FILE_SIZE', $_ENV['MAX_FILE_SIZE'] ?? 5242880); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../' . ($_ENV['UPLOAD_PATH'] ?? 'uploads/'));

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
        'invitations_per_month' => -1, // unlimited
        'templates_access' => 'all',
        'custom_domain' => true,
        'analytics' => true
    ]
]);

// Error reporting based on environment
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set('UTC');

// CSRF token generation
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Generate CSRF token for forms
 */
function csrf_token() {
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate unique invitation code
 */
function generate_invitation_code($length = 10) {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
}

/**
 * Format date for display
 */
function format_date($date, $format = 'F j, Y g:i A') {
    return date($format, strtotime($date));
}