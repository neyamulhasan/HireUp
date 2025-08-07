<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle review update
    if (isset($_POST['update_review'])) {
        $review_id = $_POST['review_id'] ?? 0;
        $rating = $_POST['rating'] ?? 0;
        $review = $_POST['review'] ?? '';
        
        if ($review_id <= 0 || $rating < 1 || $rating > 5 || empty($review)) {
            $_SESSION['error'] = "Please provide valid review details";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE company_reviews SET rating = ?, review = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$rating, $review, $review_id, $_SESSION['user_id']]);
                $_SESSION['success'] = "Review updated successfully";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error updating review";
            }
        }
        header("Location: manage-reviews.php");
        exit();
    }

    // Handle new review submission
    $company_id = $_POST['company_id'] ?? 0;
    $rating = $_POST['rating'] ?? 0;
    $review = $_POST['review'] ?? '';
    
    // Validate input
    if ($company_id <= 0 || $rating < 1 || $rating > 5 || empty($review)) {
        $_SESSION['error'] = "Please provide valid review details";
        header("Location: manage-reviews.php");
        exit();
    }
    
    // Check if user has already reviewed this company
    $stmt = $pdo->prepare("SELECT id FROM company_reviews WHERE user_id = ? AND company_id = ?");
    $stmt->execute([$_SESSION['user_id'], $company_id]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = "You have already reviewed this company";
        header("Location: manage-reviews.php");
        exit();
    }
    
    // Insert review
    $stmt = $pdo->prepare("
        INSERT INTO company_reviews (user_id, company_id, rating, review, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    try {
        $stmt->execute([$_SESSION['user_id'], $company_id, $rating, $review]);
        $_SESSION['success'] = "Review submitted successfully";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error submitting review";
    }
    
    header("Location: manage-reviews.php");
    exit();
}
?> 