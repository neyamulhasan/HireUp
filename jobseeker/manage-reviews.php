<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

// Handle review deletion
if (isset($_POST['delete_review'])) {
    $review_id = $_POST['review_id'] ?? 0;
    $stmt = $pdo->prepare("DELETE FROM company_reviews WHERE id = ? AND user_id = ?");
    $stmt->execute([$review_id, $_SESSION['user_id']]);
    $_SESSION['success'] = "Review deleted successfully";
    header("Location: manage-reviews.php");
    exit();
}

// Handle review update
if (isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'] ?? 0;
    $rating = $_POST['rating'] ?? 0;
    $review = $_POST['review'] ?? '';
    
    if ($rating < 1 || $rating > 5 || empty($review)) {
        $_SESSION['error'] = "Please provide valid review details";
    } else {
        $stmt = $pdo->prepare("UPDATE company_reviews SET rating = ?, review = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$rating, $review, $review_id, $_SESSION['user_id']]);
        $_SESSION['success'] = "Review updated successfully";
    }
    header("Location: manage-reviews.php");
    exit();
}

// Fetch all companies and their ratings
$stmt = $pdo->prepare("
    SELECT c.*, 
           (SELECT AVG(rating) FROM company_reviews WHERE company_id = c.id) as avg_rating,
           (SELECT COUNT(*) FROM company_reviews WHERE company_id = c.id) as review_count
    FROM companies c
    ORDER BY c.company_name ASC
");
$stmt->execute();
$all_companies = $stmt->fetchAll();

// Fetch company IDs the user has already reviewed
$stmt = $pdo->prepare("SELECT company_id FROM company_reviews WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$reviewed_company_ids = array_column($stmt->fetchAll(), 'company_id');

// Fetch user's reviews
$stmt = $pdo->prepare("
    SELECT cr.*, c.company_name, c.is_verified, c.id as company_id
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
    <title>Manage Reviews - HireUP</title>
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
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --accent-color: #1a73e8;
            --text-color: #202124;
            --light-bg: #f8f9fa;
            --gradient-start: #1a237e;
            --gradient-end: #283593;
            --card-bg: rgba(255, 255, 255, 0.98);
            --section-bg: rgba(255, 255, 255, 0.95);
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
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .main-content h2::before {
            content: "\f005";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 1.8rem;
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .main-content h4 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: 12px;
            border-left: 4px solid var(--accent);
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
        }

        .review-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--hover-shadow);
        }

        .review-card h5 a {
            font-weight: 600;
            color: #1a73e8 !important;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 1.15rem;
            background: #e3e9f7;
            padding: 0.4rem 1rem 0.4rem 0.8rem;
            border-radius: 999px;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none !important;
            border: 2px solid #1a73e8;
        }

        .review-card h5 a:hover {
            background: #1a237e;
            color: white !important;
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
            text-decoration: none !important;
            border-color: #1a237e;
        }

        .review-card h5 a:focus {
            outline: 3px solid rgba(26, 115, 232, 0.3);
            outline-offset: 2px;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .company-name {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.2rem;
            margin: 0;
        }

        .company-name a {
            color: inherit;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
        }

        .company-name a:hover {
            color: var(--accent);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .company-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .company-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .company-card h5 a {
            font-weight: 600;
            color: #1a73e8 !important;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 1.15rem;
            background: #e3e9f7;
            padding: 0.4rem 1rem 0.4rem 0.8rem;
            border-radius: 999px;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none !important;
            border: 2px solid #1a73e8;
        }

        .company-card h5 a:hover {
            background: #1a237e;
            color: white !important;
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
            text-decoration: none !important;
            border-color: #1a237e;
        }

        .company-card h5 a:focus {
            outline: 3px solid rgba(26, 115, 232, 0.3);
            outline-offset: 2px;
        }

        h5 a {
            color: inherit;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        h5 a:hover {
            color: var(--accent);
            text-decoration: none;
        }

        .review-date {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .rating {
            margin-bottom: 15px;
        }

        .rating i {
            color: #ffd700;
            margin-right: 0.2rem;
            font-size: 1.1rem;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }

        .review-content {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            line-height: 1.7;
            font-size: 1.05rem;
            background: #f8fafc;
            padding: 1.2rem;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .review-actions {
            display: flex;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--gradient-end) 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(26, 115, 232, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 115, 232, 0.3);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.2);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #4a5568 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(95, 99, 104, 0.2);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(95, 99, 104, 0.3);
            color: white;
        }

        /* Star Rating System Styles */
        .star-rating {
            display: inline-flex;
            flex-direction: row-reverse;
            font-size: 1.8em;
            justify-content: flex-end;
            gap: 0.1rem;
            margin: 0.5rem 0;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            color: #cbd5e0;
            cursor: pointer;
            padding: 0 0.1em;
            font-size: 1.5em;
            transition: all 0.2s ease;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }

        .star-rating label:before {
            content: '★';
        }

        .star-rating input:checked ~ label {
            color: #ffd700;
            transform: scale(1.1);
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffd700;
            transform: scale(1.1);
        }

        .star-rating input:checked + label:hover,
        .star-rating input:checked + label:hover ~ label,
        .star-rating input:checked ~ label:hover,
        .star-rating input:checked ~ label:hover ~ label {
            color: #ffd700;
            transform: scale(1.1);
        }

        /* Modal Styles */
        .modal {
            z-index: 1050;
        }

        .modal-backdrop {
            z-index: 1040;
            background: rgba(26, 35, 126, 0.3);
            backdrop-filter: blur(8px);
        }

        .modal-dialog {
            z-index: 1060;
        }

        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(20px);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            border-radius: 18px 18px 0 0;
            padding: 1.5rem 2rem;
            border-bottom: none;
        }

        .modal-header h5 {
            font-weight: 600;
            font-size: 1.3rem;
        }

        .modal-body {
            padding: 2rem;
            background: var(--card-bg);
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid rgba(233, 236, 239, 0.5);
            background: var(--card-bg);
            border-radius: 0 0 18px 18px;
        }

        /* Form Styles */
        .form-control {
            border: 1.5px solid #e9ecef;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            background: #ffffff;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 115, 232, 0.1);
            background: #ffffff;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            font-weight: 600;
            color: var(--gradient-start);
            margin-bottom: 0.7rem;
            font-size: 1.05rem;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(231, 76, 60, 0.2);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
            color: white;
        }

        .no-reviews {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(44, 62, 80, 0.08);
            border: 1.5px solid #e9ecef;
            margin: 2rem 0;
        }

        .no-reviews i {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            opacity: 0.7;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .no-reviews h4 {
            color: var(--gradient-start);
            margin-bottom: 1rem;
            font-weight: 600;
            font-size: 1.4rem;
        }

        .no-reviews p {
            color: var(--secondary-color);
            margin-bottom: 0;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .alert {
            border-radius: 15px;
            padding: 1.2rem 1.5rem;
            border: none;
            margin-bottom: 1.5rem;
            font-size: 1.05rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(46, 204, 113, 0.1) 0%, rgba(39, 174, 96, 0.05) 100%);
            color: #27ae60;
            border: 1px solid rgba(46, 204, 113, 0.2);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.05) 100%);
            color: #c0392b;
            border: 1px solid rgba(231, 76, 60, 0.2);
        }

        .alert-info {
            background: linear-gradient(135deg, rgba(26, 115, 232, 0.1) 0%, rgba(26, 35, 126, 0.05) 100%);
            color: var(--primary-color);
            border: 1px solid rgba(26, 115, 232, 0.2);
        }

        .company-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .company-rating .text-warning i {
            color: #ffd700;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }

        .text-muted {
            color: var(--secondary-color) !important;
            font-size: 0.9rem;
        }

        /* Verified badge styling */
        .text-primary {
            color: var(--primary-color) !important;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem 1rem;
            }
            
            .sidebar {
                display: none;
            }
            
            .main-content h2 {
                font-size: 1.8rem;
                padding: 1rem 1.5rem;
            }
            
            .company-card, .review-card {
                padding: 1.5rem 1rem;
            }
            
            .review-actions {
                flex-direction: column;
            }
            
            .review-actions .btn {
                width: 100%;
                justify-content: center;
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
                    <a class="nav-link" href="edit-profile.php">
                        <i class="fas fa-user"></i> Edit Profile
                    </a>
                    <a class="nav-link" href="search-jobs.php">
                        <i class="fas fa-briefcase"></i> Browse Jobs
                    </a>
                    <a class="nav-link" href="articles.php">
                        <i class="fas fa-newspaper"></i> Articles
                    </a>
                    <a class="nav-link active" href="manage-reviews.php">
                        <i class="fas fa-star mr-2"></i> Manage Reviews
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <h2 class="mb-4">Manage Company Reviews</h2>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Give New Review Section -->
                <div class="mb-4">
                    <h4 class="mb-3">Give New Review</h4>
                    <!-- DEBUG: reviewed_company_ids: <?php echo json_encode($reviewed_company_ids); ?> -->
                    <!-- DEBUG: first company id: <?php echo isset($all_companies[0]['id']) ? $all_companies[0]['id'] : 'none'; ?> -->
                    <?php
                    $reviewable_companies = array_filter($all_companies, function($company) use ($reviewed_company_ids) {
                        return !in_array((int)$company['id'], array_map('intval', $reviewed_company_ids));
                    });
                    ?>
                    <?php if (!empty($reviewable_companies)): ?>
                        <?php foreach($reviewable_companies as $company): ?>
                            <div class="company-card">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <a href="company-profile.php?company_id=<?php echo $company['id']; ?>&ref=reviews" style="text-decoration: none; color: inherit;" title="Click to view company profile">
                                            <i class="fas fa-external-link-alt" style="font-size: 0.8rem; margin-right: 0.3rem; opacity: 0.8;"></i>
                                            <?php echo htmlspecialchars($company['company_name']); ?>
                                        </a>
                                        <?php if ($company['is_verified']): ?>
                                            <i class="fas fa-check-circle text-primary ml-1" title="Verified Company"></i>
                                        <?php endif; ?>
                                    </h5>
                                    <?php if ($company['avg_rating']): ?>
                                        <div class="company-rating">
                                            <span class="text-warning">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star<?php echo $i <= round($company['avg_rating']) ? '' : '-o'; ?>"></i>
                                                <?php endfor; ?>
                                            </span>
                                            <small class="text-muted">(<?php echo $company['review_count']; ?> reviews)</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <form action="submit-review.php" method="POST">
                                    <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                    
                                    <div class="form-group">
                                        <label class="font-weight-bold">Your Rating</label>
                                        <div class="star-rating">
                                            <input type="radio" name="rating" value="5" id="new_star5_<?php echo $company['id']; ?>" required>
                                            <label for="new_star5_<?php echo $company['id']; ?>"></label>
                                            <input type="radio" name="rating" value="4" id="new_star4_<?php echo $company['id']; ?>">
                                            <label for="new_star4_<?php echo $company['id']; ?>"></label>
                                            <input type="radio" name="rating" value="3" id="new_star3_<?php echo $company['id']; ?>">
                                            <label for="new_star3_<?php echo $company['id']; ?>"></label>
                                            <input type="radio" name="rating" value="2" id="new_star2_<?php echo $company['id']; ?>">
                                            <label for="new_star2_<?php echo $company['id']; ?>"></label>
                                            <input type="radio" name="rating" value="1" id="new_star1_<?php echo $company['id']; ?>">
                                            <label for="new_star1_<?php echo $company['id']; ?>"></label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="font-weight-bold">Your Review</label>
                                        <textarea class="form-control" name="review" rows="3" required 
                                                  placeholder="Share your experience with this company..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane mr-2"></i>Submit Review
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-2"></i>
                            You have already reviewed all companies. Thank you!
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Your Reviews Section -->
                <div class="your-reviews">
                    <h4 class="mb-3">Your Reviews</h4>
                    <?php if (!empty($user_reviews)): ?>
                        <?php foreach($user_reviews as $review): ?>
                            <div class="review-card" data-review-id="<?php echo $review['id']; ?>">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0">
                                        <a href="company-profile.php?company_id=<?php echo $review['company_id']; ?>&ref=reviews" style="text-decoration: none; color: inherit;" title="Click to view company profile">
                                            <i class="fas fa-external-link-alt" style="font-size: 0.8rem; margin-right: 0.3rem; opacity: 0.8;"></i>
                                            <?php echo htmlspecialchars($review['company_name']); ?>
                                        </a>
                                        <?php if ($review['is_verified']): ?>
                                            <i class="fas fa-check-circle text-primary ml-1" title="Verified Company"></i>
                                        <?php endif; ?>
                                    </h5>
                                    <div class="review-actions">
                                        <button type="button" class="btn btn-sm btn-primary edit-review-btn" 
                                                data-review-id="<?php echo $review['id']; ?>"
                                                data-company-name="<?php echo htmlspecialchars($review['company_name']); ?>"
                                                data-rating="<?php echo $review['rating']; ?>"
                                                data-review="<?php echo htmlspecialchars($review['review']); ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this review?');">
                                            <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                            <button type="submit" name="delete_review" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <div class="rating mb-2">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? ' text-warning' : ' text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                
                                <small class="text-muted">
                                    Reviewed on <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            You haven't reviewed any companies yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Review Modal -->
    <div class="modal fade" id="editReviewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Review for <span id="modalCompanyName"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editReviewForm" action="submit-review.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="review_id" id="modalReviewId">
                        
                        <div class="form-group">
                            <label class="font-weight-bold">Your Rating</label>
                            <div class="star-rating">
                                <input type="radio" name="rating" value="5" id="star5">
                                <label for="star5"></label>
                                <input type="radio" name="rating" value="4" id="star4">
                                <label for="star4"></label>
                                <input type="radio" name="rating" value="3" id="star3">
                                <label for="star3"></label>
                                <input type="radio" name="rating" value="2" id="star2">
                                <label for="star2"></label>
                                <input type="radio" name="rating" value="1" id="star1">
                                <label for="star1"></label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="font-weight-bold">Your Review</label>
                            <textarea class="form-control" name="review" id="modalReview" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_review" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    // Initialize when document is ready
    $(document).ready(function() {
        // Handle edit button clicks
        $(document).on('click', '.edit-review-btn', function() {
            const reviewId = $(this).data('review-id');
            const companyName = $(this).data('company-name');
            const rating = $(this).data('rating');
            const review = $(this).data('review');
            
            openEditModal(reviewId, companyName, rating, review);
        });

        // Handle modal hidden event
        $('#editReviewModal').on('hidden.bs.modal', function () {
            // Reset form when modal is closed
            document.getElementById('editReviewForm').reset();
        });
    });

    function openEditModal(reviewId, companyName, rating, review) {
        // Set modal content
        document.getElementById('modalCompanyName').textContent = companyName;
        document.getElementById('modalReviewId').value = reviewId;
        document.getElementById('modalReview').value = review;
        
        // Set the correct star rating
        const starInput = document.getElementById('star' + rating);
        if (starInput) {
            starInput.checked = true;
        }
        
        // Show modal
        $('#editReviewModal').modal('show');
    }

    // Handle form submission
    document.getElementById('editReviewForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(this);
        const reviewId = formData.get('review_id');
        const rating = formData.get('rating');
        const review = formData.get('review');

        // Validate data
        if (!rating || !review) {
            return;
        }

        // Send update request
        fetch('submit-review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'review_id': reviewId,
                'rating': rating,
                'review': review,
                'update_review': '1'
            })
        })
        .then(response => response.text())
        .then(data => {
            if (data.includes('success')) {
                // Close modal
                $('#editReviewModal').modal('hide');
                // Refresh the page to show updated data
                window.location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            window.location.reload();
        });
    });
    </script>
</body>
</html> 