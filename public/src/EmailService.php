<?php

namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Email service for sending invitations
 */
class EmailService {
    private $db;
    private $mailer;

    public function __construct($database) {
        $this->db = $database;
        $this->setupMailer();
    }

    /**
     * Setup PHPMailer configuration
     */
    private function setupMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['SMTP_HOST'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['SMTP_USERNAME'];
            $this->mailer->Password = $_ENV['SMTP_PASSWORD'];
            $this->mailer->SMTPSecure = $_ENV['SMTP_ENCRYPTION'];
            $this->mailer->Port = $_ENV['SMTP_PORT'];
            $this->mailer->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Mailer setup error: " . $e->getMessage());
        }
    }

    /**
     * Send invitation email
     */
    public function sendInvitation($invitation_id, $recipient_emails, $custom_subject = null) {
        try {
            // Get invitation details
            $stmt = $this->db->prepare("
                SELECT i.*, t.html_content, t.css_content, t.name as template_name,
                       u.first_name, u.last_name, u.email as sender_email
                FROM invitations i
                LEFT JOIN templates t ON i.template_id = t.id
                LEFT JOIN users u ON i.user_id = u.id
                WHERE i.id = ?
            ");
            $stmt->execute([$invitation_id]);
            $invitation = $stmt->fetch();

            if (!$invitation) {
                return ['success' => false, 'message' => 'Invitation not found'];
            }

            // Prepare email content
            $subject = $custom_subject ?? "You're invited: " . $invitation['title'];
            $invitation_url = APP_URL . "/invitation/" . $invitation['unique_code'];
            
            $html_content = $this->renderEmailTemplate($invitation, $invitation_url);

            $results = [];
            $sent_count = 0;
            $failed_count = 0;

            foreach ($recipient_emails as $email) {
                try {
                    // Recipients
                    $this->mailer->clearAddresses();
                    $this->mailer->setFrom($_ENV['SMTP_USERNAME'], 'Digital Invitations');
                    $this->mailer->addAddress($email);
                    $this->mailer->addReplyTo($invitation['sender_email'], $invitation['first_name'] . ' ' . $invitation['last_name']);

                    // Content
                    $this->mailer->isHTML(true);
                    $this->mailer->Subject = $subject;
                    $this->mailer->Body = $html_content;
                    $this->mailer->AltBody = strip_tags($html_content);

                    $this->mailer->send();
                    
                    // Log success
                    $this->logEmail($invitation_id, $email, $subject, 'sent');
                    $sent_count++;
                    $results[] = ['email' => $email, 'status' => 'sent'];
                    
                } catch (Exception $e) {
                    // Log failure
                    $this->logEmail($invitation_id, $email, $subject, 'failed', $e->getMessage());
                    $failed_count++;
                    $results[] = ['email' => $email, 'status' => 'failed', 'error' => $e->getMessage()];
                }
            }

            return [
                'success' => true,
                'message' => "Sent {$sent_count} emails, {$failed_count} failed",
                'results' => $results,
                'sent_count' => $sent_count,
                'failed_count' => $failed_count
            ];

        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Email sending failed'];
        }
    }

    /**
     * Render email template
     */
    private function renderEmailTemplate($invitation, $invitation_url) {
        $custom_data = json_decode($invitation['custom_data'], true) ?? [];
        
        // Basic email template
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars($invitation['title']) . '</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .email-header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .email-content { padding: 30px; }
                .invitation-preview { border: 2px dashed #dee2e6; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f8f9fa; }
                .cta-button { display: inline-block; background: #28a745; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
                ' . ($invitation['css_content'] ?? '') . '
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <h1>You\'re Invited!</h1>
                </div>
                <div class="email-content">
                    <p>Hello!</p>
                    <p>You have received an invitation to <strong>' . htmlspecialchars($invitation['title']) . '</strong></p>
                    
                    <div class="invitation-preview">
                        ' . $this->processInvitationContent($invitation['html_content'], $custom_data, $invitation) . '
                    </div>
                    
                    <div style="text-align: center;">
                        <a href="' . $invitation_url . '" class="cta-button">View Invitation & RSVP</a>
                    </div>
                    
                    <p>Or copy and paste this link in your browser:<br>
                    <a href="' . $invitation_url . '">' . $invitation_url . '</a></p>
                </div>
                <div class="footer">
                    <p>This invitation was sent using Digital Invitations Platform</p>
                    <p>Sent by: ' . htmlspecialchars($invitation['first_name'] . ' ' . $invitation['last_name']) . '</p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Process invitation content with data
     */
    private function processInvitationContent($html_content, $custom_data, $invitation) {
        $processed = $html_content;
        
        // Replace custom data placeholders
        foreach ($custom_data as $key => $value) {
            $processed = str_replace('{{' . $key . '}}', htmlspecialchars($value), $processed);
        }
        
        // Replace standard placeholders
        $processed = str_replace('{{event_date}}', format_date($invitation['event_date']), $processed);
        $processed = str_replace('{{event_location}}', htmlspecialchars($invitation['event_location']), $processed);
        $processed = str_replace('{{custom_message}}', htmlspecialchars($invitation['custom_message']), $processed);
        
        return $processed;
    }

    /**
     * Log email sending attempt
     */
    private function logEmail($invitation_id, $recipient_email, $subject, $status, $error_message = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_logs (invitation_id, recipient_email, subject, status, error_message) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$invitation_id, $recipient_email, $subject, $status, $error_message]);
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
        }
    }

    /**
     * Get email logs for invitation
     */
    public function getEmailLogs($invitation_id, $user_id) {
        // Verify ownership
        $stmt = $this->db->prepare("SELECT id FROM invitations WHERE id = ? AND user_id = ?");
        $stmt->execute([$invitation_id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Access denied'];
        }

        $stmt = $this->db->prepare("
            SELECT * FROM email_logs 
            WHERE invitation_id = ? 
            ORDER BY sent_at DESC
        ");
        $stmt->execute([$invitation_id]);
        
        return ['success' => true, 'logs' => $stmt->fetchAll()];
    }
}