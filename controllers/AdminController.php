<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Meeting.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Gallery.php';

class AdminController {
    private $user;
    private $post;
    private $meeting;
    private $event;
    private $admin;
    private $gallery;
    
    public function __construct() {
        $this->user = new User();
        $this->post = new Post();
        $this->meeting = new Meeting();
        $this->event = new Event();
        $this->admin = new Admin();
        $this->gallery = new Gallery();
    }
    
    // Add the missing uploadImage method
    public function uploadImage($data, $files) {
        if (!verify_csrf_token($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }
        
        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($files['image'], '../assets/uploads/gallery/');
            if ($upload_result['success']) {
                $caption = escape($data['caption'] ?? '');
                $success = $this->gallery->addImage(
                    $upload_result['file_path'],
                    $caption,
                    $_SESSION['admin_id'],
                    'admin'
                );
                
                if ($success) {
                    return [
                        'success' => true, 
                        'message' => 'Image uploaded successfully',
                        'image' => [
                            'file_name' => $upload_result['file_path'],
                            'caption' => $caption
                        ]
                    ];
                } else {
                    return ['success' => false, 'message' => 'Failed to save image to database'];
                }
            } else {
                return ['success' => false, 'message' => $upload_result['message']];
            }
        } else {
            return ['success' => false, 'message' => 'No image file uploaded or upload error'];
        }
    }
    
    // Fix the createPost method
    public function createPost($data, $files) {
        if (!verify_csrf_token($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }
        
        $content = escape($data['content'] ?? '');
        $image_path = null;
        
        // Handle file upload
        if (isset($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = uploadFile($files['image'], '../assets/uploads/posts/');
            if ($upload_result['success']) {
                $image_path = $upload_result['file_path'];
            } else {
                return ['success' => false, 'message' => $upload_result['message']];
            }
        }
        
        if (empty($content) && empty($image_path)) {
            return ['success' => false, 'message' => 'Post must have content or image'];
        }
        
        $post_data = [
            'admin_id' => $_SESSION['admin_id'],
            'content' => $content,
            'image' => $image_path
        ];
        
        $success = $this->post->createAdminPost($post_data);
        
        return [
            'success' => $success,
            'message' => $success ? 'Post created successfully' : 'Failed to create post'
        ];
    }
    
    // Other methods remain the same...
    public function createEvent($data) {
        if (!verify_csrf_token($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }
        
        $event_data = [
            'title' => escape($data['title'] ?? ''),
            'description' => escape($data['description'] ?? ''),
            'event_date' => escape($data['event_date'] ?? ''),
            'event_time' => escape($data['event_time'] ?? ''),
            'location' => escape($data['location'] ?? ''),
            'max_attendees' => isset($data['max_attendees']) && !empty($data['max_attendees']) ? (int)$data['max_attendees'] : null,
            'created_by' => $_SESSION['admin_id']
        ];
        
        if (empty($event_data['title']) || empty($event_data['event_date']) || empty($event_data['event_time']) || empty($event_data['location'])) {
            return ['success' => false, 'message' => 'All required fields must be filled'];
        }
        
        $success = $this->event->create($event_data);
        
        return [
            'success' => $success,
            'message' => $success ? 'Event created successfully' : 'Failed to create event'
        ];
    }
    
    public function createMeeting($data) {
        if (!verify_csrf_token($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }
        
        $meeting_data = [
            'user_id' => $_SESSION['admin_id'], // Use admin_id as user_id
            'title' => escape($data['title'] ?? ''),
            'description' => escape($data['description'] ?? ''),
            'date' => escape($data['date'] ?? ''),
            'time' => escape($data['time'] ?? ''),
            'type' => escape($data['type'] ?? 'public')
        ];
        
        if (empty($meeting_data['title']) || empty($meeting_data['date']) || empty($meeting_data['time'])) {
            return ['success' => false, 'message' => 'All required fields must be filled'];
        }
        
        $scheduled_time = $meeting_data['date'] . ' ' . $meeting_data['time'];
        if (strtotime($scheduled_time) <= time()) {
            return ['success' => false, 'message' => 'Meeting time must be in the future'];
        }
        
        $success = $this->meeting->create($meeting_data);
        
        return [
            'success' => $success,
            'message' => $success ? 'Meeting created successfully' : 'Failed to create meeting'
        ];
    }

    // Add this method to handle AJAX post deletion
    public function deletePost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                return;
            }
            
            $post_id = $_POST['post_id'] ?? 0;
            if ($post_id) {
                $success = $this->post->delete($post_id);
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'Post deleted successfully' : 'Failed to delete post'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            }
        }
    }

    // Other existing methods...
    public function deleteMeeting() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                return;
            }
            
