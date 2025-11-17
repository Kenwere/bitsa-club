<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in
$auth = new Auth();
$auth->requireAuth('user');

// Get current user
$current_user = $auth->getCurrentUser();

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    require_once '../controllers/AuthController.php';
    $authController = new AuthController();
    $authController->logout();
}

// Load models
require_once '../models/Post.php';
require_once '../models/Meeting.php';
require_once '../models/Event.php';
require_once '../models/User.php';

$postModel = new Post();
$meetingModel = new Meeting();
$eventModel = new Event();
$userModel = new User();

// IMPORTANT: Update meeting statuses automatically
$meetingModel->checkAndUpdateMeetingStatus();

// Get data for dashboard
$posts = $postModel->getAll();
$meetings = $meetingModel->getUserMeetings($_SESSION['user_id']);
$events = $eventModel->getAll();
$userStats = $userModel->getUserStats($_SESSION['user_id']);

// Get properly filtered meetings using model methods
$activeMeetings = $meetingModel->getActiveMeetings();
$scheduledMeetings = $meetingModel->getScheduledMeetings();
$pastMeetings = $meetingModel->getPastMeetings();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Bitsa Club</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        <?php include '../assets/css/dashboard.css'; ?>
        
        /* Professional Color Scheme */
        :root[data-theme="dark"] {
            --primary-bg: #0a0e27;
            --secondary-bg: #131829;
            --card-bg: #1a1f3a;
            --border-color: #2d3348;
            --text-primary: #e8eaf6;
            --text-secondary: #9ca3af;
            --text-muted: #6b7280;
            --accent-blue: #3b82f6;
            --accent-blue-hover: #2563eb;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
            --accent-info: #06b6d4;
            --hover-bg: #252b47;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        :root[data-theme="light"] {
            --primary-bg: #f8fafc;
            --secondary-bg: #ffffff;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --accent-blue: #3b82f6;
            --accent-blue-hover: #2563eb;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
            --accent-info: #06b6d4;
            --hover-bg: #f1f5f9;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--primary-bg);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
        }
        
        /* Professional Posts Styles */
        .posts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
        }
        
        .post-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s ease;
        }
        
        .post-card:hover {
            border-color: var(--accent-blue);
            box-shadow: var(--shadow-md);
        }
        
        .post-header {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1rem;
            gap: 0.75rem;
        }
        
        .post-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #ffffff;
            font-size: 1rem;
            flex-shrink: 0;
        }
        /* Meeting Badges */
