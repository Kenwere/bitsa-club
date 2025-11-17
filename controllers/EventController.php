<?php
require_once '../models/Event.php';

class EventController {
    public function getAllEvents() {
        $eventModel = new Event();
        $events = $eventModel->getActiveEvents();
        
        return $this->formatEvents($events);
    }

    public function getUpcomingEvents($limit = 10) {
        $eventModel = new Event();
        $events = $eventModel->getUpcoming($limit);
        
        return $this->formatEvents($events);
    }

    private function formatEvents($events) {
        $formattedEvents = [];
        
        foreach ($events as $event) {
            $formattedEvents[] = [
                'id' => $event['id'],
                'title' => $event['title'],
                'description' => $event['description'],
                'event_date' => $event['event_date'],
                'event_time' => $event['event_time'],
                'location' => $event['location'],
                'max_attendees' => $event['max_attendees'],
                'attendees_count' => $event['attendees_count'] ?? 0,
                'formatted_date' => $this->formatDate($event['event_date']),
                'formatted_time' => date('g:i A', strtotime($event['event_time'])),
                'is_upcoming' => strtotime($event['event_date']) >= strtotime('today')
            ];
        }

        return $formattedEvents;
    }

    private function formatDate($date) {
        return date('M j, Y', strtotime($date));
    }
}
?>