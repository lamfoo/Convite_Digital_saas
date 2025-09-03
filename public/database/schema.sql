-- Digital Invitations SaaS Platform Database Schema

CREATE DATABASE IF NOT EXISTS digital_invitations CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digital_invitations;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    subscription_tier ENUM('free', 'basic', 'premium') DEFAULT 'free',
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255) NULL,
    reset_token VARCHAR(255) NULL,
    reset_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Templates table
CREATE TABLE templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT,
    html_content LONGTEXT NOT NULL,
    css_content LONGTEXT,
    thumbnail_url VARCHAR(500),
    is_premium BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Invitations table
CREATE TABLE invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    template_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    event_date DATETIME,
    event_location VARCHAR(500),
    custom_message TEXT,
    custom_data JSON,
    unique_code VARCHAR(50) NOT NULL UNIQUE,
    qr_code_path VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES templates(id) ON DELETE RESTRICT
);

-- RSVPs table
CREATE TABLE rsvps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitation_id INT NOT NULL,
    guest_name VARCHAR(255) NOT NULL,
    guest_email VARCHAR(255),
    guest_phone VARCHAR(20),
    response ENUM('yes', 'no', 'maybe') NOT NULL,
    guest_count INT DEFAULT 1,
    message TEXT,
    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- Subscriptions table
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tier ENUM('free', 'basic', 'premium') NOT NULL,
    stripe_subscription_id VARCHAR(255),
    stripe_customer_id VARCHAR(255),
    status ENUM('active', 'cancelled', 'past_due', 'unpaid') DEFAULT 'active',
    current_period_start DATETIME,
    current_period_end DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Email logs table
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitation_id INT NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500),
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent',
    error_message TEXT NULL,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password, first_name, last_name, role, email_verified) 
VALUES ('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', TRUE);

