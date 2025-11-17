<?php
require_once '../models/Meeting.php';
require_once '../includes/auth.php';

class MeetingController {
    private $auth;

    public function __construct() {
        $this->auth = new Auth();
    }

    public function getAllMeetings() {
        $meetingModel = new Meeting();
        
        $activeMeetings = $meetingModel->getActiveMeetings();
        $scheduledMeetings = $meetingModel->getScheduledMeetings();

        return [
            'active_meetings' => $this->formatMeetings($activeMeetings),
            'scheduled_meetings' => $this->formatMeetings($scheduledMeetings)
        ];
    }

    public function getUserMeetings($user_id) {
        $meetingModel = new Meeting();
        // You'll need to implement methods to get user-specific meetings
        $meetings = []; // Placeholder
        
        return $this->formatMeetings($meetings);
    }

    private function formatMeetings($meetings) {
        $formattedMeetings = [];
        
        foreach ($meetings as $meeting) {
            $formattedMeetings[] = [
                'id' => $meeting['id'],
                'title' => $meeting['title'],
                'description' => $meeting['description'],
                'scheduled_time' => $meeting['scheduled_time'],
                'type' => $meeting['type'],
                'meeting_id' => $meeting['meeting_id'],
                'is_active' => (bool)$meeting['is_active'],
                'participants_count' => $meeting['participants_count'],
                'user' => [
                    'name' => $meeting['user_name'] ?? 'Host',
                    'id' => $meeting['user_id']
                ],
                'active_participants' => $this->getMeetingParticipants($meeting['id'])
            ];
        }

        return $formattedMeetings;
    }

    private function getMeetingParticipants($meeting_id) {
        $meetingModel = new Meeting();
        $participants = $meetingModel->getParticipants($meeting_id);
        
        $formattedParticipants = [];
        foreach ($participants as $participant) {
            $formattedParticipants[] = [
                'user' => [
                    'name' => $participant['user_name'],
                    'id' => $participant['user_id']
                ],
                'is_host' => (bool)$participant['is_host'],
                'joined_at' => $participant['joined_at']
            ];
        }

        return $formattedParticipants;
    }
}
?>