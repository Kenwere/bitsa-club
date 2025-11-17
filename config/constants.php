<?php
// Site Configuration
define('SITE_NAME', 'Bitsa Club');
define('SITE_URL', 'http://localhost/bitsa-club');

// File Upload Paths
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('POST_IMAGE_PATH', UPLOAD_PATH . 'posts/');

// Session Configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// Email Configuration
define('EMAIL_FROM', 'noreply@bitsaclub.com');
define('EMAIL_FROM_NAME', 'Bitsa Club');

// Other Constants
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
?>