.live-badge {
    background: var(--accent-danger);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.scheduled-badge {
    background: var(--accent-warning);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.private-badge {
    color: var(--text-muted);
    font-size: 0.875rem;
}

/* Meeting Cards */
.meeting-card {
    background: var(--card-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}

.meeting-card:hover {
    border-color: var(--accent-blue);
    box-shadow: var(--shadow-sm);
}

.meeting-live {
    border-left: 4px solid var(--accent-danger);
}

.meeting-scheduled {
    border-left: 4px solid var(--accent-warning);
}
        
        .post-user-info {
            flex: 1;
            min-width: 0;
        }
        
        .post-user {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 0.95rem;
            margin-bottom: 0.125rem;
        }
        
        .post-username {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 400;
        }
        
        .post-time {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-top: 0.125rem;
        }
        
        .post-content {
            margin-bottom: 1rem;
            line-height: 1.65;
            white-space: pre-line;
            color: var(--text-primary);
            font-size: 0.9375rem;
        }
        
        .post-image-container {
            margin-bottom: 1rem;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .post-image {
            width: 100%;
            max-height: 420px;
            object-fit: cover;
            display: block;
        }
        
        .post-actions {
            display: flex;
            gap: 0.5rem;
            border-top: 1px solid var(--border-color);
            padding-top: 0.875rem;
        }
        
        .post-action {
            background: none;
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .post-action:hover {
            background: var(--hover-bg);
            border-color: var(--accent-blue);
            color: var(--accent-blue);
        }
        
        .post-action.liked {
            color: var(--accent-danger);
            border-color: var(--accent-danger);
            background: rgba(239, 68, 68, 0.1);
        }
        
        .post-action.active {
            color: var(--accent-blue);
            border-color: var(--accent-blue);
            background: rgba(59, 130, 246, 0.1);
        }
        
        .comments-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .comment-form {
            display: flex;
            gap: 0.625rem;
            margin-bottom: 1rem;
        }
        
        .comment-input {
            flex: 1;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.625rem 1rem;
            color: var(--text-primary);
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .comment-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .comment-input::placeholder {
            color: var(--text-muted);
        }
        
        .comments-list {
            max-height: 360px;
            overflow-y: auto;
        }
        
        .comment {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding: 0.875rem;
            background: var(--secondary-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            flex-shrink: 0;
            font-weight: 600;
            color: #ffffff;
        }
        
        .comment-content {
            flex: 1;
            min-width: 0;
        }
        
        .comment-user {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }
        
        .comment-username {
            color: var(--text-secondary);
            font-weight: 400;
        }
        
        .comment-text {
            margin: 0.375rem 0;
            font-size: 0.875rem;
            color: var(--text-primary);
            line-height: 1.5;
        }
        
        .comment-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        /* Professional Create Post */
        .create-post {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .create-post textarea {
            resize: none;
            border: 1px solid var(--border-color);
            background: var(--secondary-bg);
            color: var(--text-primary);
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .create-post textarea:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Professional Buttons */
        .btn-primary-custom {
            background: var(--accent-blue);
            color: #ffffff;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-primary-custom:hover {
            background: var(--accent-blue-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-secondary-custom {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-secondary-custom:hover {
            background: var(--hover-bg);
            border-color: var(--accent-blue);
            color: var(--accent-blue);
        }
        
        .btn-success-custom {
            background: var(--accent-success);
            color: #ffffff;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-success-custom:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        .btn-danger-custom {
            background: var(--accent-danger);
            color: #ffffff;
            border: none;
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-danger-custom:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }
        
        /* Professional Alerts */
        .timed-alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            min-width: 320px;
            max-width: 420px;
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 4.7s forwards;
            border-radius: 8px;
            border: 1px solid;
            padding: 1rem 1.25rem;
            font-weight: 500;
            font-size: 0.875rem;
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .timed-alert i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .timed-alert.alert-success {
            background: var(--accent-success);
            border-color: #059669;
            color: #ffffff;
        }
        
        .timed-alert.alert-danger {
            background: var(--accent-danger);
            border-color: #dc2626;
            color: #ffffff;
        }
        
        .timed-alert.alert-warning {
            background: var(--accent-warning);
            border-color: #d97706;
            color: #ffffff;
        }
        
        .timed-alert.alert-info {
            background: var(--accent-info);
            border-color: #0891b2;
            color: #ffffff;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(120%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(120%);
                opacity: 0;
            }
        }
        
        /* Loading States */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        
        .spinner {
            animation: spin 1s linear infinite;
            margin-right: 0.625rem;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h4 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            font-size: 0.875rem;
            color: var(--text-muted);
        }
        
        /* Form Controls */
        .form-control {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.625rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: var(--secondary-bg);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        /* Card Styles */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .card-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--secondary-bg);
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Text Colors */
        .text-muted {
            color: var(--text-muted) !important;
        }
        
        .text-secondary {
            color: var(--text-secondary) !important;
        }
        
        .text-primary {
            color: var(--text-primary) !important;
        }
        
        /* Small Text */
        small, .small {
            font-size: 0.8125rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .posts-grid {
                gap: 1rem;
            }
            
            .post-card {
                padding: 1.25rem;
            }
            
            .timed-alert {
                min-width: auto;
                max-width: calc(100% - 2rem);
                right: 1rem;
                left: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Sidebar Toggle -->
    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- User Header -->
    <header class="user-header">
        <div class="user-brand">
            <span>Bitsa Club</span>
        </div>
        <div class="user-actions">
            <span class="text-muted">Welcome, <?php echo htmlspecialchars($current_user['name']); ?></span>
          <form method="POST" action="../controllers/AuthController.php" class="d-inline">
    <input type="hidden" name="action" value="logout">
    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
    <button type="submit" class="btn btn-danger-custom">
        <i class="bi bi-box-arrow-right"></i> Logout
    </button>
</form>
        </div>
    </header>

    <div class="user-container">
        <!-- User Sidebar -->
        <div class="user-sidebar" id="userSidebar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-chevron-left" id="sidebarToggleIcon"></i>
            </button>

            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($current_user['name'], 0, 2)); ?>
                </div>
                <h3><?php echo htmlspecialchars($current_user['name']); ?></h3>
                <p class="text-muted">@<?php echo htmlspecialchars($current_user['username']); ?></p>
            </div>

            <div class="nav-section">
                <h3>Dashboard</h3>
                <a href="#feed" class="nav-item active" data-section="feed">
                    <i class="bi bi-house"></i> <span>Posts</span>
                </a>
            </div>

            <div class="nav-section">
                <h3>Features</h3>
                <a href="#events" class="nav-item" data-section="events">
                    <i class="bi bi-calendar-event"></i> <span>Events</span>
                </a>
                <a href="#meetings" class="nav-item" data-section="meetings">
                    <i class="bi bi-camera-video"></i> <span>Meetings</span>
                </a>
            </div>

            <div class="nav-section">
                <h3>Account</h3>
                <a href="#profile" class="nav-item" data-section="profile">
                    <i class="bi bi-person"></i> <span>My Profile</span>
                </a>
                <a href="#settings" class="nav-item" data-section="settings">
                    <i class="bi bi-gear"></i> <span>Settings</span>
                </a>
            </div>

            <div class="nav-section">
                <h3>Theme</h3>
                <button class="nav-item w-100 text-start" id="themeToggle" style="background: none; border: none;">
                    <i class="bi bi-moon" id="themeIcon"></i> 
                    <span id="themeText">Dark Mode</span>
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div class="user-main" id="userMain">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Feed Section -->
            <section id="feed-section" class="content-section active">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Posts</h2>
                    </div>
                    
                    <!-- Create Post -->
                    <div class="create-post">
                        <form id="createPostForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <div class="mb-3">
                                <textarea class="form-control" id="postContent" name="content" placeholder="Share your thoughts with the community..." rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <input type="file" class="form-control" id="postImage" name="image" accept="image/*" style="display: none;">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn-secondary-custom" onclick="document.getElementById('postImage').click()">
                                        <i class="bi bi-image"></i> Add Photo
                                    </button>
                                    <small class="text-muted align-self-center" id="imageFileName"></small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle"></i> Visible to all members
                                </div>
                                <button type="submit" class="btn-primary-custom" id="postButton">
                                    <i class="bi bi-send"></i> Post
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Posts Feed -->
                    <div class="posts-grid" id="postsFeed">
                        <?php if (empty($posts)): ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h4>No Posts Yet</h4>
                                <p>Be the first to share something with the community</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                            <div class="post-card" id="post-<?php echo $post['id']; ?>">
                                <div class="post-header">
                                    <div class="post-avatar">
                                        <?php echo strtoupper(substr($post['user_name'] ?? 'User', 0, 2)); ?>
                                    </div>
                                    <div class="post-user-info">
                                        <div class="post-user">
                                            <?php echo htmlspecialchars($post['user_name'] ?? 'User'); ?> 
                                            <span class="post-username">@<?php echo htmlspecialchars($post['username'] ?? 'user'); ?></span>
                                        </div>
                                        <div class="post-time"><?php echo formatDate($post['created_at']); ?></div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($post['content'])): ?>
                                    <div class="post-content"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                                <?php endif; ?>
                                
                                <?php if (!empty($post['image'])): ?>
                                    <div class="post-image-container">
                                        <img src="../assets/uploads/posts/<?php echo $post['image']; ?>" class="post-image" alt="Post image" onerror="this.style.display='none'">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="post-actions">
                                    <button class="post-action" onclick="likePost(<?php echo $post['id']; ?>)" id="like-btn-<?php echo $post['id']; ?>">
                                        <i class="bi bi-heart"></i> 
                                        <span id="like-count-<?php echo $post['id']; ?>"><?php echo $post['likes_count']; ?></span>
                                    </button>
                                    <button class="post-action" onclick="toggleComments(<?php echo $post['id']; ?>)" id="comment-btn-<?php echo $post['id']; ?>">
                                        <i class="bi bi-chat"></i> 
                                        <span id="comment-count-<?php echo $post['id']; ?>"><?php echo $post['comments_count']; ?></span>
                                    </button>
                                </div>
                                
                                <!-- Comments Section -->
                                <div class="comments-section" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                                    <div class="comment-form">
                                        <input type="text" class="comment-input" placeholder="Write a comment..." id="comment-input-<?php echo $post['id']; ?>">
                                        <button class="btn-primary-custom" onclick="addComment(<?php echo $post['id']; ?>)">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </div>
                                    <div class="comments-list" id="comments-list-<?php echo $post['id']; ?>">
                                        <!-- Comments will be loaded here -->
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Events Section -->
            <section id="events-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Events</h2>
                    </div>
                    
                    <div class="card-body">
                        <div id="eventsList">
                            <?php if (empty($events)): ?>
                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <h4>No Events</h4>
                                    <p>No upcoming events at the moment</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
                                <div class="event-card">
                                    <div class="event-header">
                                        <div class="event-date">
                                            <div class="event-month"><?php echo date('M', strtotime($event['event_date'])); ?></div>
                                            <div class="event-day"><?php echo date('j', strtotime($event['event_date'])); ?></div>
                                        </div>
                                        <div class="event-content">
                                            <h3 class="event-title"><?php echo $event['title']; ?></h3>
                                            <p class="event-description"><?php echo $event['description']; ?></p>
                                            <div class="event-meta">
                                                <div class="event-meta-item">
                                                    <i class="bi bi-clock"></i>
                                                    <span><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                                </div>
                                                <div class="event-meta-item">
                                                    <i class="bi bi-geo-alt"></i>
                                                    <span><?php echo $event['location']; ?></span>
                                                </div>
                                                <?php if ($event['max_attendees']): ?>
                                                    <div class="event-meta-item">
                                                        <i class="bi bi-people"></i>
                                                        <span><?php echo $event['max_attendees']; ?> max attendees</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="event-actions">
                                                <button class="btn-primary-custom" onclick="attendEvent(<?php echo $event['id']; ?>)">
                                                    <i class="bi bi-check-circle"></i> Attend Event
                                                </button>
                                                <span class="attendee-count">
                                                    <i class="bi bi-person"></i> 
                                                    <?php echo $event['attendees_count'] ?? 0; ?> attending
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

           <!-- Meetings Section -->
<section id="meetings-section" class="content-section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title mb-0"><i class="bi bi-camera-video"></i> Live Meetings</h2>
            <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createMeetingModal">
                <i class="bi bi-plus-circle"></i> New Meeting
            </button>
        </div>
        
        <!-- Active Meetings -->
        <div class="mb-4">
            <h3 class="mb-3">
                Active Meetings
                <small class="text-muted" id="activeMeetingsCount">(<?php echo count($activeMeetings); ?>)</small>
            </h3>
            <div id="activeMeetings" class="row">
                <?php if (empty($activeMeetings)): ?>
                    <div class="col-12">
                        <div class="empty-state">
                            <i class="bi bi-camera-video-off"></i>
                            <h4>No Active Meetings</h4>
                            <p>There are no live meetings at the moment</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($activeMeetings as $meeting): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="meeting-card meeting-live">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h5>
                                <div>
                                    <span class="live-badge">LIVE</span>
                                    <?php if ($meeting['type'] === 'private'): ?>
                                        <span class="private-badge ms-1"><i class="bi bi-lock"></i></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <p class="text-muted mb-2">
                                <i class="bi bi-person"></i> 
                                <span class="meeting-host"><?php echo htmlspecialchars($meeting['user_name']); ?></span>
                            </p>
                            
                            <?php if (!empty($meeting['description'])): ?>
                                <p class="mb-3 small"><?php echo htmlspecialchars($meeting['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="text-muted mb-2">
                                <i class="bi bi-clock"></i> Started <?php echo formatDate($meeting['scheduled_time']); ?>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="participants">
                                    <i class="bi bi-people"></i>
                                    <span><?php echo $meeting['participants_count']; ?> participants</span>
                                </div>
                                <div class="meeting-actions">
                                    <?php if ($meeting['user_id'] == $_SESSION['user_id']): ?>
                                        <button class="btn-danger-custom btn-sm" onclick="deleteMeeting(<?php echo $meeting['id']; ?>)" title="Delete Meeting">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-primary-custom btn-sm" onclick="joinMeeting(<?php echo $meeting['id']; ?>)">
                                        <i class="bi bi-camera-video"></i> Join
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scheduled Meetings -->
        <div>
            <h3 class="mb-3">
                Scheduled Meetings
                <small class="text-muted" id="scheduledMeetingsCount">(<?php echo count($scheduledMeetings); ?>)</small>
            </h3>
            <div id="scheduledMeetings">
                <?php if (empty($scheduledMeetings)): ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <h4>No Scheduled Meetings</h4>
                        <p>No upcoming meetings are scheduled</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($scheduledMeetings as $meeting): ?>
                    <div class="meeting-card meeting-scheduled">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="mb-1"><?php echo htmlspecialchars($meeting['title']); ?></h5>
                            <div>
                                <span class="scheduled-badge">SCHEDULED</span>
                                <?php if ($meeting['type'] === 'private'): ?>
                                    <span class="private-badge ms-1"><i class="bi bi-lock"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <p class="text-muted mb-2">
                            <i class="bi bi-person"></i> 
                            <span class="meeting-host"><?php echo htmlspecialchars($meeting['user_name']); ?></span>
                        </p>
                        
                        <?php if (!empty($meeting['description'])): ?>
                            <p class="mb-3 small"><?php echo htmlspecialchars($meeting['description']); ?></p>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="text-muted">
                                    <i class="bi bi-calendar"></i> <?php echo formatDate($meeting['scheduled_time'], 'M j, Y g:i A'); ?>
                                </span>
                            </div>
                            <div class="meeting-actions">
                                <?php if ($meeting['user_id'] == $_SESSION['user_id']): ?>
                                    <button class="btn-danger-custom btn-sm" onclick="deleteMeeting(<?php echo $meeting['id']; ?>)" title="Delete Meeting">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                <?php endif; ?>
                                <button class="btn-primary-custom btn-sm join-meeting-btn" 
                                        data-meeting-id="<?php echo $meeting['id']; ?>"
                                        data-scheduled-time="<?php echo $meeting['scheduled_time']; ?>"
                                        onclick="checkAndJoinMeeting(<?php echo $meeting['id']; ?>, '<?php echo $meeting['scheduled_time']; ?>')">
                                    <i class="bi bi-camera-video"></i> <span class="join-text">Join</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

            <!-- Profile Section -->
            <section id="profile-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="bi bi-person"></i> My Profile</h2>
                    </div>
                    
                    <div class="row">
                        <!-- Profile Info -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="user-avatar-large mb-3">
                                        <?php echo strtoupper(substr($current_user['name'], 0, 2)); ?>
                                    </div>
                                    <h3 id="profileName"><?php echo $current_user['name']; ?></h3>
                                    <p class="text-muted" id="profileUsername">@<?php echo $current_user['username']; ?></p>
                                    <p class="text-muted" id="profileEmail"><?php echo $current_user['email']; ?></p>
                                    
                                    <div class="stats mt-4">
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <div class="stat-number" id="postsCount"><?php echo $userStats['posts_count'] ?? 0; ?></div>
                                                <div class="stat-label">Posts</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stat-number" id="followingCount"><?php echo $userStats['following_count'] ?? 0; ?></div>
                                                <div class="stat-label">Following</div>
                                            </div>
                                            <div class="col-4">
                                                <div class="stat-number" id="followersCount"><?php echo $userStats['followers_count'] ?? 0; ?></div>
                                                <div class="stat-label">Followers</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Profile Details -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Profile Information</h3>
                                </div>
                                <div class="card-body">
                                    <form id="profileForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" class="form-control" name="name" value="<?php echo $current_user['name']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Username</label>
                                                    <input type="text" class="form-control" name="username" value="<?php echo $current_user['username']; ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?php echo $current_user['email']; ?>" disabled>
                                            <small class="text-muted">Email cannot be changed</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Bio</label>
                                            <textarea class="form-control" name="bio" rows="3" placeholder="Tell us about yourself..."><?php echo $current_user['bio'] ?? ''; ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Skills</label>
                                            <input type="text" class="form-control" name="skills" placeholder="Add your skills (comma separated)" value="<?php echo $current_user['skills'] ?? ''; ?>">
                                            <small class="text-muted">Separate multiple skills with commas</small>
                                        </div>
                                        <button type="submit" class="btn-primary-custom" id="updateProfileBtn">
                                            <i class="bi bi-check-circle"></i> Update Profile
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Settings Section -->
            <section id="settings-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="bi bi-gear"></i> Settings</h2>
                    </div>
                    
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Account Settings</h3>
                                    </div>
                                    <div class="card-body">
                                        <form id="settingsForm">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Email Notifications</label>
                                                <select class="form-control" name="email_notifications">
                                                    <option value="all">All notifications</option>
                                                    <option value="important">Important only</option>
                                                    <option value="none">None</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Privacy</label>
                                                <select class="form-control" name="privacy">
                                                    <option value="public">Public</option>
                                                    <option value="friends">Friends only</option>
                                                    <option value="private">Private</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn-primary-custom">
                                                <i class="bi bi-check-circle"></i> Save Settings
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Security</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Change Password</label>
                                            <input type="password" class="form-control mb-2" placeholder="Current password">
                                            <input type="password" class="form-control mb-2" placeholder="New password">
                                            <input type="password" class="form-control mb-2" placeholder="Confirm new password">
                                            <button class="btn-primary-custom">Update Password</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Create Meeting Modal -->
    <div class="modal fade" id="createMeetingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background: var(--card-bg); color: var(--text-primary);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title">Create New Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createMeetingForm">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label">Meeting Title *</label>
                            <input type="text" class="form-control" name="title" required placeholder="Enter meeting title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Describe the purpose of this meeting"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Date *</label>
                                    <input type="date" class="form-control" name="date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Time *</label>
                                    <input type="time" class="form-control" name="time" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meeting Type *</label>
                            <select class="form-control" name="type">
                                <option value="public">Public - Anyone can join</option>
                                <option value="private">Private - Only invited users can join</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top: 1px solid var(--border-color);">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn-primary-custom" onclick="createMeeting()" id="createMeetingBtn">
                        <i class="bi bi-plus-circle"></i> Create Meeting
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced Dashboard JavaScript
        let isLoadingPosts = false;
        let currentLikes = {};

        // Mobile sidebar functionality
        const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const userSidebar = document.getElementById('userSidebar');

        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', function() {
                userSidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                userSidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            });
        }

        // Desktop sidebar toggle
        const userMain = document.getElementById('userMain');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');

        if (window.innerWidth > 768 && sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                userSidebar.classList.toggle('collapsed');
                userMain.classList.toggle('expanded');
                
                if (userSidebar.classList.contains('collapsed')) {
                    sidebarToggleIcon.className = 'bi bi-chevron-right';
                } else {
                    sidebarToggleIcon.className = 'bi bi-chevron-left';
                }
            });
        } else if (sidebarToggle) {
            sidebarToggle.style.display = 'none';
        }

        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (this.tagName === 'BUTTON' && this.type === 'submit') return;
                
                e.preventDefault();
                const section = this.getAttribute('data-section');
                
                document.querySelectorAll('.nav-item').forEach(nav => {
                    nav.classList.remove('active');
                });
                this.classList.add('active');
                
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(section + '-section').classList.add('active');
                
                // Close mobile sidebar
                if (window.innerWidth <= 768) {
                    userSidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                }
            });
        });

        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        const themeText = document.getElementById('themeText');
        const html = document.documentElement;

        const currentTheme = localStorage.getItem('theme') || 'dark';
        html.setAttribute('data-theme', currentTheme);
        updateThemeIcon(currentTheme);

        themeToggle.addEventListener('click', () => {
            const theme = html.getAttribute('data-theme');
            const newTheme = theme === 'light' ? 'dark' : 'light';
            
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-sun';
                themeText.textContent = 'Light Mode';
            } else {
                themeIcon.className = 'bi bi-moon';
                themeText.textContent = 'Dark Mode';
            }
        }

        // Post functionality
        const createPostForm = document.getElementById('createPostForm');
        if (createPostForm) {
            createPostForm.addEventListener('submit', function(e) {
                e.preventDefault();
                createPost();
            });
        }

        // Show image file name when selected
        const postImageInput = document.getElementById('postImage');
        if (postImageInput) {
            postImageInput.addEventListener('change', function(e) {
                const fileName = this.files[0]?.name || '';
                document.getElementById('imageFileName').textContent = fileName;
            });
        }

        async function createPost() {
            const content = document.getElementById('postContent').value.trim();
            const imageFile = document.getElementById('postImage').files[0];
            const postButton = document.getElementById('postButton');
            
            if (!content && !imageFile) {
                showTimedAlert('Please add some content or an image to your post', 'warning');
                return;
            }

            postButton.disabled = true;
            postButton.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Posting...';

            const formData = new FormData();
            formData.append('content', content);
            if (imageFile) {
                formData.append('image', imageFile);
            }
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

            try {
                const response = await fetch('../api/posts.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('postContent').value = '';
                    document.getElementById('postImage').value = '';
                    document.getElementById('imageFileName').textContent = '';
                    showTimedAlert('Post created successfully', 'success');
                    loadPosts();
                } else {
                    showTimedAlert(data.message || 'Failed to create post', 'danger');
                }
            } catch (error) {
                console.error('Error creating post:', error);
                showTimedAlert('Network error. Please check your connection', 'danger');
            } finally {
                postButton.disabled = false;
                postButton.innerHTML = '<i class="bi bi-send"></i> Post';
            }
        }

        async function loadPosts() {
            if (isLoadingPosts) return;
            
            isLoadingPosts = true;
            const feed = document.getElementById('postsFeed');
            feed.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spinner"></i> Loading posts...</div>';

            try {
                const response = await fetch('../api/posts.php');
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const posts = await response.json();

                if (posts.length === 0) {
                    feed.innerHTML = `
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <h4>No Posts Yet</h4>
                            <p>Be the first to share something with the community</p>
                        </div>
                    `;
                    return;
                }

                feed.innerHTML = posts.map(post => `
                    <div class="post-card" id="post-${post.id}">
                        <div class="post-header">
                            <div class="post-avatar">
                                ${post.user_name ? post.user_name.substring(0, 2).toUpperCase() : 'U'}
                            </div>
                            <div class="post-user-info">
                                <div class="post-user">${escapeHtml(post.user_name || 'User')} <span class="post-username">@${escapeHtml(post.username || 'user')}</span></div>
                                <div class="post-time">${formatTime(post.created_at)}</div>
                            </div>
                        </div>
                        
                        ${post.content ? `<div class="post-content">${escapeHtml(post.content).replace(/\n/g, '<br>')}</div>` : ''}
                        
                        ${post.image ? `
                            <div class="post-image-container">
                                <img src="../assets/uploads/posts/${post.image}" class="post-image" alt="Post image" onerror="this.style.display='none'">
                            </div>
                        ` : ''}
                        
                        <div class="post-actions">
                            <button class="post-action ${currentLikes[post.id] ? 'liked' : ''}" onclick="likePost(${post.id})" id="like-btn-${post.id}">
                                <i class="bi ${currentLikes[post.id] ? 'bi-heart-fill' : 'bi-heart'}"></i> 
                                <span id="like-count-${post.id}">${post.likes_count || 0}</span>
                            </button>
                            <button class="post-action" onclick="toggleComments(${post.id})" id="comment-btn-${post.id}">
                                <i class="bi bi-chat"></i> 
                                <span id="comment-count-${post.id}">${post.comments_count || 0}</span>
                            </button>
                        </div>
                        
                        <div class="comments-section" id="comments-${post.id}" style="display: none;">
                            <div class="comment-form">
                                <input type="text" class="comment-input" placeholder="Write a comment..." id="comment-input-${post.id}">
                                <button class="btn-primary-custom" onclick="addComment(${post.id})">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                            <div class="comments-list" id="comments-list-${post.id}">
                                <!-- Comments will be loaded here -->
                            </div>
                        </div>
                    </div>
                `).join('');

            } catch (error) {
                console.error('Error loading posts:', error);
                feed.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-exclamation-triangle"></i>
                        <h4>Failed to Load Posts</h4>
                        <p>Please try again later</p>
                    </div>
                `;
            } finally {
                isLoadingPosts = false;
            }
        }

        // Like post function
        async function likePost(postId) {
            try {
                const response = await fetch(`../api/posts.php?action=like&post_id=${postId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const likeButton = document.getElementById(`like-btn-${postId}`);
                    const likeCount = document.getElementById(`like-count-${postId}`);
                    
                    currentLikes[postId] = data.liked;
                    
                    likeButton.classList.toggle('liked', data.liked);
                    likeButton.innerHTML = `
                        <i class="bi ${data.liked ? 'bi-heart-fill' : 'bi-heart'}"></i> 
                        <span id="like-count-${postId}">${data.likes_count}</span>
                    `;
                    
                    showTimedAlert(data.liked ? 'Post liked' : 'Post unliked', 'success');
                } else {
                    showTimedAlert(data.message || 'Failed to like post', 'danger');
                }
            } catch (error) {
                console.error('Error liking post:', error);
                showTimedAlert('Network error. Please try again', 'danger');
            }
        }

        function toggleComments(postId) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            const commentBtn = document.getElementById(`comment-btn-${postId}`);
            
            if (commentsSection.style.display === 'none') {
                commentsSection.style.display = 'block';
                commentBtn.classList.add('active');
                loadComments(postId);
            } else {
                commentsSection.style.display = 'none';
                commentBtn.classList.remove('active');
            }
        }

        async function loadComments(postId) {
            const commentsList = document.getElementById(`comments-list-${postId}`);
            commentsList.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spinner"></i> Loading comments...</div>';

            try {
                const response = await fetch(`../api/posts.php?post_id=${postId}`);
                if (!response.ok) throw new Error('Failed to load comments');
                
                const comments = await response.json();
                
                if (comments.length === 0) {
                    commentsList.innerHTML = '<div class="text-muted text-center py-3" style="font-size: 0.875rem;">No comments yet. Be the first to comment</div>';
                    return;
                }

                commentsList.innerHTML = comments.map(comment => `
                    <div class="comment">
                        <div class="comment-avatar">
                            ${comment.user_name ? comment.user_name.substring(0, 2).toUpperCase() : 'U'}
                        </div>
                        <div class="comment-content">
                            <div class="comment-user">${escapeHtml(comment.user_name || 'User')} <span class="comment-username">@${escapeHtml(comment.username || 'user')}</span></div>
                            <div class="comment-text">${escapeHtml(comment.content)}</div>
                            <div class="comment-time">${formatTime(comment.created_at)}</div>
                        </div>
                    </div>
                `).join('');

            } catch (error) {
                console.error('Error loading comments:', error);
                commentsList.innerHTML = '<div class="text-danger text-center py-3" style="font-size: 0.875rem;">Failed to load comments</div>';
            }
        }

        async function addComment(postId) {
            const commentInput = document.getElementById(`comment-input-${postId}`);
            const content = commentInput.value.trim();
            
            if (!content) {
                showTimedAlert('Please enter a comment', 'warning');
                return;
            }

            try {
                const response = await fetch(`../api/posts.php?action=comment&post_id=${postId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
                    },
                    body: JSON.stringify({ content: content })
                });

                const data = await response.json();

                if (data.success) {
                    commentInput.value = '';
                    showTimedAlert('Comment added successfully', 'success');
                    loadComments(postId);
                    
                    const commentCount = document.getElementById(`comment-count-${postId}`);
                    const currentCount = parseInt(commentCount.textContent) || 0;
                    commentCount.textContent = currentCount + 1;
                } else {
                    showTimedAlert(data.message || 'Failed to add comment', 'danger');
                }
            } catch (error) {
                console.error('Error adding comment:', error);
                showTimedAlert('Network error. Please try again', 'danger');
            }
        }

       

        // Profile functionality
        const profileForm = document.getElementById('profileForm');
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                updateProfile();
            });
        }

        async function updateProfile() {
            const form = document.getElementById('profileForm');
            const formData = new FormData(form);
            const updateBtn = document.getElementById('updateProfileBtn');

            updateBtn.disabled = true;
            updateBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Updating...';

            try {
                const response = await fetch('../api/profile.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showTimedAlert('Profile updated successfully', 'success');
                    document.getElementById('profileName').textContent = formData.get('name');
                    document.getElementById('profileUsername').textContent = '@' + formData.get('username');
                } else {
                    showTimedAlert(data.message || 'Failed to update profile', 'danger');
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                showTimedAlert('Failed to update profile', 'danger');
            } finally {
                updateBtn.disabled = false;
                updateBtn.innerHTML = '<i class="bi bi-check-circle"></i> Update Profile';
            }
        }

        // Event functionality
        async function attendEvent(eventId) {
            try {
                const response = await fetch(`../api/events.php?action=attend&event_id=${eventId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('input[name="csrf_token"]').value
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showTimedAlert('You are now attending this event', 'success');
                } else {
                    showTimedAlert(data.message || 'Failed to attend event', 'danger');
                }
            } catch (error) {
                console.error('Error attending event:', error);
                showTimedAlert('Network error. Please try again', 'danger');
            }
        }

        // Utility functions
        function escapeHtml(unsafe) {
            if (!unsafe) return '';
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);

            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes}m ago`;
            if (hours < 24) return `${hours}h ago`;
            if (days < 7) return `${days}d ago`;
            
            return date.toLocaleDateString();
        }

        // Enhanced timed alert function
        function showTimedAlert(message, type = 'info') {
            document.querySelectorAll('.timed-alert').forEach(alert => alert.remove());

            const alert = document.createElement('div');
            alert.className = `timed-alert alert alert-${type}`;
            alert.innerHTML = `
                <i class="bi ${getAlertIcon(type)}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(alert);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        function getAlertIcon(type) {
            const icons = {
                'success': 'bi-check-circle-fill',
                'danger': 'bi-exclamation-triangle-fill',
                'warning': 'bi-exclamation-triangle-fill',
                'info': 'bi-info-circle-fill'
            };
            return icons[type] || 'bi-info-circle-fill';
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                if (sidebarToggle) sidebarToggle.style.display = 'none';
                userSidebar.classList.remove('collapsed');
                userMain.classList.remove('expanded');
            } else {
                if (sidebarToggle) sidebarToggle.style.display = 'flex';
                userSidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            }
        });

        // Auto-set meeting date to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="date"]');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.min = today;
                dateInput.value = today;
            }

            const timeInput = document.querySelector('input[name="time"]');
            if (timeInput) {
                const nextHour = new Date();
                nextHour.setHours(nextHour.getHours() + 1);
                nextHour.setMinutes(0);
                timeInput.value = nextHour.toTimeString().substring(0, 5);
            }

            loadPosts();
        });

        // Auto-refresh posts every 30 seconds
        setInterval(loadPosts, 30000);




        // ==================== MEETING FUNCTIONS ====================

