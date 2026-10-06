<?php
// controllers/PrivateChatController.php

require_once 'core/functions.php';
require_once 'core/Storage.php';

class PrivateChatController {
    
// ... inside PrivateChatController ...

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php?route=login');
        }

        $user_id = $_SESSION['user_id'];
        $conn = connect_db();
        
        // Complex query to get latest message for each conversation
        // Filter out if I deleted the message (as sender or receiver)
        $sql = "SELECT 
                    CASE 
                        WHEN sender_id = ? THEN receiver_id 
                        ELSE sender_id 
                    END as partner_id,
                    MAX(created_at) as last_msg_time
                FROM private_messages 
                WHERE (sender_id = ? AND deleted_by_sender = 0) 
                   OR (receiver_id = ? AND deleted_by_receiver = 0)
                GROUP BY partner_id 
                ORDER BY last_msg_time DESC";
                
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conversations = [];
        while ($row = $result->fetch_assoc()) {
            $partner_id = $row['partner_id'];
            
            // Fetch Partner Details
            $uStmt = $conn->prepare("SELECT id, username, COALESCE(nickname, username) as display_name, avatar FROM users WHERE id = ?");
            $uStmt->bind_param("i", $partner_id);
            $uStmt->execute();
            $partner = $uStmt->get_result()->fetch_assoc();
            
            if (!$partner) continue;

            // Fetch Last Message Content & Unread Count (Respect Soft Delete)
            $mStmt = $conn->prepare("SELECT content, image_url, video_url, audio_url, is_read, sender_id FROM private_messages 
                                     WHERE ((sender_id = ? AND receiver_id = ? AND deleted_by_sender = 0) 
                                        OR (sender_id = ? AND receiver_id = ? AND deleted_by_receiver = 0))
                                     ORDER BY created_at DESC LIMIT 1");
            $mStmt->bind_param("iiii", $user_id, $partner_id, $partner_id, $user_id);
            $mStmt->execute();
            $lastMsg = $mStmt->get_result()->fetch_assoc();
            
            // If all messages deleted, skip this conversation from list?
            // Usually yes, if 'Delete Session' was used.
            // If 'Clear History' was used, maybe keep it but empty?
            // But our query logic relies on existing messages. If no messages found, $lastMsg is null.
            // If so, and we filtered messages in the first query, do we still see it?
            // The first query gets partners where AT LEAST ONE message exists and is visible.
            // So if all messages are hidden, the partner won't show in the loop!
            // Perfect behavior for 'Delete Session'.
            
            if (!$lastMsg) continue; // Should effectively hide conversation if valid msg exists but is deleted? Wait.
            // First query already filters. So here we should have a message. 
            // If $lastMsg is null (e.g. race condition or logic mismatch), skip.

            // Get Unread Count (where I am receiver and is_read=0 and NOT deleted)
            $cStmt = $conn->prepare("SELECT COUNT(*) as count FROM private_messages 
                                     WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 AND deleted_by_receiver = 0");
            $cStmt->bind_param("ii", $partner_id, $user_id);
            $cStmt->execute();
            $unread = $cStmt->get_result()->fetch_assoc()['count'];
            
            $conversations[] = [
                'partner' => $partner,
                'last_message' => $lastMsg,
                'unread' => $unread
            ];
        }
        
        $conn->close();
        
        view('private/list', [
            'conversations' => $conversations,
            'user_id' => $user_id
        ]);
    }

    public function chat() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php?route=login');
        }

        $user_id = $_SESSION['user_id'];
        $partner_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        
        if ($partner_id == 0 || $partner_id == $user_id) {
            redirect('index.php?route=messages');
        }
        
        $conn = connect_db();
        
        // Fetch Partner Info
        $stmt = $conn->prepare("SELECT id, username, COALESCE(nickname, username) as display_name, avatar FROM users WHERE id = ?");
        $stmt->bind_param("i", $partner_id);
        $stmt->execute();
        $partner = $stmt->get_result()->fetch_assoc();
        
        if (!$partner) {
            redirect('index.php?route=messages');
        }
        
        // Mark as Read
        $stmt = $conn->prepare("UPDATE private_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt->bind_param("ii", $partner_id, $user_id);
        $stmt->execute();
        
        // Fetch Messages (Filter Deleted)
        $stmt = $conn->prepare("SELECT * FROM private_messages 
                                WHERE ((sender_id = ? AND receiver_id = ? AND deleted_by_sender = 0) 
                                   OR (sender_id = ? AND receiver_id = ? AND deleted_by_receiver = 0))
                                ORDER BY created_at ASC");
        $stmt->bind_param("iiii", $user_id, $partner_id, $partner_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $messages = [];
        while($row = $res->fetch_assoc()) {
            $messages[] = $row;
        }
        
        $conn->close();
        
        // Process Burn Logic
        $messages = $this->processMessages($messages, $user_id);
        
        view('private/chat', [
            'messages' => $messages,
            'partner' => $partner,
            'user_id' => $user_id,
            'csrf_token' => generate_csrf_token()
        ]);
    }

    
    public function send() {
        header('Content-Type: application/json');

        try {
            if (!isset($_SESSION['user_id'])) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => '未登录']);
                exit;
            }

            // ... csrf check ...
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'CSRF Fail']);
                exit;
            }
            
            $user_id = $_SESSION['user_id'];
            $receiver_id = intval($_POST['receiver_id'] ?? 0);
            $content = trim($_POST['content'] ?? '');
            $burn_after_read = intval($_POST['burn_after_read'] ?? 0); // Seconds
            
            // ... file upload logic ...
            $image_url = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $image_url = Storage::upload($_FILES['image']);
            }
            $video_url = null;
            if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $video_url = Storage::upload($_FILES['video']);
            }
            $audio_url = null;
            if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
                $audio_url = Storage::upload($_FILES['audio']);
            }

            if (empty($content) && empty($image_url) && empty($video_url) && empty($audio_url)) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => '内容不能为空']);
                exit;
            }
            
            $conn = connect_db();
            
            $stmt = $conn->prepare("INSERT INTO private_messages (sender_id, receiver_id, content, image_url, video_url, audio_url, burn_after_read) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("iissssi", $user_id, $receiver_id, $content, $image_url, $video_url, $audio_url, $burn_after_read);
            
            if ($stmt->execute()) {
                $msgId = $conn->insert_id;
                // Fetch detailed message
                $q = $conn->prepare("SELECT * FROM private_messages WHERE id = ?");
                $q->bind_param("i", $msgId);
                $q->execute();
                $newMsg = $q->get_result()->fetch_assoc();
                
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => true, 'message' => $newMsg]);
            } else {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'Db Error']);
            }
            
            $conn->close();

        } catch (Throwable $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
            exit;
        }
    }

    public function mark_opened() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            die('Unauthorized');
        }
        $message_id = intval($_POST['message_id'] ?? 0);
        if ($message_id <= 0) die('Invalid Parameter');
        
        $conn = connect_db();
        // Update opened_at = NOW() if it is NULL, and I am the receiver
        $stmt = $conn->prepare("UPDATE private_messages SET opened_at = NOW() WHERE id = ? AND receiver_id = ? AND opened_at IS NULL");
        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("ii", $message_id, $user_id);
        $stmt->execute();
        
        $conn->close();
        echo json_encode(['success' => true]);
    }

    // Helper to process messages for view
    private function processMessages($messages, $user_id) {
        $processed = [];
        foreach($messages as $msg) {
            // Check Burn Status
            if ($msg['burn_after_read'] > 0 && $msg['image_url']) {
                $isReceiver = $msg['receiver_id'] == $user_id;
                
                if ($msg['opened_at']) {
                    $openedTime = strtotime($msg['opened_at']);
                    $burnTime = $msg['burn_after_read'];
                    if (time() > $openedTime + $burnTime) {
                        $msg['image_url'] = 'burned'; // Marker
                        $msg['video_url'] = null;
                        $msg['audio_url'] = null;
                        $msg['content'] = '[内容已销毁]';
                    }
                }
            }
            $processed[] = $msg;
        }
        return $processed;
    }
    
    // ... update index() and chat() to use processMessages ...
    
    public function clear_history() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            die('Unauthorized');
        }
        $user_id = $_SESSION['user_id'];
        $partner_id = intval($_POST['partner_id'] ?? 0);
        
        if ($partner_id <= 0) die('Invalid Parameter');
        
        $conn = connect_db();
        // 1. Where I am sender -> deleted_by_sender = 1
        $stmt1 = $conn->prepare("UPDATE private_messages SET deleted_by_sender = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt1->bind_param("ii", $user_id, $partner_id);
        $stmt1->execute();
        
        // 2. Where I am receiver -> deleted_by_receiver = 1
        $stmt2 = $conn->prepare("UPDATE private_messages SET deleted_by_receiver = 1 WHERE sender_id = ? AND receiver_id = ?");
        $stmt2->bind_param("ii", $partner_id, $user_id);
        $stmt2->execute();
        
        $conn->close();
        echo json_encode(['success' => true]);
    }
    
    public function delete_session() {
         $this->clear_history();
    }
    
    public function checkUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
            redirect('index.php?route=messages');
        }
        
        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            redirect('index.php?route=messages');
        }
        
        $conn = connect_db();
        // Support searching by Username OR User ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR id = ? LIMIT 1");
        $stmt->bind_param("ss", $username, $username); // Bind same input for both checks
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($row = $res->fetch_assoc()) {
            // Self check
            if ($row['id'] == $_SESSION['user_id']) {
                echo "<script>alert('不能给自己发私信'); window.location.href='index.php?route=messages';</script>";
                exit;
            }
            redirect('index.php?route=chat_private&user_id=' . $row['id']);
        } else {
            // JS Alert fallback if simple redirect isn't enough
            echo "<script>alert('用户 [{$username}] 不存在'); window.history.back();</script>";
            exit;
        }
        
        $conn->close();
    }

    public function recall() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
             if (ob_get_length()) ob_clean();
             echo json_encode(['success' => false, 'error' => 'Unauthorized']);
             exit;
        }
        $user_id = $_SESSION['user_id'];
        $message_id = intval($_POST['message_id'] ?? 0);
        
        $conn = connect_db();
        // Check ownership and time limit (2 minutes)
        $stmt = $conn->prepare("SELECT id, created_at, receiver_id FROM private_messages WHERE id = ? AND sender_id = ? AND is_recalled = 0");
        if (!$stmt) {
             if (ob_get_length()) ob_clean();
             echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
             $conn->close();
             exit;
        }
        $stmt->bind_param("ii", $message_id, $user_id);
        $stmt->execute();
        $msg = $stmt->get_result()->fetch_assoc();
        
        if (!$msg) {
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'error' => 'Message not found or already recalled']);
            $conn->close();
            exit;
        }
        
        if (time() - strtotime($msg['created_at']) > 120) {
             if (ob_get_length()) ob_clean();
             echo json_encode(['success' => false, 'error' => '超过2分钟无法撤回']);
             $conn->close();
             exit;
        }
        
        // Execute Recall
        $stmt = $conn->prepare("UPDATE private_messages SET is_recalled = 1, content = NULL, image_url = NULL, video_url = NULL, audio_url = NULL WHERE id = ?");
        if (!$stmt) {
             if (ob_get_length()) ob_clean();
             echo json_encode(['success' => false, 'error' => 'Prepare failed (Update): ' . $conn->error]);
             $conn->close();
             exit;
        }
        $stmt->bind_param("i", $message_id);
        
        if($stmt->execute()) {
             // Return success with partner_id so we can broadcast
             if (ob_get_length()) ob_clean();
             echo json_encode(['success' => true, 'receiver_id' => $msg['receiver_id'], 'message_id' => $message_id]);
        } else {
            if (ob_get_length()) ob_clean();
             echo json_encode(['success' => false, 'error' => 'Database error']);
        }
        $conn->close();
    }
}
