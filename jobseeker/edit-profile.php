<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a jobseeker
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'jobseeker') {
    header("Location: ../login.php");
    exit();
}

// Fetch current profile
$stmt = $pdo->prepare("
    SELECT jp.* 
    FROM jobseeker_profiles jp 
    WHERE jp.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $full_name = $_POST['full_name'];
        $skills = $_POST['skills'];
        $experience = $_POST['experience'];
        $education = $_POST['education'];
        $project = $_POST['project'];
        $about_you = $_POST['about_you'];

        // Update or insert profile
        if ($profile) {
            $stmt = $pdo->prepare("
                UPDATE jobseeker_profiles 
                SET full_name = ?, 
                    skills = ?, 
                    experience = ?, 
                    education = ?, 
                    project = ?,
                    about_you = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$full_name, $skills, $experience, $education, $project, $about_you, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO jobseeker_profiles 
                (user_id, full_name, skills, experience, education, project, about_you)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $full_name, $skills, $experience, $education, $project, $about_you]);
        }

        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: dashboard.php");
        exit();
        
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Profile - HireUP</title>
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
            content: "\f007";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 1.8rem;
            color: var(--accent);
            margin-right: 0.5rem;
        }

        .profile-form {
            background: white;
            padding: 2rem 2.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(44, 62, 80, 0.08);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .profile-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
        }

        .form-group label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.7rem;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-control {
            border: 1.5px solid #e9ecef;
            border-radius: 12px;
            padding: 0.8rem 1rem;
            transition: all 0.3s ease;
            font-size: 1rem;
            background: #ffffff;
            word-wrap: break-word;
            overflow-wrap: break-word;
            resize: vertical;
            max-width: 100%;
            box-sizing: border-box;
            min-height: 48px;
            max-height: 300px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
            background: #ffffff;
            transform: translateY(-1px);
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }

        .form-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            font-style: italic;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: white;
            box-shadow: 0 4px 15px rgba(44, 62, 80, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(44, 62, 80, 0.3);
            color: white;
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: var(--card-shadow);
        }

        .skills-tag {
            background: rgba(52, 152, 219, 0.1);
            color: var(--primary);
            padding: 5px 12px;
            border-radius: 20px;
            margin: 5px;
            display: inline-block;
            font-size: 0.9rem;
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

        .section-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 18px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(20px);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--accent) 100%);
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
            background: white;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid rgba(233, 236, 239, 0.5);
            background: white;
            border-radius: 0 0 18px 18px;
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--text-secondary) 0%, #4a5568 100%);
            color: white;
            box-shadow: 0 2px 8px rgba(95, 99, 104, 0.2);
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(95, 99, 104, 0.3);
            color: white;
        }

        /* Enhanced form styling */
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-control::placeholder {
            color: #9ca3af;
            font-style: italic;
        }

        /* Character counter styling */
        .character-counter {
            position: absolute;
            bottom: -1.5rem;
            right: 0;
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .character-counter.warning {
            color: #f59e0b;
        }

        .character-counter.danger {
            color: #ef4444;
        }

        /* Input focus animation */
        .form-group {
            position: relative;
        }

        .form-control:focus + .form-text,
        .form-control:focus ~ .character-counter {
            color: var(--primary);
        }

        /* Save button enhancement */
        .btn-primary::before {
            content: "\f0c7";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            margin-right: 0.5rem;
            color: white;
        }

        /* Responsive improvements */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1.5rem 1rem;
            }
            
            .profile-form {
                padding: 1.5rem;
            }
            
            .main-content h2 {
                font-size: 1.8rem;
                padding: 1rem 1.5rem;
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
                    <a class="nav-link active" href="edit-profile.php">
                        <i class="fas fa-user"></i> Edit Profile
                    </a>
                    <a class="nav-link" href="search-jobs.php">
                        <i class="fas fa-briefcase"></i> Browse Jobs
                    </a>
                    <a class="nav-link" href="articles.php">
                        <i class="fas fa-newspaper"></i> Articles
                    </a>
                    <a class="nav-link" href="manage-reviews.php">
                        <i class="fas fa-star mr-2"></i> Manage Reviews
                    </a>
                    <a class="nav-link" href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 main-content">
                <h2 class="mb-4">Edit Your Profile</h2>
                
                <div class="profile-form">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Bootstrap Modal for Confirmation -->
                    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">
                                        <i class="fas fa-save mr-2"></i>Confirm Changes
                                    </h5>
                                    <button type="button" class="close" data-dismiss="modal">
                                        <span>&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <p class="mb-0">Are you sure you want to save these changes to your profile?</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                        <i class="fas fa-times mr-1"></i>Cancel
                                    </button>
                                    <button type="button" class="btn btn-primary" id="confirmSave">
                                        <i class="fas fa-check mr-1"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form id="profileForm" method="POST" action="">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i>
                                Full Name
                            </label>
                            <input type="text" name="full_name" class="form-control" 
                                value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" 
                                placeholder="Enter your full name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-tools"></i>
                                Skills
                            </label>
                            <textarea name="skills" class="form-control" rows="3" 
                                placeholder="e.g., JavaScript, Python, React, Project Management..."
                                required><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                            <small class="form-text">
                                List your key skills, separated by commas
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-briefcase"></i>
                                Experience
                            </label>
                            <textarea name="experience" class="form-control" rows="5" 
                                placeholder="Describe your work experience, including company names, roles, and achievements..."
                                required><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                            <small class="form-text">
                                Describe your work experience and achievements
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-graduation-cap"></i>
                                Education
                            </label>
                            <textarea name="education" class="form-control" rows="3" 
                                placeholder="List your degrees, certifications, and educational background..."
                                required><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                            <small class="form-text">
                                List your educational qualifications and certifications
                            </small>
                        </div>

                        <div class="form-group">
                            <label>
                                <i class="fas fa-project-diagram"></i>
                                Projects
                            </label>
                            <textarea name="project" class="form-control" rows="4"
                                placeholder="Describe your notable projects, technologies used, and your contributions..."><?php echo htmlspecialchars($profile['project'] ?? ''); ?></textarea>
                            <small class="form-text">
                                Describe your projects, including technologies used and your role
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="about_you">
                                <i class="fas fa-info-circle"></i>
                                About You 
                                <small class="text-muted">(Maximum 298 characters)</small>
                            </label>
                            <textarea id="about_you" name="about_you" class="form-control" rows="4" 
                                maxlength="298"
                                placeholder="Tell us about yourself, your career goals, and what makes you unique..."><?php echo htmlspecialchars($profile['about_you'] ?? ''); ?></textarea>
                            <div class="character-counter" id="aboutCounter">
                                <span id="charCount"><?php echo strlen($profile['about_you'] ?? ''); ?></span>/298
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <button type="button" class="btn btn-lg btn-primary" onclick="showConfirmModal()">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    $(document).ready(function() {
        window.showConfirmModal = function() {
            $('#confirmModal').modal('show');
        };
        
        $('#confirmSave').on('click', function() {
            $('#profileForm').submit();
        });

        // Character counter for About You field
        const aboutTextarea = document.getElementById('about_you');
        const charCount = document.getElementById('charCount');
        const counter = document.getElementById('aboutCounter');
        
        function updateCharCounter() {
            const currentLength = aboutTextarea.value.length;
            charCount.textContent = currentLength;
            
            // Remove all counter classes
            counter.classList.remove('warning', 'danger');
            
            // Add appropriate class based on character count
            if (currentLength > 250) {
                counter.classList.add('danger');
            } else if (currentLength > 200) {
                counter.classList.add('warning');
            }
        }

        if (aboutTextarea) {
            aboutTextarea.addEventListener('input', updateCharCounter);
            // Initialize counter on page load
            updateCharCounter();
        }

        // Auto-expand textarea to fit content
        function autoResizeTextarea(el) {
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight) + 'px';
        }
        
        // Apply auto-resize to all textareas
        document.querySelectorAll('textarea.form-control').forEach(function(textarea) {
            autoResizeTextarea(textarea);
            textarea.addEventListener('input', function() {
                autoResizeTextarea(this);
            });
        });

        // Add subtle animations to form elements
        $('.form-control').on('focus', function() {
            $(this).closest('.form-group').addClass('focused');
        });

        $('.form-control').on('blur', function() {
            $(this).closest('.form-group').removeClass('focused');
        });

        // Add loading state to save button
        $('#confirmSave').on('click', function() {
            const $btn = $(this);
            const originalText = $btn.html();
            
            $btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Saving...');
            $btn.prop('disabled', true);
            
            // Re-enable button after form submission (in case of validation errors)
            setTimeout(function() {
                $btn.html(originalText);
                $btn.prop('disabled', false);
            }, 3000);
        });
    });
    </script>
</body>
</html> 