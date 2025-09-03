<?php

namespace App;

use PDO;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Authentication class for user management
 */
class Auth {
    private $db;
    private $secret_key;

    public function __construct($database) {
        $this->db = $database;
        $this->secret_key = APP_SECRET_KEY;
    }

    /**
     * Register a new user
     */
    public function register($email, $password, $first_name, $last_name) {
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Email already registered'];
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate verification token
            $verification_token = bin2hex(random_bytes(32));

            // Insert new user
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, first_name, last_name, verification_token) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$email, $hashed_password, $first_name, $last_name, $verification_token]);
            
            $user_id = $this->db->lastInsertId();

            // Create free subscription
            $this->createSubscription($user_id, 'free');

            return [
                'success' => true, 
                'message' => 'Registration successful', 
                'user_id' => $user_id,
                'verification_token' => $verification_token
            ];
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed'];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, password, first_name, last_name, role, subscription_tier, email_verified 
                FROM users 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            $user = $stmt->fetch();

            if (!password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Invalid credentials'];
            }

            // Generate JWT token
            $payload = [
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
                'iat' => time(),
                'exp' => time() + (24 * 60 * 60) // 24 hours
            ];

            $jwt = JWT::encode($payload, $this->secret_key, 'HS256');

            // Start session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['subscription_tier'] = $user['subscription_tier'];

            unset($user['password']); // Remove password from response

            return [
                'success' => true, 
                'message' => 'Login successful',
                'user' => $user,
                'token' => $jwt
            ];
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed'];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    /**
     * Verify JWT token
     */
    public function verifyToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, 'HS256'));
            return ['success' => true, 'data' => $decoded];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Invalid token'];
        }
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT id, email, first_name, last_name, role, subscription_tier 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch();
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset($email) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Email not found'];
            }

            $reset_token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $stmt = $this->db->prepare("
                UPDATE users 
                SET reset_token = ?, reset_token_expires = ? 
                WHERE email = ?
            ");
            $stmt->execute([$reset_token, $expires, $email]);

            return [
                'success' => true, 
                'message' => 'Reset token generated',
                'reset_token' => $reset_token
            ];
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }

    /**
     * Reset password
     */
    public function resetPassword($token, $new_password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM users 
                WHERE reset_token = ? AND reset_token_expires > NOW()
            ");
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Invalid or expired token'];
            }

            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                UPDATE users 
                SET password = ?, reset_token = NULL, reset_token_expires = NULL 
                WHERE reset_token = ?
            ");
            $stmt->execute([$hashed_password, $token]);

            return ['success' => true, 'message' => 'Password reset successful'];
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password reset failed'];
        }
    }

    /**
     * Create subscription for user
     */
    private function createSubscription($user_id, $tier = 'free') {
        $stmt = $this->db->prepare("
            INSERT INTO subscriptions (user_id, tier, status, current_period_start, current_period_end) 
            VALUES (?, ?, 'active', NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
        ");
        return $stmt->execute([$user_id, $tier]);
    }

    /**
     * Check if user has permission for action
     */
    public function hasPermission($action) {
        $user = $this->getCurrentUser();
        if (!$user) return false;

        switch ($action) {
            case 'manage_templates':
                return $user['role'] === 'admin';
            case 'manage_users':
                return $user['role'] === 'admin';
            case 'create_invitation':
                return true; // All authenticated users can create invitations
            default:
                return false;
        }
    }
}