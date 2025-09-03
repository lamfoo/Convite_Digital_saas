<?php
/**
 * Simplified installation script for testing
 */

session_start();

$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// Handle success messages from redirects
if (isset($_GET['success'])) {
    switch ($step) {
        case 3:
            $success = 'Database connection successful!';
            break;
        case 4:
            $success = 'Configuration saved successfully!';
            break;
        case 5:
            $success = 'Database tables created successfully!';
            break;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 2) {
        // Test database connection
        try {
            $host = $_POST['db_host'] ?? 'localhost';
            $port = $_POST['db_port'] ?? '3306';
            $dbname = $_POST['db_name'] ?? 'digital_invitations';
            $user = $_POST['db_user'] ?? 'root';
            $pass = $_POST['db_pass'] ?? '';
            
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Store config in session
            $_SESSION['db_config'] = [
                'DB_HOST' => $host,
                'DB_PORT' => $port,
                'DB_NAME' => $dbname,
                'DB_USER' => $user,
                'DB_PASS' => $pass,
            ];
            
            header('Location: test-install.php?step=3&success=1');
            exit();
        } catch (PDOException $e) {
            $error = 'Database connection failed: ' . $e->getMessage();
        }
    } elseif ($step == 3) {
        // Save configuration
        $db_config = $_SESSION['db_config'] ?? [];
        
        $full_config = array_merge($db_config, [
            'APP_URL' => rtrim($_POST['app_url'] ?? 'http://localhost', '/'),
            'APP_SECRET_KEY' => $_POST['app_secret'] ?? bin2hex(random_bytes(32)),
            'SMTP_HOST' => $_POST['smtp_host'] ?? '',
            'SMTP_PORT' => $_POST['smtp_port'] ?? '587',
            'SMTP_USERNAME' => $_POST['smtp_username'] ?? '',
            'SMTP_PASSWORD' => $_POST['smtp_password'] ?? '',
            'SMTP_ENCRYPTION' => $_POST['smtp_encryption'] ?? 'tls',
        ]);
        
        // Create .env file
        $env_content = '';
        foreach ($full_config as $key => $value) {
            $env_content .= "{$key}={$value}\n";
        }
        
        if (file_put_contents('.env', $env_content)) {
            header('Location: test-install.php?step=4&success=1');
            exit();
        } else {
            $error = 'Failed to save configuration file. Check directory permissions.';
        }
    } elseif ($step == 4) {
        // Setup database
        try {
            // Load configuration
            if (file_exists('.env')) {
                $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    if (strpos(trim($line), '#') === 0) continue;
                    if (strpos($line, '=') === false) continue;
                    list($name, $value) = explode('=', $line, 2);
                    $_ENV[trim($name)] = trim($value);
                }
            }
            
            $dsn = "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_NAME']};charset=utf8mb4";
            $pdo = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Read and execute schema
            if (file_exists('database/schema.sql')) {
                $schema = file_get_contents('database/schema.sql');
                $statements = array_filter(array_map('trim', explode(';', $schema)));
                
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
            }
            
            header('Location: test-install.php?step=5&success=1');
            exit();
        } catch (Exception $e) {
            $error = 'Database setup failed: ' . $e->getMessage();
        }
    }
}

