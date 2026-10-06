<?php
// controllers/ChatController.php

require_once 'core/Storage.php';

class ChatController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php?route=login');
        }

        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        $avatar = $_SESSION['avatar'];

        if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
            redirect('index.php?route=rooms');
        }

        $room_id = intval($_GET['room_id']);

        // Check membership
        if (!is_room_member($room_id, $user_id)) {
            redirect('index.php?route=rooms');
        }

        // Update online status
        update_user_online_status($user_id, $room_id, 1);

        // Get Room Info
        $room = get_room($room_id);
        if (!$room) {
            redirect('index.php?route=rooms');
        }

        // Connect DB once
        $conn = connect_db();

        // Fetch All Rooms for Sidebar
        // Note: Reusing query logic from RoomController roughly
        $rooms_res = $conn->query("SELECT r.*, 
                               (SELECT COUNT(*) FROM room_members WHERE room_id = r.id AND is_online = 1) as online_count 
                               FROM rooms r ORDER BY r.created_at DESC");
        $rooms = [];
        while ($row = $rooms_res->fetch_assoc()) {
            $rooms[] = $row;
        }

        // Get Members for Current Room
        $stmt = $conn->prepare("SELECT u.id, u.username, COALESCE(u.nickname, u.username) as display_name, u.avatar, rm.is_online 
                               FROM room_members rm 
                               JOIN users u ON rm.user_id = u.id 
                               WHERE rm.room_id = ? 
                               ORDER BY rm.is_online DESC, display_name ASC");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }

        $stmt->close();
        $conn->close();

        // Render View
        view('chat/index', [
            'room' => $room,
            'rooms' => $rooms, // Passed for Sidebar
            'members' => $members,
            'user_id' => $user_id,
            'username' => $username,
            'avatar' => $avatar,
            'room_id' => $room_id
        ]);
    }
    public function mark_opened() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
             // Return JSON error
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'error' => 'Unauthorized']);
             exit;
        }

        $message_id = intval($_POST['message_id'] ?? 0);
        if ($message_id <= 0) {
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'error' => 'Invalid Parameter']);
             exit;
        }

        $conn = connect_db();
        // Update opened_at = NOW() if it is NULL
        $stmt = $conn->prepare("UPDATE messages SET opened_at = NOW() WHERE id = ? AND opened_at IS NULL");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();

        $conn->close();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function fetch_messages() {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '未登录']);
            return;
        }

        if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
            echo json_encode(['success' => false, 'error' => '无效的房间ID']);
            return;
        }

        $room_id = intval($_GET['room_id']);
        $last_id = isset($_GET['last_id']) && is_numeric($_GET['last_id']) ? intval($_GET['last_id']) : 0;
        $before_id = isset($_GET['before_id']) && is_numeric($_GET['before_id']) ? intval($_GET['before_id']) : 0;
        $user_id = $_SESSION['user_id'];

        // Check membership
        if (!is_room_member($room_id, $user_id)) {
            echo json_encode(['success' => false, 'error' => '无权访问此房间']);
            return;
        }

        $conn = connect_db();

        if ($last_id > 0) {
            // Poll for NEW messages (after last_id)
            $stmt = $conn->prepare("SELECT m.*, COALESCE(u.nickname, u.username) as username, u.avatar 
                                   FROM messages m 
                                   JOIN users u ON m.user_id = u.id 
                                   WHERE m.room_id = ? AND m.id > ? 
                                   ORDER BY m.created_at ASC");
            $stmt->bind_param("ii", $room_id, $last_id);
        } elseif ($before_id > 0) {
            // Load HISTORY (before before_id)
            $stmt = $conn->prepare("SELECT m.*, COALESCE(u.nickname, u.username) as username, u.avatar 
                                   FROM messages m 
                                   JOIN users u ON m.user_id = u.id 
                                   WHERE m.room_id = ? AND m.id < ? 
                                   ORDER BY m.created_at DESC 
                                   LIMIT 20");
            $stmt->bind_param("ii", $room_id, $before_id);
        } else {
            // Initial Load (Recent 20)
            $stmt = $conn->prepare("SELECT m.*, COALESCE(u.nickname, u.username) as username, u.avatar 
                                   FROM messages m 
                                   JOIN users u ON m.user_id = u.id 
                                   WHERE m.room_id = ? 
                                   ORDER BY m.created_at DESC 
                                   LIMIT 20");
            $stmt->bind_param("i", $room_id);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        while ($row = $result->fetch_assoc()) {
            // Backend Burn Logic Enforcement
            if ($row['burn_after_read'] > 0 && $row['opened_at']) {
                $openedTime = strtotime($row['opened_at']);
                $burnTime = $row['burn_after_read'];
                // Buffer of 2 seconds for latency
                if (time() > ($openedTime + $burnTime + 2)) {
                     $row['image_url'] = null;
                     $row['video_url'] = null;
                     $row['audio_url'] = null;
                     $row['content'] = '[内容已销毁]';
                     $row['is_burned'] = true; 
                }
            }
            $messages[] = $row;
        }

        $stmt->close();
        $conn->close();

        // Reverse if fetching history (Initial load OR loading more/before)
        // Because they were fetched with DESC to get "most recent before X".
        if ($last_id == 0) {
            $messages = array_reverse($messages);
        }

        echo json_encode([
            'success' => true,
            'messages' => $messages
        ]);
    }

    public function send_message() {
        // Ensure JSON response
        header('Content-Type: application/json');

        try {
            if (!isset($_SESSION['user_id'])) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => '未登录']);
                return;
            }

            if (!isset($_POST['room_id']) || !is_numeric($_POST['room_id'])) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => '无效的房间ID']);
                return;
            }

            // Verify CSRF
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => 'CSRF令牌验证失败']);
                return;
            }

            $room_id = intval($_POST['room_id']);
            $user_id = $_SESSION['user_id'];
            $content = trim($_POST['content'] ?? '');
            $burn_after_read = intval($_POST['burn_after_read'] ?? 0);
            $image_url = null;
            $video_url = null;
            $audio_url = null;

            $conn = connect_db();

            // Check membership & mute status
            $stmt = $conn->query("SELECT is_muted FROM room_members WHERE room_id = $room_id AND user_id = $user_id");
            if ($stmt->num_rows == 0) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => '无权访问此房间']);
                return;
            }
            $member_info = $stmt->fetch_assoc();
            if ($member_info['is_muted'] == 1) {
                 if (ob_get_length()) ob_clean();
                 echo json_encode(['success' => false, 'error' => '您已被禁言']);
                 return;
            }

            // Check if empty
            if (empty($content) && 
                (!isset($_FILES['image']) || $_FILES['image']['error'] != UPLOAD_ERR_OK) &&
                (!isset($_FILES['video']) || $_FILES['video']['error'] != UPLOAD_ERR_OK) &&
                (!isset($_FILES['audio']) || $_FILES['audio']['error'] != UPLOAD_ERR_OK)
               ) {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => '消息内容不能为空']);
                return;
            }

            // Handle Uploads
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $image_url = Storage::upload($_FILES['image']);
            }
            if (isset($_FILES['video']) && $_FILES['video']['error'] == UPLOAD_ERR_OK) {
                $video_url = Storage::upload($_FILES['video']);
            }
            if (isset($_FILES['audio']) && $_FILES['audio']['error'] == UPLOAD_ERR_OK) {
                $audio_url = Storage::upload($_FILES['audio']);
            }

            // Insert directly
            $stmt = $conn->prepare("INSERT INTO messages (room_id, user_id, content, image_url, video_url, audio_url, burn_after_read) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissssi", $room_id, $user_id, $content, $image_url, $video_url, $audio_url, $burn_after_read);

            if ($stmt->execute()) {
                $message_id = $conn->insert_id;
                
                // Update Status
                update_user_online_status($user_id, $room_id, 1);
                
                // Fetch Details for Frontend
                $query = "SELECT m.*, COALESCE(u.nickname, u.username) as username, u.avatar 
                          FROM messages m 
                          JOIN users u ON m.user_id = u.id 
                          WHERE m.id = ?";
                $stmt_fetch = $conn->prepare($query);
                $stmt_fetch->bind_param("i", $message_id);
                $stmt_fetch->execute();
                $result = $stmt_fetch->get_result();
                $message_data = $result->fetch_assoc();
                
                if (ob_get_length()) ob_clean(); // Nuclear option: discard all previous output (warnings etc)
                echo json_encode(['success' => true, 'message' => $message_data]);
            } else {
                if (ob_get_length()) ob_clean();
                echo json_encode(['success' => false, 'error' => '保存消息失败']);
            }

            $stmt->close();
            $conn->close();

        } catch (Throwable $e) {
            // Catch Fatal Errors and Exceptions
            if (ob_get_length()) ob_clean();
            echo json_encode(['success' => false, 'error' => 'Server Error: ' . $e->getMessage()]);
        }
    }

    public function recall_message() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '未登录']);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'CSRF令牌验证失败']);
            return;
        }

        if (!isset($_POST['message_id']) || !is_numeric($_POST['message_id'])) {
            echo json_encode(['success' => false, 'error' => '参数无效']);
            return;
        }

        $message_id = intval($_POST['message_id']);
        $user_id = $_SESSION['user_id'];

        $conn = connect_db();
        
        $stmt = $conn->prepare("SELECT m.user_id, m.created_at, m.is_recalled, m.room_id, r.created_by as room_owner 
                               FROM messages m 
                               JOIN rooms r ON m.room_id = r.id 
                               WHERE m.id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => '消息不存在']);
            return;
        }

        $message = $result->fetch_assoc();
        $stmt->close();

        if ($message['is_recalled']) {
            echo json_encode(['success' => false, 'error' => '消息已撤回']);
            return;
        }

        // Logic: Allow if (Owner of message) OR (Owner of room)
        $is_owner_of_msg = ($message['user_id'] == $user_id);
        $is_owner_of_room = ($message['room_owner'] == $user_id);

        if (!$is_owner_of_msg && !$is_owner_of_room) {
            echo json_encode(['success' => false, 'error' => '无权撤回此消息']);
            return;
        }

        // Time limit check ONLY for normal user (msg owner), NOT for room owner? 
        // Or strictly strictly only 2 mins for self, limitless for owner?
        // Usually admins can recall anything anytime.
        if (!$is_owner_of_room) {
            $created_at = strtotime($message['created_at']);
            if (time() - $created_at > 120) {
                echo json_encode(['success' => false, 'error' => '超过2分钟无法撤回']);
                return;
            }
        }

        $stmt_update = $conn->prepare("UPDATE messages SET is_recalled = 1, recalled_at = NOW() WHERE id = ?");
        $stmt_update->bind_param("i", $message_id);

        if ($stmt_update->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => '操作失败']);
        }
        
        $stmt_update->close();
        $conn->close();
    }

    public function update_status() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '未登录']);
            return;
        }

        if (!isset($_POST['room_id']) || !is_numeric($_POST['room_id']) || !isset($_POST['status'])) {
            echo json_encode(['success' => false, 'error' => '参数无效']);
            return;
        }

        // Verify CSRF
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'CSRF令牌验证失败']);
            return;
        }

        $room_id = intval($_POST['room_id']);
        $user_id = $_SESSION['user_id'];
        $status = intval($_POST['status']);

        update_user_online_status($user_id, $room_id, $status);

        echo json_encode(['success' => true]);
    }

    public function search() {
        if (!isset($_SESSION['user_id']) || empty($_GET['keyword'])) {
             header('Content-Type: application/json');
             echo json_encode(['success' => false, 'results' => []]);
             exit;
        }
        
        $user_id = $_SESSION['user_id'];
        $keyword = '%' . $_GET['keyword'] . '%';
        $results = [];
        $conn = connect_db();

        // 1. Search Public Messages
        $sqlPublic = "SELECT m.id, m.content, m.created_at, m.room_id as source_id, r.name as source_name, u.avatar, 'public' as type 
                      FROM messages m 
                      JOIN rooms r ON m.room_id = r.id 
                      JOIN users u ON m.user_id = u.id 
                      WHERE m.content LIKE ? 
                      ORDER BY m.created_at DESC LIMIT 20";
        
        $stmt = $conn->prepare($sqlPublic);
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $results[] = $row;
        }

        // 2. Search Private Messages
        $sqlPrivate = "SELECT pm.id, pm.content, pm.created_at, 
                       CASE WHEN pm.sender_id = ? THEN pm.receiver_id ELSE pm.sender_id END as source_id,
                       u.nickname as source_name, u.avatar, 'private' as type
                       FROM private_messages pm
                       JOIN users u ON (CASE WHEN pm.sender_id = ? THEN pm.receiver_id ELSE pm.sender_id END) = u.id
                       WHERE (pm.sender_id = ? OR pm.receiver_id = ?) 
                       AND pm.content LIKE ?
                       ORDER BY pm.created_at DESC LIMIT 20";
                       
        $stmt = $conn->prepare($sqlPrivate);
        $stmt->bind_param("iiiis", $user_id, $user_id, $user_id, $user_id, $keyword);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) {
            $row['source_name'] = $row['source_name'] . ' (私聊)';
            $results[] = $row;
        }
        
        // Sort combined results
        usort($results, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'results' => array_slice($results, 0, 50)]);
        $conn->close();
    }
}
?>
