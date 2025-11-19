<?php
require_once '../config/database.php';

class Meeting {
    private $db;
    private $table = 'meetings';

    public function __construct() {
        $this->db = new Database();
    }

    // Statistics methods
    public function getActiveAndScheduledCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                  WHERE (
                    (is_active = 1 AND scheduled_time <= NOW() AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)) 
                    OR scheduled_time > NOW()
                  ) 
                  AND deleted_at IS NULL";
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc()['count'] : 0;
    }

    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE deleted_at IS NULL";
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc()['count'] : 0;
    }

    public function getActiveCount() {
        $query = "SELECT COUNT(*) as count FROM {$this->table} 
                  WHERE is_active = 1 
                  AND scheduled_time <= NOW() 
                  AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR) 
                  AND deleted_at IS NULL";
        $result = $this->db->query($query);
        return $result ? $result->fetch_assoc()['count'] : 0;
    }

    // Data retrieval methods
    public function getVisibleMeetings() {
        $query = "SELECT m.*, 
                  u.name as user_name,
                  u.username as username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count,
                  CASE 
                    WHEN m.scheduled_time <= NOW() AND m.scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'active'
                    WHEN m.scheduled_time > NOW() THEN 'scheduled'
                    ELSE 'past'
                  END as status
                  FROM {$this->table} m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.deleted_at IS NULL
                  ORDER BY 
                    CASE 
                      WHEN status = 'active' THEN 1
                      WHEN status = 'scheduled' THEN 2
                      ELSE 3
                    END,
                    m.scheduled_time ASC";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getUserMeetings($user_id) {
    $query = "SELECT m.*, 
              u.name as user_name,
              u.username as username,
              (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count,
              CASE 
                WHEN m.scheduled_time <= NOW() AND m.scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'active'
                WHEN m.scheduled_time > NOW() THEN 'scheduled'
                ELSE 'past'
              END as status
              FROM {$this->table} m 
              JOIN users u ON m.user_id = u.id 
              WHERE m.user_id = ?  -- Only get meetings created by this user
              AND m.deleted_at IS NULL
              ORDER BY 
                CASE 
                  WHEN status = 'active' THEN 1
                  WHEN status = 'scheduled' THEN 2
                  ELSE 3
                END,
                m.scheduled_time ASC";
    
    $stmt = $this->db->prepare($query);
    if (!$stmt) return [];
    
    $stmt->bind_param('i', $user_id);  // Only one parameter needed
    $stmt->execute();
    $result = $stmt->get_result();
    $meetings = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $meetings;
}
    public function getAllMeetings() {
        $query = "SELECT m.*, 
                  u.name as user_name,
                  u.username as username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count,
                  CASE 
                    WHEN m.scheduled_time <= NOW() AND m.scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR) THEN 'active'
                    WHEN m.scheduled_time > NOW() THEN 'scheduled'
                    ELSE 'past'
                  END as status
                  FROM {$this->table} m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.deleted_at IS NULL
                  ORDER BY 
                    CASE 
                      WHEN status = 'active' THEN 1
                      WHEN status = 'scheduled' THEN 2
                      ELSE 3
                    END,
                    m.scheduled_time ASC";
        
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getActiveMeetings() {
        $query = "SELECT m.*, 
                  u.name as user_name, 
                  u.username as username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count
                  FROM {$this->table} m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.is_active = 1 
                  AND m.scheduled_time <= NOW() 
                  AND m.scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                  AND m.deleted_at IS NULL
                  ORDER BY m.scheduled_time ASC";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getScheduledMeetings() {
        $query = "SELECT m.*, 
                  u.name as user_name, 
                  u.username as username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count
                  FROM {$this->table} m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.scheduled_time > NOW()
                  AND m.deleted_at IS NULL
                  ORDER BY m.scheduled_time ASC";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getPastMeetings() {
        $query = "SELECT m.*, 
                  u.name as user_name, 
                  u.username as username,
                  (SELECT COUNT(*) FROM meeting_participants WHERE meeting_id = m.id AND left_at IS NULL) as participants_count
                  FROM {$this->table} m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.scheduled_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)
                  AND m.deleted_at IS NULL
                  ORDER BY m.scheduled_time DESC";
        $result = $this->db->query($query);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getAll() {
    return $this->getAllMeetings(); // Alias for compatibility
}

    public function getAllActiveAndScheduledMeetings() {
        return [
            'active' => $this->getActiveMeetings(),
            'scheduled' => $this->getScheduledMeetings()
        ];
    }

    // CRUD operations
    public function create($data) {
        if (empty($data['user_id']) || empty($data['title']) || empty($data['date']) || empty($data['time'])) {
            error_log("Missing required fields for meeting creation");
            return false;
        }

        $meeting_id = 'meet_' . uniqid() . '_' . time();
        $scheduled_time = $data['date'] . ' ' . $data['time'];
        
        $current_time = date('Y-m-d H:i:s');
        $is_active = (strtotime($scheduled_time) <= strtotime($current_time)) ? 1 : 0;
        
        $query = "INSERT INTO {$this->table} (user_id, title, description, scheduled_time, type, meeting_id, is_active, participants_count) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, 0)";
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Failed to prepare meeting insert statement");
            return false;
        }
        
        $stmt->bind_param('isssssi', 
            $data['user_id'], 
            $data['title'], 
            $data['description'], 
            $scheduled_time, 
            $data['type'], 
            $meeting_id, 
            $is_active
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Meeting insert failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $result;
    }

    public function delete($id) {
        $query = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function findById($id) {
        $query = "SELECT m.*, 
                  u.name as user_name,
                  u.username as username
                  FROM {$this->table} m 
                  JOIN users u ON m.user_id = u.id 
                  WHERE m.id = ? AND m.deleted_at IS NULL";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return null;
        
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    // Meeting participation methods
    public function join($meeting_id, $user_id) {
        $check = "SELECT id FROM meeting_participants WHERE meeting_id = ? AND user_id = ? AND left_at IS NULL";
        $stmt = $this->db->prepare($check);
        if (!$stmt) return false;
        
        $stmt->bind_param('ii', $meeting_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $stmt->close();
            return true;
        }
        $stmt->close();

        $query = "INSERT INTO meeting_participants (meeting_id, user_id, is_host, joined_at) VALUES (?, ?, 0, NOW())";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param('ii', $meeting_id, $user_id);
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            $this->updateParticipantsCount($meeting_id);
        }
        
        return $result;
    }

    private function updateParticipantsCount($meeting_id) {
        $query = "UPDATE meetings SET participants_count = (
                    SELECT COUNT(*) FROM meeting_participants 
                    WHERE meeting_id = ? AND left_at IS NULL
                  ) WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param('ii', $meeting_id, $meeting_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function isMeetingJoinable($meeting_id) {
        $query = "SELECT id, scheduled_time, is_active 
                  FROM {$this->table} 
                  WHERE id = ? 
                  AND (is_active = 1 OR scheduled_time <= NOW()) 
                  AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 4 HOUR)
                  AND deleted_at IS NULL";
        $stmt = $this->db->prepare($query);
        if (!$stmt) return false;
        
        $stmt->bind_param('i', $meeting_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return !empty($result);
    }

    // Maintenance methods
    public function checkAndUpdateMeetingStatus() {
        // Activate meetings that should be active
        $activateQuery = "UPDATE {$this->table} SET is_active = 1 
                         WHERE scheduled_time <= NOW() 
                         AND scheduled_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                         AND is_active = 0
                         AND deleted_at IS NULL";
        $this->db->query($activateQuery);

        // Deactivate old meetings
        $deactivateQuery = "UPDATE {$this->table} SET is_active = 0 
                           WHERE scheduled_time < DATE_SUB(NOW(), INTERVAL 2 HOUR)
                           AND is_active = 1
                           AND deleted_at IS NULL";
        $this->db->query($deactivateQuery);
        
        return true;
    }
}
?>