<?php
/**
 * Working login page with direct database connection
 */

session_start();

// Database configuration
$db_config = [
    'host' => 'localhost',
    'dbname' => 'sql_fileserver_c',
    'username' => 'sql_fileserver_c',
    'password' => '7f80c627e749b8'
];

$error_message = '';
$success_message = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = 'Email and password are required';
    } else {
        try {
            // Connect to database
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Check if users table exists
            try {
                $pdo->query("SELECT 1 FROM users LIMIT 1");
            } catch (PDOException $e) {
                // Table doesn't exist, redirect to setup
                header('Location: install-hosting.php');
                exit();
            }
            
            // Find user
            $stmt = $pdo->prepare("SELECT id, email, password, first_name, last_name, role, subscription_tier FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() === 0) {
                $error_message = 'Invalid email or password';
            } else {
                $user = $stmt->fetch();
                
                if (password_verify($password, $user['password'])) {
                    // Login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['subscription_tier'] = $user['subscription_tier'];
                    
                    header('Location: working-dashboard.php');
                    exit();
                } else {
                    $error_message = 'Invalid email or password';
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Database connection failed. Please contact support.';
            error_log("Login DB error: " . $e->getMessage());
        }
    }
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: working-dashboard.php');
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    $success_message = 'You have been logged out successfully.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="login-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="login-card p-4">
                        <div class="text-center mb-4">
                            <h1 class="h3 mb-3 fw-bold text-primary">
                                <i class="fas fa-envelope-open-text me-2"></i>
                                Digital Invitations
                            </h1>
                            <h2 class="h5 text-muted">Sign in to your account</h2>
                        </div>

                        <?php if ($error_message): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($success_message): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="working-login.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? 'admin@example.com'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           value="admin123" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="passwordToggle"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign In
                            </button>
                        </form>

                        <div class="text-center">
                            <a href="working-index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Back to Home
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="alert alert-info">
                            <h6>Demo Credentials:</h6>
                            <p class="mb-1"><strong>Email:</strong> admin@example.com</p>
                            <p class="mb-0"><strong>Password:</strong> admin123</p>
                            <small class="text-muted">Use these to login after installation</small>
                        </div>
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Need to setup? <a href="install-hosting.php" class="text-decoration-none">Run Installer</a>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordToggle.classList.remove('fa-eye');
                passwordToggle.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordToggle.classList.remove('fa-eye-slash');
                passwordToggle.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>