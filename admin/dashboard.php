<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if admin is logged in
$auth = new Auth();
$auth->requireAuth('admin');

// Get current admin
$current_admin = $auth->getCurrentAdmin();

// Handle logout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'logout') {
    require_once '../controllers/AuthController.php';
    $authController = new AuthController();
    $authController->logoutAdmin();
}

// Load models
require_once '../models/User.php';
require_once '../models/Post.php';
require_once '../models/Meeting.php';
require_once '../models/Event.php';
require_once '../models/Admin.php';
require_once '../models/Gallery.php';

$userModel = new User();
$postModel = new Post();
$meetingModel = new Meeting();
$eventModel = new Event();
$adminModel = new Admin();
$galleryModel = new Gallery();

// Get dashboard statistics
$stats = [
    'total_users' => $userModel->getTotalCount(),
    'total_posts' => $postModel->getTotalCount(),
    'total_meetings' => $meetingModel->getActiveAndScheduledCount(),
    'active_meetings' => $meetingModel->getActiveCount(),
    'total_events' => $eventModel->getTotalCount(),
    'upcoming_events' => $eventModel->getUpcomingCount()
];

// Get recent data
$recent_users = $userModel->getRecent(5);
$recent_posts = $postModel->getRecent(5);
$upcoming_events = $eventModel->getUpcoming(5);

