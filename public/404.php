<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Digital Invitations</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .error-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .error-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="error-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="error-card p-5 text-center">
                        <div class="mb-4">
                            <i class="fas fa-search text-primary" style="font-size: 5rem;"></i>
                        </div>
                        
                        <h1 class="display-4 fw-bold text-dark mb-3">404</h1>
                        <h3 class="mb-3">Page Not Found</h3>
                        <p class="text-muted mb-4">
                            The page you're looking for doesn't exist or has been moved.
                        </p>
                        
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="/" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Go Home
                            </a>
                            <a href="/templates.php" class="btn btn-outline-primary">
                                <i class="fas fa-palette me-2"></i>Browse Templates
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>