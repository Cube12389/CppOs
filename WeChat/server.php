<?php
// server.php
// Run this file in CLI: php server.php

require_once 'core/WebSocketServer.php';

// Disable timeout
set_time_limit(0);

// Configuration
$HOST = '0.0.0.0';
$PORT = 8080;

$server = new WebSocketServer($HOST, $PORT);
$clients = []; // Map resourceId => ['socket' => $socket, 'room_id' => null, 'user_id' => null]

$server->onConnect = function($socket) use (&$clients) {
    $id = is_object($socket) ? spl_object_id($socket) : intval($socket);
    echo "New client connected: $id\n";
    $clients[$id] = ['socket' => $socket, 'room_id' => 0, 'user_id' => 0];
};

$server->onDisconnect = function($socket) use (&$clients) {
    $id = is_object($socket) ? spl_object_id($socket) : intval($socket);
    echo "Client disconnected: $id\n";
    unset($clients[$id]);
};

$server->onMessage = function($socket, $message) use (&$clients, $server) {
    $id = is_object($socket) ? spl_object_id($socket) : intval($socket);
    $data = json_decode($message, true);
    
    if (!$data) return;

    // Handle Actions
    $type = $data['type'] ?? '';
    switch ($type) {
        case 'login':
            // Logic: User connects globally (not necessarily in a room)
            $userId = intval($data['user_id'] ?? 0);
            if ($userId > 0) {
                $clients[$id]['user_id'] = $userId;
                echo "Client $id authenticated as User $userId\n";
            }
            break;

        case 'join':
            // Logic: User joins a room channel
            $roomId = intval($data['room_id'] ?? 0);
            
            if ($roomId > 0) {
                $clients[$id]['room_id'] = $roomId;
                // $clients[$id]['user_id'] = $userId; // Already set by login or here
                echo "Client $id joined room $roomId\n";
            }
            break;

        case 'private_message':
            // Logic: Direct Message
            $targetUserId = intval($data['receiver_id'] ?? 0);
            $msgData = $data['data'] ?? [];
            
            if ($targetUserId > 0) {
                // Find all sockets for this user
                foreach ($clients as $cid => $client) {
                    if ($client['user_id'] == $targetUserId) {
                        $payload = $server->encode(json_encode([
                            'type' => 'private_message',
                            'data' => $msgData
                        ]));
                         @socket_write($client['socket'], $payload, strlen($payload));
                    }
                }
                // Also send back to Sender (for multiple tabs sync)
                 foreach ($clients as $cid => $client) {
                    if ($client['user_id'] == $clients[$id]['user_id'] && $cid != $id) {
                         $payload = $server->encode(json_encode([
                            'type' => 'private_message',
                            'data' => $msgData
                        ]));
                         @socket_write($client['socket'], $payload, strlen($payload));
                    }
                }
            }
            break;

        case 'typing':
            // Logic: Typing Indicator
            $targetUserId = intval($data['receiver_id'] ?? 0);
            if ($targetUserId > 0) {
                $senderId = $clients[$id]['user_id'];
                foreach ($clients as $cid => $client) {
                    if ($client['user_id'] == $targetUserId) {
                        $payload = $server->encode(json_encode([
                            'type' => 'typing',
                            'sender_id' => $senderId
                        ]));
                         @socket_write($client['socket'], $payload, strlen($payload));
                    }
                }
            }
            break;

        case 'mark_read':
            // Logic: Notify Sender that their message was read
            $targetUserId = intval($data['receiver_id'] ?? 0); // The user who sent the message
            if ($targetUserId > 0) {
                 $readerId = $clients[$id]['user_id'];
                 foreach ($clients as $cid => $client) {
                    if ($client['user_id'] == $targetUserId) {
                        $payload = $server->encode(json_encode([
                            'type' => 'mark_read',
                            'reader_id' => $readerId
                        ]));
                         @socket_write($client['socket'], $payload, strlen($payload));
                    }
                }
            }
            break;

        case 'private_recall':
        case 'private_burn':
            // Logic: Sync Recall or Burn event
            $targetUserId = intval($data['receiver_id'] ?? 0);
            $messageId = $data['message_id'];
            $duration = $data['duration'] ?? 0;
            
            if ($targetUserId > 0) {
                // Broadcast to Receiver
                foreach ($clients as $cid => $client) {
                    if ($client['user_id'] == $targetUserId) {
                        $payload = $server->encode(json_encode([
                            'type' => $type, // private_recall or private_burn
                            'message_id' => $messageId,
                            'duration' => $duration
                        ]));
                         @socket_write($client['socket'], $payload, strlen($payload));
                    }
                }
                // Broadcast to Sender (Sync)
                 foreach ($clients as $cid => $client) {
                    if ($client['user_id'] == $clients[$id]['user_id'] && $cid != $id) {
                         $payload = $server->encode(json_encode([
                            'type' => $type,
                            'message_id' => $messageId,
                            'duration' => $duration
                        ]));
                         @socket_write($client['socket'], $payload, strlen($payload));
                    }
                }
            }
            break;

        case 'new_message':

        case 'new_message':
            // Logic: Broadcast message to room
            // The message persistence is done via AJAX, this is just for notification
            // Payload should contain the full message object to append to DOM
            $msgData = $data['data'] ?? [];
            $targetRoomId = intval($msgData['room_id'] ?? 0); // Or use clients[$id]['room_id']
            
            if ($targetRoomId > 0) {
                broadcast($server, $clients, $targetRoomId, [
                    'type' => 'new_message',
                    'data' => $msgData
                ]);
            }
            break;

        case 'recall':
             // Broadcast recall event
             $targetRoomId = $clients[$id]['room_id'];
             $messageId = $data['message_id'];
             
             if ($targetRoomId > 0) {
                 broadcast($server, $clients, $targetRoomId, [
                     'type' => 'recall',
                     'message_id' => $messageId
                 ]);
             }
             break;
             
        case 'kick':
            // Broadcast kick event
             $targetRoomId = $clients[$id]['room_id'];
             $kickedUserId = $data['user_id'];
             
             if ($targetRoomId > 0) {
                 broadcast($server, $clients, $targetRoomId, [
                     'type' => 'kick',
                     'user_id' => $kickedUserId
                 ]);
             }
             break;
    }
};

function broadcast($server, $clients, $roomId, $payload) {
    $msg = $server->encode(json_encode($payload));
    foreach ($clients as $client) {
        if ($client['room_id'] == $roomId) {
            @socket_write($client['socket'], $msg, strlen($msg));
        }
    }
}

// Start
$server->start();
?>
