<?php
require_once __DIR__ . '/../includes/auth.php';

class AuthController {
    private $auth;
    
    public function __construct() {
        $this->auth = new Auth();
    }
    
    public function registerUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify CSRF token
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: user-register.php');
                exit;
            }
            
            $data = escape($_POST);
            $result = $this->auth->registerUser($data);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Account created successfully! Welcome to Bitsa Club!';
                header('Location: ../user/dashboard.php');
                exit;
            } else {
                $_SESSION['errors'] = $result['errors'];
                $_SESSION['old_input'] = $data;
                header('Location: user-register.php');
                exit;
            }
        }
    }
    
    public function loginUser() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify CSRF token
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: user-login.php');
                exit;
            }
            
            $email = escape($_POST['email']);
            $password = $_POST['password'];
            
            $result = $this->auth->loginUser($email, $password);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Login successful!';
                header('Location: ../user/dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
                $_SESSION['old_email'] = $email;
                header('Location: user-login.php');
                exit;
            }
        }
    }
    
    public function registerAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify CSRF token
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: admin-register.php');
                exit;
            }
            
            $data = escape($_POST);
            $result = $this->auth->registerAdmin($data);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Admin account created successfully!';
                header('Location: ../admin/dashboard.php');
                exit;
            } else {
                $_SESSION['errors'] = $result['errors'];
                $_SESSION['old_input'] = $data;
                header('Location: admin-register.php');
                exit;
            }
        }
    }
    
    public function loginAdmin() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify CSRF token
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid CSRF token';
                header('Location: admin-login.php');
                exit;
            }
            
            $email = escape($_POST['email']);
            $password = $_POST['password'];
            
            $result = $this->auth->loginAdmin($email, $password);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Admin login successful!';
                header('Location: ../admin/dashboard.php');
                exit;
            } else {
                $_SESSION['error'] = $result['message'];
                $_SESSION['old_email'] = $email;
                header('Location: admin-login.php');
                exit;
            }
        }
    }
    
    public function logout() {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to home page
        header('Location: ../index.php');
        exit;
    }
    
    public function logoutAdmin() {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to home page
        header('Location: ../index.php');
        exit;
    }
}
?>