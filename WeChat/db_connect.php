<?php
require_once 'config.php';

// 数据库连接函数
function connect_db() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // 检查连接
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    // 设置字符集
    $conn->set_charset("utf8mb4");
    
    return $conn;
}

// 获取用户信息
function get_user($user_id) {
    $conn = connect_db();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $user;
}

// 获取房间信息
function get_room($room_id) {
    $conn = connect_db();
    $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $room = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $room;
}

// 检查用户是否为房间成员
function is_room_member($room_id, $user_id) {
    $conn = connect_db();
    $stmt = $conn->prepare("SELECT * FROM room_members WHERE room_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $room_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_member = $result->num_rows > 0;
    $stmt->close();
    $conn->close();
    return $is_member;
}

// 更新用户在线状态
function update_user_online_status($user_id, $room_id, $is_online) {
    $conn = connect_db();
    $stmt = $conn->prepare("UPDATE room_members SET is_online = ?, last_active = NOW() WHERE user_id = ? AND room_id = ?");
    $stmt->bind_param("iii", $is_online, $user_id, $room_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}
// 生成CSRF令牌
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// 验证CSRF令牌
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
?>
