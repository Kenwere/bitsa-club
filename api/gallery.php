<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../models/Gallery.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-TOKEN');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$galleryModel = new Gallery();

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            $images = $galleryModel->getAll();

            // Include files that might exist without DB entries
            $gallery_path = '../assets/uploads/gallery/';
            if (file_exists($gallery_path)) {
                $files = scandir($gallery_path);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                        continue;
                    }

                    $alreadyTracked = array_filter($images, function($img) use ($file) {
                        return isset($img['file_name']) && $img['file_name'] === $file;
                    });

                    if (empty($alreadyTracked)) {
                        $images[] = [
                            'file_name' => $file,
                            'caption' => null,
                            'uploaded_by' => null,
                            'uploaded_type' => null,
                            'created_at' => date('Y-m-d H:i:s', filemtime($gallery_path . $file))
                        ];
                    }
                }
            }

            // Normalize payload
            $normalized = array_map(function($image) {
                return [
                    'file_name' => $image['file_name'],
                    'caption' => $image['caption'] ?? null,
                    'uploaded_by' => $image['uploaded_by'] ?? null,
                    'uploaded_type' => $image['uploaded_type'] ?? null,
                    'created_at' => $image['created_at'] ?? null
                ];
            }, $images);
            
            echo json_encode(['success' => true, 'images' => $normalized]);
            break;
            
        case 'POST':
            // Upload image (admin only)
            if (!isset($_SESSION['admin_id'])) {
                echo json_encode(['success' => false, 'message' => 'Admin access required']);
                break;
            }
            
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
                break;
            }
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $gallery_path = '../assets/uploads/gallery/';
                if (!file_exists($gallery_path)) {
                    mkdir($gallery_path, 0777, true);
                }
                
                $upload_result = uploadFile($_FILES['image'], $gallery_path);
                if ($upload_result['success']) {
                    $caption = escape($_POST['caption'] ?? '');
                    $galleryModel->addImage(
                        $upload_result['file_path'],
                        $caption,
                        $_SESSION['admin_id'],
                        'admin'
                    );
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Image uploaded successfully',
                        'image' => [
                            'file_name' => $upload_result['file_path'],
                            'caption' => $caption
                        ]
                    ]);
                } else {
                    echo json_encode(['success' => false, 'message' => $upload_result['message']]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'No image file uploaded']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Gallery API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>