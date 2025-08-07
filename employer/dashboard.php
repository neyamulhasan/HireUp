<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php");
    exit();
}

// Fetch employer's company profile
$stmt = $pdo->prepare("
    SELECT u.*, c.*,
           (SELECT AVG(rating) FROM company_reviews WHERE company_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM company_reviews WHERE company_id = c.id) as review_count
    FROM users u 
    LEFT JOIN companies c ON u.id = c.user_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    echo "Error: User not found";
    exit();
}

// Get company_id from the user data
$company_id = $user['id'] ?? 0;

// Debug output
echo "<!-- User ID: " . $_SESSION['user_id'] . " -->";
echo "<!-- Company ID: " . $company_id . " -->";

// Fetch posted jobs count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as job_count
    FROM jobs 
    WHERE company_id = ?
");
$stmt->execute([$company_id]);
$jobCount = $stmt->fetch()['job_count'];

// Fetch total applications count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_applications
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    WHERE j.company_id = ?
");
$stmt->execute([$company_id]);
$totalApplications = $stmt->fetch()['total_applications'];

// Fetch recent applications
$stmt = $pdo->prepare("
    SELECT ja.*, j.title, u.username, jp.full_name
    FROM job_applications ja
    JOIN jobs j ON ja.job_id = j.id
    JOIN users u ON ja.user_id = u.id
    LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
    WHERE j.company_id = ?
    ORDER BY ja.application_date DESC
    LIMIT 5
");
$stmt->execute([$company_id]);
$recentApplications = $stmt->fetchAll();

// Debug output
echo "<!-- Jobs Count: " . $jobCount . " -->";
echo "<!-- Total Applications: " . $totalApplications . " -->";

// Debug user data
echo "<!-- Debug: user data = " . print_r($user, true) . " -->";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employer Dashboard - HireUP</title>
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

        .company-header {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .company-header h2 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .stats-card {
            background: white;
            padding: 2rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            position: relative;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stats-card p {
            color: var(--secondary-color);
            margin: 0;
            font-size: 0.9rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-card .stats-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            opacity: 0.1;
            color: var(--accent-color);
            transition: none;
        }

        .company-section {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .company-section:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .company-section h4 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .company-about {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid var(--accent-color);
        }

        .recent-applications {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .recent-applications:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
        }

        .recent-applications h4 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table {
            margin-bottom: 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
        }

        .table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            border: none;
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .table th:first-child {
            text-align: left;
        }

        .table th:last-child {
            text-align: center;
        }

        .table td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
            font-weight: 500;
            background: white;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .table td:first-child {
            font-weight: 600;
            color: var(--primary-color);
        }

        .table-hover tbody tr {
            transition: all 0.3s ease;
        }

        .table-hover tbody tr:hover {
            background: #f8fafc;
        }

        .table-hover tbody tr:hover td {
            background: transparent;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .job-title-cell {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .applicant-cell {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-sm:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 8px rgba(44, 62, 80, 0.2);
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: none;
        }

        .badge-warning {
            background: #f39c12;
            color: white;
        }

        .badge-success {
            background: #27ae60;
            color: white;
        }

        .badge-danger {
            background: #e74c3c;
            color: white;
        }

        .badge-info {
            background: var(--accent-color);
            color: white;
        }

        .alert {
            border-radius: 8px;
            padding: 1rem 1.5rem;
            border: 1px solid transparent;
            font-size: 0.95rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            color: white;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: #1a252f;
            color: white;
            text-decoration: none;
        }

        .btn-warning, .btn-info {
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .btn-info {
            background: var(--accent-color);
            color: white;
        }

        .btn-info:hover {
            background: #2980b9;
        }

        .btn-outline-primary {
            color: var(--accent-color);
            border: 1px solid var(--accent-color);
            background: transparent;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-outline-primary:hover {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            text-decoration: none;
        }

        .verification-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .rating-display {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rating-display .fas {
            color: #ffd700;
            font-size: 1.1rem;
            filter: drop-shadow(0 1px 3px rgba(255, 215, 0, 0.3));
        }

        /* Responsive enhancements */
        @media (max-width: 768px) {
            .main-content {
                padding: 1.5rem 1rem;
            }
            
            .company-header {
                padding: 1.5rem;
            }
            
            .company-header h2 {
                font-size: 1.5rem;
            }
            
            .stats-card, .company-section, .recent-applications {
                padding: 1.5rem;
            }
            
            .stats-card h3 {
                font-size: 2rem;
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
                    <a class="nav-link active" href="dashboard.php">
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
                <!-- Company Header -->
                <div class="company-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap">
                        <div class="d-flex align-items-center">
                            <h2>
                                <i class="fas fa-building mr-2"></i>
                                <?php echo htmlspecialchars($user['company_name'] ?? $user['username']); ?>
                            </h2>
                            <div class="verification-badge ml-3">
                                <?php if ($user['is_verified']): ?>
                                    <i class="fas fa-check-circle text-primary" title="Verified Company"></i>
                                    <span class="text-muted small">Verified</span>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-muted" title="Not Verified"></i>
                                    <span class="text-muted small">Not Verified</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <?php if ($user['avg_rating']): ?>
                                <div class="rating-display mr-3">
                                    <div class="d-flex">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= round($user['avg_rating']) ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted ml-2">
                                        (<?php echo number_format($user['avg_rating'], 1); ?> from <?php echo $user['review_count']; ?> reviews)
                                    </small>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$user['is_verified']): ?>
                                <a class="btn btn-outline-primary" href="get-verified.html?verify=1">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Get Verified
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="stats-card text-center">
                            <i class="fas fa-briefcase stats-icon"></i>
                            <h3><?php echo $jobCount; ?></h3>
                            <p>Active Jobs</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stats-card text-center">
                            <i class="fas fa-file-alt stats-icon"></i>
                            <h3><?php echo $totalApplications; ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>
                </div>

                <!-- Company Profile Section -->
                <div class="company-section">
                    <h4><i class="fas fa-building mr-2"></i>Company Profile</h4>
                    <?php if (!$user['company_name']): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Please complete your company profile to start posting jobs.
                            <a href="edit-company.php" class="btn btn-warning ml-3">
                                <i class="fas fa-edit mr-2"></i>Complete Profile
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong style="color: var(--primary); font-weight: 600;">Company Name:</strong>
                                    <p class="mb-2"><?php echo htmlspecialchars($user['company_name']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong style="color: var(--primary); font-weight: 600;">Location:</strong>
                                    <p class="mb-2"><?php echo htmlspecialchars($user['location']); ?></p>
                                </div>
                                <div class="mb-3">
                                    <strong style="color: var(--primary); font-weight: 600;">Website:</strong>
                                    <p class="mb-2">
                                        <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" class="text-primary">
                                            <?php echo htmlspecialchars($user['website']); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong style="color: var(--primary); font-weight: 600;">Description:</strong>
                                    <div class="company-about mt-2">
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($user['description'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- About Company Section -->
                <div class="company-section">
                    <h4><i class="fas fa-info-circle mr-2"></i>About Your Company</h4>
                    <?php if (!empty($user['about'])): ?>
                        <div class="company-about">
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($user['about'])); ?></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No company description available yet.
                            <a href="edit-company.php" class="btn btn-info ml-3">
                                <i class="fas fa-plus mr-2"></i>Add Description
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Applications -->
                <div class="recent-applications">
                    <h4><i class="fas fa-clock mr-2"></i>Recent Applications</h4>
                    <?php if (empty($recentApplications)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            No applications received yet. Start by posting your first job!
                            <a href="post-job.php" class="btn btn-primary ml-3">
                                <i class="fas fa-plus mr-2"></i>Post a Job
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Applicant</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recentApplications as $application): ?>
                                        <tr>
                                            <td>
                                                <div class="job-title-cell">
                                                    <strong style="color: var(--primary); font-size: 1rem;">
                                                        <?php echo htmlspecialchars($application['title']); ?>
                                                    </strong>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <div class="applicant-cell">
                                                    <span style="color: var(--text-primary); font-weight: 500;">
                                                        <?php echo htmlspecialchars($application['full_name'] ?? $application['username']); ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span style="color: var(--text-secondary); font-weight: 500;">
                                                    <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="badge badge-<?php 
                                                    echo $application['status'] == 'pending' ? 'warning' : 
                                                        ($application['status'] == 'accepted' ? 'success' : 
                                                        ($application['status'] == 'rejected' ? 'danger' : 'info')); 
                                                ?>">
                                                    <?php echo ucfirst($application['status']); ?>
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <a href="manage-jobs.php" class="btn btn-primary btn-sm" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                                                    <i class="fas fa-eye mr-1"></i>View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Required Bootstrap JavaScript files -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 