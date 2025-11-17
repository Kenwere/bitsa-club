<?php
require_once '../config/database.php';

class User {
    private $db;
    private $table = 'users';

    public function __construct() {
        $this->db = new Database();
    }

    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['count'];
    }

    public function getRecent($limit = 5) {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAll() {
        $query = "SELECT u.*, COUNT(p.id) as posts_count 
                  FROM {$this->table} u 
                  LEFT JOIN posts p ON u.id = p.user_id 
                  GROUP BY u.id 
                  ORDER BY u.created_at DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Alias for findById for compatibility
    public function find($id) {
        return $this->findById($id);
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (name, email, username, password, role, is_active) VALUES (?, ?, ?, ?, 'user', 1)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssss', $data['name'], $data['email'], $data['username'], $data['password']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET name = ?, username = ?, bio = ?, skills = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $skills = isset($data['skills']) ? $data['skills'] : null;
        $stmt->bind_param('ssssi', $data['name'], $data['username'], $data['bio'], $skills, $id);
        return $stmt->execute();
    }

    // Alias for update for profile updates
    public function updateProfile($user_id, $data) {
        return $this->update($user_id, $data);
    }

    public function suspend($id) {
        $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function activate($id) {
        $query = "UPDATE {$this->table} SET is_active = 1 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function getPostsCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM posts WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }

    public function getUserStats($user_id) {
        $posts_count = $this->getPostsCount($user_id);
        
        // Placeholder values - implement these based on your database structure
        $following_count = 0;
        $followers_count = 0;
        
        return [
            'posts_count' => $posts_count,
            'following_count' => $following_count,
            'followers_count' => $followers_count
        ];
    }
}
?>