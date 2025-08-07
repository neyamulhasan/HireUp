<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php");
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header("Location: manage-jobs.php");
    exit();
}

// Get employer's company ID
$stmt = $pdo->prepare("SELECT c.id FROM companies c WHERE c.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: edit-company.php");
    exit();
}

// Fetch job details
$stmt = $pdo->prepare("
    SELECT j.* 
    FROM jobs j 
    WHERE j.id = ? AND j.company_id = ?
");
$stmt->execute([$_GET['id'], $company['id']]);
$job = $stmt->fetch();

// If job not found or doesn't belong to this company
if (!$job) {
    header("Location: manage-jobs.php");
    exit();
}

// Get categories for dropdown
$stmt = $pdo->query("SELECT * FROM job_categories ORDER BY category_name ASC");
$categories = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE jobs 
            SET 
                category_id = ?,
                title = ?,
                description = ?,
                requirements = ?,
                salary_range = ?,
                location = ?,
                job_type = ?
            WHERE id = ? AND company_id = ?
        ");

        $stmt->execute([
            $_POST['category_id'],
            $_POST['title'],
            $_POST['description'],
            $_POST['requirements'],
            $_POST['salary_range'],
            $_POST['location'],
            $_POST['job_type'],
            $job['id'],
            $company['id']
        ]);

        $success = "Job updated successfully!";
        
        // Refresh job data
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
        $stmt->execute([$job['id']]);
        $job = $stmt->fetch();
    } catch(PDOException $e) {
        $error = "Error updating job: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Job - HireUP</title>
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

        .btn-save {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.1);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
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
                    <a class="nav-link" href="post-job.php">
                        <i class="fas fa-plus-circle"></i> Post a Job
                    </a>
                    <a class="nav-link active" href="manage-jobs.php">
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Edit Job</h2>
                    <a href="manage-jobs.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Jobs
                    </a>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Job Title</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo htmlspecialchars($job['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" class="form-control" required>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category['id'] == $job['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="5" required><?php 
                            echo htmlspecialchars($job['description']); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Requirements</label>
                        <textarea name="requirements" class="form-control" rows="5" required><?php 
                            echo htmlspecialchars($job['requirements']); 
                        ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Salary Range</label>
                        <input type="text" name="salary_range" class="form-control" 
                               value="<?php echo htmlspecialchars($job['salary_range']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" 
                               value="<?php echo htmlspecialchars($job['location']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Job Type</label>
                        <select name="job_type" class="form-control" required>
                            <?php 
                            $types = ['full-time', 'part-time', 'contract', 'remote'];
                            foreach($types as $type): 
                            ?>
                                <option value="<?php echo $type; ?>" 
                                    <?php echo $job['job_type'] == $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-lg btn-save">Update Job</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 