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
        $set = ["name = ?", "username = ?"];
        $types = "ss";
        $params = [$data['name'], $data['username']];

        if (array_key_exists('contact', $data)) {
            $set[] = "contact = ?";
            $types .= "s";
            $params[] = $data['contact'];
        }

        if (!empty($data['password'])) {
            $set[] = "password = ?";
            $types .= "s";
            $params[] = $data['password'];
        }

        $types .= "i";
        $params[] = $id;

        $query = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }
}
?>