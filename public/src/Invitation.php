<?php

namespace App;

/**
 * Invitation management class
 */
class Invitation {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Create new invitation
     */
    public function createInvitation($user_id, $data) {
        try {
            // Generate unique code
            do {
                $unique_code = generate_invitation_code();
                $stmt = $this->db->prepare("SELECT id FROM invitations WHERE unique_code = ?");
                $stmt->execute([$unique_code]);
            } while ($stmt->rowCount() > 0);

            $stmt = $this->db->prepare("
                INSERT INTO invitations (user_id, template_id, title, event_date, event_location, custom_message, custom_data, unique_code) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id,
                $data['template_id'],
                $data['title'],
                $data['event_date'] ?? null,
                $data['event_location'] ?? '',
                $data['custom_message'] ?? '',
                json_encode($data['custom_data'] ?? []),
                $unique_code
            ]);

            $invitation_id = $this->db->lastInsertId();

            // Generate QR code (placeholder path)
            $qr_code_path = $this->generateQRCode($unique_code);
            
            $stmt = $this->db->prepare("UPDATE invitations SET qr_code_path = ? WHERE id = ?");
            $stmt->execute([$qr_code_path, $invitation_id]);

            return [
                'success' => true, 
                'message' => 'Invitation created successfully',
                'invitation_id' => $invitation_id,
                'unique_code' => $unique_code
            ];
        } catch (Exception $e) {
            error_log("Invitation creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invitation creation failed'];
        }
    }

    /**
     * Get user invitations
     */
    public function getUserInvitations($user_id, $limit = 20, $offset = 0) {
        $stmt = $this->db->prepare("
            SELECT i.*, t.name as template_name, t.category as template_category,
                   COUNT(r.id) as rsvp_count
            FROM invitations i
            LEFT JOIN templates t ON i.template_id = t.id
            LEFT JOIN rsvps r ON i.id = r.invitation_id
            WHERE i.user_id = ?
            GROUP BY i.id
            ORDER BY i.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Get invitation by unique code
     */
    public function getInvitationByCode($unique_code) {
        $stmt = $this->db->prepare("
            SELECT i.*, t.name as template_name, t.html_content, t.css_content,
                   u.first_name as creator_first_name, u.last_name as creator_last_name
            FROM invitations i
            LEFT JOIN templates t ON i.template_id = t.id
            LEFT JOIN users u ON i.user_id = u.id
            WHERE i.unique_code = ? AND i.is_active = 1
        ");
        $stmt->execute([$unique_code]);
        return $stmt->fetch();
    }

    /**
     * Update invitation
     */
    public function updateInvitation($invitation_id, $user_id, $data) {
        try {
            // Verify ownership
            $stmt = $this->db->prepare("SELECT id FROM invitations WHERE id = ? AND user_id = ?");
            $stmt->execute([$invitation_id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Invitation not found or access denied'];
            }

            $allowed_fields = ['title', 'event_date', 'event_location', 'custom_message', 'custom_data', 'is_active'];
            $set_clauses = [];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowed_fields)) {
                    if ($field === 'custom_data') {
                        $value = json_encode($value);
                    }
                    $set_clauses[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($set_clauses)) {
                return ['success' => false, 'message' => 'No valid fields to update'];
            }

            $values[] = $invitation_id;
            $sql = "UPDATE invitations SET " . implode(', ', $set_clauses) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            return ['success' => true, 'message' => 'Invitation updated successfully'];
        } catch (Exception $e) {
            error_log("Invitation update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invitation update failed'];
        }
    }

    /**
     * Delete invitation
     */
    public function deleteInvitation($invitation_id, $user_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM invitations WHERE id = ? AND user_id = ?");
            $stmt->execute([$invitation_id, $user_id]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Invitation not found or access denied'];
            }

            return ['success' => true, 'message' => 'Invitation deleted successfully'];
        } catch (Exception $e) {
            error_log("Invitation deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Invitation deletion failed'];
        }
    }

    /**
     * Track invitation view
     */
    public function trackView($unique_code) {
        try {
            $stmt = $this->db->prepare("UPDATE invitations SET views_count = views_count + 1 WHERE unique_code = ?");
            $stmt->execute([$unique_code]);
            return true;
        } catch (Exception $e) {
            error_log("View tracking error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add RSVP response
     */
    public function addRSVP($invitation_id, $rsvp_data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO rsvps (invitation_id, guest_name, guest_email, guest_phone, response, guest_count, message) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $invitation_id,
                $rsvp_data['guest_name'],
                $rsvp_data['guest_email'] ?? '',
                $rsvp_data['guest_phone'] ?? '',
                $rsvp_data['response'],
                $rsvp_data['guest_count'] ?? 1,
                $rsvp_data['message'] ?? ''
            ]);

            return ['success' => true, 'message' => 'RSVP submitted successfully'];
        } catch (Exception $e) {
            error_log("RSVP error: " . $e->getMessage());
            return ['success' => false, 'message' => 'RSVP submission failed'];
        }
    }

    /**
     * Get RSVPs for invitation
     */
    public function getInvitationRSVPs($invitation_id, $user_id) {
        // Verify ownership
        $stmt = $this->db->prepare("SELECT id FROM invitations WHERE id = ? AND user_id = ?");
        $stmt->execute([$invitation_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $stmt = $this->db->prepare("
            SELECT * FROM rsvps 
            WHERE invitation_id = ? 
            ORDER BY responded_at DESC
        ");
        $stmt->execute([$invitation_id]);
        
        return ['success' => true, 'rsvps' => $stmt->fetchAll()];
    }

    /**
     * Generate QR code (placeholder implementation)
     */
    private function generateQRCode($unique_code) {
        // In a real implementation, you would use a QR code library
        // For now, return a placeholder path
        return "/assets/qr-codes/{$unique_code}.png";
    }

    /**
     * Get invitation analytics
     */
    public function getInvitationAnalytics($invitation_id, $user_id) {
        // Verify ownership
        $stmt = $this->db->prepare("SELECT id FROM invitations WHERE id = ? AND user_id = ?");
        $stmt->execute([$invitation_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $analytics = [];

        // Get basic stats
        $stmt = $this->db->prepare("SELECT views_count, created_at FROM invitations WHERE id = ?");
        $stmt->execute([$invitation_id]);
        $invitation = $stmt->fetch();
        $analytics['views'] = $invitation['views_count'];
        $analytics['created_at'] = $invitation['created_at'];

        // Get RSVP breakdown
        $stmt = $this->db->prepare("
            SELECT response, COUNT(*) as count, SUM(guest_count) as guest_count
            FROM rsvps 
            WHERE invitation_id = ? 
            GROUP BY response
        ");
        $stmt->execute([$invitation_id]);
        $rsvp_breakdown = $stmt->fetchAll();
        
        $analytics['rsvp_breakdown'] = [];
        $analytics['total_guests'] = 0;
        
        foreach ($rsvp_breakdown as $rsvp) {
            $analytics['rsvp_breakdown'][$rsvp['response']] = [
                'count' => $rsvp['count'],
                'guest_count' => $rsvp['guest_count']
            ];
            $analytics['total_guests'] += $rsvp['guest_count'];
        }

        return ['success' => true, 'analytics' => $analytics];
    }
}