$db_config = $_SESSION['db_config'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Digital Invitations SaaS Platform</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .install-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .install-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        .step-indicator {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .step {
            display: inline-block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            text-align: center;
            border-radius: 50%;
            margin: 0 10px;
            font-weight: bold;
        }
        .step.active {
            background: #007bff;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step.pending {
            background: #e9ecef;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="install-container d-flex align-items-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="install-card p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3 fw-bold text-primary">
                                <i class="fas fa-envelope-open-text me-2"></i>
                                Digital Invitations Setup
                            </h1>
                            <p class="text-muted">Let's get your SaaS platform ready!</p>
                        </div>

                        <!-- Step Indicator -->
                        <div class="step-indicator text-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="step <?php 
                                if ($i < $step) echo 'completed';
                                elseif ($i == $step) echo 'active';
                                else echo 'pending';
                            ?>"><?php echo $i; ?></span>
                            <?php endfor; ?>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <?php
                                    $step_names = [
                                        1 => 'Welcome',
                                        2 => 'Database',
                                        3 => 'Configuration',
                                        4 => 'Setup',
                                        5 => 'Complete'
                                    ];
                                    echo $step_names[$step] ?? 'Unknown';
                                    ?>
                                </small>
                            </div>
                        </div>

                        <!-- Debug Info -->
                        <div class="alert alert-info">
                            <small>
                                <strong>Debug:</strong> Current step: <?php echo $step; ?>
                                <?php if (!empty($_POST)): ?>
                                | POST received: Yes
                                <?php endif; ?>
                                <?php if (!empty($db_config)): ?>
                                | DB Config: Yes
                                <?php endif; ?>
                            </small>
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
                        <!-- Step 1: Welcome -->
                        <div class="text-center">
                            <h4>Welcome to Digital Invitations Setup</h4>
                            <p class="text-muted mb-4">This installer will help you set up your SaaS platform in a few simple steps.</p>
                            
                            <div class="mb-4">
                                <h6>System Requirements:</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-check text-success me-2"></i>PHP 8.0 or higher
                                        <span class="text-muted">(Current: <?php echo PHP_VERSION; ?>)</span>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-<?php echo extension_loaded('pdo') ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                        PDO Extension
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-<?php echo extension_loaded('pdo_mysql') ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                        PDO MySQL Extension
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-<?php echo is_writable('.') ? 'check text-success' : 'times text-danger'; ?> me-2"></i>
                                        Writable Directory
                                    </li>
                                </ul>
                            </div>
                            
                            <a href="?step=2" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right me-2"></i>Start Installation
                            </a>
                        </div>

                        <?php elseif ($step == 2): ?>
                        <!-- Step 2: Database Configuration -->
                        <form method="POST" action="test-install.php?step=2">
                            <h4 class="mb-4">Database Configuration</h4>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="db_host" class="form-label">Database Host</label>
                                        <input type="text" class="form-control" id="db_host" name="db_host" 
                                               value="localhost" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="db_port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="db_port" name="db_port" 
                                               value="3306" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" 
                                       value="digital_invitations" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="db_user" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="db_user" name="db_user" 
                                               value="root" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="db_pass" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="test-install.php?step=1" class="btn btn-outline-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-database me-2"></i>Test Connection
                                </button>
                            </div>
                        </form>

                        <?php elseif ($step == 3): ?>
                        <!-- Step 3: Application Configuration -->
                        <form method="POST" action="test-install.php?step=3">
                            <!-- Preserve database config -->
                            <?php 
                            $db_config = $_SESSION['db_config'] ?? [];
                            foreach ($db_config as $key => $value): 
                            ?>
                            <input type="hidden" name="<?php echo strtolower($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                            <?php endforeach; ?>
                            
                            <h4 class="mb-4">Application Configuration</h4>
                            
                            <div class="mb-3">
                                <label for="app_url" class="form-label">Application URL</label>
                                <input type="url" class="form-control" id="app_url" name="app_url" 
                                       value="http://<?php echo $_SERVER['HTTP_HOST']; ?>" required>
                                <div class="form-text">The URL where your application will be accessible</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="app_secret" class="form-label">Application Secret Key</label>
                                <input type="text" class="form-control" id="app_secret" name="app_secret" 
                                       value="<?php echo bin2hex(random_bytes(32)); ?>" required>
                                <div class="form-text">Keep this secret and secure</div>
                            </div>
                            
                            <h5 class="mb-3 mt-4">Email Configuration (Optional)</h5>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="smtp_host" class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                               placeholder="smtp.gmail.com">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="smtp_port" class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                               value="587">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_username" class="form-label">SMTP Username</label>
                                        <input type="email" class="form-control" id="smtp_username" name="smtp_username" 
                                               placeholder="your-email@gmail.com">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp_password" class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" id="smtp_password" name="smtp_password" 
                                               placeholder="Your app password">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_encryption" class="form-label">Encryption</label>
                                <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                    <option value="tls">TLS</option>
                                    <option value="ssl">SSL</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="test-install.php?step=2" class="btn btn-outline-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Configuration
                                </button>
                            </div>
                        </form>

                        <?php elseif ($step == 4): ?>
                        <!-- Step 4: Database Setup -->
                        <form method="POST" action="test-install.php?step=4">
                            <h4 class="mb-4">Database Setup</h4>
                            <p class="text-muted mb-4">Create database tables and insert sample data</p>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                This will create all necessary database tables and insert sample templates.
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="test-install.php?step=3" class="btn btn-outline-secondary">Back</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-database me-2"></i>Setup Database
                                </button>
                            </div>
                        </form>

                        <?php elseif ($step == 5): ?>
                        <!-- Step 5: Installation Complete -->
                        <div class="text-center">
                            <div class="mb-4">
                                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                            </div>
                            
                            <h4 class="text-success mb-3">Installation Complete!</h4>
                            <p class="text-muted mb-4">Your Digital Invitations SaaS platform is ready to use.</p>
                            
                            <div class="alert alert-success text-start">
                                <h6>Default Admin Account:</h6>
                                <p class="mb-1"><strong>Email:</strong> admin@example.com</p>
                                <p class="mb-0"><strong>Password:</strong> admin123</p>
                                <small class="text-muted">Please change these credentials after first login!</small>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="public/" class="btn btn-primary btn-lg">
                                    <i class="fas fa-rocket me-2"></i>Launch Application
                                </a>
                                <a href="public/login.php" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Admin Login
                                </a>
                            </div>
                            
                            <div class="mt-4 p-3 bg-light rounded">
                                <h6>Next Steps:</h6>
                                <ul class="list-unstyled small text-start">
                                    <li>1. Login with the admin account</li>
                                    <li>2. Change the default admin password</li>
                                    <li>3. Configure email settings if needed</li>
                                    <li>4. Add more templates if desired</li>
                                    <li>5. Remove or rename install.php for security</li>
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
    
    <script>
        // Add form debugging
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    console.log('Form submitted:', this.action);
                    console.log('Form data:', new FormData(this));
                });
            });
        });
    </script>
</body>
</html>