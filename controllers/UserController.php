<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/../models/Meeting.php';
require_once __DIR__ . '/../models/Event.php';

class UserController {
    private $user;
    private $post;
    private $meeting;
    private $event;
    
    public function __construct() {
        $this->user = new User();
        $this->post = new Post();
        $this->meeting = new Meeting();
        $this->event = new Event();
    }
    
    public function dashboard() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../auth/user-login.php');
            exit;
        }
        
        $current_user = $this->user->find($_SESSION['user_id']);
        $posts = $this->post->getAll();
        $meetings = $this->meeting->getUserMeetings($_SESSION['user_id']);
        $events = $this->event->getAll();
        
        return [
            'user' => $current_user,
            'posts' => $posts,
            'meetings' => $meetings,
            'events' => $events
        ];
    }
    
    public function updateProfile() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => escape($_POST['name'] ?? ''),
                'username' => escape($_POST['username'] ?? ''),
                'bio' => escape($_POST['bio'] ?? ''),
                'skills' => escape($_POST['skills'] ?? '')
            ];
            
            $result = $this->user->updateProfile($_SESSION['user_id'], $data);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
        }
    }
}

// Handle direct requests
if (isset($_POST['action']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    $controller = new UserController();
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $controller->updateProfile();
    }
}
?>