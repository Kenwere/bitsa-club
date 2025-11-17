<?php
require_once '../config/database.php';

class Admin {
    private $db;
    private $table = 'admins';

    public function __construct() {
        $this->db = new Database();
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM {$this->table} WHERE email = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function findById($id) {
        $query = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (name, email, username, password, is_active) VALUES (?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssss', $data['name'], $data['email'], $data['username'], $data['password']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET name = ?, username = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssi', $data['name'], $data['username'], $id);
        return $stmt->execute();
    }
}
?>