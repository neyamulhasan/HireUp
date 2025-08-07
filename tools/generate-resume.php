<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Determine which user's resume to show
$target_user_id = $_SESSION['user_id']; // Default to logged-in user

// If an employer is viewing an applicant's resume
if (isset($_GET['user_id']) && $_SESSION['user_type'] == 'employer') {
    // Verify that the employer has access to view this resume
    $stmt = $pdo->prepare("
        SELECT DISTINCT ja.user_id 
        FROM job_applications ja
        JOIN jobs j ON ja.job_id = j.id
        JOIN companies c ON j.company_id = c.id
        WHERE ja.user_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$_GET['user_id'], $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        $target_user_id = $_GET['user_id'];
    } else {
        die("Access denied: You don't have permission to view this resume.");
    }
}

// Fetch user's profile
$stmt = $pdo->prepare("
    SELECT u.*, jp.* 
    FROM users u 
    LEFT JOIN jobseeker_profiles jp ON u.id = jp.user_id 
    WHERE u.id = ?
");
$stmt->execute([$target_user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("Error: User not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($user['full_name']); ?> - Resume</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .resume-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 30px !important;
            }
            a {
                text-decoration: none !important;
                color: #000 !important;
            }
            .skill-item {
                border: 1px solid #ddd !important;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
            color: #2c3e50;
        }
        .resume-container {
            background: white;
            max-width: 850px;
            margin: 40px auto;
            padding: 50px;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        .header {
            position: relative;
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 30px;
            padding-bottom: 20px;
        }
        .header::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 80px;
            height: 2px;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .header h1 {
            font-weight: 600;
            color: #2c3e50;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        .contact-info {
            display: flex;
            gap: 20px;
            color: #6c757d;
        }
        .contact-info i {
            color: #764ba2;
        }
        .section {
            margin-bottom: 35px;
            position: relative;
        }
        .section-title {
            color: #764ba2;
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.3em;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
        }
        .section-title::before {
            content: '';
            width: 30px;
            height: 2px;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            margin-right: 10px;
        }
        .content {
            line-height: 1.8;
            color: #2c3e50;
            font-size: 1.05em;
            font-weight: 300;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            padding: 12px 25px;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            border: none;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .print-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }
        .skill-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.95em;
            border: 1px solid #e9ecef;
            color: #2c3e50;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .skill-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-color: #764ba2;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 0.9em;
        }
        .highlight {
            background: linear-gradient(120deg, rgba(118,75,162,0.1) 0%, rgba(102,126,234,0.1) 100%);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <button onclick="window.print()" class="btn btn-primary print-button no-print">
        <i class="fas fa-download mr-2"></i>Download Resume
    </button>

    <div class="resume-container">
        <!-- Header -->
        <div class="header">
            <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
            <div class="contact-info">
                <span><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($user['email']); ?></span>
                <?php if (!empty($user['location'])): ?>
                    <span><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($user['location']); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- About -->
        <?php if (!empty($user['about_you'])): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-user mr-2"></i>About Me</h2>
            <div class="content highlight">
                <?php echo nl2br(htmlspecialchars($user['about_you'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Skills -->
        <?php if (!empty($user['skills'])): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-tools mr-2"></i>Skills</h2>
            <div class="skills-list">
                <?php 
                $skills = explode(',', $user['skills']);
                foreach($skills as $skill): ?>
                    <span class="skill-item">
                        <i class="fas fa-check-circle mr-2" style="color: #764ba2;"></i>
                        <?php echo htmlspecialchars(trim($skill)); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Experience -->
        <?php if (!empty($user['experience'])): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-briefcase mr-2"></i>Professional Experience</h2>
            <div class="content">
                <?php echo nl2br(htmlspecialchars($user['experience'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Projects -->
        <?php if (!empty($user['project'])): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-project-diagram mr-2"></i>Projects</h2>
            <div class="content highlight">
                <?php echo nl2br(htmlspecialchars($user['project'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Education -->
        <?php if (!empty($user['education'])): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-graduation-cap mr-2"></i>Education</h2>
            <div class="content">
                <?php echo nl2br(htmlspecialchars($user['education'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="footer text-center">
            <i class="fas fa-paper-plane mr-2"></i>
            Generated by HireUP - <?php echo date('Y'); ?>
        </div>
    </div>

    <script>
        // Optional: Smooth scroll for print button
        document.querySelector('.print-button').addEventListener('mouseover', function() {
            this.style.transition = 'all 0.3s ease';
        });
    </script>
</body>
</html>