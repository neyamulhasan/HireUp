<?php
session_start();
require_once '../config/database.php';

// Add this after session_start() and database connection
try {
    // Test database connection
    $pdo->query("SELECT 1");
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $test_user = $stmt->fetch();
    if (!$test_user) {
        die("User not found in database");
    }
    
    // Check user type
    if ($test_user['user_type'] !== 'jobseeker') {
        die("Invalid user type: " . $test_user['user_type']);
    }
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fetch jobseeker's profile
$stmt = $pdo->prepare("
    SELECT u.*, jp.* 
    FROM users u 
    LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Fetch jobseeker's applications with job details
$stmt = $pdo->prepare("
    SELECT 
        ja.*,
        j.title,
        j.job_type,
        j.location,
        j.salary_range,
        c.company_name,
        jc.category_name
    FROM job_applications ja
    LEFT JOIN jobs j ON ja.job_id = j.id
    LEFT JOIN companies c ON j.company_id = c.id
    LEFT JOIN job_categories jc ON j.category_id = jc.id
    WHERE ja.user_id = ?
    ORDER BY ja.application_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll();

// Get some statistics
$total_applications = count($applications);
$pending_applications = 0;
$accepted_applications = 0;
$rejected_applications = 0;

foreach ($applications as $app) {
    switch ($app['status']) {
        case 'pending': $pending_applications++; break;
        case 'accepted': $accepted_applications++; break;
        case 'rejected': $rejected_applications++; break;
    }
}

// Fetch jobseeker's reviews
$stmt = $pdo->prepare("
    SELECT cr.*, c.company_name, c.is_verified
    FROM company_reviews cr
    JOIN companies c ON cr.company_id = c.id
    WHERE cr.user_id = ?
    ORDER BY cr.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$user_reviews = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - HireUP</title>
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
            min-height: 100vh;
        }

        .main-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(44, 62, 80, 0.08);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
            position: relative;
            overflow: hidden;
        }

        .main-content h2::before {
            content: "\f015";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 1.8rem;
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .main-content h2::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
        }

        .stats-card {
            background: white;
            padding: 2rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(44, 62, 80, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: left;
        }

        .stats-card:hover::before {
            transform: scaleX(1);
        }

        .stats-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 8px 30px rgba(44, 62, 80, 0.15);
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stats-card p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stats-card .stats-icon {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 2rem;
            opacity: 0.1;
            color: var(--primary);
        }

        .application-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(44, 62, 80, 0.08);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .application-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .application-card:hover::before {
            opacity: 1;
        }

        .application-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(44, 62, 80, 0.15);
        }

        .application-card h5 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
        }

        .application-card h6 {
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .application-card h6::before {
            content: "\f1ad";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin-right: 0.5rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .profile-section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(44, 62, 80, 0.08);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .profile-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
        }

        .profile-section h4 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0f1b24 0%, #1a2930 100%);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            box-shadow: 0 3px 12px rgba(15, 27, 36, 0.5);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #0a141b 0%, #15212a 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(15, 27, 36, 0.7);
            color: white;
            text-decoration: none;
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .review-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(44, 62, 80, 0.08);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .review-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #ffd700 0%, #ff6b35 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .review-card:hover::before {
            opacity: 1;
        }

        .review-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(44, 62, 80, 0.15);
        }

        .review-card h5 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 0.8rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .rating .fas {
            color: #ffd700;
            margin-right: 0.2rem;
            font-size: 1.1rem;
            filter: drop-shadow(0 1px 3px rgba(255, 215, 0, 0.3));
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem 2rem;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .card-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }

        .card-header:hover::before {
            left: 100%;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.3rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .alert {
            border-radius: 15px;
            padding: 1.2rem 1.5rem;
            border: none;
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(52, 152, 219, 0.05) 100%);
            color: var(--primary);
            font-size: 1.05rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: 0 2px 10px rgba(52, 152, 219, 0.1);
            border: 1px solid rgba(52, 152, 219, 0.2);
        }

        .alert::before {
            content: "\f05a";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 1.2rem;
            color: var(--primary);
            margin-right: 0.5rem;
        }

        .alert-link {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .alert-link:hover {
            border-bottom-color: var(--primary);
            text-decoration: none;
            color: var(--primary-dark);
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .badge-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            color: white;
        }

        .badge-info {
            background: linear-gradient(135deg, var(--accent) 0%, #5dade2 100%);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%);
            color: white;
        }

        .badge-success {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }

        .badge-danger {
            background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%);
            color: white;
        }

        /* Skills badges in profile section */
        .badge-info.skill-badge {
            background: rgba(52,152,219,0.12);
            color: var(--primary);
            border-radius: 20px;
            padding: 0.6rem 1.2rem;
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0.3rem 0.3rem 0.3rem 0;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(52,152,219,0.2);
        }

        .badge-info.skill-badge:hover {
            background: rgba(52,152,219,0.2);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(52,152,219,0.2);
        }

        /* Section headers enhancement */
        .main-content h4 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 12px;
            border-left: 4px solid var(--accent);
            box-shadow: 0 2px 10px rgba(44, 62, 80, 0.06);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }



        /* Responsive enhancements */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem 1rem;
            }
            
            .sidebar {
                display: none;
            }
            
            .main-content h2 {
                font-size: 2rem;
                padding: 1rem 1.5rem;
            }
            
            .stats-card, .application-card, .review-card, .profile-section {
                padding: 1.5rem;
            }
            
            .stats-card h3 {
                font-size: 2rem;
            }
            
            .btn-primary {
                width: 100%;
                justify-content: center;
                margin-bottom: 1rem;
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
                    <a class="nav-link" href="edit-profile.php">
                        <i class="fas fa-user"></i> Edit Profile
                    </a>
                    <a class="nav-link" href="search-jobs.php">
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
                <!-- Profile Summary -->
                <div class="profile-section">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <h4><i class="fas fa-user-circle mr-2"></i><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h4>
                                <a href="../tools/generate-resume.php" class="btn btn-primary mb-2 mb-md-0">
                                    <i class="fas fa-download mr-2"></i>Download Resume
                                </a>
                            </div>
                            <?php if (!empty($user['skills'])): ?>
                                <div class="mt-3 w-100">
                                    <strong style="color: var(--primary); font-weight: 600; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                                        <i class="fas fa-cogs"></i>Skills:
                                    </strong>
                                    <div class="skills-container">
                                    <?php
                                        $skills = array_filter(array_map('trim', explode(',', $user['skills'])));
                                        sort($skills, SORT_NATURAL | SORT_FLAG_CASE);
                                        foreach ($skills as $skill):
                                    ?>
                                        <span class="badge badge-info skill-badge">
                                            <?php echo htmlspecialchars($skill); ?>
                                        </span>
                                    <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (empty($user['full_name']) || empty($user['skills'])): ?>
                                <div class="mt-3">
                                    <a href="edit-profile.php" class="btn btn-primary">
                                        <i class="fas fa-user-edit"></i>
                                        Complete Your Profile
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-paper-plane stats-icon"></i>
                            <h3><?php echo $total_applications; ?></h3>
                            <p>Total Applications</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-clock stats-icon"></i>
                            <h3><?php echo $pending_applications; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-check-circle stats-icon"></i>
                            <h3><?php echo $accepted_applications; ?></h3>
                            <p>Accepted</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card text-center">
                            <i class="fas fa-times-circle stats-icon"></i>
                            <h3><?php echo $rejected_applications; ?></h3>
                            <p>Rejected</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Applications -->
                <h4 class="mb-3">Your Recent Applications</h4>
                <?php if (empty($applications)): ?>
                    <div class="alert alert-info">
                        You haven't applied to any jobs yet. 
                        <a href="search-jobs.php" class="alert-link">Start searching for jobs!</a>
                    </div>
                <?php else: ?>
                    <?php foreach($applications as $application): ?>
                        <div class="application-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5><?php echo htmlspecialchars($application['title']); ?></h5>
                                    <h6 class="text-muted">
                                        <?php echo htmlspecialchars($application['company_name']); ?> - 
                                        <?php echo htmlspecialchars($application['location']); ?>
                                    </h6>
                                </div>
                                <span class="badge badge-<?php 
                                    echo $application['status'] == 'pending' ? 'warning' : 
                                        ($application['status'] == 'accepted' ? 'success' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($application['status']); ?>
                                </span>
                            </div>
                            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                                <span class="badge badge-primary"><?php echo $application['category_name']; ?></span>
                                <span class="badge badge-info"><?php echo ucfirst($application['job_type']); ?></span>
                                <small class="text-muted ml-2">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Applied on: <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Your Reviews Section -->
                <?php if (!empty($user_reviews)): ?>
                    <div class="your-reviews-section mt-5">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0"><i class="fas fa-star mr-2"></i>Your Reviews</h4>
                            </div>
                            <div class="card-body" style="background: white; border-radius: 0 0 15px 15px;">
                                <?php foreach($user_reviews as $review): ?>
                                    <div class="review-card mb-3 p-3 border rounded">                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">
                                <?php echo htmlspecialchars($review['company_name']); ?>
                                <?php if ($review['is_verified']): ?>
                                    <i class="fas fa-check-circle text-primary ml-1" title="Verified Company"></i>
                                <?php endif; ?>
                            </h5>
                                            <small class="text-muted">
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="rating mb-2">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? ' text-warning' : ' text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ml-2 text-muted">(<?php echo $review['rating']; ?>/5)</span>
                                        </div>
                                        <p class="mb-0" style="background: #f8fafc; padding: 1rem; border-radius: 8px; border-left: 3px solid #ffd700;"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 