<?php
require_once '../models/Meeting.php';
require_once '../includes/auth.php';

class MeetingController {
    private $meetingModel;

    public function __construct() {
        $this->meetingModel = new Meeting();
    }

    public function createMeeting($data) {
        try {
            // Validate required fields
            if (empty($data['title']) || empty($data['date']) || empty($data['time'])) {
                return [
                    'success' => false,
                    'message' => 'All required fields must be filled'
                ];
            }

            // Validate date and time
            $scheduled_time = $data['date'] . ' ' . $data['time'];
            if (strtotime($scheduled_time) <= time()) {
                return [
                    'success' => false,
                    'message' => 'Meeting time must be in the future'
                ];
            }

            // Use create method for both users and admins (since we removed admin_id)
            $success = $this->meetingModel->create($data);

            return [
                'success' => $success,
                'message' => $success ? 'Meeting created successfully' : 'Failed to create meeting'
            ];

        } catch (Exception $e) {
            error_log("MeetingController Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create meeting'
            ];
        }
    }
}

// Handle direct API calls
if (isset($_POST['action']) && realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
    
    $controller = new MeetingController();
    $action = $_POST['action'];
    
    if ($action === 'create') {
        $data = $_POST;
        
        // Set user_id based on session
        if (isset($_SESSION['admin_id'])) {
            $data['user_id'] = $_SESSION['admin_id']; // Use admin_id as user_id
        } else if (isset($_SESSION['user_id'])) {
            $data['user_id'] = $_SESSION['user_id'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        
        $result = $controller->createMeeting($data);
        echo json_encode($result);
    }
}
?>