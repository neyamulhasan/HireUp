<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php");
    exit();
}

// Fetch current company profile
$stmt = $pdo->prepare("
    SELECT c.* 
    FROM companies c 
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $company_name = $_POST['company_name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $website = $_POST['website'];
    $about = $_POST['about'];

    try {
        if ($company) {
            // Update existing company profile
            $stmt = $pdo->prepare("
                UPDATE companies 
                SET company_name = ?, description = ?, location = ?, website = ?, about = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$company_name, $description, $location, $website, $about, $_SESSION['user_id']]);
        } else {
            // Create new company profile
            $stmt = $pdo->prepare("
                INSERT INTO companies (user_id, company_name, description, location, website, about)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $company_name, $description, $location, $website, $about]);
        }
        $_SESSION['success'] = "Company profile updated successfully!";
        
        // Refresh company data
        $stmt = $pdo->prepare("SELECT * FROM companies WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $company = $stmt->fetch();
        
        // Redirect after successful update
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Company Profile - HireUP</title>
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

        .header-section {
            margin-bottom: 2rem;
        }

        .form-section {
            background: #fafbfc;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
        }

        .btn-save {
            background: var(--primary-color);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-save:hover {
            background: #1a252f;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .form-group label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid var(--border-color);
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.15);
            background: white;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .form-text {
            font-size: 0.8rem;
            color: var(--secondary-color);
            margin-top: 0.5rem;
        }

        .alert {
            border-radius: 8px;
            border: 1px solid transparent;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
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
                    <a class="nav-link" href="post-job.php">
                        <i class="fas fa-plus-circle"></i> Post a Job
                    </a>
                    <a class="nav-link" href="manage-jobs.php">
                        <i class="fas fa-briefcase"></i> Manage Jobs
                    </a>
                    <a class="nav-link" href="manage-articles.php">
                        <i class="fas fa-newspaper"></i> Articles
                    </a>
                    <a class="nav-link active" href="edit-company.php">
                        <i class="fas fa-building"></i> Edit Company Profile
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="header-section">
                    <h2 class="page-title">Edit Company Profile</h2>
                </div>
                
                <div class="form-section">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bootstrap Modal for Confirmation -->
                    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirm Changes</h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    Are you sure you want to save these changes to your company profile?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" id="confirmSave">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="companyForm" method="POST" action="">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="company_name" class="form-control" 
                                value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3" required><?php 
                                echo htmlspecialchars($company['description'] ?? ''); 
                            ?></textarea>
                            <small class="form-text text-muted">
                                Short description of your company (will appear in job listings)
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Location</label>
                            <input type="text" name="location" class="form-control" 
                                value="<?php echo htmlspecialchars($company['location'] ?? ''); ?>" required>
                            <small class="form-text text-muted">
                                Company's main location or headquarters
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>Website</label>
                            <input type="url" name="website" class="form-control" 
                                value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="about">About Company</label>
                            <textarea id="about" name="about" class="form-control" rows="4"><?php echo htmlspecialchars($company['about'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                Detailed description about your company, culture, mission, etc.
                            </small>
                        </div>
                        
                        <button type="button" class="btn btn-lg btn-save" onclick="showConfirmModal()">
                            <i class="fas fa-save"></i>Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    function showConfirmModal() {
        $('#confirmModal').modal('show');
    }

    document.getElementById('confirmSave').addEventListener('click', function() {
        document.getElementById('companyForm').submit();
    });

    // Auto-expand textarea to fit content
    function autoResizeTextarea(el) {
        el.style.height = 'auto';
        el.style.height = (el.scrollHeight) + 'px';
    }
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('textarea.form-control').forEach(function(textarea) {
            autoResizeTextarea(textarea);
            textarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        });
    });
    </script>
</body>
</html>