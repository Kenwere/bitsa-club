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

$userModel = new User();
$postModel = new Post();
$meetingModel = new Meeting();
$eventModel = new Event();
$adminModel = new Admin();

// Get dashboard statistics
$stats = [
    'total_users' => $userModel->getTotalCount(),
    'total_posts' => $postModel->getTotalCount(),
    'total_meetings' => $meetingModel->getTotalCount(),
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
$meetings = $meetingModel->getAll();
$events = $eventModel->getAll();

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
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
            }
            break;
            
        case 'create_meeting':
            require_once '../controllers/AdminController.php';
            $adminController = new AdminController();
            $result = $adminController->createMeeting($_POST);
            if ($result['success']) {
                $_SESSION['success'] = $result['message'];
                header('Location: admin_dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
            }
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
        <?php include '../assets/css/dashboard.css'; ?>
        
        /* Admin specific styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .badge {
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
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

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }

        .table th {
            background: var(--bg-secondary);
            border: none;
            font-weight: 600;
            padding: 1rem;
        }

        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background: var(--bg-secondary);
        }

        .modal-content {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        .btn-close {
            filter: invert(1);
        }

        [data-theme="light"] .btn-close {
            filter: invert(0);
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
                <button type="submit" class="btn btn-danger btn-sm">
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
            </div>

            <div class="nav-section">
                <h3>Management</h3>
                <a href="#users" class="nav-item" data-section="users">
                    <i class="bi bi-people"></i> <span>User Management</span>
                </a>
                <a href="#posts" class="nav-item" data-section="posts">
                    <i class="bi bi-file-text"></i> <span>Post Management</span>
                </a>
                <a href="#meetings" class="nav-item" data-section="meetings">
                    <i class="bi bi-camera-video"></i> <span>Meeting Management</span>
                </a>
                <a href="#events" class="nav-item" data-section="events">
                    <i class="bi bi-calendar-event"></i> <span>Event Management</span>
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

            <!-- Overview Section -->
            <section id="overview-section" class="content-section active">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Dashboard Overview</h2>
                    <div class="text-muted">Last updated: <?php echo date('M j, Y g:i A'); ?></div>
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
                        <div class="stat-number text-secondary"><?php echo $stats['upcoming_events']; ?></div>
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
                                            <?php foreach($recent_posts as $post): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(strlen($post['content']) > 50 ? substr($post['content'], 0, 50) . '...' : $post['content']); ?></td>
                                                <td><?php echo htmlspecialchars($post['user_name']); ?></td>
                                                <td><?php echo formatDate($post['created_at']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h3 class="card-title">Upcoming Events</h3>
                    </div>
                    <div class="card-body">
                        <?php if(count($upcoming_events) > 0): ?>
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
                        <div class="text-center py-4">
                            <i class="bi bi-calendar-x display-4 text-muted mb-3"></i>
                            <h4 class="text-muted">No Upcoming Events</h4>
                            <p class="text-muted">Create events to display them here.</p>
                        </div>
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
                                                        <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Are you sure you want to suspend this user?')">Suspend</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="activate_user">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Are you sure you want to activate this user?')">Activate</button>
                                                    </form>
                                                <?php endif; ?>
                                                <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Post Management Section -->
            <section id="posts-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">Post Management</h2>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Content</th>
                                        <th>User</th>
                                        <th>Likes</th>
                                        <th>Comments</th>
                                        <th>Posted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($posts as $post): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(strlen($post['content']) > 80 ? substr($post['content'], 0, 80) . '...' : $post['content']); ?></td>
                                        <td><?php echo htmlspecialchars($post['user_name']); ?></td>
                                        <td><?php echo $post['likes_count']; ?></td>
                                        <td><?php echo $post['comments_count']; ?></td>
                                        <td><?php echo formatDate($post['created_at']); ?></td>
                                        <td>
                                            <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_post">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this post?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Meeting Management Section -->
            <section id="meetings-section" class="content-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Meeting Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createMeetingModal">
                            <i class="bi bi-plus-circle"></i> Create Meeting
                        </button>
                    </div>
                    <div class="card-body">
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
                                        <td><?php echo $meeting['participants_count']; ?></td>
                                        <td>
                                            <span class="badge <?php echo $meeting['type'] === 'public' ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo ucfirst($meeting['type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $meeting['is_active'] ? 'badge-success' : 'badge-secondary'; ?>">
                                                <?php echo $meeting['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form action="../controllers/AdminController.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="delete_meeting">
                                                <input type="hidden" name="meeting_id" value="<?php echo $meeting['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this meeting?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Event Management Section -->
            <section id="events-section" class="content-section">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Event Management</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createEventModal">
                            <i class="bi bi-plus-circle"></i> Create Event
                        </button>
                    </div>
                    <div class="card-body">
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
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this event?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_event">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event Title *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Location *</label>
                                    <input type="text" class="form-control" name="location" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
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
                            <input type="number" class="form-control" name="max_attendees" min="1">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Event</button>
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
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_meeting">
                        <input type="hidden" name="csrf_token" value="<?php echo csrf_token(); ?>">
                        <div class="mb-3">
                            <label class="form-label">Meeting Title *</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
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
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Meeting</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile sidebar functionality
        const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const adminSidebar = document.getElementById('adminSidebar');

        mobileSidebarToggle.addEventListener('click', function() {
            adminSidebar.classList.toggle('mobile-open');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', function() {
            adminSidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('active');
        });

        // Desktop sidebar toggle
        const adminMain = document.getElementById('adminMain');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');

        if (window.innerWidth > 768) {
            sidebarToggle.addEventListener('click', function() {
                adminSidebar.classList.toggle('collapsed');
                adminMain.classList.toggle('expanded');
                
                if (adminSidebar.classList.contains('collapsed')) {
                    sidebarToggleIcon.className = 'bi bi-chevron-right';
                } else {
                    sidebarToggleIcon.className = 'bi bi-chevron-left';
                }
            });
        } else {
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

        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth <= 768) {
                sidebarToggle.style.display = 'none';
                adminSidebar.classList.remove('collapsed');
                adminMain.classList.remove('expanded');
            } else {
                sidebarToggle.style.display = 'flex';
                adminSidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            }
        });

        // Auto-set event date to tomorrow
        document.addEventListener('DOMContentLoaded', function() {
            const eventDateInput = document.querySelector('input[name="event_date"]');
            if (eventDateInput) {
                const tomorrow = new Date();
                tomorrow.setDate(tomorrow.getDate() + 1);
                eventDateInput.min = tomorrow.toISOString().split('T')[0];
                eventDateInput.value = tomorrow.toISOString().split('T')[0];
            }

            // Auto-set meeting date to today
            const meetingDateInput = document.querySelector('input[name="date"]');
            if (meetingDateInput) {
                const today = new Date();
                meetingDateInput.min = today.toISOString().split('T')[0];
                meetingDateInput.value = today.toISOString().split('T')[0];
            }

            // Auto-set meeting time to next hour
            const meetingTimeInput = document.querySelector('input[name="time"]');
            if (meetingTimeInput) {
                const nextHour = new Date();
                nextHour.setHours(nextHour.getHours() + 1);
                nextHour.setMinutes(0);
                meetingTimeInput.value = nextHour.toTimeString().substring(0, 5);
            }
        });

        // Show alerts
        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('.admin-main').insertBefore(alert, document.querySelector('.admin-main').firstChild);
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>