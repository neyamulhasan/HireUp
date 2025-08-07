<?php
session_start();
require_once '../config/database.php';

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            // Check if admin exists
            $stmt = $pdo->prepare("SELECT * FROM admin_credentials WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && $password === $admin['password']) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        } catch(PDOException $e) {
            $error = "Database error occurred. Please try again later.";
            // For debugging, you can uncomment the line below
            // $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - HireUP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --primary-dark: #1a252f;
            --accent: #3498db;
            --background: #f8f9fa;
            --text-primary: #2c3e50;
            --text-secondary: #6c757d;
            --border-color: #e9ecef;
            --card-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --success: #27ae60;
            --info: #3498db;
            --warning: #f39c12;
            --danger: #e74c3c;
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        }

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gradient-primary);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 400px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logo h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
            text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
        }

        .logo p {
            color: var(--text-primary);
            opacity: 0.8;
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-control {
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            border-radius: 6px;
            height: auto;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .btn-login {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border: none;
            width: 100%;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-login:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .input-group {
            position: relative;
        }

        .input-group-prepend {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }

        .input-group .form-control {
            padding-left: 40px;
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            padding: 0;
        }

        .alert {
            border-radius: 6px;
            border: none;
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 10px 15px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }

        .security-badge {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: rgba(39, 174, 96, 0.1);
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--success);
            color: var(--success);
            box-shadow: var(--card-shadow);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 100;
            transition: all 0.3s ease;
        }

        .security-badge:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .security-badge i {
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .security-badge {
                top: 1rem;
                right: 1rem;
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .security-badge {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 1rem;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="security-badge">
        <i class="fas fa-shield-alt"></i>
        <span>Secure Login</span>
    </div>

    <div class="login-container">
        <div class="logo text-center">
            <a href="../index.php" style="text-decoration: none;">
                <h1 class="mb-2">HireUP</h1>
                <p class="text-muted mb-4">Admin Login</p>
            </a>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" class="mt-4">
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                    </div>
                    <input type="text" name="username" class="form-control" placeholder="Username" required>
                </div>
            </div>
            
            <div class="form-group">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                    </div>
                    <input type="password" name="password" class="form-control" placeholder="Password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Login
            </button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 