// Get all data for management sections
$users = $userModel->getAll();
$posts = $postModel->getAll();
$meetings = $meetingModel->getVisibleMeetings();
$events = $eventModel->getAll();
$admin_posts = $postModel->getAdminPosts();
$gallery_images = $galleryModel->getAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_event':
            require_once '../controllers/AdminController.php';
            $adminController = new AdminController();
            $result = $adminController->createEvent($_POST);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            // Don't redirect, let the page refresh naturally
            break;
            
        case 'create_meeting':
            require_once '../controllers/AdminController.php';
            $adminController = new AdminController();
            $result = $adminController->createMeeting($_POST);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            // Don't redirect, let the page refresh naturally
            break;
            
        case 'create_post':
            require_once '../controllers/AdminController.php';
            $adminController = new AdminController();
            $result = $adminController->createPost($_POST, $_FILES);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            // Don't redirect, let the page refresh naturally
            break;
            
        case 'update_profile':
            require_once '../controllers/AdminController.php';
            $adminController = new AdminController();
            $result = $adminController->updateProfile($_POST);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            // Don't redirect, let the page refresh naturally
            break;
            
        case 'upload_image':
            require_once '../controllers/AdminController.php';
            $adminController = new AdminController();
            $result = $adminController->uploadImage($_POST, $_FILES);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
            } else {
                $_SESSION['error'] = $result['message'];
            }
            // Don't redirect, let the page refresh naturally
            break;
            
        case 'logout':
            require_once '../controllers/AuthController.php';
            $authController = new AuthController();
            $authController->logoutAdmin();
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bitsa Club</title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        /* Professional Color Scheme - Matching User Dashboard */
        :root[data-theme="dark"] {
            --primary-bg: #0a0a0a;
            --secondary-bg: #1a1a1a;
            --bg-sidebar: #1e293b;
            --card-bg: #2a2a2a;
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --text-muted: #6b7280;
            --accent-blue: #3b82f6;
            --accent-blue-hover: #2563eb;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
            --accent-info: #06b6d4;
            --accent-cyan: #00fff5;
            --accent-purple: #bf00ff;
            --accent-pink: #ff006e;
            --hover-bg: #252b47;
            --border-color: #404040;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        :root[data-theme="light"] {
            --primary-bg: #f8fafc;
            --secondary-bg: #ffffff;
            --bg-sidebar: #1e40af;
            --card-bg: #ffffff;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --accent-blue: #3b82f6;
            --accent-blue-hover: #2563eb;
            --accent-success: #10b981;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
            --accent-info: #06b6d4;
            --accent-cyan: #00b8b0;
            --accent-purple: #8e00bf;
            --accent-pink: #d6004d;
            --hover-bg: #f1f5f9;
            --border-color: #e2e8f0;
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
            min-height: 100vh;
        }

        /* Admin Layout - Matching User Dashboard */
        .admin-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: var(--secondary-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 2rem;
            z-index: 1002;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .admin-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
            padding-top: 80px;
        }

        /* Updated Sidebar Positioning */
        .admin-sidebar {
            width: 280px;
            background: var(--bg-sidebar);
            padding: 3rem 1.5rem 2rem;
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1003;
            transform: translateX(-100%);
        }

        .admin-sidebar.mobile-open {
            transform: translateX(0);
        }

        .admin-sidebar.collapsed {
            width: 80px;
            padding: 3rem 0.5rem 2rem;
        }

        .admin-sidebar.collapsed .nav-section h3,
        .admin-sidebar.collapsed .admin-info h3,
        .admin-sidebar.collapsed .admin-info p,
        .admin-sidebar.collapsed .nav-item span {
            display: none;
        }

        .admin-sidebar.collapsed .nav-item {
            justify-content: center;
            padding: 0.75rem;
        }

        .sidebar-toggle {
            position: absolute;
            top: 1rem;
            right: -12px;
            background: var(--accent-blue);
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            font-size: 0.8rem;
            z-index: 1004;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .admin-main {
            flex: 1;
            margin-left: 0;
            padding: 2rem;
            transition: all 0.3s ease;
            min-height: calc(100vh - 80px);
            width: 100%;
        }

        /* Desktop sidebar behavior */
        @media (min-width: 769px) {
            .admin-sidebar {
                transform: translateX(0);
                height: calc(100vh - 80px);
                top: 80px;
            }

            .admin-main {
                margin-left: 280px;
            }

            .admin-main.expanded {
                margin-left: 80px;
            }

            .admin-sidebar.collapsed {
                transform: translateX(0);
            }
        }

        /* Admin Info */
        .admin-info {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
            color: white;
            font-weight: 600;
        }

        .admin-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin: 0 auto;
            color: #000;
        }

        /* Navigation */
        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section h3 {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        [data-theme="light"] .nav-section h3 {
            color: #ffffff;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            color: #ffffff;
            text-decoration: none;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
            cursor: pointer;
        }

        [data-theme="light"] .nav-item {
            color: #ffffff;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Content Areas */
        .content-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .card-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            border: none;
            transition: all 0.2s ease;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: var(--accent-blue);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            color: white;
        }

        .btn-success {
            background: var(--accent-success);
            color: white;
        }

        .btn-warning {
            background: var(--accent-warning);
            color: white;
        }

        .btn-danger {
            background: var(--accent-danger);
            color: white;
        }

        /* Forms */
        .form-control {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 0.75rem 1rem;
        }

        .form-control::placeholder {
            color: var(--text-secondary);
        }

        .form-control:focus {
            background: var(--secondary-bg);
            border-color: var(--accent-blue);
            color: var(--text-primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .table {
            color: var(--text-primary) !important;
        }

        .table th {
            color: var(--text-primary) !important;
            background: var(--secondary-bg) !important;
            border-color: var(--border-color) !important;
        }

        .table td {
            color: var(--text-primary) !important;
            background: var(--card-bg) !important;
            border-color: var(--border-color) !important;
        }

        /* Mobile Sidebar */
        .mobile-sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1004;
            background: var(--accent-blue);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 0.5rem;
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-header {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                height: auto;
            }

            .admin-container {
                flex-direction: column;
                padding-top: 0;
            }

            .admin-sidebar {
                width: 280px;
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                z-index: 1003;
                transition: transform 0.3s ease;
                padding-top: 5rem;
            }

            .admin-main {
                margin-left: 0;
                padding: 1rem;
                min-height: auto;
                width: 100%;
            }

            .sidebar-toggle {
                display: none;
            }

            .mobile-sidebar-toggle {
                display: block;
            }

            .admin-sidebar.collapsed {
                width: 80px;
            }

            /* Overlay for mobile */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1002;
            }

            .sidebar-overlay.active {
                display: block;
            }
        }

        /* Ensure text visibility in both themes */
        .card-title,
        .stat-number,
        .stat-label,
        .table th,
        .table td,
        .admin-info h3,
        .admin-info p {
            color: var(--text-primary) !important;
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .form-label {
            color: var(--text-primary) !important;
        }

        .table {
            color: var(--text-primary) !important;
        }

        .table th {
            color: var(--text-primary) !important;
            background: var(--secondary-bg) !important;
        }

        .table td {
            color: var(--text-primary) !important;
            background: var(--card-bg) !important;
        }

        .table tbody tr:hover td {
            background: var(--secondary-bg) !important;
            color: var(--text-primary) !important;
        }

        /* Professional Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-blue);
        }

        .stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.8125rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }

        /* Professional Badges */
        .badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-success {
            background: var(--accent-success);
            color: white;
        }

        .badge-warning {
            background: var(--accent-warning);
            color: white;
        }

        .badge-danger {
            background: var(--accent-danger);
            color: white;
        }

        .badge-secondary {
            background: var(--text-secondary);
            color: white;
        }

        .badge-info {
            background: var(--accent-info);
            color: white;
        }

        /* Professional Tables */
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .table {
            margin-bottom: 0;
            font-size: 0.875rem;
            background: var(--card-bg);
        }

        .table th {
            background: var(--secondary-bg);
            border: none;
            font-weight: 600;
            padding: 1rem;
            color: var(--text-primary);
            font-size: 0.8125rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-color);
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: var(--hover-bg);
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary-custom:hover {
            background: var(--accent-blue-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            color: white;
            text-decoration: none;
        }

        .btn-success-custom {
            background: var(--accent-success);
            color: #ffffff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8125rem;
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
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8125rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-danger-custom:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-warning-custom {
            background: var(--accent-warning);
            color: #ffffff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.8125rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .btn-warning-custom:hover {
            background: #d97706;
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

        .btn-group-sm .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        /* Professional Modals */
        .modal-content {
            background: var(--card-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
        }

        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .btn-close {
            filter: invert(1);
            opacity: 0.6;
        }

        .btn-close:hover {
            opacity: 1;
        }

        [data-theme="light"] .btn-close {
            filter: invert(0);
        }

        /* Professional Alerts */
        .alert {
            border-radius: 8px;
            border: 1px solid;
            padding: 1rem 1.25rem;
            font-weight: 500;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert i {
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .alert-success {
            background: var(--accent-success);
            border-color: #059669;
            color: #ffffff;
        }
        
        .alert-danger {
            background: var(--accent-danger);
            border-color: #dc2626;
            color: #ffffff;
        }
        
        .alert-warning {
            background: var(--accent-warning);
            border-color: #d97706;
            color: #ffffff;
        }
        
        .alert-info {
            background: var(--accent-info);
            border-color: #0891b2;
            color: #ffffff;
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

        /* Text Colors */
        .text-muted {
            color: var(--text-muted) !important;
        }
        
        .text-secondary {
            color: var(--text-secondary) !important;
        }
        
        .text-primary {
            color: var(--accent-blue) !important;
        }

        .text-success {
            color: var(--accent-success) !important;
        }

        .text-danger {
            color: var(--accent-danger) !important;
        }

        .text-warning {
            color: var(--accent-warning) !important;
        }

        .text-info {
            color: var(--accent-info) !important;
        }

        /* Small Text */
        small, .small {
            font-size: 0.8125rem;
        }

        /* Post Styles */
        .post-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-primary);
        }

        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .post-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-cyan), var(--accent-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-weight: bold;
            color: #000;
        }

        .post-user {
            font-weight: 600;
            color: var(--text-primary);
        }

        .post-time {
            font-size: 0.8rem;
            color: var(--text-primary);
            opacity: 0.7;
        }

        .post-content {
            margin-bottom: 1rem;
            line-height: 1.6;
            white-space: pre-line;
            color: var(--text-primary);
        }

        .post-image {
            max-width: 100%;
            border-radius: 10px;
            margin-bottom: 1rem;
            max-height: 400px;
            object-fit: cover;
        }

        .post-actions {
            display: flex;
            gap: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 1rem;
        }

        .post-action {
            background: none;
            border: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
        }

        .post-action:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--accent-cyan);
        }

        .post-action.liked {
            color: var(--accent-pink);
        }

        .comments-section {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .comment-form {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .comment-input {
            flex: 1;
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
        }

        .comment-input::placeholder {
            color: rgba(255,255,255,0.5);
        }

        .comment {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            color: var(--text-primary);
        }

        .comment-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
            font-weight: bold;
            color: #000;
        }

        .comment-content {
            flex: 1;
        }

        .comment-user {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .comment-text {
            margin: 0.25rem 0;
            font-size: 0.9rem;
            color: var(--text-primary);
        }

        .comment-time {
            font-size: 0.75rem;
            color: var(--text-primary);
            opacity: 0.7;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1rem;
            animation: slideDown 0.3s ease;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            color: #d4edda;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
            border-left: 4px solid #dc3545;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: var(--text-primary);
            opacity: 0.7;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-primary);
            opacity: 0.7;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Dark mode text fixes */
        [data-theme="dark"] .text-muted {
            color: #adb5bd !important;
        }

        [data-theme="dark"] .post-time,
        [data-theme="dark"] .comment-time {
            color: #adb5bd !important;
        }

        [data-theme="dark"] .form-control {
            color: #ffffff !important;
        }

        [data-theme="dark"] .form-control::placeholder {
            color: #adb5bd !important;
        }

        [data-theme="dark"] .comment-input {
            color: #ffffff !important;
        }

        [data-theme="dark"] .comment-input::placeholder {
            color: #adb5bd !important;
        }

        [data-theme="dark"] .loading,
        [data-theme="dark"] .empty-state {
            color: #adb5bd !important;
        }

        /* Event Cards */
        .event-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: all 0.2s ease;
        }

        .event-card:hover {
            border-color: var(--accent-blue);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .event-header {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .event-date {
            background: var(--accent-blue);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            min-width: 80px;
        }

        .event-month {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .event-day {
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1;
        }

        .event-content {
            flex: 1;
        }

        .event-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .event-description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .event-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .event-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn-attend {
            background: var(--accent-blue);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-attend:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .attendee-count {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-left: auto;
        }

        /* Meeting Cards */
        .meeting-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
        }

        .meeting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .meeting-live {
            border-left: 4px solid var(--accent-pink);
        }

        .meeting-scheduled {
            border-left: 4px solid var(--accent-cyan);
        }

        .live-badge {
            background: var(--accent-pink);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .scheduled-badge {
            background: var(--accent-cyan);
            color: #000;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .private-badge {
            background: var(--accent-purple);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .participants {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .participant-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--accent-purple);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            color: #000;
            flex-shrink: 0;
        }

        .participants-list {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .host-badge {
            background: var(--accent-pink);
            color: white;
            padding: 0.1rem 0.5rem;
            border-radius: 10px;
            font-size: 0.7rem;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
        }

        .meeting-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .meeting-host {
            color: var(--accent-pink);
            font-weight: 600;
        }

        /* Profile input styling */
        .profile-input {
            background: rgba(0,0,0,0.3) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: var(--text-primary) !important;
            transition: all 0.3s ease;
        }

        .profile-input:focus {
            border-color: var(--accent-cyan) !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 255, 245, 0.25) !important;
            background: rgba(0,0,0,0.4) !important;
        }

        .profile-input:disabled {
            background: rgba(0,0,0,0.2) !important;
            color: var(--text-muted) !important;
            opacity: 0.7;
        }

        /* Stats */
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--accent-cyan);
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .create-post {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Gallery grid */
        #galleryGrid .gallery-item {
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            cursor: pointer;
            transition: transform 0.22s ease, box-shadow 0.22s ease;
        }

        #galleryGrid .gallery-item img {
            display:block;
            width:100%;
            height:200px;
            object-fit:cover;
            transition: transform 0.35s ease;
        }

        #galleryGrid .gallery-item:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-md);
        }

        #galleryGrid .gallery-caption {
            padding: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Contact section styling */
        .contact-item {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .whatsapp-btn:hover {
            background: #128C7E;
            transform: scale(1.1);
            color: white;
        }

        /* Mobile responsive tables */
        @media (max-width: 768px) {
            .table-responsive {
                border: none;
            }
            
            .table {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
            }
            
            .btn-group-sm .btn {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }
        }

        /* Contact section text visibility */
        .contact-item .fw-600,
        .contact-item .text-muted {
            color: var(--text-primary) !important;
        }

        [data-theme="dark"] .contact-item .text-muted {
            color: var(--text-secondary) !important;
        }

        /* Timed alert for form submissions */
        .timed-alert {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 4.7s forwards;
            background: var(--accent-success);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-weight: 500;
            box-shadow: var(--shadow-xl);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .timed-alert.alert-danger {
            background: var(--accent-danger);
        }

        .timed-alert.alert-warning {
            background: var(--accent-warning);
        }

        .timed-alert.alert-info {
            background: var(--accent-info);
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
    </style>
</head>
<body>
    <!-- Mobile Sidebar Toggle -->
    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-brand">
            <i class="bi bi-shield-check"></i>
            <span>Admin Console</span>
        </div>
        <div class="admin-actions">
            <span class="text-muted">Welcome, <?php echo htmlspecialchars($current_admin['name']); ?></span>
            <form method="POST" action="" class="d-inline">
                <input type="hidden" name="action" value="logout">
                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </form>
        </div>
    </header>

    <div class="admin-container">
        <!-- Admin Sidebar -->
        <div class="admin-sidebar" id="adminSidebar">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="bi bi-chevron-left" id="sidebarToggleIcon"></i>
            </button>

            <div class="admin-info">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($current_admin['name'], 0, 2)); ?>
                </div>
                <h3><?php echo htmlspecialchars($current_admin['name']); ?></h3>
                <p class="text-muted">Administrator</p>
            </div>

            <div class="nav-section">
                <h3>Dashboard</h3>
                <a href="#overview" class="nav-item active" data-section="overview">
                    <i class="bi bi-speedometer2"></i> <span>Overview</span>
                </a>
                <a href="#posts" class="nav-item" data-section="posts">
                    <i class="bi bi-file-text"></i> <span>Posts</span>
                </a>
            </div>

            <div class="nav-section">
                <h3>Management</h3>
                <a href="#users" class="nav-item" data-section="users">
                    <i class="bi bi-people"></i> <span>User Management</span>
                </a>
                <a href="#meetings" class="nav-item" data-section="meetings">
                    <i class="bi bi-camera-video"></i> <span>Meeting Management</span>
                </a>
                <a href="#events" class="nav-item" data-section="events">
                    <i class="bi bi-calendar-event"></i> <span>Event Management</span>
                </a>
            </div>

            <div class="nav-section">
                <h3>Content</h3>
                <a href="#gallery" class="nav-item" data-section="gallery">
                    <i class="bi bi-images"></i> <span>Gallery</span>
                </a>
                <a href="#contact" class="nav-item" data-section="contact">
                    <i class="bi bi-telephone"></i> <span>Contact</span>
                </a>
            </div>

            <div class="nav-section">
                <h3>Account</h3>
                <a href="#profile" class="nav-item" data-section="profile">
                    <i class="bi bi-person"></i> <span>My Profile</span>
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
        <div class="admin-main" id="adminMain">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i>
                    <span><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Overview Section -->
            <section id="overview-section" class="content-section active">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 style="font-size: 1.5rem; font-weight: 600; margin: 0;">Dashboard Overview</h2>
                    <div class="text-muted" style="font-size: 0.8125rem;">Last updated: <?php echo date('M j, Y g:i A'); ?></div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number text-primary"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-success"><?php echo $stats['total_posts']; ?></div>
                        <div class="stat-label">Total Posts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?php echo $stats['total_meetings']; ?></div>
                        <div class="stat-label">Total Meetings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-info"><?php echo $stats['total_events']; ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number text-danger"><?php echo $stats['active_meetings']; ?></div>
                        <div class="stat-label">Active Meetings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: var(--text-secondary);"><?php echo $stats['upcoming_events']; ?></div>
                        <div class="stat-label">Upcoming Events</div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Users -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Users</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Joined</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_users)): ?>
                                                <?php foreach($recent_users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo formatDate($user['created_at']); ?></td>
                                                    <td>
                                                        <?php if($user['is_active']): ?>
                                                            <span class="badge badge-success">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge badge-danger">Suspended</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-3">No users found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Recent Posts</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Content</th>
                                                <th>User</th>
                                                <th>Posted</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_posts)): ?>
                                                <?php foreach($recent_posts as $post): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(strlen($post['content']) > 50 ? substr($post['content'], 0, 50) . '...' : $post['content']); ?></td>
                                                    <td><?php echo htmlspecialchars($post['user_name'] ?? 'Unknown'); ?></td>
                                                    <td><?php echo formatDate($post['created_at']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-3">No posts found</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upcoming Events</h3>
                    </div>
                    <div class="card-body">
                        <?php if(!empty($upcoming_events)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Attendees</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($upcoming_events as $event): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(strlen($event['description']) > 60 ? substr($event['description'], 0, 60) . '...' : $event['description']); ?></small>
                                        </td>
                                        <td><?php echo formatDate($event['event_date']); ?> at <?php echo date('g:i A', strtotime($event['event_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td><?php echo $event['attendees_count'] ?? 0; ?> / <?php echo $event['max_attendees'] ?? '∞'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h4>No Upcoming Events</h4>
                            <p>Create events to display them here</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Posts Section -->
            <section id="posts-section" class="content-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">Posts Management</h2>
                        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createPostModal">
                            <i class="bi bi-plus-circle"></i> Create Post
                        </button>
                    </div>
                    
                    <!-- Create Post -->
                    <div class="create-post">
                        <form id="createPostForm" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                            <input type="hidden" name="action" value="create_post">
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
                        <?php if (empty($admin_posts)): ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <h4>No Posts Yet</h4>
                                <p>Be the first to share something with the community</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($admin_posts as $post): ?>
                            <div class="post-card" id="post-<?php echo $post['id']; ?>">
                                <div class="post-header">
                                    <div class="post-avatar">
                                        <?php echo strtoupper(substr($post['user_name'] ?? 'Admin', 0, 2)); ?>
                                    </div>
                                    <div class="post-user-info">
                                        <div class="post-user">
                                            <?php echo htmlspecialchars($post['user_name'] ?? 'Admin'); ?> 
                                            <span class="post-username">@<?php echo htmlspecialchars($post['username'] ?? 'admin'); ?></span>
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
                                    <?php if ($post['user_id'] == $_SESSION['admin_id'] || $post['admin_id'] == $_SESSION['admin_id']): ?>
                                        <button class="post-action text-danger" onclick="deletePost(<?php echo $post['id']; ?>)">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
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

            <!-- User Management Section -->
            <section id="users-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">User Management</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($users)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Username</th>
                                        <th>Posts</th>
                                        <th>Joined</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo $user['posts_count'] ?? 0; ?></td>
                                        <td><?php echo formatDate($user['created_at']); ?></td>
                                        <td>
                                            <?php if($user['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Suspended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <?php if($user['is_active']): ?>
                                                    <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="suspend_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <button type="submit" class="btn-warning-custom" onclick="return confirm('Are you sure you want to suspend this user?')">Suspend</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="activate_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <button type="submit" class="btn-success-custom" onclick="return confirm('Are you sure you want to activate this user?')">Activate</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form action="../controllers/AdminController.php" method="POST" class="d-inline ms-1">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <button type="submit" class="btn-danger-custom" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            <h4>No Users Found</h4>
                            <p>There are no users in the system yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Meeting Management Section -->
            <section id="meetings-section" class="content-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">Meeting Management</h2>
                        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createMeetingModal">
                            <i class="bi bi-plus-circle"></i> Create Meeting
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($meetings)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Host</th>
                                        <th>Scheduled</th>
                                        <th>Participants</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($meetings as $meeting): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($meeting['title']); ?></td>
                                        <td><?php echo htmlspecialchars($meeting['user_name'] ?? 'Admin'); ?></td>
                                        <td><?php echo formatDate($meeting['scheduled_time']); ?></td>
                                        <td><?php echo $meeting['participants_count'] ?? 0; ?></td>
                                        <td>
                                            <span class="badge <?php echo $meeting['type'] === 'public' ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo ucfirst($meeting['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $meeting['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo $meeting['is_active'] ? 'Active' : 'Scheduled'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_meeting">
                                                <input type="hidden" name="meeting_id" value="<?php echo $meeting['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <button type="submit" class="btn-danger-custom" onclick="return confirm('Are you sure you want to delete this meeting?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-camera-video"></i>
                            <h4>No Meetings Found</h4>
                            <p>There are no meetings scheduled yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Event Management Section -->
            <section id="events-section" class="content-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="card-title mb-0">Event Management</h2>
                        <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#createEventModal">
                            <i class="bi bi-plus-circle"></i> Create Event
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($events)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Date & Time</th>
                                        <th>Location</th>
                                        <th>Max Attendees</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo htmlspecialchars(strlen($event['description']) > 60 ? substr($event['description'], 0, 60) . '...' : $event['description']); ?></td>
                                        <td><?php echo formatDate($event['event_date']); ?> at <?php echo date('g:i A', strtotime($event['event_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td><?php echo $event['max_attendees'] ?? 'Unlimited'; ?></td>
                                        <td>
                                            <span class="badge <?php echo $event['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo $event['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_event">
                                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <button type="submit" class="btn-danger-custom" onclick="return confirm('Are you sure you want to delete this event?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-event"></i>
                            <h4>No Events Found</h4>
                            <p>There are no events created yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Gallery Section -->
<section id="gallery-section" class="content-section">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h2 class="card-title"><i class="bi bi-images"></i> Gallery</h2>
            <div>
                <small class="text-muted me-3" id="galleryCount">Loading...</small>
                <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#uploadImageModal">
                    <i class="bi bi-upload"></i> Upload Image
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="galleryGrid" class="row g-3">
                <!-- Images will be loaded dynamically by JavaScript -->
            </div>

            <!-- Empty state -->
            <div id="galleryEmpty" class="empty-state" style="display:none;">
                <i class="bi bi-images"></i>
                <h4>No Gallery Images</h4>
                <p>Upload images to display them in the gallery.</p>
            </div>

            <!-- Loading state -->
            <div id="galleryLoading" class="loading" style="display:none;">
                <i class="bi bi-arrow-repeat spinner"></i> Loading gallery...
            </div>
        </div>
    </div>
</section>

            <!-- Contact Section -->
            <section id="contact-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title"><i class="bi bi-telephone"></i> Contact</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h5 class="mb-1">Club Email</h5>
                            <p class="text-muted">bitsaclub@ueab.ac.ke</p>
                        </div>

                        <div class="mb-4">
                            <h5 class="mb-2">Leadership</h5>

                            <div class="contact-item d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-600">Alpha Chamba</div>
                                    <div class="text-muted small">President</div>
                                    <div class="text-muted small">0708898899</div>
                                </div>
                                <div>
                                    <a href="https://wa.me/254708898899?text=Hello%20Alpha%20Chamba" target="_blank" class="whatsapp-btn" title="Chat on WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                </div>
                            </div>

                            <div class="contact-item d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-600">Gloria Jebet</div>
                                    <div class="text-muted small">Vice President</div>
                                    <div class="text-muted small">0725486687</div>
                                </div>
                                <div>
                                    <a href="https://wa.me/254725486687?text=Hello%20Gloria%20Jebet" target="_blank" class="whatsapp-btn" title="Chat on WhatsApp">
                                        <i class="bi bi-whatsapp"></i>
                                    </a>
                                </div>
                            </div>

                            <small class="text-muted">Click the WhatsApp buttons to start a chat with the leader (opens WhatsApp / WhatsApp Web).</small>
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
                                    <div class="admin-avatar-large mb-3">
                                        <?php echo strtoupper(substr($current_admin['name'], 0, 2)); ?>
                                    </div>
                                    <h3 id="profileName"><?php echo $current_admin['name']; ?></h3>
                                    <p class="text-muted" id="profileUsername">@<?php echo $current_admin['username']; ?></p>
                                    <p class="text-muted" id="profileEmail"><?php echo $current_admin['email']; ?></p>
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
                                    <form id="profileForm" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                        <input type="hidden" name="action" value="update_profile">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Full Name</label>
                                                    <input type="text" class="form-control profile-input" name="name" value="<?php echo $current_admin['name']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Username</label>
                                                    <input type="text" class="form-control profile-input" name="username" value="<?php echo $current_admin['username']; ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control profile-input" value="<?php echo $current_admin['email']; ?>" disabled>
                                            <small class="text-muted">Email cannot be changed</small>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">New Password</label>
                                                    <input type="password" class="form-control profile-input" name="password" placeholder="Leave blank to keep current">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label class="form-label">Confirm Password</label>
                                                    <input type="password" class="form-control profile-input" name="password_confirmation" placeholder="Confirm new password">
                                                </div>
                                            </div>
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
        </div>
    </div>

    <!-- Create Event Modal -->
    <div class="modal fade" id="createEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="createEventForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_event">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Title *</label>
                                    <input type="text" class="form-control" name="title" required placeholder="Enter event title">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location *</label>
                                    <input type="text" class="form-control" name="location" required placeholder="Enter event location">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="3" required placeholder="Describe the event"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Date *</label>
                                    <input type="date" class="form-control" name="event_date" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Time *</label>
                                    <input type="time" class="form-control" name="event_time" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maximum Attendees (Optional)</label>
                            <input type="number" class="form-control" name="max_attendees" min="1" placeholder="Leave empty for unlimited">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary-custom" id="createEventBtn">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Meeting Modal -->
    <div class="modal fade" id="createMeetingModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Meeting</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="createMeetingForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_meeting">
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
                                    <input type="date" class="form-control" name="date" required min="<?php echo date('Y-m-d'); ?>">
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
                            <select class="form-control" name="type" required>
                                <option value="public">Public - Anyone can join</option>
                                <option value="private">Private - Only invited users can join</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary-custom" id="createMeetingBtn">Create Meeting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Image Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Image to Gallery</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadImageForm" enctype="multipart/form-data" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                    <input type="hidden" name="action" value="upload_image">
                    <div class="mb-3">
                        <label class="form-label">Image *</label>
                        <input type="file" class="form-control" name="image" accept="image/*" required>
                        <small class="text-muted">Supported formats: JPG, PNG, GIF, WebP</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Caption</label>
                        <input type="text" class="form-control" name="caption" placeholder="Enter image caption (optional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom" id="uploadImageBtn">Upload Image</button>
                </div>
            </form>
        </div>
    </div>
</div>

    <!-- Create Post Modal -->
    <div class="modal fade" id="createPostModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createPostModalForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <input type="hidden" name="action" value="create_post">
                        <div class="mb-3">
                            <textarea class="form-control" name="content" placeholder="Share your thoughts with the community..." rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-primary-custom" id="createPostModalBtn">Create Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Image View Modal -->
    <div class="modal fade" id="imageViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: var(--card-bg); color: var(--text-primary); border: 1px solid var(--border-color);">
                <div class="modal-header" style="border-bottom: 1px solid var(--border-color);">
                    <h5 class="modal-title" id="imageViewTitle">Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="imageViewEl" src="" alt="" style="max-width:100%; max-height:70vh; border-radius:8px; border:1px solid var(--border-color);" />
                    <p id="imageViewCaption" class="text-muted mt-2"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar functionality
        const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const adminSidebar = document.getElementById('adminSidebar');

        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', function() {
                adminSidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
            });
        }

        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                adminSidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            });
        }

        // Desktop sidebar toggle
        const adminMain = document.getElementById('adminMain');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');

        if (window.innerWidth > 768 && sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                adminSidebar.classList.toggle('collapsed');
                adminMain.classList.toggle('expanded');
                
                if (adminSidebar.classList.contains('collapsed')) {
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
        
        // Load gallery if section is active
        if (section === 'gallery') {
            loadGallery();
        }
        
        // Close mobile sidebar
        if (window.innerWidth <= 768) {
            adminSidebar.classList.remove('mobile-open');
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

        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const theme = html.getAttribute('data-theme');
                const newTheme = theme === 'light' ? 'dark' : 'light';
                
                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
        }

        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.className = 'bi bi-sun';
                themeText.textContent = 'Light Mode';
            } else {
                themeIcon.className = 'bi bi-moon';
                themeText.textContent = 'Dark Mode';
            }
        }

        // Show image file name when selected
        const postImage = document.getElementById('postImage');
        if (postImage) {
            postImage.addEventListener('change', function(e) {
                const fileName = this.files[0]?.name || '';
                const fileNameDisplay = document.getElementById('imageFileName');
                if (fileNameDisplay) {
                    fileNameDisplay.textContent = fileName;
                }
            });
        }

        // Form submission handlers
        document.addEventListener('DOMContentLoaded', function() {
            // Create Event Form
            const createEventForm = document.getElementById('createEventForm');
            if (createEventForm) {
                createEventForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'createEventBtn', 'Creating Event...');
                });
            }

            // Create Meeting Form
            const createMeetingForm = document.getElementById('createMeetingForm');
            if (createMeetingForm) {
                createMeetingForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'createMeetingBtn', 'Creating Meeting...');
                });
            }

           // Upload Image Form
const uploadImageForm = document.getElementById('uploadImageForm');
if (uploadImageForm) {
    uploadImageForm.addEventListener('submit', function(e) {
        e.preventDefault();
        uploadImage(this);
    });
}

            // Create Post Modal Form
            const createPostModalForm = document.getElementById('createPostModalForm');
            if (createPostModalForm) {
                createPostModalForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'createPostModalBtn', 'Creating Post...', true);
                });
            }

            // Auto-set dates and times
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

            const eventDateInput = document.querySelector('#createEventModal input[name="event_date"]');
            if (eventDateInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                eventDateInput.min = tomorrow.toISOString().split('T')[0];
                eventDateInput.value = tomorrow.toISOString().split('T')[0];
            }

            const eventTimeInput = document.querySelector('#createEventModal input[name="event_time"]');
            if (eventTimeInput) {
                const nextHour = new Date();
                nextHour.setHours(nextHour.getHours() + 1);
                nextHour.setMinutes(0);
                eventTimeInput.value = nextHour.toTimeString().substring(0, 5);
            }
        });

        // Generic form submission function
        async function submitForm(form, buttonId, loadingText, isFileUpload = false) {
            const submitButton = document.getElementById(buttonId);
            const originalText = submitButton.innerHTML;
            
            // Disable button and show loading
            submitButton.disabled = true;
            submitButton.innerHTML = `<i class="bi bi-arrow-repeat spinner"></i> ${loadingText}`;

            try {
                const formData = new FormData(form);
                
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                // For form submissions, we expect a page reload
                // The success message will be shown after page reload
                location.reload(); // Reload to show the new content and message

            } catch (error) {
                console.error('Error submitting form:', error);
                showTimedAlert('Network error. Please check your connection and try again.', 'danger');
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
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

        async function createPost() {
            const content = document.getElementById('postContent').value.trim();
            const imageFile = document.getElementById('postImage').files[0];
            const postButton = document.getElementById('postButton');
            
            if (!content && !imageFile) {
                showTimedAlert('Please add some content or an image to your post.', 'danger');
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
            formData.append('action', 'create_post');

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                // For form submissions, we expect a page reload
                location.reload();

            } catch (error) {
                console.error('Error creating post:', error);
                showTimedAlert('Network error. Please check your connection and try again.', 'danger');
                postButton.disabled = false;
                postButton.innerHTML = '<i class="bi bi-send"></i> Post';
            }
        }

        async function loadPosts() {
            // This would reload the posts section
            window.location.reload();
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
                    // Update the like count and state in the UI
                    const likeButton = document.getElementById(`like-btn-${postId}`);
                    const likeCount = document.getElementById(`like-count-${postId}`);
                    
                    if (likeButton && likeCount) {
                        likeButton.classList.toggle('liked', data.liked);
                        likeButton.innerHTML = `
                            <i class="bi ${data.liked ? 'bi-heart-fill' : 'bi-heart'}"></i> 
                            <span id="like-count-${postId}">${data.likes_count}</span>
                        `;
                    }
                    
                    showTimedAlert(data.liked ? 'Post liked!' : 'Post unliked!', 'success');
                } else {
                    showTimedAlert(data.message || 'Failed to like post. Please try again.', 'danger');
                }
            } catch (error) {
                console.error('Error liking post:', error);
                showTimedAlert('Network error. Please try again.', 'danger');
            }
        }

        function toggleComments(postId) {
            const commentsSection = document.getElementById(`comments-${postId}`);
            const commentBtn = document.getElementById(`comment-btn-${postId}`);
            
            if (!commentsSection || !commentBtn) return;
            
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
            if (!commentsList) return;
            
            commentsList.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spinner"></i> Loading comments...</div>';

            try {
                const response = await fetch(`../api/posts.php?action=comments&post_id=${postId}`);
                if (!response.ok) throw new Error('Failed to load comments');
                
                const comments = await response.json();
                
                if (comments.length === 0) {
                    commentsList.innerHTML = '<div class="text-muted text-center py-3">No comments yet. Be the first to comment!</div>';
                    return;
                }

                commentsList.innerHTML = comments.map(comment => `
                    <div class="comment">
                        <div class="comment-avatar">
                            ${comment.user_name ? comment.user_name.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <div class="comment-content">
                            <div class="comment-user">${comment.user_name || 'User'} <span class="comment-username">@${comment.username || 'user'}</span></div>
                            <div class="comment-text">${escapeHtml(comment.content)}</div>
                            <div class="comment-time">${formatTime(comment.created_at)}</div>
                        </div>
                    </div>
                `).join('');

            } catch (error) {
                console.error('Error loading comments:', error);
                commentsList.innerHTML = '<div class="alert alert-danger">Failed to load comments</div>';
            }
        }

        async function addComment(postId) {
            const commentInput = document.getElementById(`comment-input-${postId}`);
            if (!commentInput) return;
            
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
                    showTimedAlert('Comment added successfully!', 'success');
                    loadComments(postId);
                    
                    // Update comment count
                    const commentCount = document.getElementById(`comment-count-${postId}`);
                    if (commentCount) {
                        const currentCount = parseInt(commentCount.textContent) || 0;
                        commentCount.textContent = currentCount + 1;
                    }
                } else {
                    showTimedAlert(data.message || 'Failed to add comment. Please try again.', 'danger');
                }
            } catch (error) {
                console.error('Error adding comment:', error);
                showTimedAlert('Network error. Please try again.', 'danger');
            }
        }

        // Delete post function
        async function deletePost(postId) {
            if (!confirm('Are you sure you want to delete this post? This action cannot be undone.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete_post');
                formData.append('post_id', postId);
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                const response = await fetch('../controllers/AdminController.php', {
                    method: 'POST',
                    body: formData
                });

                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    // If not JSON, it might be an HTML error page
                    const text = await response.text();
                    console.error('Non-JSON response:', text.substring(0, 200));
                    throw new Error('Server returned HTML instead of JSON. Check for PHP errors.');
                }

                const data = await response.json();

                if (data.success) {
                    showTimedAlert('Post deleted successfully!', 'success');
                    // Remove post from UI
                    const postElement = document.getElementById(`post-${postId}`);
                    if (postElement) {
                        postElement.remove();
                    }
                } else {
                    showTimedAlert(data.message || 'Failed to delete post', 'danger');
                }
            } catch (error) {
                console.error('Error deleting post:', error);
                showTimedAlert('Failed to delete post: ' + error.message, 'danger');
            }
        }

       // Enhanced Gallery functionality
