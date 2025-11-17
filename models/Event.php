<?php
require_once '../config/database.php';

class Event {
    private $db;
    private $table = 'events';

    public function __construct() {
        $this->db = new Database();
    }

    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
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
                  JOIN admins a ON e.created_by = a.id 
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
                  JOIN admins a ON e.created_by = a.id 
                  ORDER BY e.event_date DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveEvents() {
        $query = "SELECT e.*, a.name as admin_name,
                  (SELECT COUNT(*) FROM event_attendees WHERE event_id = e.id) as attendees_count
                  FROM {$this->table} e 
                  JOIN admins a ON e.created_by = a.id 
                  WHERE e.event_date >= CURDATE() AND e.is_active = 1 
                  ORDER BY e.event_date ASC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $query = "INSERT INTO {$this->table} (title, description, event_date, event_time, location, max_attendees, created_by, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $this->db->prepare($query);
        $max_attendees = !empty($data['max_attendees']) ? $data['max_attendees'] : null;
        $stmt->bind_param('sssssii', $data['title'], $data['description'], $data['event_date'], $data['event_time'], $data['location'], $max_attendees, $data['created_by']);
        return $stmt->execute();
    }

    public function delete($id) {
        // Delete attendees first
        $this->db->query("DELETE FROM event_attendees WHERE event_id = $id");
        
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE {$this->table} SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, max_attendees = ? WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $max_attendees = !empty($data['max_attendees']) ? $data['max_attendees'] : null;
        $stmt->bind_param('sssssii', $data['title'], $data['description'], $data['event_date'], $data['event_time'], $data['location'], $max_attendees, $id);
        return $stmt->execute();
    }

    public function attend($event_id, $user_id) {
        // Check if already attending
        $check = "SELECT id FROM event_attendees WHERE event_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($check);
        $stmt->bind_param('ii', $event_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $query = "INSERT INTO event_attendees (event_id, user_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ii', $event_id, $user_id);
            return $stmt->execute();
        }
        return true;
    }

    public function getAttendeesCount($event_id) {
        $query = "SELECT COUNT(*) as count FROM event_attendees WHERE event_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $event_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'];
    }
}
?>