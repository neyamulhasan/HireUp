<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_type = $_POST['user_type'];

    // First, check if username already exists
    $check_username = $pdo->prepare("SELECT username FROM users WHERE username = ?");
    $check_username->execute([$username]);

    // Then, check if email already exists
    $check_email = $pdo->prepare("SELECT email FROM users WHERE email = ?");
    $check_email->execute([$email]);

    if ($check_username->rowCount() > 0) {
        $error = "Username is already taken. Please choose a different username.";
    } elseif ($check_email->rowCount() > 0) {
        $error = "Email address is already registered. Please use a different email.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, user_type) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $user_type]);
            
            $_SESSION['message'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } catch(PDOException $e) {
            $error = "An unexpected error occurred. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - HireUP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        }

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap');

        body {
            background: var(--gradient-primary);
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
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

        .register-container {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 450px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .logo {
            text-align: center;
            margin-bottom: 15px;
        }

        .logo h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
            text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
        }

        .logo p {
            color: var(--text-primary);
            opacity: 0.8;
            font-size: 0.9rem;
            margin-bottom: 0;
        }

        .form-group {
            margin-bottom: 12px;
        }

        .form-group label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 4px;
            display: block;
            font-size: 0.85rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 8px 12px;
            height: auto;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .user-type-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }

        .user-type-option {
            position: relative;
        }

        .user-type-option input {
            display: none;
        }

        .user-type-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .user-type-option label i {
            font-size: 18px;
            margin-bottom: 6px;
            color: var(--text-secondary);
        }

        .user-type-option input:checked + label {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .user-type-option input:checked + label i {
            color: white;
        }

        .btn-register {
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 20px;
            font-weight: 600;
            width: 100%;
            margin-top: 15px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-register:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .login-link {
            text-align: center;
            margin-top: 15px;
            color: var(--text-primary);
            font-size: 0.85rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
            color: #dc3545;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 0.8rem;
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
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo text-center">
            <a href="index.php" style="text-decoration: none;">
                <h1>HireUP</h1>
                <p>Create your account and start your journey</p>
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="user-type-selector">
                <div class="user-type-option">
                    <input type="radio" id="jobseeker" name="user_type" value="jobseeker" checked>
                    <label for="jobseeker">
                        <i class="fas fa-user"></i>
                        <span>Job Seeker</span>
                    </label>
                </div>
                <div class="user-type-option">
                    <input type="radio" id="employer" name="user_type" value="employer">
                    <label for="employer">
                        <i class="fas fa-building"></i>
                        <span>Employer</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-user"></i>
                        </span>
                    </div>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                    </div>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                    </div>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-register">
                <i class="fas fa-user-plus mr-2"></i>
                Create Account
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 