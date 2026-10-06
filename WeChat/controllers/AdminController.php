<?php
// controllers/AdminController.php

require_once 'core/functions.php';

class AdminController {
    
    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php');
        }
        
        // Refresh user role from DB in case it changed
        $conn = connect_db();
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $conn->close();

        if ($user['role'] !== 'admin') {
            die("Access Denied: Admin only.");
        }
    }

    public function index() {
        $conn = connect_db();
        
        // Fetch Settings
        $settings = [];
        $result = $conn->query("SELECT * FROM system_settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }
        
        $conn->close();
        
        $csrf_token = generate_csrf_token();
        $success = $_GET['success'] ?? '';
        
        view('admin/settings', [
            'settings' => $settings, 
            'csrf_token' => $csrf_token,
            'success' => $success
        ]);
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                die("CSRF Token Verification Failed");
            }

            $conn = connect_db();
            
            // Allowed keys to prevent pollution
            $allowed_keys = [
                'storage_driver', 
                'upload_image_limit',
                'upload_video_limit',
                'aliyun_access_key', 
                'aliyun_access_key_secret', 
                'aliyun_endpoint', 
                'aliyun_bucket',
                's3_endpoint',
                's3_region', 
                's3_access_key', 
                's3_secret_key', 
                's3_bucket',
                's3_bucket',
                'imgur_client_id',
                'site_name',
                'site_announcement',
                'registration_enabled'
            ];

            foreach ($allowed_keys as $key) {
                if (isset($_POST[$key])) {
                    $val = trim($_POST[$key]);
                    $sql = "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $key, $val, $val);
                    $stmt->execute();
                }
            }
            
            $conn->close();
            redirect('index.php?route=admin&success=配置已更新');
            $conn->close();
            redirect('index.php?route=admin&success=配置已更新');
        }
    }

    public function get_users() {
        header('Content-Type: application/json');
        $conn = connect_db();
        $res = $conn->query("SELECT id, username, nickname, avatar, role, is_banned, created_at FROM users ORDER BY id DESC");
        $users = [];
        while($row = $res->fetch_assoc()) {
            $users[] = $row;
        }
        echo json_encode(['success' => true, 'users' => $users]);
        $conn->close();
    }

    public function ban_user() {
        $this->toggle_ban(1);
    }

    public function unban_user() {
        $this->toggle_ban(0);
    }

    private function toggle_ban($status) {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die(json_encode(['error' => 'Method Not Allowed']));

        $user_id = intval($_POST['user_id'] ?? 0);
        if ($user_id <= 0) die(json_encode(['error' => 'Invalid ID']));
        if ($user_id == $_SESSION['user_id']) die(json_encode(['error' => 'Cannot ban yourself']));

        $conn = connect_db();
        $stmt = $conn->prepare("UPDATE users SET is_banned = ? WHERE id = ?");
        $stmt->bind_param("ii", $status, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Database Error']);
        }
        $conn->close();
    }

    public function get_rooms() {
        header('Content-Type: application/json');
        $conn = connect_db();
        // Get rooms with member count and owner name
        $sql = "SELECT r.id, r.name, r.created_at, u.username as owner_name, 
                (SELECT COUNT(*) FROM room_members rm WHERE rm.room_id = r.id) as member_count 
                FROM rooms r 
                LEFT JOIN users u ON r.created_by = u.id 
                ORDER BY r.created_at DESC";
        $res = $conn->query($sql);
        $rooms = [];
        while($row = $res->fetch_assoc()) {
            $rooms[] = $row;
        }
        echo json_encode(['success' => true, 'rooms' => $rooms]);
        $conn->close();
    }

    public function delete_room() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die(json_encode(['error' => 'Method Not Allowed']));

        $room_id = intval($_POST['room_id'] ?? 0);
        if ($room_id <= 0) die(json_encode(['error' => 'Invalid ID']));

        $conn = connect_db();
        // Manual Cascade Delete (safest approach if FKs aren't strict)
        $conn->query("DELETE FROM messages WHERE room_id = $room_id");
        $conn->query("DELETE FROM room_members WHERE room_id = $room_id");
        $conn->query("DELETE FROM rooms WHERE id = $room_id");
        
        echo json_encode(['success' => true]);
        $conn->close();
    }

    public function clear_room_history() {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') die(json_encode(['error' => 'Method Not Allowed']));

        $room_id = intval($_POST['room_id'] ?? 0);
        if ($room_id <= 0) die(json_encode(['error' => 'Invalid ID']));

        $conn = connect_db();
        if ($conn->query("DELETE FROM messages WHERE room_id = $room_id")) {
             echo json_encode(['success' => true]);
        } else {
             echo json_encode(['error' => 'Database Error']);
        }
        $conn->close();
    }
}
?>
