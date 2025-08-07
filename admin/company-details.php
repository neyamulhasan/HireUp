<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get company ID from URL
$company_id = $_GET['id'] ?? 0;

// Handle verification toggle
if (isset($_POST['toggle_verification'])) {
    try {
        $stmt = $pdo->prepare("UPDATE companies SET is_verified = NOT is_verified WHERE id = ?");
        $stmt->execute([$company_id]);
        
        // Redirect to refresh the page
        header("Location: company-details.php?id=" . $company_id);
        exit();
    } catch(PDOException $e) {
        $error = "Error updating verification status";
    }
}

// Fetch company details with stats
$stmt = $pdo->prepare("
    SELECT 
        c.*,
        u.username,
        (SELECT COUNT(*) FROM jobs WHERE company_id = c.id) as job_count,
        (SELECT COUNT(*) FROM job_applications ja 
         JOIN jobs j ON ja.job_id = j.id 
         WHERE j.company_id = c.id) as application_count,
        (SELECT AVG(rating) FROM company_reviews WHERE company_id = c.id) as avg_rating,
        (SELECT COUNT(*) FROM company_reviews WHERE company_id = c.id) as review_count
    FROM companies c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: dashboard.php");
    exit();
}

// Fetch company's jobs
$stmt = $pdo->prepare("
    SELECT j.*, 
           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
    FROM jobs j
    WHERE j.company_id = ?
    ORDER BY j.id DESC
");
$stmt->execute([$company_id]);
$jobs = $stmt->fetchAll();

// Fetch company reviews
$stmt = $pdo->prepare("
    SELECT cr.*, u.username
    FROM company_reviews cr
    JOIN users u ON cr.user_id = u.id
    WHERE cr.company_id = ?
    ORDER BY cr.created_at DESC
");
$stmt->execute([$company_id]);
$reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Company Details - HireUP Admin</title>
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
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
        }

        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gradient-primary);
            color: var(--text-primary);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
            z-index: 0;
        }

        .main-content {
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .back-btn {
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
        }

        .back-btn:hover {
            background: white;
            transform: translateY(-1px);
            color: var(--primary);
            text-decoration: none;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .info-card h4 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .verified-badge {
            color: var(--accent);
            font-size: 1.1rem;
            margin-left: 0.5rem;
        }

        .stats-badge {
            background: rgba(255, 255, 255, 0.9);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
            margin-right: 0.8rem;
            color: var(--text-primary);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .stats-badge:hover {
            background: white;
        }

        .header-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .company-name {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
        }

        .verification-btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .verification-btn:hover {
            transform: translateY(-1px);
        }

        .review-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 0.8rem;
            transition: all 0.2s ease;
        }

        .review-card:hover {
            background: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .review-card h6 {
            color: var(--primary);
            font-weight: 600;
        }

        .text-warning {
            color: #f1c40f !important;
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        a {
            color: var(--accent);
            text-decoration: none;
        }

        a:hover {
            color: var(--primary);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }
            .info-card {
                padding: 1rem;
            }
            .header-section {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main Content -->
        <div class="main-content">
            <a href="dashboard.php" class="btn back-btn">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="company-name">
                        <?php echo htmlspecialchars($company['company_name']); ?>
                        <?php if ($company['is_verified']): ?>
                            <i class="fas fa-check-circle verified-badge" title="Verified Company"></i>
                        <?php endif; ?>
                    </h2>
                    <form method="POST" class="d-inline">
                        <button type="submit" name="toggle_verification" 
                                class="btn verification-btn btn-<?php echo $company['is_verified'] ? 'danger' : 'success'; ?>">
                            <?php if ($company['is_verified']): ?>
                                <i class="fas fa-times-circle mr-2"></i>Remove Verification
                            <?php else: ?>
                                <i class="fas fa-check-circle mr-2"></i>Verify This Company
                            <?php endif; ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Company Info -->
            <div class="info-card">
                <h4><i class="fas fa-building mr-2"></i>Company Information</h4>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-user mr-2"></i>Username:</strong> <?php echo htmlspecialchars($company['username']); ?></p>
                        <p><strong><i class="fas fa-map-marker-alt mr-2"></i>Location:</strong> <?php echo htmlspecialchars($company['location']); ?></p>
                        <p><strong><i class="fas fa-globe mr-2"></i>Website:</strong> <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank"><?php echo htmlspecialchars($company['website']); ?></a></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong><i class="fas fa-info-circle mr-2"></i>Description:</strong></p>
                        <p><?php echo nl2br(htmlspecialchars($company['description'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="info-card">
                <h4><i class="fas fa-chart-bar mr-2"></i>Statistics</h4>
                <div class="mt-3">
                    <span class="stats-badge">
                        <i class="fas fa-briefcase mr-1"></i> <?php echo $company['job_count']; ?> Jobs Posted
                    </span>
                    <span class="stats-badge">
                        <i class="fas fa-file-alt mr-1"></i> <?php echo $company['application_count']; ?> Total Applications
                    </span>
                    <?php if ($company['avg_rating']): ?>
                        <span class="stats-badge">
                            <i class="fas fa-star text-warning mr-1"></i> 
                            <?php echo number_format($company['avg_rating'], 1); ?> Average Rating
                        </span>
                        <span class="stats-badge">
                            <i class="fas fa-comment mr-1"></i> <?php echo $company['review_count']; ?> Reviews
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reviews -->
            <div class="info-card">
                <h4><i class="fas fa-star mr-2"></i>Company Reviews</h4>
                <?php if (empty($reviews)): ?>
                    <p class="text-muted">No reviews yet.</p>
                <?php else: ?>
                    <?php foreach($reviews as $review): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between">
                                <h6 class="mb-0"><?php echo htmlspecialchars($review['username']); ?></h6>
                                <div class="text-warning">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                            <small class="text-muted">Posted: <?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
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