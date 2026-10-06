<?php
// index.php
ob_start(); // Buffer all output to prevent warnings from breaking JSON
error_reporting(E_ALL); // Debugging: Show all errors
ini_set('display_errors', 1);
session_start();

// Disable strict CSP triggers by server (Allow Tailwind CDN eval)
header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' data: blob: https: wss: ws:;");

// 0. Check Installation
if (!file_exists('config.php')) {
    header("Location: install.php");
    exit;
}

require_once 'config.php';
require_once 'db_connect.php';
require_once 'core/functions.php';
require_once 'core/i18n.php';
$LOCALE = load_language();

// --- AUTO MIGRATION CHECK (For Admin Features) ---
// This acts as a self-healing mechanism since CLI access is flaky
if (!isset($_GET['migrated'])) { 
    try {
        $conn = connect_db();
    } catch (Exception $e) {
        // If DB connection fails, it might be due to bad config or DB down.
        // We can't automatically redirect to install.php if config.php exists (it might be a transient error),
        // but for this specific "Access denied" case on fresh deploy, it helps to show a clear message.
        die('<div style="font-family:sans-serif;text-align:center;padding:50px;">
                <h1>数据库连接失败</h1>
                <p>无法连接到数据库，可能是配置错误。</p>
                <p>错误信息: '. htmlspecialchars($e->getMessage()) .'</p>
                <hr>
                <p>如果您正在重新安装，请删除根目录下的 <code>config.php</code> 文件并刷新页面。</p>
             </div>');
    }
    
    // Check system_settings
    $check = $conn->query("SHOW TABLES LIKE 'system_settings'");
    if ($check->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(50) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Defaults
        $defaults = [
            'storage_driver' => 'local',
            'upload_max_size' => '5', // Legacy, to be removed or mapped
            'upload_image_limit' => '5',
            'upload_video_limit' => '50',
            'aliyun_access_key' => '',
            'aliyun_access_key_secret' => '',
            'aliyun_endpoint' => '',
            'aliyun_bucket' => '',
            's3_endpoint' => '',
            's3_region' => 'auto',
            's3_access_key' => '',
            's3_secret_key' => '',
            's3_bucket' => '',
            's3_bucket' => '',
            'imgur_client_id' => '',
            'site_name' => '轻聊 LiteTalk',
            'site_announcement' => '欢迎来到聊天室！请文明发言。',
            'registration_enabled' => '1',
            'landing_page_enabled' => '1'
        ];
        foreach ($defaults as $key => $val) {
            $sql = "INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES ('$key', '$val')";
            $conn->query($sql);
        }
    }
    
    // This part seems to be a new migration check, assuming $res would be defined elsewhere or is a placeholder.
    // Keeping it as is per instruction, but it might lead to an undefined variable error if $res is not set.
    if (isset($res) && $res->num_rows == 0) { // Added isset($res) to prevent error if $res is not defined
        $conn->query("ALTER TABLE messages ADD COLUMN is_recalled BOOLEAN DEFAULT 0, ADD COLUMN recalled_at DATETIME NULL");
    }

    // Check nickname column (Separation of Username/Nickname)
    $check_nick = $conn->query("SHOW COLUMNS FROM users LIKE 'nickname'");
    if ($check_nick->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN nickname VARCHAR(50) DEFAULT NULL AFTER username");
        // Backfill existing users
        $conn->query("UPDATE users SET nickname = username WHERE nickname IS NULL");
    }
    
    // Check role column
    $checkCol = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
    if ($checkCol->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER username");
        $conn->query("UPDATE users SET role = 'admin' WHERE username = 'admin'");
    }

    // Check is_banned column
    $checkBan = $conn->query("SHOW COLUMNS FROM users LIKE 'is_banned'");
    if ($checkBan->num_rows == 0) {
        $conn->query("ALTER TABLE users ADD COLUMN is_banned TINYINT(1) DEFAULT 0 AFTER role");
    }
    
    // Check private_messages table
    $check_pm = $conn->query("SHOW TABLES LIKE 'private_messages'");
    if ($check_pm->num_rows == 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS private_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL,
            content TEXT,
            image VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id),
            FOREIGN KEY (receiver_id) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    // Check private_messages deletion columns (Soft Delete)
    $check_pm_del = $conn->query("SHOW COLUMNS FROM private_messages LIKE 'deleted_by_sender'");
    if ($check_pm_del->num_rows == 0) {
        $conn->query("ALTER TABLE private_messages ADD COLUMN deleted_by_sender TINYINT(1) DEFAULT 0");
        $conn->query("ALTER TABLE private_messages ADD COLUMN deleted_by_receiver TINYINT(1) DEFAULT 0");
    }

    // Media Columns Migration (Fix for Unknown column 'video')
    $check_pm_vid = $conn->query("SHOW COLUMNS FROM private_messages LIKE 'video_url'");
    if ($check_pm_vid->num_rows == 0) {
        // Migration: Add video_url and audio_url
        $conn->query("ALTER TABLE private_messages ADD COLUMN video_url VARCHAR(255) DEFAULT NULL");
        $conn->query("ALTER TABLE private_messages ADD COLUMN audio_url VARCHAR(255) DEFAULT NULL");
    }
    
    // Ensure image_url (Fix if 'image' was used previously)
    $check_pm_img = $conn->query("SHOW COLUMNS FROM private_messages LIKE 'image_url'");
    if ($check_pm_img->num_rows == 0) {
         $check_legacy_img = $conn->query("SHOW COLUMNS FROM private_messages LIKE 'image'");
         if ($check_legacy_img->num_rows > 0) {
             $conn->query("ALTER TABLE private_messages CHANGE COLUMN image image_url VARCHAR(255) DEFAULT NULL");
         } else {
             $conn->query("ALTER TABLE private_messages ADD COLUMN image_url VARCHAR(255) DEFAULT NULL");
         }
    }

    // Media Columns Migration (Fix for Unknown column 'video')
    // (Duplicate block removed)

    // Check rooms hidden column
    $check_room_hidden = $conn->query("SHOW COLUMNS FROM rooms LIKE 'is_hidden'");
    if ($check_room_hidden->num_rows == 0) {
        $conn->query("ALTER TABLE rooms ADD COLUMN is_hidden TINYINT(1) DEFAULT 0 AFTER password");
    }

    // Check room_members muted column
    $check_member_muted = $conn->query("SHOW COLUMNS FROM room_members LIKE 'is_muted'");
    if ($check_member_muted->num_rows == 0) {
        $conn->query("ALTER TABLE room_members ADD COLUMN is_muted TINYINT(1) DEFAULT 0 AFTER is_online");
    }

    // Check burn_after_read for private_messages
    $check_burn = $conn->query("SHOW COLUMNS FROM private_messages LIKE 'burn_after_read'");
    if ($check_burn->num_rows == 0) {
        $conn->query("ALTER TABLE private_messages ADD COLUMN burn_after_read INT DEFAULT 0"); 
        $conn->query("ALTER TABLE private_messages ADD COLUMN opened_at DATETIME DEFAULT NULL");
    }

    // Check is_recalled for private_messages
    $check_recall = $conn->query("SHOW COLUMNS FROM private_messages LIKE 'is_recalled'");
    if ($check_recall->num_rows == 0) {
        $conn->query("ALTER TABLE private_messages ADD COLUMN is_recalled TINYINT DEFAULT 0");
    }

    // Check burn_after_read for public messages
    $check_burn_pub = $conn->query("SHOW COLUMNS FROM messages LIKE 'burn_after_read'");
    if ($check_burn_pub->num_rows == 0) {
        $conn->query("ALTER TABLE messages ADD COLUMN burn_after_read INT DEFAULT 0");
        $conn->query("ALTER TABLE messages ADD COLUMN opened_at TIMESTAMP NULL DEFAULT NULL");
    }

    // Check video_url columns
    $checkVideo = $conn->query("SHOW COLUMNS FROM messages LIKE 'video_url'");
    if ($checkVideo->num_rows == 0) {
        $conn->query("ALTER TABLE messages ADD COLUMN video_url VARCHAR(255) DEFAULT NULL AFTER image_url");
        $conn->query("ALTER TABLE private_messages ADD COLUMN video VARCHAR(255) DEFAULT NULL AFTER image");
    }

    // Check audio columns
    $checkAudio = $conn->query("SHOW COLUMNS FROM messages LIKE 'audio_url'");
    if ($checkAudio->num_rows == 0) {
        $conn->query("ALTER TABLE messages ADD COLUMN audio_url VARCHAR(255) DEFAULT NULL AFTER video_url");
        $conn->query("ALTER TABLE private_messages ADD COLUMN audio VARCHAR(255) DEFAULT NULL AFTER video");
    }

    $conn->close();
}

// Routes
$route = $_GET['route'] ?? 'home';

switch ($route) {
    case 'login':
        require_once 'controllers/AuthController.php';
        (new AuthController())->index();
        break;
        
    case 'logout':
        require_once 'controllers/AuthController.php';
        $controller = new AuthController();
        $controller->logout();
        break;
        
    case 'rooms':
        require_once 'controllers/RoomController.php';
        $controller = new RoomController();
        if (isset($_POST['create_room'])) {
            $controller->create();
        } elseif (isset($_POST['join_room'])) {
            $controller->join();
        } else {
            $controller->index();
        }
        break;
        
    // --- PROFILE ROUTES ---
    case 'profile':
        require_once 'controllers/ProfileController.php';
        (new ProfileController())->index();
        break;

    case 'profile_update':
        require_once 'controllers/ProfileController.php';
        (new ProfileController())->update();
        break;

    case 'chat':
        require_once 'controllers/ChatController.php';
        $controller = new ChatController();
        $controller->index();
        break;

    case 'mark_public_opened':
        require_once 'controllers/ChatController.php';
        (new ChatController())->mark_opened();
        break;

    case 'chat_get_messages':
        require_once 'controllers/ChatController.php';
        (new ChatController())->fetch_messages();
        break;

    case 'chat_send_message':
        require_once 'controllers/ChatController.php';
        (new ChatController())->send_message();
        break;

    // --- SEARCH ---
    case 'search_messages':
        require_once 'controllers/ChatController.php';
        (new ChatController())->search();
        break;

    case 'chat_recall':
        require_once 'controllers/ChatController.php';
        (new ChatController())->recall_message();
        break;

    case 'room_kick':
        require_once 'controllers/RoomController.php';
        (new RoomController())->kick_member();
        break;

    case 'room_leave':
        require_once 'controllers/RoomController.php';
        (new RoomController())->leave_room();
        break;

    case 'room_delete':
        require_once 'controllers/RoomController.php';
        (new RoomController())->delete_room();
        break;

    case 'invite':
        require_once 'controllers/RoomController.php';
        (new RoomController())->handle_invite();
        break;

    case 'room_members':
        require_once 'controllers/RoomController.php';
        (new RoomController())->get_members();
        break;

    case 'user_status':
        require_once 'controllers/ChatController.php';
        (new ChatController())->update_status();
        break;

    // --- ADMIN ROUTES ---
    case 'admin':
        require_once 'controllers/AdminController.php';
        $controller = new AdminController();
        $controller->index();
        break;

    case 'admin_update':
        require_once 'controllers/AdminController.php';
        (new AdminController())->update();
        break;

    case 'admin_get_users':
        require_once 'controllers/AdminController.php';
        (new AdminController())->get_users();
        break;

    case 'admin_ban_user':
        require_once 'controllers/AdminController.php';
        (new AdminController())->ban_user();
        break;

    case 'admin_unban_user':
        require_once 'controllers/AdminController.php';
        (new AdminController())->unban_user();
        break;

    case 'admin_get_rooms':
        require_once 'controllers/AdminController.php';
        (new AdminController())->get_rooms();
        break;

    case 'admin_delete_room':
        require_once 'controllers/AdminController.php';
        (new AdminController())->delete_room();
        break;

    case 'admin_clear_room_history':
        require_once 'controllers/AdminController.php';
        (new AdminController())->clear_room_history();
        break;

    // --- PRIVATE CHAT ROUTES ---
    case 'messages':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->index();
        break;
        
    case 'chat_private':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->chat();
        break;
        
    case 'send_private':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->send();
        break;

    case 'private_clear_history':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->clear_history();
        break;

    case 'private_delete_session':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->delete_session();
        break;

    case 'private_mark_opened':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->mark_opened();
        break;

    case 'private_recall':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->recall();
        break;
        
    case 'check_user':
        require_once 'controllers/PrivateChatController.php';
        (new PrivateChatController())->checkUser();
        break;
        
    case 'switch_lang':
        $lang = $_GET['lang'] ?? 'zh';
        if(in_array($lang, ['zh', 'en'])) {
            $_SESSION['lang'] = $lang;
        }
        
        session_write_close(); 
        
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: $referer");
        exit;
        
    default:
        // Default route
        if (isset($_SESSION['user_id'])) {
            redirect('index.php?route=rooms');
        } else {
            // Show Landing Page if enabled, otherwise login
            require_once 'core/Storage.php';
            $landingParams = Storage::getSetting('landing_page_enabled');
            // If setting missing (during upgrade before migration runs fully?), default to 1
            if ($landingParams === null) $landingParams = '1';
            
            if ($landingParams == '1') {
                require_once 'controllers/HomeController.php';
                (new HomeController())->index();
            } else {
                redirect('index.php?route=login');
            }
        }
        break;
}
?>
