<?php
session_start();
require_once 'config/database.php';

// Fetch some stats for the homepage
try {
    $jobCount = $pdo->query("SELECT COUNT(*) FROM jobs")->fetchColumn();
    $companyCount = $pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn();
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM job_categories")->fetchColumn();
    
    // Get latest jobs
    $latestJobs = $pdo->query("
        SELECT j.*, c.company_name, cat.category_name 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        JOIN job_categories cat ON j.category_id = cat.id 
        ORDER BY j.posted_date DESC LIMIT 6
    ")->fetchAll();
} catch(PDOException $e) {
    // Handle error silently
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>HireUP - Find Your Dream Job</title>
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
            --success: #27ae60;
            --info: #3498db;
            --warning: #f39c12;
            --danger: #e74c3c;
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --gradient-hover: linear-gradient(135deg, #1a252f 0%, #2980b9 100%);
        }

        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600;700&display=swap');

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        .hero {
            background: var(--gradient-primary);
            padding: 160px 0 140px;
            position: relative;
            overflow: hidden;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
            margin-bottom: 40px;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .hero h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: white;
        }

        .hero p {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            color: white;
        }

        .hero .btn-light {
            font-family: 'Poppins', sans-serif;
            padding: 1rem 2rem;
            font-weight: 600;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            background: white;
            color: var(--primary);
        }

        .hero .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
            color: var(--primary-dark);
        }

        .navbar {
            background: #2c3e50;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            padding: 0.8rem 0;
            background: #2c3e50;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
        }

        .navbar-brand {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
        }

        .nav-link {
            font-family: 'Inter', sans-serif;
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 0.6rem 1rem !important;
            margin: 0 0.2rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
        }

        .navbar-toggler {
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 0.5rem;
        }

        .navbar-toggler:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .stats {
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            margin-top: -120px;
            position: relative;
            z-index: 2;
            padding: 25px;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            border-right: 1px solid rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            position: relative;
        }

        .stat-item:last-child {
            border-right: none;
        }

        .stat-item:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.3);
        }

        .stat-item i {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: var(--primary);
            opacity: 0.9;
        }

        .stat-item h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .stat-item p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            font-weight: 500;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .job-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            height: 100%;
            background: white;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
            border-color: var(--primary);
        }

        .job-card .card-body {
            padding: 1.5rem;
        }

        .job-card .card-title {
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .job-card .card-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .job-card .card-text {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-weight: 500;
            margin-right: 0.5rem;
            font-size: 0.85rem;
        }

        .badge-primary {
            background: rgba(44, 62, 80, 0.1);
            color: var(--primary);
        }

        .badge-info {
            background: rgba(52, 152, 219, 0.1);
            color: var(--accent);
        }

        .latest-jobs {
            padding: 80px 0;
            background: var(--background);
        }

        .latest-jobs h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2.5rem;
            text-align: center;
            color: var(--text-primary);
        }

        .cta-section {
            background: var(--gradient-primary);
            padding: 80px 0;
            color: white;
            margin-top: 60px;
            clip-path: polygon(0 10%, 100% 0, 100% 100%, 0 100%);
        }

        .cta-section h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .btn-cta {
            font-family: 'Poppins', sans-serif;
            background: white;
            color: var(--primary);
            padding: 0.8rem 2rem;
            border-radius: 6px;
            font-weight: 600;
            margin: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: white;
            color: var(--primary-dark);
        }

        footer {
            background: var(--text-primary);
            color: white;
            padding: 60px 0 30px;
            margin-top: 60px;
        }

        .footer-links h5 {
            font-family: 'Poppins', sans-serif;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .footer-links ul {
            list-style: none;
            padding: 0;
        }

        .footer-links ul li {
            margin-bottom: 12px;
        }

        .footer-links ul li a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }

        .footer-links ul li a:hover {
            color: white;
        }

        @media (max-width: 768px) {
            .hero {
                padding: 120px 0 80px;
            }

            .hero h1 {
                font-size: 2.2rem;
            }

            .stats {
                margin-top: -30px;
                padding: 15px;
            }

            .stat-item {
                border-right: none;
                border-bottom: 1px solid var(--border-color);
                padding: 12px;
            }

            .stat-item:last-child {
                border-bottom: none;
            }

            .stat-item i {
                font-size: 1.5rem;
            }

            .stat-item h3 {
                font-size: 1.5rem;
            }

            .stat-item p {
                font-size: 0.8rem;
            }

            .cta-section {
                clip-path: none;
            }
        }
    </style>

    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">HireUP</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="jobseeker/search-jobs.php">Find Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employer/post-job.php">Post a Job</a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $_SESSION['user_type'] == 'employer' ? 'employer' : 'jobseeker'; ?>/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1>Find Your Dream Job Today</h1>
                    <p class="lead">Connect with top employers and discover opportunities that match your skills and aspirations.</p>
                    <a href="register.php" class="btn btn-light btn-lg">Get Started</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="container">
        <div class="stats">
            <div class="row">
                <div class="col-md-4 stat-item">
                    <i class="fas fa-briefcase"></i>
                    <h3><?php echo number_format($jobCount); ?></h3>
                    <p>Active Jobs</p>
                </div>
                <div class="col-md-4 stat-item">
                    <i class="fas fa-building"></i>
                    <h3><?php echo number_format($companyCount); ?></h3>
                    <p>Companies</p>
                </div>
                <div class="col-md-4 stat-item">
                    <i class="fas fa-list"></i>
                    <h3><?php echo number_format($categoryCount); ?></h3>
                    <p>Categories</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Latest Jobs Section -->
    <section class="latest-jobs">
        <div class="container">
            <h2 class="text-center mb-5">Latest Job Opportunities</h2>
            <div class="row">
                <?php foreach($latestJobs as $job): ?>
                <div class="col-md-4">
                    <div class="card job-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                            <p class="card-text"><?php echo substr(htmlspecialchars($job['description']), 0, 100) . '...'; ?></p>
                            <div class="meta-info mb-3">
                                <span class="badge badge-primary"><?php echo $job['job_type']; ?></span>
                                <span class="badge badge-info"><?php echo $job['category_name']; ?></span>
                            </div>
                            <a href="jobseeker/job-details.php?id=<?php echo $job['id']; ?>" class="card-link">Learn More</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section text-center">
        <div class="container">
            <h2 class="mb-4">Ready to Take the Next Step in Your Career?</h2>
            <p class="lead mb-4">Join thousands of job seekers and employers who trust HireUP</p>
            <a href="register.php?type=jobseeker" class="btn btn-cta">Find a Job</a>
            <a href="register.php?type=employer" class="btn btn-cta">Post a Job</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row">
                <div class="col-md-4 footer-links">
                    <h5>For Job Seekers</h5>
                    <ul>
                        <li><a href="jobseeker/search-jobs.php">Browse Jobs</a></li>
                        <li><a href="register.php?type=jobseeker">Create Account</a></li>
                        <li><a href="#">Job Alerts</a></li>
                        <li><a href="#">Career Advice</a></li>
                    </ul>
                </div>
                <div class="col-md-4 footer-links">
                    <h5>For Employers</h5>
                    <ul>
                        <li><a href="employer/post-job.php">Post a Job</a></li>
                        <li><a href="register.php?type=employer">Create Account</a></li>
                        <li><a href="#">Search Candidates</a></li>
                        <li><a href="#">Recruitment Solutions</a></li>
                    </ul>
                </div>
                <div class="col-md-4 footer-links">
                    <h5>About HireUP</h5>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="text-center mt-4">
                <p>&copy; <?php echo date('Y'); ?> HireUP. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 