<?php
session_start();
require_once '../config/database.php';

// Determine the back URL based on referrer
$ref = $_GET['ref'] ?? 'search-jobs';
$backUrl = 'search-jobs.php';
$backText = 'Back to Job Search';

if ($ref === 'articles') {
    $backUrl = 'articles.php';
    $backText = 'Back to Articles';
} elseif ($ref === 'reviews') {
    $backUrl = 'manage-reviews.php';
    $backText = 'Back to Reviews';
} elseif ($ref === 'search-jobs') {
    $backUrl = 'search-jobs.php';
    $backText = 'Back to Job Search';
}

// Check if company_id is provided
if (!isset($_GET['company_id']) || !is_numeric($_GET['company_id'])) {
    $error = "Invalid company ID.";
} else {
    $company_id = (int)$_GET['company_id'];
    $stmt = $pdo->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    if (!$company) {
        $error = "Company not found.";
    } else {
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
        // Fetch company average rating and review count
        $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM company_reviews WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $rating_data = $stmt->fetch();
        $avg_rating = $rating_data && $rating_data['avg_rating'] ? round($rating_data['avg_rating'], 1) : null;
        $review_count = $rating_data ? (int)$rating_data['review_count'] : 0;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Company Profile - HireUP</title>
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
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: var(--text-color);
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .profile-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .company-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .verified-badge {
            color: var(--accent-color);
            font-size: 1rem;
            margin-left: 0.5rem;
        }

        .profile-section {
            margin-bottom: 1.5rem;
        }

        .profile-section label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            display: block;
            font-size: 0.9rem;
        }

        .profile-section .value {
            color: var(--secondary-color);
            font-size: 1rem;
            line-height: 1.5;
        }

        .profile-section .about {
            color: var(--text-color);
            font-size: 1rem;
            white-space: pre-line;
            line-height: 1.6;
        }

        .profile-section .website-link {
            color: var(--accent-color);
            text-decoration: none;
            word-break: break-all;
            transition: all 0.3s ease;
        }

        .profile-section .website-link:hover {
            text-decoration: underline;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--accent-color);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        .not-found {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: var(--text-color);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-top: 3rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .company-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .btn-outline-primary {
            color: var(--accent-color);
            border-color: var(--accent-color);
            background: transparent;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--accent-color);
            border-color: var(--accent-color);
            color: white;
            transform: translateY(-1px);
        }

        .card {
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .alert {
            border-radius: 8px;
            border: 1px solid transparent;
            font-weight: 500;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        h4 {
            color: var(--primary-color);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo htmlspecialchars($backUrl); ?>" class="back-link"><i class="fas fa-arrow-left mr-1"></i><?php echo htmlspecialchars($backText); ?></a>
        <?php if (isset($error)): ?>
            <div class="not-found">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i><br>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php else: ?>
            <div class="profile-card">
                <div class="profile-header">
                    <div class="company-name">
                        <?php echo htmlspecialchars($company['company_name']); ?>
                        <?php if (!empty($company['is_verified'])): ?>
                            <span class="verified-badge" title="Verified Company"><i class="fas fa-check-circle"></i></span>
                        <?php endif; ?>
                        <?php if ($avg_rating): ?>
                            <span class="ml-2 align-middle" title="Company Rating">
                                <span class="text-warning">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= round($avg_rating) ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </span>
                                <small class="text-muted ml-1" style="font-size:0.95em;">
                                    (<?php echo number_format($avg_rating, 1); ?><?php if ($review_count): ?>, <?php echo $review_count; ?> reviews<?php endif; ?>)
                                </small>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-section">
                    <label>Description</label>
                    <div class="value"><?php echo htmlspecialchars($company['description']); ?></div>
                </div>
                <div class="profile-section">
                    <label>Location</label>
                    <div class="value"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($company['location']); ?></div>
                </div>
                <div class="profile-section">
                    <label>Website</label>
                    <div class="value"><a class="website-link" href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank"><?php echo htmlspecialchars($company['website']); ?></a></div>
                </div>
                <div class="profile-section">
                    <label>About Company</label>
                    <div class="about"><?php echo nl2br(htmlspecialchars($company['about'])); ?></div>
                </div>
            </div>
            <div class="mt-4">
                <button class="btn btn-outline-primary mb-3" type="button" data-toggle="collapse" data-target="#reviewsDrawer" aria-expanded="false" aria-controls="reviewsDrawer" id="toggleReviewsBtn">
                    <i class="fas fa-star text-warning mr-2"></i>
                    <span id="toggleReviewsText">Show Reviews</span>
                </button>
                <div class="collapse" id="reviewsDrawer">
                    <h4 class="mb-3"><i class="fas fa-star text-warning mr-2"></i>Company Reviews</h4>
                    <?php if (empty($reviews)): ?>
                        <div class="alert alert-info">No reviews yet for this company.</div>
                    <?php else: ?>
                        <?php foreach($reviews as $review): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? ' text-warning' : ' text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div><?php echo nl2br(htmlspecialchars($review['review'])); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var reviewsDrawer = document.getElementById('reviewsDrawer');
                var toggleBtn = document.getElementById('toggleReviewsBtn');
                var toggleText = document.getElementById('toggleReviewsText');
                if (reviewsDrawer && toggleBtn && toggleText) {
                    $('#reviewsDrawer').on('show.bs.collapse', function () {
                        toggleText.textContent = 'Hide Reviews';
                    });
                    $('#reviewsDrawer').on('hide.bs.collapse', function () {
                        toggleText.textContent = 'Show Reviews';
                    });
                }
            });
            </script>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 