<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch overall statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'jobseekers' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'jobseeker'")->fetchColumn(),
    'employers' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'employer'")->fetchColumn(),
    'total_jobs' => $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn(),
    'total_applications' => $pdo->query("SELECT COUNT(*) FROM job_applications")->fetchColumn(),
    'accepted_applications' => $pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'accepted'")->fetchColumn(),
    'rejected_applications' => $pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'rejected'")->fetchColumn(),
    'pending_applications' => $pdo->query("SELECT COUNT(*) FROM job_applications WHERE status = 'pending'")->fetchColumn(),
    'verified_companies' => $pdo->query("SELECT COUNT(*) FROM companies WHERE is_verified = 1")->fetchColumn(),
    'total_companies' => $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn(),
    'total_reviews' => $pdo->query("SELECT COUNT(*) FROM company_reviews")->fetchColumn(),
    'avg_rating' => $pdo->query("SELECT AVG(rating) FROM company_reviews")->fetchColumn()
];

// Fetch monthly job postings
$monthly_jobs = $pdo->query("
    SELECT DATE_FORMAT(posted_date, '%Y-%m') as month, COUNT(*) as count 
    FROM jobs 
    GROUP BY DATE_FORMAT(posted_date, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
")->fetchAll();

// Fetch job categories distribution
$category_distribution = $pdo->query("
    SELECT c.category_name, COUNT(j.id) as count
    FROM job_categories c
    LEFT JOIN jobs j ON c.id = j.category_id
    GROUP BY c.id
    ORDER BY count DESC
")->fetchAll();

// Fetch application status distribution
$application_status = $pdo->query("
    SELECT status, COUNT(*) as count
    FROM job_applications
    GROUP BY status
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Website Statistics - HireUP Admin</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stats-card {
            background: rgba(255, 255, 255, 0.7);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.8);
        }

        .stats-card h4 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .stat-item:hover {
            background: rgba(255, 255, 255, 0.7);
            transform: translateX(5px);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .stat-info h5 {
            margin: 0;
            font-weight: 600;
            color: var(--primary);
        }

        .stat-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                padding: 1rem;
            }
            .main-content {
                padding: 1rem;
            }
            .stats-card {
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
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-chart-line mr-2"></i> Website Statistics
                    </a>
                    <a class="nav-link" href="company-list.php">
                        <i class="fas fa-building mr-2"></i> Companies
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <h2>Website Statistics</h2>

                <!-- Overview Stats -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stats-card">
                            <h4>User Statistics</h4>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['total_users']; ?></h5>
                                    <p>Total Users</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['jobseekers']; ?></h5>
                                    <p>Jobseekers</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['employers']; ?></h5>
                                    <p>Employers</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="stats-card">
                            <h4>Job Statistics</h4>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-briefcase"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['total_jobs']; ?></h5>
                                    <p>Total Jobs Posted</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['total_applications']; ?></h5>
                                    <p>Total Applications</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['accepted_applications']; ?></h5>
                                    <p>Accepted Applications</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="stats-card">
                            <h4>Company Statistics</h4>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['total_companies']; ?></h5>
                                    <p>Total Companies</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo $stats['verified_companies']; ?></h5>
                                    <p>Verified Companies</p>
                                </div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-info">
                                    <h5><?php echo number_format($stats['avg_rating'], 1); ?></h5>
                                    <p>Average Company Rating</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h4>Monthly Job Postings</h4>
                            <canvas id="monthlyJobsChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container">
                            <h4>Application Status Distribution</h4>
                            <canvas id="applicationStatusChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="chart-container">
                            <h4>Job Categories Distribution</h4>
                            <canvas id="categoryDistributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        // Monthly Jobs Chart
        new Chart(document.getElementById('monthlyJobsChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($monthly_jobs), 'month')); ?>,
                datasets: [{
                    label: 'Jobs Posted',
                    data: <?php echo json_encode(array_column(array_reverse($monthly_jobs), 'count')); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Application Status Chart
        new Chart(document.getElementById('applicationStatusChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($application_status, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($application_status, 'count')); ?>,
                    backgroundColor: [
                        '#3498db',
                        '#2ecc71',
                        '#e74c3c',
                        '#f1c40f'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Category Distribution Chart
        new Chart(document.getElementById('categoryDistributionChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($category_distribution, 'category_name')); ?>,
                datasets: [{
                    label: 'Jobs per Category',
                    data: <?php echo json_encode(array_column($category_distribution, 'count')); ?>,
                    backgroundColor: '#3498db'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html> 