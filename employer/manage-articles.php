<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an employer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employer') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get company information
$stmt = $pdo->prepare("SELECT id, company_name FROM companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    header('Location: dashboard.php');
    exit();
}

// Handle article deletion
if (isset($_POST['delete_article'])) {
    $article_id = $_POST['article_id'];
    $stmt = $pdo->prepare("DELETE FROM company_articles WHERE id = ? AND company_id = ?");
    $stmt->execute([$article_id, $company['id']]);
    
    if ($stmt->rowCount() > 0) {
        $success = "Article deleted successfully!";
    } else {
        $error = "Error deleting article";
    }
}

// Get all articles for this company
$stmt = $pdo->prepare("
    SELECT a.*, 
           (SELECT COUNT(*) FROM article_votes WHERE article_id = a.id AND vote_type = 'upvote') as upvotes,
           (SELECT COUNT(*) FROM article_votes WHERE article_id = a.id AND vote_type = 'downvote') as downvotes
    FROM company_articles a 
    WHERE a.company_id = ? 
    ORDER BY a.created_at DESC
");
$stmt->execute([$company['id']]);
$articles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Articles - HireUP</title>
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
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
        }
        .sidebar {
            background: var(--primary-color);
            min-height: 100vh;
            color: white;
            padding: 1.5rem;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar h3 {
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 2rem;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            margin: 0.5rem 0;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        .main-content {
            padding: 2rem;
        }

        /* Page title styling */
        .page-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50%;
            height: 3px;
            background: linear-gradient(90deg, #1f5582, transparent);
            border-radius: 2px;
        }

        .header-section {
            margin-bottom: 2rem;
        }

        .header-button {
            margin-top: 1rem;
        }

        .article-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border-color);
            transition: transform 0.2s ease;
        }
        .article-card:hover {
            transform: translateY(-2px);
        }
        .article-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .article-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-color);
            margin: 0;
        }
        .article-meta {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        .article-content {
            color: var(--text-color);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        .article-stats {
            display: flex;
            gap: 1rem;
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .article-actions {
            display: flex;
            gap: 1rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .btn-outline-primary {
            color: var(--accent-color);
            border-color: var(--accent-color);
        }
        .btn-outline-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
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
                    <a class="nav-link" href="post-job.php">
                        <i class="fas fa-plus-circle"></i> Post a Job
                    </a>
                    <a class="nav-link" href="manage-jobs.php">
                        <i class="fas fa-briefcase"></i> Manage Jobs
                    </a>
                    <a class="nav-link active" href="manage-articles.php">
                        <i class="fas fa-newspaper"></i> Articles
                    </a>
                    <a class="nav-link" href="edit-company.php">
                        <i class="fas fa-building"></i> Edit Company Profile
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>
            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <div class="header-section">
                    <h2 class="page-title">Manage Articles</h2>
                    <div class="header-button">
                        <a href="write-article.php" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i>Write New Article
                        </a>
                    </div>
                </div>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']); 
                        ?>
                    </div>
                <?php endif; ?>
                <?php if (empty($articles)): ?>
                    <div class="article-card">
                        <p>You haven't written any articles yet. Start by clicking the "Write New Article" button above.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <div class="article-card">
                            <div class="article-header">
                                <h2 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h2>
                                <div class="article-meta">
                                    Posted on <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
                                </div>
                            </div>
                            <div class="article-content">
                                <?php 
                                // Show first 200 characters of content
                                echo htmlspecialchars(substr($article['content'], 0, 200)) . '...'; 
                                ?>
                            </div>
                            <div class="article-stats">
                                <span><i class="fas fa-thumbs-up"></i> <?php echo $article['upvotes']; ?> upvotes</span>
                                <span><i class="fas fa-thumbs-down"></i> <?php echo $article['downvotes']; ?> downvotes</span>
                            </div>
                            <div class="article-actions">
                                <a href="write-article.php?edit=<?php echo $article['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this article?');">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <button type="submit" name="delete_article" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
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