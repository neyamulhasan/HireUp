SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS job_portal;
USE job_portal;

DROP TABLE IF EXISTS job_applications;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS jobseeker_profiles;
DROP TABLE IF EXISTS companies;
DROP TABLE IF EXISTS job_categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    user_type ENUM('jobseeker', 'employer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE jobseeker_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    full_name VARCHAR(100),
    skills TEXT,
    experience TEXT,
    education TEXT,
    project TEXT,
    about_you VARCHAR(200),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    company_name VARCHAR(100) NOT NULL,
    description TEXT,
    about TEXT,
    location VARCHAR(100),
    website VARCHAR(255),
    is_verified TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE job_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(50) NOT NULL
);

CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_id INT,
    category_id INT,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    salary_range VARCHAR(50),
    location VARCHAR(100),
    job_type ENUM('full-time', 'part-time', 'contract', 'remote') NOT NULL,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (category_id) REFERENCES job_categories(id)
);

CREATE TABLE job_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    job_id INT,
    user_id INT,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert some basic categories
INSERT INTO job_categories (category_name) VALUES 
('Information Technology'),
('Software Development'),
('Marketing'),
('Sales'),
('Customer Service'),
('Finance'),
('Human Resources'),
('Healthcare'),
('Education'),
('Engineering'),
('Design'),
('Administrative'),
('Management'); 

CREATE TABLE IF NOT EXISTS company_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    company_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    review TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_company_review (user_id, company_id)
);

CREATE TABLE IF NOT EXISTS admin_credentials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin credentials
INSERT INTO admin_credentials (username, password) VALUES 
('admin', '1234');

CREATE TABLE IF NOT EXISTS company_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS article_votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('upvote', 'downvote') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES company_articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_article_vote (user_id, article_id)
);