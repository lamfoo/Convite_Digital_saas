<?php
/**
 * Installation script for shared hosting
 * All files are within the public directory
 */

session_start();

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        try {
            // Database credentials
            $host = $_POST['db_host'] ?? 'localhost';
            $dbname = $_POST['db_name'] ?? 'sql_fileserver_c';
            $username = $_POST['db_user'] ?? 'sql_fileserver_c';
            $password = $_POST['db_pass'] ?? '';
            
            // Test connection
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables
            $tables = [
                "CREATE TABLE IF NOT EXISTS users (
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
                ) ENGINE=InnoDB",
                
                "CREATE TABLE IF NOT EXISTS templates (
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
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB",
                
                "CREATE TABLE IF NOT EXISTS invitations (
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
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB",
                
                "CREATE TABLE IF NOT EXISTS rsvps (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    invitation_id INT NOT NULL,
                    guest_name VARCHAR(255) NOT NULL,
                    guest_email VARCHAR(255),
                    guest_phone VARCHAR(20),
                    response ENUM('yes', 'no', 'maybe') NOT NULL,
                    guest_count INT DEFAULT 1,
                    message TEXT,
                    responded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB",
                
                "CREATE TABLE IF NOT EXISTS subscriptions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    tier ENUM('free', 'basic', 'premium') NOT NULL,
                    stripe_subscription_id VARCHAR(255),
                    stripe_customer_id VARCHAR(255),
                    status ENUM('active', 'cancelled', 'past_due', 'unpaid') DEFAULT 'active',
                    current_period_start DATETIME,
                    current_period_end DATETIME,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB",
                
                "CREATE TABLE IF NOT EXISTS email_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    invitation_id INT NOT NULL,
                    recipient_email VARCHAR(255) NOT NULL,
                    subject VARCHAR(500),
                    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status ENUM('sent', 'failed') DEFAULT 'sent',
                    error_message TEXT NULL
                ) ENGINE=InnoDB"
            ];
            
            // Execute table creation
            foreach ($tables as $table_sql) {
                $pdo->exec($table_sql);
            }
            
            // Insert admin user if not exists
            $admin_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $admin_check->execute(['admin@example.com']);
            
            if ($admin_check->fetchColumn() == 0) {
                $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
                $admin_insert = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, role, email_verified) VALUES (?, ?, ?, ?, ?, ?)");
                $admin_insert->execute(['admin@example.com', $admin_password, 'Admin', 'User', 'admin', 1]);
            }
            
            // Insert templates if not exist
            $template_check = $pdo->prepare("SELECT COUNT(*) FROM templates");
            $template_check->execute();
            
            if ($template_check->fetchColumn() == 0) {
                $templates = [
                    ['Elegant Wedding', 'wedding', 'Beautiful wedding invitation', 
                     '<div class="invitation-card wedding-elegant"><div class="header"><h1 class="couple-names">{{bride_name}} & {{groom_name}}</h1><div class="wedding-date">{{event_date}}</div></div><div class="content"><p class="invitation-text">{{custom_message}}</p><div class="event-details"><div class="detail"><strong>Date:</strong> {{event_date}}</div><div class="detail"><strong>Location:</strong> {{event_location}}</div></div></div></div>',
                     '.wedding-elegant { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 40px; border-radius: 15px; text-align: center; font-family: Georgia, serif; } .couple-names { font-size: 2.5rem; color: #2c3e50; margin-bottom: 20px; } .wedding-date { font-size: 1.2rem; color: #7f8c8d; margin-bottom: 30px; }'],
                    
                    ['Birthday Party', 'birthday', 'Fun birthday celebration', 
                     '<div class="invitation-card birthday-fun"><div class="header"><h1 class="birthday-title">🎉 Birthday Party! 🎉</h1><h2 class="celebrant-name">{{celebrant_name}}</h2></div><div class="content"><p class="invitation-text">{{custom_message}}</p><div class="party-details"><div class="detail"><strong>📅 When:</strong> {{event_date}}</div><div class="detail"><strong>📍 Where:</strong> {{event_location}}</div></div></div></div>',
                     '.birthday-fun { background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #f9ca24); padding: 40px; border-radius: 20px; text-align: center; color: white; } .birthday-title { font-size: 2rem; margin-bottom: 10px; } .celebrant-name { font-size: 2.5rem; margin-bottom: 20px; }'],
                    
                    ['Corporate Event', 'corporate', 'Professional business event', 
                     '<div class="invitation-card corporate-professional"><div class="header"><div class="company-logo">{{company_name}}</div><h1 class="event-title">{{event_title}}</h1></div><div class="content"><p class="invitation-text">{{custom_message}}</p><div class="event-info"><div class="info-row"><span class="label">Date & Time:</span><span class="value">{{event_date}}</span></div><div class="info-row"><span class="label">Venue:</span><span class="value">{{event_location}}</span></div></div></div></div>',
                     '.corporate-professional { background: #ffffff; border: 2px solid #e74c3c; padding: 40px; border-radius: 10px; font-family: Arial, sans-serif; } .company-logo { font-size: 1.5rem; font-weight: bold; color: #e74c3c; } .event-title { font-size: 2rem; color: #2c3e50; }']
                ];
                
                $template_insert = $pdo->prepare("INSERT INTO templates (name, category, description, html_content, css_content, is_premium) VALUES (?, ?, ?, ?, ?, 0)");
                foreach ($templates as $template) {
                    $template_insert->execute($template);
                }
            }
            
            // Create .env file
            $env_content = "# Database Configuration
