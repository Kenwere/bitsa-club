<?php
require_once '../config/database.php';

class Post {
    private $db;
    private $table = 'posts';

    public function __construct() {
        $this->db = new Database();
    }

    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE deleted_at IS NULL";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['count'];
    }

    public function getRecent($limit = 5) {
        $query = "SELECT p.*, u.name as user_name 
                  FROM {$this->table} p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.deleted_at IS NULL
                  ORDER BY p.created_at DESC 
                  LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAll() {
        $query = "SELECT p.*, u.name as user_name, u.username,
                  (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                  (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND deleted_at IS NULL) as comments_count
                  FROM {$this->table} p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.deleted_at IS NULL
                  ORDER BY p.created_at DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAdminPosts() {
        $query = "SELECT p.*, 
                  COALESCE(u.name, a.name) as user_name,
                  COALESCE(u.username, a.username) as username,
                  (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes_count,
                  (SELECT COUNT(*) FROM comments WHERE post_id = p.id AND deleted_at IS NULL) as comments_count
                  FROM {$this->table} p 
                  LEFT JOIN users u ON p.user_id = u.id 
                  LEFT JOIN admins a ON p.admin_id = a.id 
                  WHERE p.deleted_at IS NULL
                  ORDER BY p.created_at DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function findById($id) {
        $query = "SELECT p.*, u.name as user_name, u.username 
                  FROM {$this->table} p 
                  JOIN users u ON p.user_id = u.id 
                  WHERE p.id = ? AND p.deleted_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (user_id, content, image, likes_count, comments_count) VALUES (?, ?, ?, 0, 0)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('iss', $data['user_id'], $data['content'], $data['image']);
        return $stmt->execute();
    }
public function createAdminPost($data) {
    $query = "INSERT INTO {$this->table} (admin_id, content, image, likes_count, comments_count) VALUES (?, ?, ?, 0, 0)";
    $stmt = $this->db->prepare($query);
    $stmt->bind_param('iss', $data['admin_id'], $data['content'], $data['image']);
    return $stmt->execute();
}

    public function delete($id) {
        $query = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function deleteAdminPost($id) {
        $query = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function getUserPosts($user_id) {
        $query = "SELECT * FROM {$this->table} WHERE user_id = ? AND deleted_at IS NULL ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function like($post_id, $user_id) {
        // Check if already liked
        $check = "SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($check);
        $stmt->bind_param('ii', $post_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Unlike
            $query = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ii', $post_id, $user_id);
            $stmt->execute();
            return false;
        } else {
            // Like
            $query = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ii', $post_id, $user_id);
            $stmt->execute();
            return true;
        }
    }

    public function getLikesCount($post_id) {
        $query = "SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }

    public function addComment($post_id, $user_id, $content) {
        $query = "INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('iis', $post_id, $user_id, $content);
        return $stmt->execute();
    }

    public function getComments($post_id) {
        $query = "SELECT c.*, u.name as user_name, u.username 
                  FROM comments c 
                  JOIN users u ON c.user_id = u.id 
                  WHERE c.post_id = ? AND c.deleted_at IS NULL
                  ORDER BY c.created_at ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET content = ?, image = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssi', $data['content'], $data['image'], $id);
        return $stmt->execute();
    }

    public function isPostOwner($post_id, $user_id) {
        $query = "SELECT id FROM {$this->table} WHERE id = ? AND user_id = ? AND deleted_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $post_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
?>