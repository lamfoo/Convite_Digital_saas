<?php

namespace App;

/**
 * User model class
 */
class User {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->db->prepare("
            SELECT u.*, s.tier as subscription_tier, s.status as subscription_status
            FROM users u
            LEFT JOIN subscriptions s ON u.id = s.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all users (admin only)
     */
    public function getAllUsers($limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT u.id, u.email, u.first_name, u.last_name, u.role, u.created_at,
                   s.tier as subscription_tier, s.status as subscription_status
            FROM users u
            LEFT JOIN subscriptions s ON u.id = s.user_id
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Update user profile
     */
    public function updateProfile($user_id, $data) {
        try {
            $allowed_fields = ['first_name', 'last_name', 'email'];
            $set_clauses = [];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    $set_clauses[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($set_clauses)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $values[] = $user_id;
            $sql = "UPDATE users SET " . implode(', ', $set_clauses) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            return ['success' => true, 'message' => 'Profile updated successfully'];
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Profile update failed'];
        }
    }

    /**
     * Get user statistics
     */
    public function getUserStats($user_id) {
        $stats = [];

        // Total invitations
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM invitations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_invitations'] = $stmt->fetch()['total'];

        // Active invitations
        $stmt = $this->db->prepare("SELECT COUNT(*) as active FROM invitations WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        $stats['active_invitations'] = $stmt->fetch()['active'];

        // Total views
        $stmt = $this->db->prepare("SELECT SUM(views_count) as total_views FROM invitations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $stats['total_views'] = $stmt->fetch()['total_views'] ?? 0;

        // Total RSVPs
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total_rsvps 
            FROM rsvps r 
            JOIN invitations i ON r.invitation_id = i.id 
            WHERE i.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $stats['total_rsvps'] = $stmt->fetch()['total_rsvps'];

        return $stats;
    }

    /**
     * Check subscription limits
     */
    public function checkSubscriptionLimits($user_id, $action) {
        $user = $this->getUserById($user_id);
        if (!$user) return false;

        $limits = SUBSCRIPTION_LIMITS[$user['subscription_tier']];

        switch ($action) {
            case 'create_invitation':
                if ($limits['invitations_per_month'] == -1) return true;
                
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count 
                    FROM invitations 
                    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
                ");
                $stmt->execute([$user_id]);
                $current_count = $stmt->fetch()['count'];
                
                return $current_count < $limits['invitations_per_month'];
            
            case 'access_premium_templates':
                return $limits['templates_access'] === 'all';
            
            case 'analytics':
                return $limits['analytics'];
            
            case 'custom_domain':
                return $limits['custom_domain'];
            
            default:
                return false;
        }
    }
}