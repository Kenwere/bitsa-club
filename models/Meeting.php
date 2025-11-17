<?php
require_once '../config/database.php';

class Meeting {
    private $db;
    private $table = 'meetings';

    public function __construct() {
        $this->db = new Database();
    }

    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['count'];
    }

    public function getActiveCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1 AND scheduled_time <= NOW() AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)";
        $result = $this->db->query($query);
        return $result->fetch_assoc()['count'];
    }

    public function getAll() {
        $query = "SELECT m.*, u.name as user_name, 
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id) as participants_count
                  FROM {$this->table} m 
                  LEFT JOIN users u ON m.user_id = u.id 
                  ORDER BY m.scheduled_time DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveMeetings() {
        $query = "SELECT m.*, u.name as user_name, u.username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count
                  FROM {$this->table} m 
                  LEFT JOIN users u ON m.user_id = u.id 
                  WHERE m.is_active = 1 
                  AND m.scheduled_time <= NOW() 
                  AND m.scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                  ORDER BY m.scheduled_time ASC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getScheduledMeetings() {
        $query = "SELECT m.*, u.name as user_name, u.username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id) as participants_count
                  FROM {$this->table} m 
                  LEFT JOIN users u ON m.user_id = u.id 
                  WHERE m.scheduled_time > NOW()
                  ORDER BY m.scheduled_time ASC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getPastMeetings() {
        $query = "SELECT m.*, u.name as user_name,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id) as participants_count
                  FROM {$this->table} m 
                  LEFT JOIN users u ON m.user_id = u.id 
                  WHERE m.scheduled_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)
                  ORDER BY m.scheduled_time DESC";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getUserMeetings($user_id) {
        $query = "SELECT m.*, u.name as user_name, u.username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count,
                  CASE 
                    WHEN m.scheduled_time > NOW() THEN 'scheduled'
                    WHEN m.scheduled_time <= NOW() AND m.scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'active'
                    ELSE 'past'
                  END as meeting_status
                  FROM {$this->table} m 
                  LEFT JOIN users u ON m.user_id = u.id 
                  WHERE m.user_id = ? OR m.id IN (SELECT meeting_id FROM meeting_participants WHERE user_id = ?)
                  ORDER BY 
                    CASE 
                      WHEN meeting_status = 'active' THEN 1
                      WHEN meeting_status = 'scheduled' THEN 2
                      ELSE 3
                    END,
                    m.scheduled_time DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $meeting_id = 'meet_' . uniqid() . '_' . time();
        $scheduled_time = $data['date'] . ' ' . $data['time'];
        
        // Set is_active based on scheduled time
        $current_time = date('Y-m-d H:i:s');
        $is_active = ($scheduled_time <= $current_time) ? 1 : 0;
        
        $query = "INSERT INTO {$this->table} (user_id, title, description, scheduled_time, type, meeting_id, is_active, participants_count) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('isssssi', $data['user_id'], $data['title'], $data['description'], $scheduled_time, $data['type'], $meeting_id, $is_active);
        return $stmt->execute();
    }

    public function delete($id) {
        // Delete participants first
        $deleteParticipants = $this->db->prepare("DELETE FROM meeting_participants WHERE meeting_id = ?");
        $deleteParticipants->bind_param('i', $id);
        $deleteParticipants->execute();
        $deleteParticipants->close();
        
        $query = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function findById($id) {
        $query = "SELECT m.*, u.name as user_name 
                  FROM {$this->table} m 
                  LEFT JOIN users u ON m.user_id = u.id 
                  WHERE m.id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function join($meeting_id, $user_id) {
        // Check if already joined
        $check = "SELECT id FROM meeting_participants WHERE meeting_id = ? AND user_id = ? AND left_at IS NULL";
        $stmt = $this->db->prepare($check);
        $stmt->bind_param('ii', $meeting_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            return true; // Already joined
        }
        $stmt->close();

        // Join meeting
        $query = "INSERT INTO meeting_participants (meeting_id, user_id, is_host, joined_at) VALUES (?, ?, 0, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $meeting_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        // Update participants count
        if ($result) {
            $this->updateParticipantsCount($meeting_id);
        }
        
        return $result;
    }

    public function leave($meeting_id, $user_id) {
        $query = "UPDATE meeting_participants SET left_at = NOW() WHERE meeting_id = ? AND user_id = ? AND left_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $meeting_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        // Update participants count
        if ($result) {
            $this->updateParticipantsCount($meeting_id);
        }
        
        return $result;
    }

    public function rsvp($meeting_id, $user_id) {
        // Check if already RSVP'd
        $check = "SELECT id FROM meeting_participants WHERE meeting_id = ? AND user_id = ?";
        $stmt = $this->db->prepare($check);
        $stmt->bind_param('ii', $meeting_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $query = "INSERT INTO meeting_participants (meeting_id, user_id, is_host, joined_at) VALUES (?, ?, 0, NULL)";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param('ii', $meeting_id, $user_id);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        $stmt->close();
        return true;
    }

    public function getParticipants($meeting_id) {
        $query = "SELECT mp.*, u.name as user_name 
                  FROM meeting_participants mp 
                  JOIN users u ON mp.user_id = u.id 
                  WHERE mp.meeting_id = ? AND mp.left_at IS NULL";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('i', $meeting_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $result;
    }

    public function updateParticipantsCount($meeting_id) {
        $query = "UPDATE meetings SET participants_count = (
                    SELECT COUNT(*) FROM meeting_participants 
                    WHERE meeting_id = ? AND left_at IS NULL
                  ) WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param('ii', $meeting_id, $meeting_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // NEW FUNCTION: Automatically update meeting statuses
    public function checkAndUpdateMeetingStatus() {
        // Activate meetings that should be active
        $activateQuery = "UPDATE {$this->table} SET is_active = 1 
                         WHERE scheduled_time <= NOW() 
                         AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                         AND is_active = 0";
        $this->db->query($activateQuery);

        // Deactivate old meetings
        $deactivateQuery = "UPDATE {$this->table} SET is_active = 0 
                           WHERE scheduled_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)
                           AND is_active = 1";
        $this->db->query($deactivateQuery);

        return true;
    }

    // NEW FUNCTION: Check if meeting is joinable
  public function isMeetingJoinable($meeting_id) {
    $query = "SELECT id, scheduled_time, is_active 
              FROM {$this->table} 
              WHERE id = ? 
              AND (is_active = 1 OR scheduled_time <= NOW()) 
              AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 4 HOUR)";
    $stmt = $this->db->prepare($query);
    $stmt->bind_param('i', $meeting_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return !empty($result);
}
}
?>