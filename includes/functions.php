<?php
// functions.php - Essential helper functions

/**
 * Redirect to a specific URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Escape output for security
 */
function escape($data) {
    if (is_array($data)) {
        return array_map('escape', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 */
function formatDate($dateString, $format = 'M j, Y g:i A') {
    if (empty($dateString) || $dateString === '0000-00-00 00:00:00') {
        return 'Unknown date';
    }
    return date($format, strtotime($dateString));
}

/**
 * Handle file uploads
 */
function uploadFile($file, $uploadPath) {
    // Check if upload directory exists
    if (!file_exists($uploadPath)) {
        if (!mkdir($uploadPath, 0777, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory'];
        }
    }

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        return ['success' => false, 'message' => $errorMessages[$file['error']] ?? 'Unknown upload error'];
    }

    // Check file size (max 2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File size must be less than 2MB'];
    }

    // Check file type for images
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Only JPEG, PNG, GIF, and WebP images are allowed'];
    }

    // Generate unique filename
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadPath . $fileName;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'file_path' => $fileName];
    } else {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }
}

/**
 * Generate CSRF token
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? null;
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return "0 Bytes";
    $k = 1024;
    $sizes = ["Bytes", "KB", "MB", "GB"];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . " " . $sizes[$i];
}

/**
 * Sanitize database input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return trim(stripslashes(htmlspecialchars($data)));
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Log errors to file
 */
function logError($message) {
    $logFile = __DIR__ . '/../logs/error.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Get client IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Convert to camelCase
 */
function toCamelCase($string) {
    $string = str_replace(['-', '_'], ' ', $string);
    $string = ucwords($string);
    $string = str_replace(' ', '', $string);
    return lcfirst($string);
}

/**
 * Convert to snake_case
 */
function toSnakeCase($string) {
    $string = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);
    return strtolower($string);
}

/**
 * Get current URL
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
}
?>