let currentGalleryImages = [];

// Load gallery function - matches user dashboard
async function loadGallery() {
    const galleryGrid = document.getElementById('galleryGrid');
    const galleryEmpty = document.getElementById('galleryEmpty');
    const galleryLoading = document.getElementById('galleryLoading');
    const galleryCount = document.getElementById('galleryCount');
    
    if (!galleryGrid) return;

    // Show loading state
    galleryGrid.innerHTML = '';
    galleryEmpty.style.display = 'none';
    if (galleryLoading) galleryLoading.style.display = 'block';

    try {
        const response = await fetch('../api/gallery.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();

        if (data.success && Array.isArray(data.images) && data.images.length > 0) {
            currentGalleryImages = data.images;
            
            galleryGrid.innerHTML = data.images.map(image => `
                <div class="col-md-4 col-lg-3">
                    <div class="gallery-item" onclick="viewImage('${image.file_name}', '${image.caption ? escapeHtml(image.caption) : ''}')">
                        <img src="../assets/uploads/gallery/${image.file_name}" 
                             alt="${image.caption || 'Gallery image'}" 
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjMzMzIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5vIEltYWdlPC90ZXh0Pjwvc3ZnPg=='">
                        <div class="gallery-caption">${image.caption || 'No caption'}</div>
                        <div class="gallery-actions mt-2">
                            <button class="btn-danger-custom btn-sm w-100" onclick="deleteGalleryImage(${image.id}, event)">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            
            galleryEmpty.style.display = 'none';
            if (galleryCount) {
                galleryCount.textContent = `${data.images.length} image${data.images.length === 1 ? '' : 's'}`;
            }
        } else {
            galleryGrid.innerHTML = '';
            galleryEmpty.style.display = 'block';
            if (galleryCount) {
                galleryCount.textContent = '0 images';
            }
        }
    } catch (error) {
        console.error('Error loading gallery:', error);
        galleryGrid.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Failed to load gallery. Please try again later.
                </div>
            </div>
        `;
        if (galleryCount) {
            galleryCount.textContent = 'Error loading';
        }
    } finally {
        if (galleryLoading) galleryLoading.style.display = 'none';
    }
}

// Delete gallery image function
async function deleteGalleryImage(imageId, event) {
    if (event) {
        event.stopPropagation(); // Prevent triggering the image view
    }
    
    if (!confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'delete_image');
        formData.append('image_id', imageId);
        formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

        const response = await fetch('../controllers/AdminController.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showTimedAlert('Image deleted successfully!', 'success');
            // Reload the gallery
            loadGallery();
        } else {
            showTimedAlert(data.message || 'Failed to delete image', 'danger');
        }
    } catch (error) {
        console.error('Error deleting gallery image:', error);
        showTimedAlert('Failed to delete image: ' + error.message, 'danger');
    }
}

// View image function
function viewImage(fileName, caption) {
    const imageViewModal = new bootstrap.Modal(document.getElementById('imageViewModal'));
    const imageViewEl = document.getElementById('imageViewEl');
    const imageViewCaption = document.getElementById('imageViewCaption');
    const imageViewTitle = document.getElementById('imageViewTitle');
    
    imageViewEl.src = `../assets/uploads/gallery/${fileName}`;
    imageViewCaption.textContent = caption || 'No caption';
    imageViewTitle.textContent = caption || 'Image';
    imageViewModal.show();
}

// Enhanced upload image function
async function uploadImage(form) {
    const uploadButton = document.getElementById('uploadImageBtn');
    const originalText = uploadButton.innerHTML;
    
    // Disable button and show loading
    uploadButton.disabled = true;
    uploadButton.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Uploading...';

    try {
        const formData = new FormData(form);
        
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });

        // For successful uploads, we'll reload the gallery
        const modal = bootstrap.Modal.getInstance(document.getElementById('uploadImageModal'));
        if (modal) {
            modal.hide();
        }
        
        // Reset form
        form.reset();
        
        // Show success message and reload gallery
        showTimedAlert('Image uploaded successfully!', 'success');
        loadGallery();

    } catch (error) {
        console.error('Error uploading image:', error);
        showTimedAlert('Network error. Please check your connection and try again.', 'danger');
    } finally {
        uploadButton.disabled = false;
        uploadButton.innerHTML = originalText;
    }
}

        function viewImage(imagePath, caption) {
            const imageViewModal = new bootstrap.Modal(document.getElementById('imageViewModal'));
            document.getElementById('imageViewEl').src = `../assets/uploads/gallery/${imagePath}`;
            document.getElementById('imageViewCaption').textContent = caption || 'No caption';
            document.getElementById('imageViewTitle').textContent = caption || 'Image';
            imageViewModal.show();
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
            if (!dateString) return 'Unknown time';
            
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
            // Remove any existing alerts first
            document.querySelectorAll('.timed-alert').forEach(alert => alert.remove());

            const alert = document.createElement('div');
            alert.className = `timed-alert alert alert-${type}`;
            alert.innerHTML = `
                <i class="bi ${getAlertIcon(type)}"></i>
                ${message}
            `;
            
            document.body.appendChild(alert);
            
            // Auto remove after 5 seconds
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
                if (adminSidebar) {
                    adminSidebar.classList.remove('collapsed');
                    adminSidebar.classList.remove('mobile-open');
                }
                if (adminMain) adminMain.classList.remove('expanded');
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            } else {
                if (sidebarToggle) sidebarToggle.style.display = 'flex';
                if (adminSidebar) adminSidebar.classList.remove('mobile-open');
                if (sidebarOverlay) sidebarOverlay.classList.remove('active');
            }
        });

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Any additional initialization code
        });
    </script>
</body>
</html>