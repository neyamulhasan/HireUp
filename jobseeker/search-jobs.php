<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get categories for filter
$stmt = $pdo->query("SELECT * FROM job_categories");
$categories = $stmt->fetchAll();

// Build search query with application status check and company rating
$query = "
    SELECT 
        j.*, 
        c.company_name, 
        c.is_verified, 
        c.id as company_id,
        cat.category_name,
        (
            SELECT AVG(rating) FROM company_reviews WHERE company_id = c.id
        ) as avg_rating,
        (
            SELECT COUNT(*) FROM company_reviews WHERE company_id = c.id
        ) as review_count,
        CASE 
            WHEN ja.id IS NOT NULL THEN 1
            ELSE 0
        END as has_applied
    FROM jobs j 
    JOIN companies c ON j.company_id = c.id 
    JOIN job_categories cat ON j.category_id = cat.id 
    LEFT JOIN job_applications ja ON j.id = ja.job_id AND ja.user_id = ?
    WHERE 1=1
";

$params = [$_SESSION['user_id']];

if (isset($_GET['category']) && !empty($_GET['category'])) {
    $query .= " AND j.category_id = ?";
    $params[] = $_GET['category'];
}

if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ?)";
    $params[] = "%".$_GET['keyword']."%";
    $params[] = "%".$_GET['keyword']."%";
}

