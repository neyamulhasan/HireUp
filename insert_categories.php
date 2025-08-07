<?php
require_once 'config/database.php';

try {
    // First check if categories already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_categories");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "Categories already exist. Total categories: " . $count;
    } else {
        // Insert categories
        $categories = [
            'Information Technology',
            'Software Development',
            'Marketing',
            'Sales',
            'Customer Service',
            'Finance',
            'Human Resources',
            'Healthcare',
            'Education',
            'Engineering',
            'Design',
            'Administrative',
            'Management'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO job_categories (category_name) VALUES (?)");
        
        foreach ($categories as $category) {
            $stmt->execute([$category]);
        }
        
        echo "Categories inserted successfully! Total categories inserted: " . count($categories);
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}

// Display all categories
try {
    $stmt = $pdo->query("SELECT * FROM job_categories ORDER BY category_name");
    $categories = $stmt->fetchAll();
    
    echo "<br><br>Current Categories in Database:<br>";
    foreach ($categories as $category) {
        echo "ID: " . $category['id'] . " - " . $category['category_name'] . "<br>";
    }
} catch(PDOException $e) {
    echo "Error displaying categories: " . $e->getMessage();
}
?> 