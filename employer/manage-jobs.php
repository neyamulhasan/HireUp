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
    header("Location: edit-company.php");
    exit();
}

// Handle job deletion
if (isset($_POST['delete_job'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete job applications first
        $stmt = $pdo->prepare("DELETE FROM job_applications WHERE job_id = ?");
        $stmt->execute([$_POST['job_id']]);
        
        // Then delete the job
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND company_id = ?");
        $stmt->execute([$_POST['job_id'], $company['id']]);
        
        // Commit transaction
        $pdo->commit();
        $success = "Job deleted successfully!";
    } catch(PDOException $e) {
        // Rollback on error
        $pdo->rollBack();
        $error = "Error deleting job: " . $e->getMessage();
    }
}

// Fetch all jobs posted by this company
$stmt = $pdo->prepare("
    SELECT j.*, c.category_name, 
           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
    FROM jobs j
    JOIN job_categories c ON j.category_id = c.id
    WHERE j.company_id = ?
    ORDER BY j.posted_date DESC
");
$stmt->execute([$company['id']]);
$jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Jobs - HireUP</title>
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

        .header-button {
            margin-top: 1rem;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .job-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .job-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }

        .job-subtitle {
            color: var(--secondary-color);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .job-subtitle i {
            color: var(--accent-color);
            width: 16px;
        }

        .job-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn-edit {
            background: var(--accent-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-edit:hover {
            background: #2980b9;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-delete:hover {
            background: #c0392b;
            color: white;
            transform: translateY(-1px);
        }

        .job-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .badge-primary {
            background: var(--primary-color);
            color: white;
        }

        .badge-info {
            background: var(--accent-color);
            color: white;
        }

        .badge-secondary {
            background: #6c757d;
            color: white;
        }

        .job-footer {
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }

        .btn-view-applications {
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
            background: transparent;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-view-applications:hover {
            background: var(--accent-color);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
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

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background-color: #1a252f;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 3rem 2rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .empty-state h4 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--secondary-color);
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .header-section {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .job-card {
                padding: 1.5rem;
            }
            
            .job-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .job-badges {
                gap: 0.5rem;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
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
                <div class="header-section">
                    <h2 class="page-title">Manage Jobs</h2>
                    <div class="header-button">
                        <a href="post-job.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i>Post New Job
                        </a>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($jobs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-briefcase"></i>
                        <h4>No Jobs Posted Yet</h4>
                        <p>You haven't posted any jobs yet. Start building your team by posting your first job opening.</p>
                        <a href="post-job.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i>Post Your First Job
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach($jobs as $job): ?>
                        <div class="job-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    <div class="job-subtitle">
                                        <span><i class="fas fa-map-marker-alt"></i><?php echo htmlspecialchars($job['location']); ?></span>
                                        <span><i class="fas fa-briefcase"></i><?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></span>
                                        <span><i class="fas fa-dollar-sign"></i><?php echo htmlspecialchars($job['salary_range']); ?></span>
                                    </div>
                                </div>
                                <div class="job-actions">
                                    <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i>Edit
                                    </a>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this job? This action cannot be undone.');">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" name="delete_job" class="btn-delete">
                                            <i class="fas fa-trash"></i>Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="job-badges">
                                <span class="badge badge-primary">
                                    <i class="fas fa-tag"></i><?php echo htmlspecialchars($job['category_name']); ?>
                                </span>
                                <span class="badge badge-info">
                                    <i class="fas fa-users"></i><?php echo $job['application_count']; ?> Application<?php echo $job['application_count'] != 1 ? 's' : ''; ?>
                                </span>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-calendar"></i>Posted <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                </span>
                            </div>
                            
                            <div class="job-footer">
                                <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" 
                                   class="btn-view-applications">
                                    <i class="fas fa-eye"></i>View Applications (<?php echo $job['application_count']; ?>)
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 