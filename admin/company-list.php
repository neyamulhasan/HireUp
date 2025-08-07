<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all companies with their stats
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
    ORDER BY c.company_name ASC
");
$stmt->execute();
$companies = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Company List - HireUP Admin</title>
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

        .sidebar {
            background: rgba(44, 62, 80, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            min-height: 100vh;
            color: white;
            padding: 2rem;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
        }

        .sidebar h3 {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            margin: 0.5rem 0;
            padding: 0.8rem 1rem;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .main-content {
            padding: 2rem;
            position: relative;
            z-index: 1;
        }

        .main-content h2 {
            color: white;
            font-weight: 700;
            margin-bottom: 2rem;
            font-size: 1.8rem;
        }

        .company-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
        }

        .company-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .company-card h4 {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
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

        .btn-primary {
            background: var(--primary);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                padding: 1rem;
            }
            .main-content {
                padding: 1rem;
            }
            .company-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3>HireUP Admin</h3>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-chart-line mr-2"></i> Website Statistics
                    </a>
                    <a class="nav-link active" href="company-list.php">
                        <i class="fas fa-building mr-2"></i> Companies
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2>Companies</h2>

                <?php if (empty($companies)): ?>
                    <div class="text-center py-5">
                        <div class="company-card">
                            <i class="fas fa-building" style="font-size: 4rem; color: #6c757d; margin-bottom: 1rem;"></i>
                            <h4 style="color: #6c757d; margin-bottom: 1rem;">No Companies Found</h4>
                            <p style="color: #6c757d; font-size: 1.1rem; margin-bottom: 1.5rem;">
                                There are currently no companies registered on the platform.
                            </p>
                            <p style="color: #6c757d;">
                                Companies will appear here once employers start registering and creating their profiles.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach($companies as $company): ?>
                        <div class="company-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0">
                                        <?php echo htmlspecialchars($company['company_name']); ?>
                                        <?php if ($company['is_verified']): ?>
                                            <i class="fas fa-check-circle verified-badge" title="Verified Company"></i>
                                        <?php endif; ?>
                                    </h4>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($company['username']); ?></p>
                                </div>
                                <a href="company-details.php?id=<?php echo $company['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye mr-2"></i>View Details
                                </a>
                            </div>
                            
                            <div class="mt-3">
                                <span class="stats-badge">
                                    <i class="fas fa-briefcase mr-1"></i> <?php echo $company['job_count']; ?> Jobs
                                </span>
                                <span class="stats-badge">
                                    <i class="fas fa-file-alt mr-1"></i> <?php echo $company['application_count']; ?> Applications
                                </span>
                                <?php if ($company['avg_rating']): ?>
                                    <span class="stats-badge">
                                        <i class="fas fa-star text-warning mr-1"></i> 
                                        <?php echo number_format($company['avg_rating'], 1); ?> 
                                        (<?php echo $company['review_count']; ?> reviews)
                                    </span>
                                <?php endif; ?>
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