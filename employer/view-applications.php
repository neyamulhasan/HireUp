<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'employer') {
    header("Location: ../login.php");
    exit();
}

// Get employer's company ID and job details
$stmt = $pdo->prepare("
    SELECT j.*, c.company_name 
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    WHERE j.id = ? AND c.user_id = ?
");
$stmt->execute([$_GET['job_id'], $_SESSION['user_id']]);
$job = $stmt->fetch();

if (!$job) {
    header("Location: manage-jobs.php");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['application_id']) && isset($_POST['status'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE job_applications 
            SET status = ? 
            WHERE id = ? AND job_id = ?
        ");
        $stmt->execute([$_POST['status'], $_POST['application_id'], $job['id']]);
        $success = "Application status updated successfully!";
    } catch(PDOException $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// Fetch applications with complete jobseeker profiles
$stmt = $pdo->prepare("
    SELECT 
        ja.*,
        u.username,
        u.email,
        jp.*
    FROM job_applications ja
    JOIN users u ON ja.user_id = u.id
    LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id
    WHERE ja.job_id = ?
    ORDER BY 
        CASE ja.status
            WHEN 'pending' THEN 1
            WHEN 'reviewed' THEN 2
            WHEN 'accepted' THEN 3
            WHEN 'rejected' THEN 4
        END,
        ja.application_date DESC
");
$stmt->execute([$job['id']]);
$applications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Applications - <?php echo htmlspecialchars($job['title']); ?></title>
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
            background-color: #f5f7fa;
            color: var(--text-color);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            padding: 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.1);
        }

        .page-header h2 {
            font-weight: 600;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #ffffff;
        }

        .page-header p {
            opacity: 0.9;
            margin: 0;
            color: #ecf0f1;
        }

        .page-header i {
            color: #3498db;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: transform 0.2s ease;
        }

        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .profile-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-body {
            padding: 1.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .profile-section {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .profile-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .profile-section h5 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }

        .profile-section h5 i {
            margin-right: 0.5rem;
            color: var(--accent-color);
        }

        .action-buttons {
            padding: 1.5rem;
            background-color: var(--light-bg);
            border-top: 1px solid var(--border-color);
            border-radius: 0 0 12px 12px;
        }

        .btn-back {
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            transition: all 0.3s ease;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            border-radius: 6px;
        }

        .btn-back:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .btn-download {
            background-color: var(--accent-color);
            border: none;
            color: white;
            transition: all 0.3s ease;
            padding: 0.6rem 1.2rem;
            font-weight: 500;
            border-radius: 6px;
        }

        .btn-download:hover {
            background-color: #2980b9;
            color: white;
        }

        .status-select {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            background-color: white;
            transition: all 0.3s ease;
            color: var(--text-color);
        }

        .status-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }

        .applicant-name {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .applicant-email {
            color: var(--secondary-color);
        }

        .application-date {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-reviewed {
            background-color: var(--accent-color);
            color: white;
        }

        .badge-accepted {
            background-color: #28a745;
            color: white;
        }

        .badge-rejected {
            background-color: #dc3545;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-2"><?php echo htmlspecialchars($job['title']); ?></h2>
                <p class="mb-0">
                    <i class="fas fa-building mr-2"></i> <?php echo htmlspecialchars($job['company_name']); ?> | 
                    <i class="fas fa-users ml-2 mr-2"></i> <?php echo count($applications); ?> Applications
                </p>
            </div>
            <a href="manage-jobs.php" class="btn btn-back">
                <i class="fas fa-arrow-left mr-2"></i> Back to Jobs
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>

        <?php if (empty($applications)): ?>
            <div class="empty-state">
                <i class="fas fa-file-alt"></i>
                <h3>No Applications Yet</h3>
                <p>You haven't received any applications for this job posting yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($applications as $application): ?>
                <div class="profile-card">
                    <div class="profile-header d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="applicant-name mb-1"><?php echo htmlspecialchars($application['full_name'] ?? $application['username']); ?></h4>
                            <p class="applicant-email mb-0">
                                <i class="fas fa-envelope mr-2"></i> <?php echo htmlspecialchars($application['email']); ?>
                            </p>
                            <small class="application-date">
                                <i class="far fa-calendar-alt mr-1"></i> Applied on <?php echo date('F j, Y', strtotime($application['application_date'])); ?>
                            </small>
                        </div>
                        <span class="badge status-badge badge-<?php 
                            echo $application['status'] == 'pending' ? 'pending' : 
                                ($application['status'] == 'accepted' ? 'accepted' : 
                                ($application['status'] == 'rejected' ? 'rejected' : 'reviewed')); 
                        ?>">
                            <?php echo ucfirst($application['status']); ?>
                        </span>
                    </div>

                    <div class="profile-body">
                        <?php if ($application['skills']): ?>
                            <div class="profile-section">
                                <h5><i class="fas fa-tools"></i> Skills</h5>
                                <p><?php echo nl2br(htmlspecialchars($application['skills'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($application['experience']): ?>
                            <div class="profile-section">
                                <h5><i class="fas fa-briefcase"></i> Experience</h5>
                                <p><?php echo nl2br(htmlspecialchars($application['experience'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($application['education']): ?>
                            <div class="profile-section">
                                <h5><i class="fas fa-graduation-cap"></i> Education</h5>
                                <p><?php echo nl2br(htmlspecialchars($application['education'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($application['resume_path']) && $application['resume_path']): ?>
                            <div class="profile-section">
                                <h5><i class="fas fa-file-alt"></i> Resume</h5>
                                <a href="../tools/generate-resume.php?user_id=<?php echo $application['user_id']; ?>" 
                                   class="btn btn-download" target="_blank">
                                    <i class="fas fa-download mr-2"></i> Download Resume
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="action-buttons">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                            <div class="d-flex align-items-center">
                                <label for="status-<?php echo $application['id']; ?>" class="mr-2 mb-0">Update Status:</label>
                                <select id="status-<?php echo $application['id']; ?>" name="status" class="status-select" 
                                        onchange="this.form.submit()">
                                    <option value="pending" <?php echo $application['status'] == 'pending' ? 'selected' : ''; ?>>
                                        Pending
                                    </option>
                                    <option value="reviewed" <?php echo $application['status'] == 'reviewed' ? 'selected' : ''; ?>>
                                        Reviewed
                                    </option>
                                    <option value="accepted" <?php echo $application['status'] == 'accepted' ? 'selected' : ''; ?>>
                                        Accepted
                                    </option>
                                    <option value="rejected" <?php echo $application['status'] == 'rejected' ? 'selected' : ''; ?>>
                                        Rejected
                                    </option>
                                </select>
                                <a href="../tools/generate-resume.php?user_id=<?php echo $application['user_id']; ?>" 
                                   class="btn btn-download ml-3" target="_blank">
                                    <i class="fas fa-download mr-2"></i> Download Resume
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 