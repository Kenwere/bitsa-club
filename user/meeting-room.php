<?php
require_once '../config/constants.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if user is logged in
$auth = new Auth();
$auth->requireAuth('user');

// Get current user
$current_user = $auth->getCurrentUser();

// Get meeting ID
$meeting_id = $_GET['meeting_id'] ?? 0;
if (!$meeting_id) {
    header('Location: dashboard.php');
    exit;
}

// Load meeting model
require_once '../models/Meeting.php';
$meetingModel = new Meeting();
$meeting = $meetingModel->findById($meeting_id);

if (!$meeting) {
    header('Location: dashboard.php');
    exit;
}

// Check if user can join meeting
if (!$meetingModel->isMeetingJoinable($meeting_id)) {
    $_SESSION['error'] = 'Meeting is not active or has ended';
    header('Location: dashboard.php');
    exit;
}

// Join meeting
$meetingModel->join($meeting_id, $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Room - <?php echo htmlspecialchars($meeting['title']); ?></title>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root[data-theme="dark"] {
            --primary-bg: #0a0e27;
            --secondary-bg: #131829;
            --card-bg: #1a1f3a;
            --border-color: #2d3348;
            --text-primary: #e8eaf6;
            --text-secondary: #9ca3af;
            --accent-blue: #3b82f6;
            --accent-danger: #ef4444;
            --accent-success: #10b981;
        }
        
        :root[data-theme="light"] {
            --primary-bg: #f8fafc;
            --secondary-bg: #ffffff;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --accent-blue: #3b82f6;
            --accent-danger: #ef4444;
            --accent-success: #10b981;
        }
        
        body {
            background: var(--primary-bg);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        
        .meeting-container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        
        .meeting-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .meeting-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }
        
        .meeting-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .participant-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .meeting-content {
            flex: 1;
            display: flex;
            overflow: hidden;
        }
        
        .video-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            padding: 1rem;
            overflow-y: auto;
        }
        
        .video-container {
            background: var(--secondary-bg);
            border-radius: 8px;
            position: relative;
            aspect-ratio: 16/9;
            border: 2px solid var(--border-color);
        }
        
        .video-element {
            width: 100%;
            height: 100%;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .video-overlay {
            position: absolute;
            bottom: 0.5rem;
            left: 0.5rem;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        
        .video-controls {
            position: absolute;
            bottom: 0.5rem;
            right: 0.5rem;
            display: flex;
            gap: 0.5rem;
        }
        
        .control-btn {
            background: rgba(0, 0, 0, 0.7);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .control-btn.active {
            background: var(--accent-blue);
        }
        
        .control-btn.danger {
            background: var(--accent-danger);
        }
        
        .meeting-controls {
            background: var(--card-bg);
            border-top: 1px solid var(--border-color);
            padding: 1rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        .control-panel {
            display: flex;
            gap: 1rem;
        }
        
        .main-control {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.125rem;
            transition: all 0.2s ease;
        }
        
        .main-control:hover {
            background: var(--hover-bg);
        }
        
        .main-control.active {
            background: var(--accent-blue);
            color: white;
        }
        
        .main-control.danger {
            background: var(--accent-danger);
            color: white;
        }
        
        .chat-sidebar {
            width: 300px;
            background: var(--card-bg);
            border-left: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            font-weight: 600;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .chat-message {
            padding: 0.75rem;
            border-radius: 8px;
            max-width: 80%;
            word-wrap: break-word;
        }
        
        .message-self {
            background: var(--accent-blue);
            color: white;
            align-self: flex-end;
        }
        
        .message-other {
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            align-self: flex-start;
        }
        
        .message-sender {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid var(--border-color);
        }
        
        .chat-input form {
            display: flex;
            gap: 0.5rem;
        }
        
        .chat-input input {
            flex: 1;
            background: var(--secondary-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
        }
        
        .chat-input button {
            background: var(--accent-blue);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        
        .local-video {
            border-color: var(--accent-success);
        }
        
        .remote-video {
            border-color: var(--accent-blue);
        }
    </style>
</head>
<body>
    <div class="meeting-container">
        <!-- Meeting Header -->
        <div class="meeting-header">
            <div>
                <h1 class="meeting-title"><?php echo htmlspecialchars($meeting['title']); ?></h1>
                <div class="meeting-info">
                    <span>Meeting ID: <?php echo htmlspecialchars($meeting['meeting_id']); ?></span>
                    <span class="participant-count">
                        <i class="bi bi-people"></i>
                        <span id="participantCount">1</span> participants
                    </span>
                </div>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-left"></i> Leave Meeting
                </a>
            </div>
        </div>
        
        <!-- Meeting Content -->
        <div class="meeting-content">
            <!-- Video Grid -->
            <div class="video-grid" id="videoGrid">
                <!-- Local Video -->
                <div class="video-container local-video">
                    <video id="localVideo" autoplay muted class="video-element"></video>
                    <div class="video-overlay">
                        <span id="localUserName"><?php echo htmlspecialchars($current_user['name']); ?> (You)</span>
                    </div>
                    <div class="video-controls">
                        <button class="control-btn" id="toggleVideo">
                            <i class="bi bi-camera-video"></i>
                        </button>
                        <button class="control-btn" id="toggleAudio">
                            <i class="bi bi-mic"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remote videos will be added here dynamically -->
            </div>
            
            <!-- Chat Sidebar -->
            <div class="chat-sidebar">
                <div class="chat-header">
                    <i class="bi bi-chat"></i> Chat
                </div>
                <div class="chat-messages" id="chatMessages">
                    <!-- Chat messages will appear here -->
                </div>
                <div class="chat-input">
                    <form id="chatForm">
                        <input type="text" id="chatInput" placeholder="Type a message..." autocomplete="off">
                        <button type="submit">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Meeting Controls -->
        <div class="meeting-controls">
            <div class="control-panel">
                <button class="main-control" id="toggleVideoMain">
                    <i class="bi bi-camera-video"></i>
                </button>
                <button class="main-control" id="toggleAudioMain">
                    <i class="bi bi-mic"></i>
                </button>
                <button class="main-control" id="screenShare">
                    <i class="bi bi-laptop"></i>
                </button>
                <button class="main-control danger" id="leaveMeeting">
                    <i class="bi bi-telephone"></i>
                </button>
            </div>
        </div>
    </div>

    <script>
        // Simple WebRTC implementation for video meetings
        class VideoMeeting {
            constructor() {
                this.localStream = null;
                this.remoteStreams = new Map();
                this.peerConnections = new Map();
                this.isVideoEnabled = true;
                this.isAudioEnabled = true;
                this.isScreenSharing = false;
                this.meetingId = '<?php echo $meeting_id; ?>';
                this.userId = '<?php echo $_SESSION['user_id']; ?>';
                this.userName = '<?php echo $current_user['name']; ?>';
                
                this.initializeMeeting();
            }
            
            async initializeMeeting() {
                await this.initializeLocalStream();
                this.setupEventListeners();
                this.simulateRemoteParticipants(); // For demo purposes
            }
            
            async initializeLocalStream() {
                try {
                    // Get user media (camera and microphone)
                    this.localStream = await navigator.mediaDevices.getUserMedia({
                        video: true,
                        audio: true
                    });
                    
                    // Display local video
                    const localVideo = document.getElementById('localVideo');
                    localVideo.srcObject = this.localStream;
                    
                    console.log('Local stream initialized');
                } catch (error) {
                    console.error('Error accessing media devices:', error);
                    this.showError('Unable to access camera or microphone. Please check permissions.');
                }
            }
            
            setupEventListeners() {
                // Toggle video
                document.getElementById('toggleVideo').addEventListener('click', () => this.toggleVideo());
                document.getElementById('toggleVideoMain').addEventListener('click', () => this.toggleVideo());
                
                // Toggle audio
                document.getElementById('toggleAudio').addEventListener('click', () => this.toggleAudio());
                document.getElementById('toggleAudioMain').addEventListener('click', () => this.toggleAudio());
                
                // Screen share
                document.getElementById('screenShare').addEventListener('click', () => this.toggleScreenShare());
                
                // Leave meeting
                document.getElementById('leaveMeeting').addEventListener('click', () => this.leaveMeeting());
                
                // Chat form
                document.getElementById('chatForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.sendMessage();
                });
            }
            
            toggleVideo() {
                if (this.localStream) {
                    const videoTracks = this.localStream.getVideoTracks();
                    videoTracks.forEach(track => {
                        track.enabled = !track.enabled;
                    });
                    this.isVideoEnabled = !this.isVideoEnabled;
                    
                    // Update UI
                    const icon = this.isVideoEnabled ? 'bi-camera-video' : 'bi-camera-video-off';
                    document.querySelectorAll('#toggleVideo i, #toggleVideoMain i').forEach(i => {
                        i.className = `bi ${icon}`;
                    });
                    
                    if (!this.isVideoEnabled) {
                        document.getElementById('toggleVideoMain').classList.remove('active');
                    } else {
                        document.getElementById('toggleVideoMain').classList.add('active');
                    }
                }
            }
            
            toggleAudio() {
                if (this.localStream) {
                    const audioTracks = this.localStream.getAudioTracks();
                    audioTracks.forEach(track => {
                        track.enabled = !track.enabled;
                    });
                    this.isAudioEnabled = !this.isAudioEnabled;
                    
                    // Update UI
                    const icon = this.isAudioEnabled ? 'bi-mic' : 'bi-mic-mute';
                    document.querySelectorAll('#toggleAudio i, #toggleAudioMain i').forEach(i => {
                        i.className = `bi ${icon}`;
                    });
                    
                    if (!this.isAudioEnabled) {
                        document.getElementById('toggleAudioMain').classList.remove('active');
                    } else {
                        document.getElementById('toggleAudioMain').classList.add('active');
                    }
                }
            }
            
            async toggleScreenShare() {
                try {
                    if (!this.isScreenSharing) {
                        // Start screen share
                        const screenStream = await navigator.mediaDevices.getDisplayMedia({
                            video: true,
                            audio: true
                        });
                        
                        // Replace video track in local stream
                        const videoTrack = screenStream.getVideoTracks()[0];
                        const sender = this.getVideoSender();
                        if (sender) {
                            await sender.replaceTrack(videoTrack);
                        }
                        
                        // Handle when user stops screen share
                        videoTrack.onended = () => {
                            this.toggleScreenShare();
                        };
                        
                        this.isScreenSharing = true;
                        document.getElementById('screenShare').classList.add('active');
                        
                    } else {
                        // Stop screen share and revert to camera
                        const cameraStream = await navigator.mediaDevices.getUserMedia({ video: true });
                        const cameraTrack = cameraStream.getVideoTracks()[0];
                        const sender = this.getVideoSender();
                        if (sender) {
                            await sender.replaceTrack(cameraTrack);
                        }
                        
                        this.isScreenSharing = false;
                        document.getElementById('screenShare').classList.remove('active');
                    }
                } catch (error) {
                    console.error('Error toggling screen share:', error);
                }
            }
            
            getVideoSender() {
                // In a real implementation, this would get the RTCPeerConnection video sender
                return null;
            }
            
            sendMessage() {
                const chatInput = document.getElementById('chatInput');
                const message = chatInput.value.trim();
                
                if (message) {
                    this.addMessage(this.userName, message, true);
                    chatInput.value = '';
                    
                    // In a real implementation, send message via WebSocket
                    this.simulateResponse(message);
                }
            }
            
            addMessage(sender, message, isSelf = false) {
                const chatMessages = document.getElementById('chatMessages');
                const messageDiv = document.createElement('div');
                messageDiv.className = `chat-message ${isSelf ? 'message-self' : 'message-other'}`;
                
                if (!isSelf) {
                    const senderDiv = document.createElement('div');
                    senderDiv.className = 'message-sender';
                    senderDiv.textContent = sender;
                    messageDiv.appendChild(senderDiv);
                }
                
                const messageText = document.createElement('div');
                messageText.textContent = message;
                messageDiv.appendChild(messageText);
                
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            simulateResponse(message) {
                // Simulate responses for demo
                const responses = [
                    "That's a great point!",
                    "I agree with you.",
                    "Could you explain that further?",
                    "Thanks for sharing!",
                    "Let me think about that..."
                ];
                
                if (message.toLowerCase().includes('hello') || message.toLowerCase().includes('hi')) {
                    setTimeout(() => {
                        this.addMessage('Remote User', 'Hello there!', false);
                    }, 1000);
                } else if (message.includes('?')) {
                    setTimeout(() => {
                        this.addMessage('Remote User', 'That\'s an interesting question!', false);
                    }, 1500);
                } else {
                    setTimeout(() => {
                        const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                        this.addMessage('Remote User', randomResponse, false);
                    }, 2000);
                }
            }
            
            simulateRemoteParticipants() {
                // Simulate remote participants for demo
                setTimeout(() => {
                    this.addRemoteVideo('remote-user-1', 'John Doe');
                    this.updateParticipantCount(2);
                    
                    setTimeout(() => {
                        this.addMessage('John Doe', 'Hello everyone!', false);
                    }, 1000);
                    
                    setTimeout(() => {
                        this.addRemoteVideo('remote-user-2', 'Jane Smith');
                        this.updateParticipantCount(3);
                    }, 3000);
                }, 2000);
            }
            
            addRemoteVideo(userId, userName) {
                const videoGrid = document.getElementById('videoGrid');
                
                const videoContainer = document.createElement('div');
                videoContainer.className = 'video-container remote-video';
                videoContainer.id = `remote-${userId}`;
                
                const videoElement = document.createElement('video');
                videoElement.className = 'video-element';
                videoElement.autoplay = true;
                videoElement.playsInline = true;
                
                // Create a dummy stream for demo (in real app, this would be the remote stream)
                this.createDummyStream().then(stream => {
                    videoElement.srcObject = stream;
                });
                
                const videoOverlay = document.createElement('div');
                videoOverlay.className = 'video-overlay';
                videoOverlay.textContent = userName;
                
                videoContainer.appendChild(videoElement);
                videoContainer.appendChild(videoOverlay);
                videoGrid.appendChild(videoContainer);
            }
            
            async createDummyStream() {
                // Create a dummy video stream for demo purposes
                const canvas = document.createElement('canvas');
                canvas.width = 640;
                canvas.height = 480;
                const context = canvas.getContext('2d');
                
                // Create a simple animation
                let x = 0;
                const draw = () => {
                    context.fillStyle = '#1a1f3a';
                    context.fillRect(0, 0, canvas.width, canvas.height);
                    
                    context.fillStyle = '#3b82f6';
                    context.beginPath();
                    context.arc(x, canvas.height / 2, 50, 0, 2 * Math.PI);
                    context.fill();
                    
                    x = (x + 2) % canvas.width;
                };
                
                setInterval(draw, 50);
                
                return canvas.captureStream(25);
            }
            
            updateParticipantCount(count) {
                document.getElementById('participantCount').textContent = count;
            }
            
            leaveMeeting() {
                // Stop all tracks
                if (this.localStream) {
                    this.localStream.getTracks().forEach(track => track.stop());
                }
                
                // Redirect to dashboard
                window.location.href = 'dashboard.php';
            }
            
            showError(message) {
                alert(message); // In production, use a better notification system
            }
        }
        
        // Initialize the meeting when page loads
        document.addEventListener('DOMContentLoaded', () => {
            new VideoMeeting();
        });
    </script>
</body>
</html>