<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../models/Meeting.php';

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
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$meeting = new Meeting();

try {
    $action = $_GET['action'] ?? '';
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'user') {
                $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
                $meetings = $meeting->getUserMeetings($user_id);
                echo json_encode(['success' => true, 'meetings' => $meetings]);
            } elseif ($action === 'active') {
                $meetings = $meeting->getActiveMeetings();
                echo json_encode(['success' => true, 'meetings' => $meetings]);
            } elseif ($action === 'scheduled') {
                $meetings = $meeting->getScheduledMeetings();
                echo json_encode(['success' => true, 'meetings' => $meetings]);
            } elseif ($action === 'all') {
                $response = [
                    'success' => true,
                    'active' => $meeting->getActiveMeetings(),
                    'scheduled' => $meeting->getScheduledMeetings()
                ];
                echo json_encode($response);
            } else {
                $meetings = $meeting->getAllMeetings();
                echo json_encode(['success' => true, 'meetings' => $meetings]);
            }
            break;
            
        case 'POST':
            // Get CSRF token from header or POST data
            $csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!verify_csrf_token($csrf_token)) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                break;
            }
            
            if ($action === 'join') {
                // Join meeting
                $meeting_id = $_POST['meeting_id'] ?? $_GET['meeting_id'] ?? 0;
                $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
                
                if ($meeting_id && $user_id) {
                    // Check if meeting is joinable
                    if ($meeting->isMeetingJoinable($meeting_id)) {
                        $success = $meeting->join($meeting_id, $user_id);
                        echo json_encode([
                            'success' => $success, 
                            'message' => $success ? 'Joined meeting successfully' : 'Failed to join meeting'
                        ]);
                    } else {
                        echo json_encode([
                            'success' => false, 
                            'message' => 'Meeting is not active or has not started yet'
                        ]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid meeting ID or user ID']);
                }
            } elseif ($action === 'leave') {
                // Leave meeting (you'll need to implement this method)
                echo json_encode(['success' => false, 'message' => 'Leave meeting not implemented yet']);
            } elseif ($action === 'delete') {
                // Delete meeting
                $meeting_id = $_POST['meeting_id'] ?? $_GET['meeting_id'] ?? 0;
                $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
                
                if ($meeting_id) {
                    $meeting_data = $meeting->findById($meeting_id);
                    if ($meeting_data && ($meeting_data['user_id'] == $user_id || isset($_SESSION['admin_id']))) {
                        $success = $meeting->delete($meeting_id);
                        echo json_encode([
                            'success' => $success, 
                            'message' => $success ? 'Meeting deleted successfully' : 'Failed to delete meeting'
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'You can only delete your own meetings']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid meeting ID']);
                }
            } else {
                // Create new meeting
                $user_id = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 0;
                
                $data = [
                    'user_id' => $user_id,
                    'title' => escape($_POST['title'] ?? ''),
                    'description' => escape($_POST['description'] ?? ''),
                    'date' => escape($_POST['date'] ?? ''),
                    'time' => escape($_POST['time'] ?? ''),
                    'type' => escape($_POST['type'] ?? 'public')
                ];
                
                // Validate required fields
                if (empty($data['title']) || empty($data['date']) || empty($data['time'])) {
                    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
                    break;
                }
                
                // Validate date and time
                $scheduled_time = $data['date'] . ' ' . $data['time'];
                if (strtotime($scheduled_time) <= time()) {
                    echo json_encode(['success' => false, 'message' => 'Meeting time must be in the future']);
                    break;
                }
                
                $success = $meeting->create($data);
                
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Meeting created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create meeting']);
                }
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Meetings API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error: ' . $e->getMessage()]);
}
?>