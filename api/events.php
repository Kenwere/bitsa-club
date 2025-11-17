<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../models/User.php';

// Enable CORS and set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user = new User();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            // Get user profile
            $user_data = $user->findById($_SESSION['user_id']);
            echo json_encode($user_data);
            break;
            
        case 'POST':
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                break;
            }
            
            // Update profile
            $data = [
                'name' => escape($_POST['name'] ?? ''),
                'username' => escape($_POST['username'] ?? ''),
                'bio' => escape($_POST['bio'] ?? ''),
                'skills' => escape($_POST['skills'] ?? '')
            ];
            
            // Validate required fields
            if (empty($data['name']) || empty($data['username'])) {
                echo json_encode(['success' => false, 'message' => 'Name and username are required']);
                break;
            }
            
            $success = $user->updateProfile($_SESSION['user_id'], $data);
            
            if ($success) {
                // Update session data
                $_SESSION['user_name'] = $data['name'];
                $_SESSION['user_username'] = $data['username'];
                
                echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Profile API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>