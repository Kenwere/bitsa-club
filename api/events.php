<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../models/Event.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Check authentication for both users and admins
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$event = new Event();

try {
    $action = $_GET['action'] ?? '';
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetRequest($action, $event);
            break;
            
        case 'POST':
            handlePostRequest($action, $event);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Events API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

function handleGetRequest($action, $event) {
    switch ($action) {
        case 'upcoming':
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $events = $event->getUpcoming($limit);
            echo json_encode(['success' => true, 'events' => $events]);
            break;
            
        case 'active':
            $events = $event->getActiveEvents();
            echo json_encode(['success' => true, 'events' => $events]);
            break;
            
        case 'all':
            $events = $event->getAll();
            echo json_encode(['success' => true, 'events' => $events]);
            break;
            
        default:
            // Return active events by default
            $events = $event->getActiveEvents();
            echo json_encode(['success' => true, 'events' => $events]);
    }
}

function handlePostRequest($action, $event) {
    // Verify CSRF token for all POST requests
    $csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        return;
    }
    
    switch ($action) {
        case 'attend':
            handleAttendEvent($event);
            break;
            
        case 'create':
            handleCreateEvent($event);
            break;
            
        case 'delete':
            handleDeleteEvent($event);
            break;
            
        case 'update':
            handleUpdateEvent($event);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
}

function handleAttendEvent($event) {
    $event_id = $_POST['event_id'] ?? $_GET['event_id'] ?? 0;
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if (!$event_id || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID or user ID']);
        return;
    }
    
    // Check if event exists and is active
    $event_data = $event->getEventById($event_id);
    if (!$event_data || !$event_data['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Event not found or inactive']);
        return;
    }
    
    // Check if event date hasn't passed
    $event_date = $event_data['event_date'] . ' ' . $event_data['event_time'];
    if (strtotime($event_date) < time()) {
        echo json_encode(['success' => false, 'message' => 'This event has already passed']);
        return;
    }
    
    // Check max attendees if set
    if ($event_data['max_attendees'] > 0) {
        $current_attendees = $event->getAttendeesCount($event_id);
        if ($current_attendees >= $event_data['max_attendees']) {
            echo json_encode(['success' => false, 'message' => 'This event is full']);
            return;
        }
    }
    
    $success = $event->attend($event_id, $user_id);
    echo json_encode([
        'success' => $success, 
        'message' => $success ? 'You are now attending this event' : 'Failed to attend event',
        'attendees_count' => $event->getAttendeesCount($event_id)
    ]);
}

function handleCreateEvent($event) {
    // Only admins can create events
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }
    
    $data = [
        'title' => escape($_POST['title'] ?? ''),
        'description' => escape($_POST['description'] ?? ''),
        'event_date' => escape($_POST['event_date'] ?? ''),
        'event_time' => escape($_POST['event_time'] ?? ''),
        'location' => escape($_POST['location'] ?? ''),
        'max_attendees' => isset($_POST['max_attendees']) ? (int)$_POST['max_attendees'] : null,
        'created_by' => $_SESSION['admin_id']
    ];
    
    // Validate required fields
    if (empty($data['title']) || empty($data['event_date']) || empty($data['event_time']) || empty($data['location'])) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    // Validate date is not in the past
    $event_datetime = $data['event_date'] . ' ' . $data['event_time'];
    if (strtotime($event_datetime) < time()) {
        echo json_encode(['success' => false, 'message' => 'Event date and time must be in the future']);
        return;
    }
    
    // Validate max attendees
    if ($data['max_attendees'] !== null && $data['max_attendees'] < 1) {
        echo json_encode(['success' => false, 'message' => 'Maximum attendees must be at least 1']);
        return;
    }
    
    $success = $event->create($data);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Event created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create event']);
    }
}

function handleDeleteEvent($event) {
    // Only admins can delete events
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }
    
    $event_id = $_POST['event_id'] ?? 0;
    if (!$event_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        return;
    }
    
    $success = $event->delete($event_id);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Event deleted successfully' : 'Failed to delete event'
    ]);
}

function handleUpdateEvent($event) {
    // Only admins can update events
    if (!isset($_SESSION['admin_id'])) {
        echo json_encode(['success' => false, 'message' => 'Admin access required']);
        return;
    }
    
    $event_id = $_POST['event_id'] ?? 0;
    if (!$event_id) {
        echo json_encode(['success' => false, 'message' => 'Invalid event ID']);
        return;
    }
    
    $data = [
        'title' => escape($_POST['title'] ?? ''),
        'description' => escape($_POST['description'] ?? ''),
        'event_date' => escape($_POST['event_date'] ?? ''),
        'event_time' => escape($_POST['event_time'] ?? ''),
        'location' => escape($_POST['location'] ?? ''),
        'max_attendees' => isset($_POST['max_attendees']) ? (int)$_POST['max_attendees'] : null
    ];
    
    // Validate required fields
    if (empty($data['title']) || empty($data['event_date']) || empty($data['event_time']) || empty($data['location'])) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        return;
    }
    
    $success = $event->update($event_id, $data);
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Event updated successfully' : 'Failed to update event'
    ]);
}
?>