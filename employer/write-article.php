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

// Initialize variables for article data
$article = [
    'title' => '',
    'content' => '',
    'id' => null
];

// If editing an existing article
if (isset($_GET['edit'])) {
    $article_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM company_articles WHERE id = ? AND company_id = ?");
    $stmt->execute([$article_id, $company['id']]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: manage-articles.php');
        exit();
    }
}

// Handle article submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    
    if (empty($title) || empty($content)) {
        $error = "Please fill in all fields";
    } else {
        try {
            if (isset($_POST['article_id'])) {
                // Update existing article
                $stmt = $pdo->prepare("UPDATE company_articles SET title = ?, content = ? WHERE id = ? AND company_id = ?");
                $stmt->execute([$title, $content, $_POST['article_id'], $company['id']]);
                $_SESSION['success'] = "Article updated successfully!";
                header('Location: manage-articles.php');
                exit();
            } else {
                // Create new article
                $stmt = $pdo->prepare("INSERT INTO company_articles (company_id, title, content) VALUES (?, ?, ?)");
                $stmt->execute([$company['id'], $title, $content]);
                $_SESSION['success'] = "Article published successfully!";
                header('Location: manage-articles.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = "Error saving article";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article['id'] ? 'Edit' : 'Write'; ?> Article - HireUp</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --text-color: #2c3e50;
            --light-bg: #f5f6fa;
            --border-color: #e9ecef;
            --card-bg: #ffffff;
        }

        html {
            height: 100%;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg,rgb(7, 12, 72) 0%, #0d47a1 100%);
            background-attachment: fixed;
            color: var(--text-color);
            min-height: 100vh;
            padding: 0.5rem 0;
            position: relative;
            margin: 0;
            display: flex;
            align-items: center;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25px 25px, rgba(255, 255, 255, 0.1) 2%, transparent 0%),
                radial-gradient(circle at 75px 75px, rgba(255, 255, 255, 0.1) 2%, transparent 0%);
            background-size: 100px 100px;
            background-attachment: fixed;
            pointer-events: none;
            z-index: 0;
        }

        .article-form-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0.5rem;
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .article-form-card {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
        }

        .back-link i {
            margin-right: 0.4rem;
            font-size: 1rem;
        }

        .form-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.75rem;
            font-size: 1.25rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }

        .form-label {
            font-weight: 500;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.5rem;
            transition: all 0.2s ease;
            font-size: 0.9rem;
            background: white;
        }

        textarea.form-control {
            min-height: 180px;
            resize: vertical;
            line-height: 1.4;
        }

        .btn-publish {
            background: var(--primary-color);
            color: white;
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-top: 0.5rem;
            width: 100%;
            font-size: 0.9rem;
        }

        .alert {
            border-radius: 6px;
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            font-weight: 500;
            border: none;
            font-size: 0.85rem;
        }

        .form-group {
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .article-form-container {
                padding: 0.25rem;
            }
            
            .article-form-card {
                padding: 0.75rem;
            }
            
            .form-title {
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }

            textarea.form-control {
                min-height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="article-form-container">
        <a href="manage-articles.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Articles
        </a>

        <div class="article-form-card">
            <h1 class="form-title"><?php echo $article['id'] ? 'Edit Article' : 'Write New Article'; ?></h1>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <?php if ($article['id']): ?>
                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title" class="form-label">Article Title</label>
                    <input type="text" class="form-control" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($article['title']); ?>"
                           placeholder="Enter a compelling title for your article">
                </div>

                <div class="form-group">
                    <label for="content" class="form-label">Article Content</label>
                    <textarea class="form-control" id="content" name="content" required 
                              placeholder="Write your article content here..."><?php echo htmlspecialchars($article['content']); ?></textarea>
                </div>

                <button type="submit" class="btn btn-publish">
                    <i class="fas fa-paper-plane"></i> <?php echo $article['id'] ? 'Update Article' : 'Publish Article'; ?>
                </button>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 