// Create meeting function
async function createMeeting() {
    const form = document.getElementById('createMeetingForm');
    const createBtn = document.getElementById('createMeetingBtn');
    
    if (!form || !createBtn) return;

    const formData = new FormData(form);

    // Validate form
    const title = formData.get('title');
    const date = formData.get('date');
    const time = formData.get('time');

    if (!title || !date || !time) {
        showTimedAlert('Please fill in all required fields', 'warning');
        return;
    }

    // Validate date and time
    const meetingDateTime = new Date(`${date}T${time}`);
    const now = new Date();
    
    if (meetingDateTime <= now) {
        showTimedAlert('Meeting time must be in the future', 'warning');
        return;
    }

    // Disable button and show loading
    createBtn.disabled = true;
    createBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Creating...';

    try {
        const response = await fetch('../api/meetings.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Meeting created successfully!', 'success');
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('createMeetingModal'));
            if (modal) {
                modal.hide();
            }
            
            form.reset();
            // Refresh meetings
            refreshMeetings();
        } else {
            showTimedAlert(data.message || 'Failed to create meeting', 'danger');
        }
    } catch (error) {
        console.error('Error creating meeting:', error);
        showTimedAlert('Failed to create meeting. Please try again.', 'danger');
    } finally {
        // Re-enable button
        createBtn.disabled = false;
        createBtn.innerHTML = '<i class="bi bi-plus-circle"></i> Create Meeting';
    }
}