-- Insert sample templates
INSERT INTO templates (name, category, description, html_content, css_content, thumbnail_url, is_premium) VALUES
('Elegant Wedding', 'wedding', 'A beautiful and elegant wedding invitation template', 
'<div class="invitation-card wedding-elegant">
    <div class="header">
        <h1 class="couple-names">{{bride_name}} & {{groom_name}}</h1>
        <div class="wedding-date">{{event_date}}</div>
    </div>
    <div class="content">
        <p class="invitation-text">{{custom_message}}</p>
        <div class="event-details">
            <div class="detail">
                <strong>Date:</strong> {{event_date}}
            </div>
            <div class="detail">
                <strong>Location:</strong> {{event_location}}
            </div>
        </div>
    </div>
    <div class="footer">
        <p>RSVP by clicking the button below</p>
    </div>
</div>',
'.wedding-elegant { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 40px; border-radius: 15px; text-align: center; font-family: "Georgia", serif; }
.couple-names { font-size: 2.5rem; color: #2c3e50; margin-bottom: 20px; }
.wedding-date { font-size: 1.2rem; color: #7f8c8d; margin-bottom: 30px; }
.invitation-text { font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px; color: #34495e; }
.event-details { background: rgba(255,255,255,0.8); padding: 20px; border-radius: 10px; margin: 20px 0; }
.detail { margin: 10px 0; font-size: 1rem; }',
'/assets/thumbnails/wedding-elegant.jpg', FALSE),

('Birthday Celebration', 'birthday', 'Fun and colorful birthday party invitation',
'<div class="invitation-card birthday-fun">
    <div class="header">
        <h1 class="birthday-title">🎉 Birthday Party! 🎉</h1>
        <h2 class="celebrant-name">{{celebrant_name}}</h2>
    </div>
    <div class="content">
        <p class="invitation-text">{{custom_message}}</p>
        <div class="party-details">
            <div class="detail">
                <strong>📅 When:</strong> {{event_date}}
            </div>
            <div class="detail">
                <strong>📍 Where:</strong> {{event_location}}
            </div>
        </div>
    </div>
</div>',
'.birthday-fun { background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #f9ca24); padding: 40px; border-radius: 20px; text-align: center; color: white; }
.birthday-title { font-size: 2rem; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
.celebrant-name { font-size: 2.5rem; margin-bottom: 20px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
.invitation-text { font-size: 1.2rem; margin-bottom: 30px; }
.party-details { background: rgba(0,0,0,0.2); padding: 20px; border-radius: 15px; }
.detail { margin: 15px 0; font-size: 1.1rem; }',
'/assets/thumbnails/birthday-fun.jpg', FALSE),

('Corporate Event', 'corporate', 'Professional corporate event invitation',
'<div class="invitation-card corporate-professional">
    <div class="header">
        <div class="company-logo">{{company_name}}</div>
        <h1 class="event-title">{{event_title}}</h1>
    </div>
    <div class="content">
        <p class="invitation-text">{{custom_message}}</p>
        <div class="event-info">
            <div class="info-row">
                <span class="label">Date & Time:</span>
                <span class="value">{{event_date}}</span>
            </div>
            <div class="info-row">
                <span class="label">Venue:</span>
                <span class="value">{{event_location}}</span>
            </div>
        </div>
    </div>
</div>',
'.corporate-professional { background: #ffffff; border: 2px solid #e74c3c; padding: 40px; border-radius: 10px; font-family: "Arial", sans-serif; }
.company-logo { font-size: 1.5rem; font-weight: bold; color: #e74c3c; margin-bottom: 20px; }
.event-title { font-size: 2rem; color: #2c3e50; margin-bottom: 30px; }
.invitation-text { font-size: 1rem; line-height: 1.6; margin-bottom: 30px; color: #34495e; }
.event-info { background: #f8f9fa; padding: 20px; border-radius: 8px; }
.info-row { display: flex; justify-content: space-between; margin: 10px 0; }
.label { font-weight: bold; color: #2c3e50; }
.value { color: #34495e; }',
'/assets/thumbnails/corporate-professional.jpg', FALSE),

('Baby Shower', 'baby-shower', 'Sweet and gentle baby shower invitation',
'<div class="invitation-card baby-shower-sweet">
    <div class="header">
        <h1 class="shower-title">Baby Shower</h1>
        <div class="baby-name">For {{baby_name}}</div>
    </div>
    <div class="content">
        <p class="invitation-text">{{custom_message}}</p>
        <div class="shower-details">
            <div class="detail">
                <strong>Date:</strong> {{event_date}}
            </div>
            <div class="detail">
                <strong>Location:</strong> {{event_location}}
            </div>
        </div>
    </div>
</div>',
'.baby-shower-sweet { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); padding: 40px; border-radius: 20px; text-align: center; }
.shower-title { font-size: 2.2rem; color: #d63384; margin-bottom: 10px; font-family: cursive; }
.baby-name { font-size: 1.5rem; color: #6f42c1; margin-bottom: 30px; font-style: italic; }
.invitation-text { font-size: 1.1rem; margin-bottom: 30px; color: #495057; }
.shower-details { background: rgba(255,255,255,0.7); padding: 20px; border-radius: 15px; }
.detail { margin: 15px 0; font-size: 1rem; color: #495057; }',
'/assets/thumbnails/baby-shower-sweet.jpg', FALSE),

('Graduation Party', 'graduation', 'Celebratory graduation party invitation',
'<div class="invitation-card graduation-celebration">
    <div class="header">
        <h1 class="grad-title">🎓 Graduation Celebration 🎓</h1>
        <h2 class="graduate-name">{{graduate_name}}</h2>
    </div>
    <div class="content">
        <p class="invitation-text">{{custom_message}}</p>
        <div class="celebration-details">
            <div class="detail">
                <strong>Date:</strong> {{event_date}}
            </div>
            <div class="detail">
                <strong>Location:</strong> {{event_location}}
            </div>
        </div>
    </div>
</div>',
'.graduation-celebration { background: linear-gradient(45deg, #667eea 0%, #764ba2 100%); padding: 40px; border-radius: 15px; text-align: center; color: white; }
.grad-title { font-size: 2rem; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3); }
.graduate-name { font-size: 2.2rem; margin-bottom: 25px; font-weight: bold; }
.invitation-text { font-size: 1.1rem; margin-bottom: 30px; }
.celebration-details { background: rgba(255,255,255,0.2); padding: 20px; border-radius: 10px; }
.detail { margin: 15px 0; font-size: 1.1rem; }',
'/assets/thumbnails/graduation-celebration.jpg', TRUE);