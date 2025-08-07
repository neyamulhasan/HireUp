<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['article_id']) && isset($_POST['vote_type'])) {
    try {
        // Check if user has already voted
        $stmt = $pdo->prepare("SELECT id FROM article_votes WHERE user_id = ? AND article_id = ?");
        $stmt->execute([$_SESSION['user_id'], $_POST['article_id']]);
        $existing_vote = $stmt->fetch();

        if ($existing_vote) {
            // Update existing vote
            $stmt = $pdo->prepare("UPDATE article_votes SET vote_type = ? WHERE user_id = ? AND article_id = ?");
            $stmt->execute([$_POST['vote_type'], $_SESSION['user_id'], $_POST['article_id']]);
        } else {
            // Insert new vote
            $stmt = $pdo->prepare("INSERT INTO article_votes (user_id, article_id, vote_type) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $_POST['article_id'], $_POST['vote_type']]);
        }
        
        // Redirect to prevent form resubmission
        header("Location: articles.php");
        exit();
    } catch(PDOException $e) {
        $error = "Error processing vote: " . $e->getMessage();
    }
}

// Fetch articles with company info and vote counts
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        c.company_name,
        c.id as company_id,
        c.location as company_location,
        c.is_verified,
        (SELECT COUNT(*) FROM article_votes WHERE article_id = a.id AND vote_type = 'upvote') as upvotes,
        (SELECT COUNT(*) FROM article_votes WHERE article_id = a.id AND vote_type = 'downvote') as downvotes,
        (SELECT vote_type FROM article_votes WHERE article_id = a.id AND user_id = ?) as user_vote
    FROM company_articles a
    JOIN companies c ON a.company_id = c.id
    ORDER BY RAND()
");
$stmt->execute([$_SESSION['user_id']]);
$articles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Articles - HireUP</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --accent-color: #1a73e8;
            --text-color: #202124;
            --light-bg: #f8f9fa;
            --border-color: #e9ecef;
            --card-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            --hover-shadow: 0 3px 6px rgba(0,0,0,0.16), 0 3px 6px rgba(0,0,0,0.23);
            --gradient-start: #1a237e;
            --gradient-end: #283593;
            --card-bg: rgba(255, 255, 255, 0.98);
            --section-bg: rgba(255, 255, 255, 0.95);
        }

        body {
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e3e9f7 100%);
            color: var(--text-color);
            line-height: 1.7;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2.5rem 2rem 2rem 2rem;
        }

        .page-header {
            background: linear-gradient(90deg, #f8fafc 60%, #e3e9f7 100%);
            padding: 1.2rem 1.5rem 1.2rem 1.5rem;
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(44, 62, 80, 0.07);
            margin-bottom: 2.2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1.5px solid #e9ecef;
        }

        .page-header h1 {
            margin: 0;
            font-size: 1.7rem;
            color: var(--gradient-start);
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .article-card {
            background: #fff;
            border-radius: 18px;
            padding: 2rem 1.3rem 1.3rem 1.3rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 18px 0 rgba(44, 62, 80, 0.10);
            transition: box-shadow 0.2s, transform 0.2s;
            border: 1.5px solid #e9ecef;
        }

        .article-card:hover {
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.13);
        }

        .article-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.2rem;
            gap: 1rem;
        }

        .article-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1a237e;
            margin-bottom: 0.3rem;
            line-height: 1.35;
        }

        .company-info {
            display: flex;
            align-items: center;
            background: #f4f7fb;
            padding: 0.6rem 1rem;
            border-radius: 999px;
            margin-bottom: 1.1rem;
            border: 1.5px solid #e9ecef;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
        }

        .company-info i {
            color: var(--primary-color);
            font-size: 1.1rem;
            margin-right: 0.5rem;
        }

        .company-name {
            font-weight: 600;
            color: #1a73e8 !important;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 1.05rem;
            background: #e3e9f7;
            padding: 0.2rem 0.8rem 0.2rem 0.7rem;
            border-radius: 999px;
            margin-right: 0.7rem;
            transition: all 0.2s ease;
            cursor: pointer;
            text-decoration: none !important;
            border: 2px solid #1a73e8;
        }

        .company-name:hover {
            background: #1a237e;
            color: white !important;
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
            text-decoration: none !important;
            border-color: #1a237e;
        }

        .company-name:hover .verified-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        .company-name:focus {
            outline: 3px solid rgba(26, 115, 232, 0.3);
            outline-offset: 2px;
        }

        .verified-badge {
            color: #1DA1F2;
            font-size: 1.05rem;
            display: inline-flex;
            align-items: center;
            margin-left: 0.2rem;
            background: #eaf6ff;
            border-radius: 999px;
            padding: 0.1rem 0.45rem 0.1rem 0.3rem;
            border: 1px solid #b6e0fe;
        }

        .company-location {
            color: var(--secondary-color);
            font-size: 0.95rem;
            margin-left: 0.7rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .vote-buttons {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            background: transparent;
            padding: 0.2rem 0.2rem;
            border-radius: 12px;
            border: none;
        }

        .vote-btn {
            background: #f4f7fb;
            border: 1.5px solid #e3e9f7;
            color: #1a237e;
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.18s cubic-bezier(.25,.8,.25,1);
            font-size: 1.15rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            box-shadow: 0 1px 4px rgba(44,62,80,0.07);
        }

        .vote-btn:hover {
            color: #fff;
            background: #1a73e8;
            border-color: #1a73e8;
            transform: scale(1.11) rotate(-8deg);
            box-shadow: 0 4px 12px rgba(26,35,126,0.13);
        }

        .vote-btn.active {
            color: #fff;
            background: #1a73e8;
            border-color: #1a73e8;
            box-shadow: 0 4px 12px rgba(26,35,126,0.13);
        }

        .vote-count {
            font-weight: 600;
            color: #1a237e;
            min-width: 2rem;
            text-align: center;
            font-size: 1.05rem;
        }

        .article-content {
            color: var(--secondary-color);
            font-size: 1.08rem;
            line-height: 1.7;
            margin: 1.2rem 0 1.1rem 0;
            padding: 1.1rem 1rem;
            background: #f8fafc;
            border-radius: 12px;
            border: 1.5px solid #e9ecef;
        }

        .article-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.1rem;
            border-top: 1.5px solid #e9ecef;
            color: var(--secondary-color);
            font-size: 0.97rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
        }

        .meta-item i {
            color: var(--gradient-start);
            font-size: 1rem;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            background: var(--card-bg);
        }

        .alert i {
            margin-right: 0.75rem;
        }

        .no-articles {
            text-align: center;
            padding: 3rem 2rem;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .no-articles i {
            font-size: 3.5rem;
            color: var(--primary-color);
            margin-bottom: 1.25rem;
            opacity: 0.8;
        }

        .no-articles h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .no-articles p {
            color: var(--secondary-color);
            margin-bottom: 0;
            font-size: 1.1rem;
        }

        .btn-outline-primary {
            color: var(--gradient-start);
            border-color: var(--gradient-start);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: var(--card-bg);
        }

        .btn-outline-primary:hover {
            background-color: var(--gradient-start);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(26, 35, 126, 0.2);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .article-header {
                flex-direction: column;
            }
            
            .vote-buttons {
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
            }
            
            .company-info {
                flex-wrap: wrap;
            }
            
            .company-location {
                margin-left: 0;
                margin-top: 0.5rem;
                width: 100%;
            }
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-back {
            color: var(--gradient-start);
            background: var(--card-bg);
            border: 1px solid var(--gradient-start);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-back:hover {
            background-color: var(--gradient-start);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(26, 35, 126, 0.2);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <div class="d-flex align-items-center">
                <a href="dashboard.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Back
                </a>
                <h1 class="mb-0 ml-3"><i class="fas fa-newspaper mr-2"></i>Industry Articles</h1>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline-primary" type="button" onclick="location.reload();">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($articles)): ?>
            <div class="no-articles">
                <i class="fas fa-newspaper"></i>
                <h3>No Articles Available</h3>
                <p>Check back later for new industry insights and updates.</p>
            </div>
        <?php else: ?>
            <?php foreach($articles as $article): ?>
                <div class="article-card">
                    <div class="article-header">
                        <div class="article-title">
                            <?php echo htmlspecialchars($article['title']); ?>
                        </div>
                        <div class="vote-buttons">
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                <input type="hidden" name="vote_type" value="upvote">
                                <button type="submit" class="vote-btn <?php echo $article['user_vote'] == 'upvote' ? 'active' : ''; ?>">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                            </form>
                            <span class="vote-count"><?php echo $article['upvotes']; ?></span>
                            
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                <input type="hidden" name="vote_type" value="downvote">
                                <button type="submit" class="vote-btn <?php echo $article['user_vote'] == 'downvote' ? 'active' : ''; ?>">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </form>
                            <span class="vote-count"><?php echo $article['downvotes']; ?></span>
                        </div>
                    </div>

                    <div class="company-info">
                        <i class="fas fa-building"></i>
                        <a href="company-profile.php?company_id=<?php echo $article['company_id']; ?>&ref=articles" class="company-name" style="text-decoration: none; color: inherit;" title="Click to view company profile">
                            <i class="fas fa-external-link-alt" style="font-size: 0.8rem; margin-right: 0.3rem; opacity: 0.8;"></i>
                            <?php echo htmlspecialchars($article['company_name']); ?>
                            <?php if (!empty($article['is_verified']) && $article['is_verified'] == 1): ?>
                                <span class="verified-badge" title="Verified Company">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            <?php endif; ?>
                        </a>
                        <span class="company-location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($article['company_location']); ?>
                        </span>
                    </div>

                    <div class="article-content">
                        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                    </div>

                    <div class="article-meta">
                        <div class="meta-item">
                            <i class="far fa-clock"></i>
                            Posted <?php echo date('M d, Y', strtotime($article['created_at'])); ?>
                        </div>
                        <div class="meta-item">
                            <?php if ($article['updated_at'] != $article['created_at']): ?>
                                <i class="fas fa-edit"></i>
                                Updated <?php echo date('M d, Y', strtotime($article['updated_at'])); ?>
                            <?php endif; ?>
                        </div>
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