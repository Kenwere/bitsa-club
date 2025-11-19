<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    public function registerUser($data) {
    // Validate data
    $errors = $this->validateUserRegistration($data);
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check if user already exists
    if ($this->userExists($data['email'], $data['username'])) {
        return ['success' => false, 'errors' => ['email' => 'User with this email or username already exists']];
    }
    
    // Hash password
    $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
    
    try {
        // Updated query to match your actual database columns
        $stmt = $this->db->prepare("
            INSERT INTO users (name, email, username, password_hash, role, is_active, is_verified, created_at) 
            VALUES (?, ?, ?, ?, 'user', 1, 0, NOW())
        ");
        
        $stmt->execute([
            $data['name'],
            $data['email'],
            $data['username'],
            $hashed_password
        ]);
        
        $user_id = $this->db->lastInsertId();
        
        // Log the user in
        return $this->loginUser($data['email'], $data['password']);
        
    } catch (PDOException $e) {
        error_log("User registration error: " . $e->getMessage());
        return ['success' => false, 'errors' => ['database' => 'Registration failed. Please try again.']];
    }
}
    
    public function loginUser($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE email = ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $passwordColumn = !empty($user['password_hash']) ? 'password_hash' : 'password';
                if (!isset($user[$passwordColumn]) || !password_verify($password, $user[$passwordColumn])) {
                    return ['success' => false, 'message' => 'Invalid email or password'];
                }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                
                // Update last login
                $this->updateLastLogin($user['id'], 'user');
                
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'message' => 'Invalid email or password'];
            
        } catch (PDOException $e) {
            error_log("User login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    public function registerAdmin($data) {
        // Validate data
        $errors = $this->validateAdminRegistration($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if admin already exists
        if ($this->adminExists($data['email'], $data['username'])) {
            return ['success' => false, 'errors' => ['email' => 'Admin with this email or username already exists']];
        }
        
        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO admins (name, email, username, password, is_active, created_at) 
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['username'],
                $hashed_password
            ]);
            
            $admin_id = $this->db->lastInsertId();
            
            // Log the admin in
            return $this->loginAdmin($data['email'], $data['password']);
            
        } catch (PDOException $e) {
            error_log("Admin registration error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['database' => 'Registration failed. Please try again.']];
        }
    }
    
    public function loginAdmin($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM admins 
                WHERE email = ? AND is_active = 1 
                LIMIT 1
            ");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                $adminPasswordColumn = !empty($admin['password_hash']) ? 'password_hash' : 'password';
                if (!isset($admin[$adminPasswordColumn]) || !password_verify($password, $admin[$adminPasswordColumn])) {
                    return ['success' => false, 'message' => 'Invalid email or password'];
                }
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['name'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_username'] = $admin['username'];
                
                // Update last login
                $this->updateLastLogin($admin['id'], 'admin');
                
                return ['success' => true, 'admin' => $admin];
            }
            
            return ['success' => false, 'message' => 'Invalid email or password'];
            
        } catch (PDOException $e) {
            error_log("Admin login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed. Please try again.'];
        }
    }
    
    private function validateUserRegistration($data) {
        $errors = [];
        
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Name must be at least 2 characters long';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($data['username']) || strlen(trim($data['username'])) < 3) {
            $errors['username'] = 'Username must be at least 3 characters long';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }
        
        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }
        
        return $errors;
    }
    
    private function validateAdminRegistration($data) {
        $errors = [];
        
        if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
            $errors['name'] = 'Name must be at least 2 characters long';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        if (empty($data['username']) || strlen(trim($data['username'])) < 3) {
            $errors['username'] = 'Username must be at least 3 characters long';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'Username can only contain letters, numbers, and underscores';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }
        
        if ($data['password'] !== $data['password_confirmation']) {
            $errors['password_confirmation'] = 'Passwords do not match';
        }
        
        if (!isset($data['terms']) || !$data['terms']) {
            $errors['terms'] = 'You must agree to the terms and conditions';
        }
        
        return $errors;
    }
    
    private function userExists($email, $username) {
        $stmt = $this->db->prepare("
            SELECT id FROM users 
            WHERE email = ? OR username = ? 
            LIMIT 1
        ");
        $stmt->execute([$email, $username]);
        return $stmt->fetch() !== false;
    }
    
    private function adminExists($email, $username) {
        $stmt = $this->db->prepare("
            SELECT id FROM admins 
            WHERE email = ? OR username = ? 
            LIMIT 1
        ");
        $stmt->execute([$email, $username]);
        return $stmt->fetch() !== false;
    }
    
    private function updateLastLogin($id, $type = 'user') {
        try {
            $table = $type === 'admin' ? 'admins' : 'users';
            $stmt = $this->db->prepare("UPDATE $table SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Last login update error: " . $e->getMessage());
        }
    }
    
    public function logout() {
        // Clear all session variables
        $_SESSION = array();
        
        // Destroy the session
        session_destroy();
        
        // Start a new session
        session_start();
    }
    
    public function getCurrentUser() {
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                return $stmt->fetch();
            } catch (PDOException $e) {
                error_log("Get current user error: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }
    
    public function getCurrentAdmin() {
        if (isset($_SESSION['admin_id'])) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM admins WHERE id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                return $stmt->fetch();
            } catch (PDOException $e) {
                error_log("Get current admin error: " . $e->getMessage());
                return null;
            }
        }
        return null;
    }
    
    public function requireAuth($type = 'user') {
        if ($type === 'admin') {
            if (!isset($_SESSION['admin_id'])) {
                $_SESSION['error'] = 'Please log in as administrator';
                header('Location: ../auth/admin-login.php');
                exit;
            }
        } else {
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['error'] = 'Please log in to continue';
                header('Location: ../auth/user-login.php');
                exit;
            }
        }
    }
}
?>