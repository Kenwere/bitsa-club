<?php
require_once '../config/database.php';

class Event {
    private $db;
    private $table = 'events';

    public function __construct() {
        $this->db = new Database();
    }

    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['count'];
    }

    public function getUpcomingCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE event_date >= CURDATE() AND is_active = 1";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['count'];
    }

    public function getUpcoming($limit = 5) {
        $query = "SELECT e.*, a.name as admin_name 
                  FROM {$this->table} e 
                  LEFT JOIN admins a ON e.created_by = a.id 
                  WHERE e.event_date >= CURDATE() AND e.is_active = 1 
                  ORDER BY e.event_date ASC 
                  LIMIT ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getAll() {
        $query = "SELECT e.*, a.name as admin_name 
                  FROM {$this->table} e 
                  LEFT JOIN admins a ON e.created_by = a.id 
                  WHERE e.is_active = 1 
                  ORDER BY e.event_date DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveEvents() {
        $query = "SELECT e.*, a.name as admin_name,
                  (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as attendees_count
                  FROM {$this->table} e 
                  LEFT JOIN admins a ON e.created_by = a.id 
                  WHERE e.event_date >= CURDATE() AND e.is_active = 1 
                  ORDER BY e.event_date ASC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        // Validate required fields including created_by
        if (empty($data['title']) || empty($data['event_date']) || empty($data['event_time']) || empty($data['location']) || empty($data['created_by'])) {
            error_log("Missing required fields for event creation");
            return false;
        }

        $query = "INSERT INTO {$this->table} (title, description, event_date, event_time, location, max_attendees, created_by, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($query);
        
        $max_attendees = !empty($data['max_attendees']) ? $data['max_attendees'] : null;
        $created_by = $data['created_by'];
        
        $stmt->bind_param('sssssii', 
            $data['title'], 
            $data['description'], 
            $data['event_date'], 
            $data['event_time'], 
            $data['location'], 
            $max_attendees, 
            $created_by
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Event insert failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    }

    public function delete($id) {
        $query = "UPDATE {$this->table} SET is_active = 0 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function getEventById($id) {
        $query = "SELECT e.*, a.name as admin_name 
                  FROM {$this->table} e 
                  LEFT JOIN admins a ON e.created_by = a.id 
                  WHERE e.id = ? AND e.is_active = 1";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return null;
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }
}
?>