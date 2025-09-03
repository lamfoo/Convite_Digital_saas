<?php

namespace App;

/**
 * Template management class
 */
class Template {
    private $db;

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get all templates
     */
    public function getAllTemplates($user_subscription_tier = 'free') {
        $sql = "SELECT * FROM templates WHERE is_active = 1";
        
        if ($user_subscription_tier === 'free') {
            $sql .= " AND is_premium = 0";
        }
        
        $sql .= " ORDER BY category, name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get template by ID
     */
    public function getTemplateById($id) {
        $stmt = $this->db->prepare("SELECT * FROM templates WHERE id = ? AND is_active = 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get templates by category
     */
    public function getTemplatesByCategory($category, $user_subscription_tier = 'free') {
        $sql = "SELECT * FROM templates WHERE category = ? AND is_active = 1";
        
        if ($user_subscription_tier === 'free') {
            $sql .= " AND is_premium = 0";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    /**
     * Create new template (admin only)
     */
    public function createTemplate($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO templates (name, category, description, html_content, css_content, thumbnail_url, is_premium, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['category'],
                $data['description'],
                $data['html_content'],
                $data['css_content'] ?? '',
                $data['thumbnail_url'] ?? '',
                $data['is_premium'] ?? false,
                $data['created_by'] ?? null
            ]);

            return ['success' => true, 'message' => 'Template created successfully', 'template_id' => $this->db->lastInsertId()];
        } catch (Exception $e) {
            error_log("Template creation error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Template creation failed'];
        }
    }

    /**
     * Update template
     */
    public function updateTemplate($id, $data) {
        try {
            $allowed_fields = ['name', 'category', 'description', 'html_content', 'css_content', 'thumbnail_url', 'is_premium', 'is_active'];
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

            $values[] = $id;
            $sql = "UPDATE templates SET " . implode(', ', $set_clauses) . " WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            return ['success' => true, 'message' => 'Template updated successfully'];
        } catch (Exception $e) {
            error_log("Template update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Template update failed'];
        }
    }

    /**
     * Delete template
     */
    public function deleteTemplate($id) {
        try {
            // Soft delete - mark as inactive
            $stmt = $this->db->prepare("UPDATE templates SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);

            return ['success' => true, 'message' => 'Template deleted successfully'];
        } catch (Exception $e) {
            error_log("Template deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Template deletion failed'];
        }
    }

    /**
     * Render template with custom data
     */
    public function renderTemplate($template_id, $custom_data = []) {
        $template = $this->getTemplateById($template_id);
        if (!$template) {
            return ['success' => false, 'message' => 'Template not found'];
        }

        $html = $template['html_content'];
        $css = $template['css_content'];

        // Replace placeholders with custom data
        foreach ($custom_data as $key => $value) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars($value), $html);
        }

        return [
            'success' => true,
            'html' => $html,
            'css' => $css,
            'template' => $template
        ];
    }

    /**
     * Get template categories
     */
    public function getCategories() {
        $stmt = $this->db->prepare("SELECT DISTINCT category FROM templates WHERE is_active = 1 ORDER BY category");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}