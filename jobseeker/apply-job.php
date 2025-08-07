<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

// Check if job ID is provided
if (!isset($_GET['job_id'])) {
    header("Location: search-jobs.php");
    exit();
}

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Check if already applied
    $stmt = $pdo->prepare("
        SELECT * FROM job_applications 
        WHERE job_id = ? AND user_id = ?
    ");
    $stmt->execute([$_GET['job_id'], $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "You have already applied for this job.";
        header("Location: search-jobs.php");
        exit();
    }

    // Get job details to verify it exists
    $stmt = $pdo->prepare("
        SELECT j.*, c.company_name 
        FROM jobs j
        JOIN companies c ON j.company_id = c.id
        WHERE j.id = ?
    ");
    $stmt->execute([$_GET['job_id']]);
    $job = $stmt->fetch();

    if (!$job) {
        throw new Exception("Job not found");
    }

    // Submit application
    $stmt = $pdo->prepare("
        INSERT INTO job_applications (job_id, user_id, status) 
        VALUES (?, ?, 'pending')
    ");
    $stmt->execute([$_GET['job_id'], $_SESSION['user_id']]);
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success'] = "Application submitted successfully!";
    header("Location: search-jobs.php");
    exit();
    
} catch(Exception $e) {
    // Rollback on error
    $pdo->rollBack();
    $_SESSION['error'] = "Error submitting application: " . $e->getMessage();
    header("Location: search-jobs.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Apply for Job - <?php echo htmlspecialchars($job['title']); ?></title>
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
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text-primary);
        }

        .sidebar {
            background: var(--gradient-primary);
            min-height: 100vh;
            color: white;
            padding: 25px;
            position: fixed;
            width: 280px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar h3 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            margin-bottom: 30px;
            font-size: 1.8rem;
            text-shadow: 1px 1px 0 rgba(0, 0, 0, 0.1);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            margin: 8px 0;
            padding: 12px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
        }

        .application-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .form-group label {
            color: var(--text-primary);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .job-details {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
        }

        .job-title {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .company-name {
            color: var(--text-secondary);
            font-size: 1.1rem;
            margin-bottom: 15px;
        }

        .job-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            color: var(--text-secondary);
        }

        .meta-item i {
            margin-right: 8px;
            color: var(--primary);
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background: rgba(46, 204, 113, 0.1);
            color: #27ae60;
        }

        .alert-danger {
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
        }

        .custom-file-label {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px 15px;
            background: white;
        }

        .custom-file-label::after {
            background: var(--primary);
            border: none;
            border-radius: 0 8px 8px 0;
            padding: 10px 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <h2>Apply for: <?php echo htmlspecialchars($job['title']); ?></h2>
                <h4 class="text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h4>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Cover Letter (Optional)</label>
                        <textarea name="cover_letter" class="form-control" rows="6" 
                                placeholder="Tell the employer why you're the best candidate for this position..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit Application</button>
                    <a href="jobs.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 