<?php
require_once __DIR__ . '/../config/database.php';

class Gallery {
    private $db;
    private $table = 'gallery_images';

    public function __construct() {
        $this->db = new Database();
        $this->ensureTableExists();
    }

    private function ensureTableExists() {
        $query = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            caption VARCHAR(255) NULL,
            uploaded_by INT NULL,
            uploaded_type ENUM('user','admin') DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->conn->query($query);
    }

    public function addImage($fileName, $caption, $uploadedBy = null, $uploadedType = 'admin') {
        $query = "INSERT INTO {$this->table} (file_name, caption, uploaded_by, uploaded_type) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ssis', $fileName, $caption, $uploadedBy, $uploadedType);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
?>

