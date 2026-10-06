<?php
// core/functions.php

function view($path, $data = []) {
    extract($data);
    require "views/{$path}.php";
}

function get_sidebar_data($user_id) {
    if (!$user_id) return ['rooms' => [], 'conversations' => []];

    $conn = connect_db();
    
    // 1. Fetch Rooms
    // 1. Fetch Rooms (Public OR Joined Hidden)
    $rooms_sql = "SELECT r.*, 
                  (SELECT COUNT(*) FROM room_members WHERE room_id = r.id AND is_online = 1) as online_count 
                  FROM rooms r 
                  WHERE r.is_hidden = 0 
                  OR (r.is_hidden = 1 AND EXISTS (SELECT 1 FROM room_members rm WHERE rm.room_id = r.id AND rm.user_id = ?))
                  ORDER BY r.created_at DESC";
                  
    $stmt = $conn->prepare($rooms_sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $rooms_res = $stmt->get_result();
    $rooms = [];
    while ($row = $rooms_res->fetch_assoc()) {
        $rooms[] = $row;
    }

    // 2. Fetch Helper for Private Conversations (Summary)
    // Group by partner to get unique list, order by latest message
    $sql = "SELECT 
                CASE 
                    WHEN sender_id = ? THEN receiver_id 
                    ELSE sender_id 
                END as partner_id,
                MAX(created_at) as last_msg_time,
                SUM(CASE WHEN receiver_id = ? AND is_read = 0 THEN 1 ELSE 0 END) as unread_count
            FROM private_messages 
            WHERE sender_id = ? OR receiver_id = ? 
            GROUP BY partner_id 
            ORDER BY last_msg_time DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    $conversations = [];
    while ($row = $res->fetch_assoc()) {
        $pid = $row['partner_id'];
        
        // Get Partner Info
        $pStmt = $conn->prepare("SELECT id, username, COALESCE(nickname, username) as display_name, avatar FROM users WHERE id = ?");
        $pStmt->bind_param("i", $pid);
        $pStmt->execute();
        $partner = $pStmt->get_result()->fetch_assoc();
        
        // Get Last Message Text
        $mStmt = $conn->prepare("SELECT content, image_url, video_url, audio_url FROM private_messages 
                                 WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) 
                                 ORDER BY created_at DESC LIMIT 1");
        $mStmt->bind_param("iiii", $user_id, $pid, $pid, $user_id);
        $mStmt->execute();
        $msg = $mStmt->get_result()->fetch_assoc();
        
        if ($partner && $msg) {
            $conversations[] = [
                'partner' => $partner,
                'last_message' => $msg,
                'unread' => $row['unread_count'],
                'time' => $row['last_msg_time']
            ];
        }
    }

    $conn->close();
    
    return [
        'rooms' => $rooms,
        'conversations' => $conversations
    ];
}

function redirect($url) {
    header("Location: {$url}");
    exit();
}
?>
