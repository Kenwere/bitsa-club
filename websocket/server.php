<?php
require_once '../config/database.php';

class WebSocketServer {
    private $clients = [];
    private $server;
    
    public function __construct($host = '0.0.0.0', $port = 8080) {
        $this->server = stream_socket_server("tcp://$host:$port", $errno, $errstr);
        
        if (!$this->server) {
            die("Server could not be created: $errstr ($errno)");
        }
        
        echo "WebSocket server running on ws://$host:$port\n";
    }
    
    public function run() {
        $sockets = [$this->server];
        
        while (true) {
            $read = $sockets;
            $write = $except = null;
            
            if (stream_select($read, $write, $except, 0)) {
                foreach ($read as $socket) {
                    if ($socket === $this->server) {
                        $this->acceptConnection($socket, $sockets);
                    } else {
                        $this->handleClient($socket, $sockets);
                    }
                }
            }
        }
    }
    
    private function acceptConnection($server, &$sockets) {
        $client = stream_socket_accept($server);
        $sockets[] = $client;
        $this->clients[(int)$client] = [
            'socket' => $client,
            'handshake' => false,
            'user_id' => null,
            'meeting_id' => null
        ];
    }
    
    private function handleClient($client, &$sockets) {
        $data = fread($client, 8192);
        
        if (!$data) {
            $this->disconnectClient($client, $sockets);
            return;
        }
        
        $clientId = (int)$client;
        
        if (!$this->clients[$clientId]['handshake']) {
            $this->handleHandshake($client, $data);
        } else {
            $this->handleMessage($client, $data);
        }
    }
    
    private function handleHandshake($client, $headers) {
        if (strpos($headers, 'GET') !== false) {
            $lines = explode("\r\n", $headers);
            $key = '';
            
            foreach ($lines as $line) {
                if (strpos($line, 'Sec-WebSocket-Key') !== false) {
                    $key = trim(substr($line, 19));
                    break;
                }
            }
            
            $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            
            $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                       "Upgrade: websocket\r\n" .
                       "Connection: Upgrade\r\n" .
                       "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";
            
            fwrite($client, $response);
            
            $clientId = (int)$client;
            $this->clients[$clientId]['handshake'] = true;
        }
    }
    
    private function handleMessage($client, $data) {
        $decoded = $this->decodeWebSocket($data);
        if ($decoded) {
            $message = json_decode($decoded, true);
            $this->processMessage($client, $message);
        }
    }
    
    private function processMessage($client, $message) {
        $type = $message['type'] ?? '';
        $clientId = (int)$client;
        
        switch ($type) {
            case 'join_meeting':
                $this->clients[$clientId]['user_id'] = $message['user_id'];
                $this->clients[$clientId]['meeting_id'] = $message['meeting_id'];
                $this->broadcastToMeeting($message['meeting_id'], [
                    'type' => 'user_joined',
                    'user_id' => $message['user_id'],
                    'user_name' => $message['user_name']
                ], $client);
                break;
                
            case 'chat_message':
                $this->broadcastToMeeting($this->clients[$clientId]['meeting_id'], [
                    'type' => 'chat_message',
                    'user_id' => $this->clients[$clientId]['user_id'],
                    'user_name' => $message['user_name'],
                    'message' => $message['message'],
                    'timestamp' => time()
                ]);
                break;
                
            case 'webrtc_offer':
            case 'webrtc_answer':
            case 'ice_candidate':
                $this->sendToUser($message['target_user_id'], [
                    'type' => $type,
                    'data' => $message['data'],
                    'from_user_id' => $this->clients[$clientId]['user_id']
                ]);
                break;
        }
    }
    
    private function broadcastToMeeting($meetingId, $message, $excludeClient = null) {
        foreach ($this->clients as $client) {
            if ($client['meeting_id'] == $meetingId && 
                $client['handshake'] && 
                $client['socket'] !== $excludeClient) {
                $this->sendMessage($client['socket'], $message);
            }
        }
    }
    
    private function sendToUser($userId, $message) {
        foreach ($this->clients as $client) {
            if ($client['user_id'] == $userId && $client['handshake']) {
                $this->sendMessage($client['socket'], $message);
            }
        }
    }
    
    private function sendMessage($client, $message) {
        $encoded = $this->encodeWebSocket(json_encode($message));
        fwrite($client, $encoded);
    }
    
    private function decodeWebSocket($data) {
        // Simple WebSocket frame decoding
        $length = ord($data[1]) & 127;
        $mask = null;
        $payload = '';
        
        if ($length === 126) {
            $mask = substr($data, 4, 4);
            $payload = substr($data, 8);
        } elseif ($length === 127) {
            $mask = substr($data, 10, 4);
            $payload = substr($data, 14);
        } else {
            $mask = substr($data, 2, 4);
            $payload = substr($data, 6);
        }
        
        $decoded = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $decoded .= $payload[$i] ^ $mask[$i % 4];
        }
        
        return $decoded;
    }
    
    private function encodeWebSocket($data) {
        // Simple WebSocket frame encoding
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($data);
        
        if ($length <= 125) {
            $header = pack('CC', $b1, $length);
        } elseif ($length <= 65535) {
            $header = pack('CCn', $b1, 126, $length);
        } else {
            $header = pack('CCNN', $b1, 127, 0, $length);
        }
        
        return $header . $data;
    }
    
    private function disconnectClient($client, &$sockets) {
        $clientId = (int)$client;
        $userData = $this->clients[$clientId] ?? null;
        
        if ($userData) {
            // Notify others about user leaving
            if ($userData['meeting_id']) {
                $this->broadcastToMeeting($userData['meeting_id'], [
                    'type' => 'user_left',
                    'user_id' => $userData['user_id']
                ]);
            }
            
            unset($this->clients[$clientId]);
        }
        
        $index = array_search($client, $sockets);
        if ($index !== false) {
            unset($sockets[$index]);
        }
        
        fclose($client);
    }
}

// Run the server
$server = new WebSocketServer('localhost', 8080);
$server->run();