async function joinMeeting(meetingId) {
    try {
        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        formData.append('meeting_id', meetingId);

        const response = await fetch(`../api/meetings.php?action=join`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Joining meeting...', 'success');
            // Redirect to meeting room
            window.location.href = `meeting-room.php?meeting_id=${meetingId}`;
        } else {
            showTimedAlert(data.message || 'Failed to join meeting', 'danger');
        }
    } catch (error) {
        console.error('Error joining meeting:', error);
        showTimedAlert('Network error. Please try again.', 'danger');
    }
}

// Delete meeting function
async function deleteMeeting(meetingId) {
    if (!confirm('Are you sure you want to delete this meeting? This action cannot be undone.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
        formData.append('meeting_id', meetingId);

        const response = await fetch(`../api/meetings.php?action=delete`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Meeting deleted successfully!', 'success');
            refreshMeetings();
        } else {
            showTimedAlert(data.message || 'Failed to delete meeting', 'danger');
        }
    } catch (error) {
        console.error('Error deleting meeting:', error);
        showTimedAlert('Failed to delete meeting: ' + error.message, 'danger');
    }
}

// Check if meeting can be joined and then join
async function checkAndJoinMeeting(meetingId, scheduledTime) {
    const meetingTime = new Date(scheduledTime);
    const now = new Date();
    
    if (meetingTime <= now) {
        // Meeting time has arrived, try to join
        await joinMeeting(meetingId);
    } else {
        showTimedAlert('This meeting has not started yet. Please wait until the scheduled time.', 'warning');
    }
}

// Refresh meetings function
async function refreshMeetings() {
    try {
        const response = await fetch('../api/meetings.php?action=all');
        if (!response.ok) throw new Error('Failed to load meetings');
        
        const data = await response.json();
        
        if (data.success) {
            // Update active meetings
            updateMeetingsDisplay('activeMeetings', data.active || [], true);
            
            // Update scheduled meetings
            updateMeetingsDisplay('scheduledMeetings', data.scheduled || [], false);
            
            // Update counts
            document.getElementById('activeMeetingsCount').textContent = `(${data.active ? data.active.length : 0})`;
            document.getElementById('scheduledMeetingsCount').textContent = `(${data.scheduled ? data.scheduled.length : 0})`;
        } else {
            throw new Error('Failed to load meetings');
        }
        
    } catch (error) {
        console.error('Error refreshing meetings:', error);
    }
}

// Update meetings display
function updateMeetingsDisplay(containerId, meetings, isActive) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    if (meetings.length === 0) {
        if (isActive) {
            container.innerHTML = `
                <div class="col-12">
                    <div class="empty-state">
                        <i class="bi bi-camera-video-off"></i>
                        <h4>No Active Meetings</h4>
                        <p>There are no live meetings at the moment</p>
                    </div>
                </div>
            `;
        } else {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="bi bi-calendar-x"></i>
                    <h4>No Scheduled Meetings</h4>
                    <p>No upcoming meetings are scheduled</p>
                </div>
            `;
        }
        return;
    }
    
    if (isActive) {
        container.innerHTML = meetings.map(meeting => `
            <div class="col-md-6 col-lg-4">
                <div class="meeting-card meeting-live">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="mb-1">${escapeHtml(meeting.title)}</h5>
                        <div>
                            <span class="live-badge">LIVE</span>
                            ${meeting.type === 'private' ? '<span class="private-badge ms-1"><i class="bi bi-lock"></i></span>' : ''}
                        </div>
                    </div>
                    
                    <p class="text-muted mb-2">
                        <i class="bi bi-person"></i> 
                        <span class="meeting-host">${escapeHtml(meeting.user_name)}</span>
                    </p>
                    
                    ${meeting.description ? `<p class="mb-3 small">${escapeHtml(meeting.description)}</p>` : ''}
                    
                    <div class="text-muted mb-2">
                        <i class="bi bi-clock"></i> Started ${formatTime(meeting.scheduled_time)}
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="participants">
                            <i class="bi bi-people"></i>
                            <span>${meeting.participants_count || 0} participants</span>
                        </div>
                        <div class="meeting-actions">
                            ${meeting.user_id == <?php echo $_SESSION['user_id']; ?> ? `
                                <button class="btn-danger-custom btn-sm" onclick="deleteMeeting(${meeting.id})" title="Delete Meeting">
                                    <i class="bi bi-trash"></i>
                                </button>
                            ` : ''}
                            <button class="btn-primary-custom btn-sm" onclick="joinMeeting(${meeting.id})">
                                <i class="bi bi-camera-video"></i> Join
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    } else {
        container.innerHTML = meetings.map(meeting => `
            <div class="meeting-card meeting-scheduled">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-1">${escapeHtml(meeting.title)}</h5>
                    <div>
                        <span class="scheduled-badge">SCHEDULED</span>
                        ${meeting.type === 'private' ? '<span class="private-badge ms-1"><i class="bi bi-lock"></i></span>' : ''}
                    </div>
                </div>
                
                <p class="text-muted mb-2">
                    <i class="bi bi-person"></i> 
                    <span class="meeting-host">${escapeHtml(meeting.user_name)}</span>
                </p>
                
                ${meeting.description ? `<p class="mb-3 small">${escapeHtml(meeting.description)}</p>` : ''}
                
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="text-muted">
                            <i class="bi bi-calendar"></i> ${new Date(meeting.scheduled_time).toLocaleString()}
                        </span>
                    </div>
                    <div class="meeting-actions">
                        ${meeting.user_id == <?php echo $_SESSION['user_id']; ?> ? `
                            <button class="btn-danger-custom btn-sm" onclick="deleteMeeting(${meeting.id})" title="Delete Meeting">
                                <i class="bi bi-trash"></i>
                            </button>
                        ` : ''}
                        <button class="btn-primary-custom btn-sm join-meeting-btn" 
                                data-meeting-id="${meeting.id}"
                                data-scheduled-time="${meeting.scheduled_time}"
                                onclick="checkAndJoinMeeting(${meeting.id}, '${meeting.scheduled_time}')">
                            <i class="bi bi-camera-video"></i> <span class="join-text">Join</span>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Update join buttons for scheduled meetings
        updateJoinButtons();
    }
}

// Update join buttons based on current time
function updateJoinButtons() {
    const joinButtons = document.querySelectorAll('.join-meeting-btn');
    const now = new Date();
    
    joinButtons.forEach(button => {
        const scheduledTime = new Date(button.getAttribute('data-scheduled-time'));
        const meetingId = button.getAttribute('data-meeting-id');
        
        if (scheduledTime <= now) {
            // Meeting can be joined
            button.onclick = function() { joinMeeting(meetingId); };
            button.querySelector('.join-text').textContent = 'Join';
            button.classList.remove('btn-secondary-custom');
            button.classList.add('btn-primary-custom');
        } else {
            // Meeting not ready yet
            button.onclick = function() { 
                showTimedAlert('This meeting has not started yet. Please wait until the scheduled time.', 'warning');
            };
            button.querySelector('.join-text').textContent = 'Join';
            button.classList.remove('btn-primary-custom');
            button.classList.add('btn-secondary-custom');
        }
    });
}

// Auto-refresh meetings every 30 seconds
setInterval(refreshMeetings, 30000);

// Update join buttons every 10 seconds
setInterval(updateJoinButtons, 10000);

// Load meetings when page loads and when meetings section is active
document.addEventListener('DOMContentLoaded', function() {
    // Initial load
    refreshMeetings();
    
    // Also refresh meetings when user switches to meetings section
    document.querySelectorAll('.nav-item[data-section="meetings"]').forEach(item => {
        item.addEventListener('click', function() {
            setTimeout(refreshMeetings, 100);
        });
    });
});
    </script>
</body>
</html>