DB_HOST={$host}
DB_PORT=3306
DB_NAME={$dbname}
DB_USER={$username}
DB_PASS={$password}

# Application Configuration
APP_URL=http://{$_SERVER['HTTP_HOST']}
APP_ENV=production
APP_SECRET_KEY=" . bin2hex(random_bytes(32)) . "

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_ENCRYPTION=tls

# File Upload Configuration
MAX_FILE_SIZE=5242880
UPLOAD_PATH=uploads/
";
            
            file_put_contents('.env', $env_content);
            
            $step = 2;
            $success = 'Installation completed successfully!';
            
        } catch (Exception $e) {
            $error = 'Setup failed: ' . $e->getMessage() . ' - Make sure your database credentials are correct.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .setup-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .setup-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="setup-container d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="setup-card p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3 fw-bold text-primary">
                                <i class="fas fa-envelope-open-text me-2"></i>
                                Digital Invitations Setup
                            </h1>
                            <p class="text-muted">Quick setup for shared hosting</p>
                        </div>

                        <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($step == 1): ?>
                        <form method="POST" action="install-hosting.php?step=1">
                            <h4 class="mb-4">Database Configuration</h4>
                            
                            <div class="alert alert-info">
                                <strong>Your hosting credentials:</strong><br>
                                Database: sql_fileserver_c<br>
                                Username: sql_fileserver_c<br>
                                Password: 7f80c627e749b8
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" 
                                       value="localhost" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="sql_fileserver_c" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="db_user" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="db_user" name="db_user" 
                                               value="sql_fileserver_c" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="db_pass" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="db_pass" name="db_pass" 
                                               value="7f80c627e749b8" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>Install Platform
                                </button>
                            </div>
                        </form>

                        <?php elseif ($step == 2): ?>
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            
                            <h4 class="text-success mb-3">Installation Complete!</h4>
                            <p class="text-muted mb-4">Your platform is ready to use!</p>
                            
                            <div class="alert alert-success text-start">
                                <h6>Admin Login Credentials:</h6>
                                <p class="mb-1"><strong>Email:</strong> admin@example.com</p>
                                <p class="mb-0"><strong>Password:</strong> admin123</p>
                                <small class="text-muted">⚠️ Change these after first login!</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>Go to Application
                                </a>
                                <a href="login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login as Admin
                                </a>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6>What was created:</h6>
                                <ul class="list-unstyled small">
                                    <li>✅ 6 database tables</li>
                                    <li>✅ Admin user account</li>
                                    <li>✅ 3 sample templates</li>
                                    <li>✅ Configuration file (.env)</li>
                                    <li>⚠️ Delete this installer file for security!</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>