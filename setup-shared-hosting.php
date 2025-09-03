<?php
/**
 * Simplified setup for shared hosting
 */

session_start();

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        // Test database and create tables
        try {
            $host = $_POST['db_host'] ?? 'localhost';
            $dbname = $_POST['db_name'] ?? 'sql_fileserver_c';
            $username = $_POST['db_user'] ?? 'sql_fileserver_c';
            $password = $_POST['db_pass'] ?? '';
            
            // Test connection
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Create tables
            $sql_commands = [
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
            
            // Execute each command
            foreach ($sql_commands as $sql) {
                $pdo->exec($sql);
            }
            
            // Insert admin user
            $admin_check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@example.com'");
            $admin_check->execute();
            
            if ($admin_check->fetchColumn() == 0) {
                $pdo->exec("INSERT INTO users (email, password, first_name, last_name, role, email_verified) 
                           VALUES ('admin@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', TRUE)");
            }
            
            // Insert sample templates
            $template_check = $pdo->prepare("SELECT COUNT(*) FROM templates");
            $template_check->execute();
            
            if ($template_check->fetchColumn() == 0) {
                $templates = [
                    ['Elegant Wedding', 'wedding', 'Beautiful wedding invitation', 
                     '<div class="invitation-card"><h1>{{bride_name}} & {{groom_name}}</h1><p>{{custom_message}}</p><p>Date: {{event_date}}</p><p>Location: {{event_location}}</p></div>',
                     '.invitation-card { padding: 40px; text-align: center; background: #f5f7fa; border-radius: 15px; }'],
                    ['Birthday Party', 'birthday', 'Fun birthday celebration', 
                     '<div class="invitation-card"><h1>🎉 Birthday Party! 🎉</h1><h2>{{celebrant_name}}</h2><p>{{custom_message}}</p><p>When: {{event_date}}</p><p>Where: {{event_location}}</p></div>',
                     '.invitation-card { padding: 40px; text-align: center; background: linear-gradient(45deg, #ff6b6b, #4ecdc4); color: white; border-radius: 20px; }'],
                    ['Corporate Event', 'corporate', 'Professional business event', 
                     '<div class="invitation-card"><h1>{{company_name}}</h1><h2>{{event_title}}</h2><p>{{custom_message}}</p><p>Date: {{event_date}}</p><p>Venue: {{event_location}}</p></div>',
                     '.invitation-card { padding: 40px; background: white; border: 2px solid #e74c3c; border-radius: 10px; }']
                ];
                
                $stmt = $pdo->prepare("INSERT INTO templates (name, category, description, html_content, css_content, is_premium) VALUES (?, ?, ?, ?, ?, 0)");
                foreach ($templates as $template) {
                    $stmt->execute($template);
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

# Email Configuration (configure later)
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
            $error = 'Setup failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Setup - Digital Invitations</title>
    
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
                                Quick Setup for Shared Hosting
                            </h1>
                            <p class="text-muted">Simple one-step setup for your hosting environment</p>
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
                        <form method="POST" action="setup-shared-hosting.php?step=1">
                            <h4 class="mb-4">Database Configuration</h4>
                            
                            <div class="alert alert-info">
                                <strong>Based on your hosting panel info:</strong><br>
                                Database Name: sql_fileserver_c<br>
                                Username: sql_fileserver_c<br>
                                Password: 7f80c627e749b8
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="db_host" class="form-label">Database Host</label>
                                        <input type="text" class="form-control" id="db_host" name="db_host" 
                                               value="localhost" required>
                                    </div>
                                </div>
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
                                    <i class="fas fa-rocket me-2"></i>Setup Platform
                                </button>
                            </div>
                        </form>

                        <?php elseif ($step == 2): ?>
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            
                            <h4 class="text-success mb-3">Setup Complete!</h4>
                            <p class="text-muted mb-4">Your Digital Invitations platform is ready!</p>
                            
                            <div class="alert alert-success text-start">
                                <h6>Admin Login:</h6>
                                <p class="mb-1"><strong>Email:</strong> admin@example.com</p>
                                <p class="mb-0"><strong>Password:</strong> admin123</p>
                                <small class="text-muted">Change these credentials after first login!</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="public/" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>Launch Application
                                </a>
                                <a href="public/login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Now
                                </a>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6>What was created:</h6>
                                <ul class="list-unstyled small text-start">
                                    <li>✅ Database tables created</li>
                                    <li>✅ Admin account created</li>
                                    <li>✅ Sample templates added</li>
                                    <li>✅ Configuration file created</li>
                                    <li>⚠️ Remember to delete this setup file!</li>
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