            $meeting_id = $_POST['meeting_id'] ?? 0;
            if ($meeting_id) {
                $success = $this->meeting->delete($meeting_id);
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'Meeting deleted successfully' : 'Failed to delete meeting'
                ]);
            }
        }
    }
    
    public function deleteEvent() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                return;
            }
            
            $event_id = $_POST['event_id'] ?? 0;
            if ($event_id) {
                $success = $this->event->delete($event_id);
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'Event deleted successfully' : 'Failed to delete event'
                ]);
            }
        }
    }

    public function suspendUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                return;
            }
            
            $user_id = $_POST['user_id'] ?? 0;
            if ($user_id) {
                $success = $this->user->suspend($user_id);
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'User suspended successfully' : 'Failed to suspend user'
                ]);
            }
        }
    }
    
    public function activateUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                return;
            }
            
            $user_id = $_POST['user_id'] ?? 0;
            if ($user_id) {
                $success = $this->user->activate($user_id);
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'User activated successfully' : 'Failed to activate user'
                ]);
            }
        }
    }
    
    public function deleteUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                return;
            }
            
            $user_id = $_POST['user_id'] ?? 0;
            if ($user_id) {
                $success = $this->user->delete($user_id);
                echo json_encode([
                    'success' => $success, 
                    'message' => $success ? 'User deleted successfully' : 'Failed to delete user'
                ]);
            }
        }
    }
    
    public function updateProfile($data) {
        if (!verify_csrf_token($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid CSRF token'];
        }
        
        $update_data = [
            'name' => escape($data['name'] ?? ''),
            'username' => escape($data['username'] ?? ''),
            'contact' => escape($data['contact'] ?? '')
        ];
        
        if (empty($update_data['name']) || empty($update_data['username'])) {
            return ['success' => false, 'message' => 'Name and username are required'];
        }
        
        // Handle password update if provided
        $newPassword = $data['password'] ?? '';
        $confirmPassword = $data['password_confirmation'] ?? '';

        if (!empty($newPassword) || !empty($confirmPassword)) {
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'message' => 'Password must be at least 8 characters long'];
            }
            if ($newPassword !== $confirmPassword) {
                return ['success' => false, 'message' => 'Passwords do not match'];
            }
            $update_data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        
        $success = $this->admin->update($_SESSION['admin_id'], $update_data);
        
        if ($success) {
            $_SESSION['admin_name'] = $update_data['name'];
            $_SESSION['admin_username'] = $update_data['username'];
            $_SESSION['admin_contact'] = $update_data['contact'];
        }
        
        return [
            'success' => $success,
            'message' => $success ? 'Profile updated successfully' : 'Failed to update profile'
        ];
    }
}

// Handle direct AJAX requests
if (isset($_POST['action']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    $controller = new AdminController();
    $action = $_POST['action'];
    
    switch ($action) {
        case 'suspend_user':
            $controller->suspendUser();
            break;
        case 'activate_user':
            $controller->activateUser();
            break;
        case 'delete_user':
            $controller->deleteUser();
            break;
        case 'delete_post':
            $controller->deletePost();
            break;
        case 'delete_meeting':
            $controller->deleteMeeting();
            break;
        case 'delete_event':
            $controller->deleteEvent();
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
    exit;
}
?>