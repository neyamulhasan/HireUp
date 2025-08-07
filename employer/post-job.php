<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php");
    exit();
}

// Get employer's company ID
$stmt = $pdo->prepare("SELECT c.id FROM companies c WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch();

if (!$company) {
    // Redirect to complete company profile if not set up
    header("Location: edit-company.php");
    exit();
}

// Get categories for dropdown - Add ORDER BY for better organization
try {
    $stmt = $pdo->query("SELECT * FROM job_categories ORDER BY category_name ASC");
    $categories = $stmt->fetchAll();
    if (empty($categories)) {
        $error = "No job categories found. Please contact the administrator.";
    }
} catch(PDOException $e) {
    $error = "Error loading categories: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate category
    if (empty($_POST['category_id'])) {
        $error = "Please select a job category";
    } else {
        $company_id = $company['id'];
        $category_id = $_POST['category_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $requirements = $_POST['requirements'];
        $salary_range = $_POST['salary_range'];
        $location = $_POST['location'];
        $job_type = $_POST['job_type'];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO jobs (
                    company_id, 
                    category_id, 
                    title, 
                    description, 
                    requirements, 
                    salary_range, 
                    location, 
                    job_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $company_id,
                $category_id,
                $title,
                $description,
                $requirements,
                $salary_range,
                $location,
                $job_type
            ]);
            
            $success = "Job posted successfully!";
        } catch(PDOException $e) {
            $error = "Error posting job: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Post a Job - HireUP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --text-color: #2c3e50;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
        }

        .sidebar {
            background: var(--primary-color);
            min-height: 100vh;
            color: white;
            padding: 1.5rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h3 {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: white;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            margin: 0.5rem 0;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }

        .main-content {
            padding: 2rem;
        }

        .form-section {
            background: #fafbfc;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
        }

        .form-group label {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            color: var(--text-color);
            font-size: 0.9rem;
            line-height: 1.5;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.1);
            outline: none;
        }

        .form-text {
            color: var(--secondary-color);
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        .btn-post {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-post:hover {
            background-color: #1a252f;
            color: white;
        }

        .alert {
            border-radius: 8px;
            border: 1px solid transparent;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        /* Updated select styling */
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%232c3e50' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            font-size: 0.9rem;
            line-height: 1.5;
            box-sizing: border-box;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            color: var(--text-color);
            transition: all 0.3s ease;
            cursor: pointer;
            height: auto;
        }

        select.form-control option {
            padding: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-color);
            background-color: white;
            display: block;
        }

        select.form-control:focus {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%233498db' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Page title styling */
        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50%;
            height: 3px;
            background: linear-gradient(90deg, #1f5582, transparent);
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 sidebar">
                <h3 class="mb-4">HireUP</h3>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                    <a class="nav-link active" href="post-job.php">
                        <i class="fas fa-plus-circle"></i> Post a Job
                    </a>
                    <a class="nav-link" href="manage-jobs.php">
                        <i class="fas fa-briefcase"></i> Manage Jobs
                    </a>
                    <a class="nav-link" href="manage-articles.php">
                        <i class="fas fa-newspaper"></i> Articles
                    </a>
                    <a class="nav-link" href="edit-company.php">
                        <i class="fas fa-building"></i> Edit Company Profile
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <h2 class="page-title">Post a New Job</h2>
                
                <div class="form-section">
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Job Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="Enter job title">
                        </div>
                        
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select a job category</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($categories)): ?>
                                <small class="text-danger">No categories available. Please contact the administrator.</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Job Description</label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="Provide a detailed description of the job role and responsibilities"></textarea>
                            <small class="form-text">
                                Provide a detailed description of the job role and responsibilities.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Requirements</label>
                            <textarea name="requirements" class="form-control" rows="4" required placeholder="List the qualifications, skills, and experience required"></textarea>
                            <small class="form-text">
                                List the qualifications, skills, and experience required for this position.
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Salary Range</label>
                            <input type="text" name="salary_range" class="form-control" required placeholder="$50,000 - $70,000 per year">
                            <small class="form-text">
                                Example: $50,000 - $70,000 per year
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" required placeholder="City, State or Remote">
                            <small class="form-text">
                                City, State or Remote
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Job Type</label>
                            <select name="job_type" class="form-control" required>
                                <option value="">Select job type</option>
                                <option value="full-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'full-time') ? 'selected' : ''; ?>>Full Time</option>
                                <option value="part-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'part-time') ? 'selected' : ''; ?>>Part Time</option>
                                <option value="contract" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'contract') ? 'selected' : ''; ?>>Contract</option>
                                <option value="remote" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] == 'remote') ? 'selected' : ''; ?>>Remote</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-lg btn-post">Post Job</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 