<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../models/Post.php';

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

$post = new Post();

try {
    $action = $_GET['action'] ?? '';
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            if ($action === 'comments') {
                // Get comments for a specific post
                $post_id = $_GET['post_id'] ?? 0;
                if ($post_id) {
                    $comments = $post->getComments($post_id);
                    echo json_encode($comments);
                } else {
                    echo json_encode([]);
                }
            } else {
                // Get all posts
                $posts = $post->getAll();
                echo json_encode($posts);
            }
            break;
            
        case 'POST':
            if ($action === 'like') {
                // Like/unlike post
                $post_id = $_GET['post_id'] ?? 0;
                if ($post_id) {
                    $liked = $post->like($post_id, $_SESSION['user_id']);
                    $likes_count = $post->getLikesCount($post_id);
                    echo json_encode([
                        'success' => true,
                        'liked' => $liked,
                        'likes_count' => $likes_count
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
                }
            } elseif ($action === 'comment') {
                // Add comment
                $post_id = $_GET['post_id'] ?? 0;
                $input = json_decode(file_get_contents('php://input'), true);
                $content = $input['content'] ?? '';
                
                if ($post_id && $content) {
                    $success = $post->addComment($post_id, $_SESSION['user_id'], $content);
                    if ($success) {
                        echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to add comment']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Invalid data']);
                }
            } else {
                // Create new post
                if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                    break;
                }
                
                $content = escape($_POST['content'] ?? '');
                $image_path = null;
                
                // Handle file upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_result = uploadFile($_FILES['image'], '../assets/uploads/posts/');
                    if ($upload_result['success']) {
                        $image_path = $upload_result['file_path'];
                    }
                }
                
                if (empty($content) && empty($image_path)) {
                    echo json_encode(['success' => false, 'message' => 'Post must have content or image']);
                    break;
                }
                
                $success = $post->create([
                    'user_id' => $_SESSION['user_id'],
                    'content' => $content,
                    'image' => $image_path
                ]);
                
                if ($success) {
                    echo json_encode(['success' => true, 'message' => 'Post created successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create post']);
                }
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Posts API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>