$query .= " ORDER BY j.posted_date DESC";

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Jobs - HireUP</title>
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
            --success: #27ae60;
            --success-dark: #219a52;
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

        .search-section {
            background: transparent;
            padding: 0;
            margin-bottom: 24px;
        }

        .search-container {
            background: rgba(30, 41, 59, 0.75);
            border-radius: 14px;
            padding: 24px 20px 18px 20px;
            box-shadow: 0 2px 12px rgba(44, 62, 80, 0.12);
            border: 1.5px solid rgba(44, 62, 80, 0.18);
            margin-bottom: 0;
            color: #f3f6fb;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .search-container input.form-control,
        .search-container select.form-control {
            background: rgba(255,255,255,0.10);
            color: #f3f6fb;
            border: 1px solid rgba(255,255,255,0.18);
        }

        .search-container input.form-control::placeholder {
            color: #cbd5e1;
            opacity: 1;
        }

        .search-container .btn-search,
        .search-container .btn-back {
            color: #fff;
        }

        .search-container h2,
        .search-container label,
        .search-container .fa-search {
            color: #fff !important;
        }

        .form-control {
            border: 1px solid var(--border-color);
            padding: 10px 15px;
            border-radius: 8px;
            height: auto;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
        }

        .btn-search {
            background: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-search:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .btn-back {
            background: rgba(255,255,255,0.85) !important;
            color: var(--primary) !important;
            border: 1.5px solid var(--primary);
            font-weight: 600;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
            transition: background 0.2s, color 0.2s;
        }

        .btn-back:hover {
            background: var(--primary) !important;
            color: #fff !important;
            border-color: var(--primary-dark);
        }

        .job-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .job-card .card-body {
            padding: 20px;
        }

        .applied-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--primary-dark); /* Using primary-dark for the badge to distinguish slightly */
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            z-index: 1;
        }

        .job-meta {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin: 15px 0;
        }

        .job-meta i {
            width: 18px;
            margin-right: 6px;
            color: var(--primary);
        }

        .btn-apply {
            background: var(--primary); /* Consistent with other primary buttons */
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-apply:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .btn-details {
            background: var(--background);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-details:hover {
            background: var(--border-color);
        }

        .btn-success {
            background: var(--success);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            color: white;
            transition: all 0.3s ease;
            flex-grow: 1;
        }

        .btn-success:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .btn-success:disabled {
            background: var(--success);
            opacity: 0.8;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .job-details {
            background: var(--background);
            border-radius: 8px;
            margin-top: 15px;
            padding: 15px;
        }

        .alert {
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .section-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .company-name {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 10px;
        }

        .job-title {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 10px;
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
                    <a class="nav-link" href="edit-profile.php">
                        <i class="fas fa-user"></i> Edit Profile
                    </a>
                    <a class="nav-link active" href="search-jobs.php">
                        <i class="fas fa-briefcase"></i> Browse Jobs
                    </a>
                    <a class="nav-link" href="articles.php">
                        <i class="fas fa-newspaper"></i> Articles
                    </a>
                    <a class="nav-link" href="manage-reviews.php">
                        <i class="fas fa-star mr-2"></i> Manage Reviews
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="search-section">
                    <div class="container">
                        <div class="search-container mb-0">
                            <div class="d-flex align-items-center mb-3" style="gap: 10px;">
                                <i class="fas fa-search fa-lg text-primary"></i>
                                <h2 class="mb-0" style="font-size:1.5rem; font-weight:600; color:var(--primary); letter-spacing:0.5px;">Job Feed</h2>
                            </div>
                            <form method="GET" action="" class="mb-0">
                                <div class="row align-items-center">
                                    <div class="col-md-5 mb-3 mb-md-0">
                                        <input type="text" name="keyword" class="form-control" 
                                               placeholder="Search jobs by title or description" 
                                               value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3 mb-md-0">
                                        <select name="category" class="form-control">
                                            <option value="">All Categories</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-search w-100 mb-2">
                                            <i class="fas fa-search mr-2"></i> Search
                                        </button>
                                        <a href="dashboard.php" class="btn btn-back w-100">
                                            <i class="fas fa-arrow-left mr-2"></i> Dashboard
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="container">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $success; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo $error; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <?php if (empty($jobs)): ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    No jobs found matching your criteria.
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php foreach($jobs as $job): ?>
                            <div class="col-md-6">
                                <div class="job-card">
                                    <?php if ($job['has_applied']): ?>
                                        <div class="applied-badge">
                                            <i class="fas fa-check-circle mr-1"></i> Applied
                                        </div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="job-title">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </h5>
                                        <h6 class="company-name">
                                            <i class="fas fa-building"></i>
                                            <a href="company-profile.php?company_id=<?php echo $job['company_id']; ?>&ref=search-jobs" style="color:inherit; text-decoration:underline; font-weight:600;" title="Click to view company profile">
                                                <i class="fas fa-external-link-alt" style="font-size: 0.8rem; margin-right: 0.3rem; opacity: 0.8;"></i>
                                                <?php echo htmlspecialchars($job['company_name']); ?>
                                            </a>
                                            <?php if ($job['is_verified']): ?>
                                                <i class="fas fa-check-circle text-primary ml-1" title="Verified Company"></i>
                                            <?php endif; ?>
                                            <?php if ($job['avg_rating']): ?>
                                                <span class="ml-2 align-middle" title="Company Rating">
                                                    <span class="text-warning">
                                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star<?php echo $i <= round($job['avg_rating']) ? '' : '-o'; ?>"></i>
                                                        <?php endfor; ?>
                                                    </span>
                                                    <small class="text-muted ml-1" style="font-size:0.95em;">(<?php echo number_format($job['avg_rating'], 1); ?><?php if ($job['review_count']): ?>, <?php echo $job['review_count']; ?> reviews<?php endif; ?>)</small>
                                                </span>
                                            <?php endif; ?>
                                        </h6>
                                        
                                        <div class="job-meta">
                                            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></p>
                                            <p><i class="fas fa-tags"></i> <?php echo htmlspecialchars($job['category_name']); ?></p>
                                            <p><i class="fas fa-clock"></i> <?php echo ucfirst(htmlspecialchars($job['job_type'])); ?></p>
                                            <p><i class="fas fa-money-bill-wave"></i> <?php echo htmlspecialchars($job['salary_range']); ?></p>
                                        </div>

                                        <div class="d-flex mt-3">
                                            <?php if ($job['has_applied']): ?>
                                                <button class="btn btn-success mr-2" disabled>
                                                    <i class="fas fa-check mr-1"></i> Applied
                                                </button>
                                            <?php else: ?>
                                                <a href="apply-job.php?job_id=<?php echo $job['id']; ?>" 
                                                   class="btn btn-apply mr-2"
                                                   onclick="return confirm('Are you sure you want to apply for this position?');">
                                                    <i class="fas fa-paper-plane mr-1"></i> Apply Now
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-details" 
                                                    data-toggle="collapse" 
                                                    data-target="#jobDetails<?php echo $job['id']; ?>">
                                                <i class="fas fa-info-circle mr-1"></i> Details
                                            </button>
                                        </div>

                                        <div class="collapse mt-3" id="jobDetails<?php echo $job['id']; ?>">
                                            <div class="job-details">
                                                <h6 class="font-weight-bold mb-3">Job Description:</h6>
                                                <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                                                <h6 class="font-weight-bold mb-3">Requirements:</h6>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($job['requirements'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 