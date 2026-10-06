<?php
// controllers/RoomController.php

class RoomController {
    
    // Display Room List
    // Display Room List
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php?route=login');
        }

        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        $avatar = $_SESSION['avatar'];
        $error = $_GET['error'] ?? '';
        $success = $_GET['success'] ?? '';
        
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;
        if($page < 1) $page = 1;
        $limit = 12; // Items per page
        $offset = ($page - 1) * $limit;

        $conn = connect_db();

        // Get Total Count (Only Visible or Joined)
        // Note: For pagination to be accurate, we really should include the same WHERE clause.
        // Complex query for count might be slow, for MVP we might count all or approximate.
        // Let's do it correctly:
        $count_sql = "SELECT COUNT(DISTINCT r.id) as total 
                      FROM rooms r 
                      LEFT JOIN room_members rm ON r.id = rm.room_id AND rm.user_id = $user_id
                      WHERE r.is_hidden = 0 OR rm.user_id IS NOT NULL";
        $count_res = $conn->query($count_sql);
        $total_rows = $count_res->fetch_assoc()['total'];
        $total_pages = ceil($total_rows / $limit);
        
        // Fetch Room List with Pagination
        // Logic: Show Room IF (is_hidden = 0) OR (I am a member)
        $sql = "SELECT r.*, u.username as creator_name, 
               (SELECT COUNT(*) FROM room_members WHERE room_id = r.id AND is_online = 1) as online_count 
               FROM rooms r 
               JOIN users u ON r.created_by = u.id 
               LEFT JOIN room_members rm ON r.id = rm.room_id AND rm.user_id = $user_id
               WHERE r.is_hidden = 0 OR rm.user_id IS NOT NULL
               ORDER BY r.created_at DESC 
               LIMIT $offset, $limit";
               
        $result = $conn->query($sql);

        $rooms = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rooms[] = $row;
            }
        }

        $conn->close();

        // Render View
        view('rooms/index', [
            'rooms' => $rooms,
            'error' => $error,
            'success' => $success,
            'user_id' => $user_id,
            'username' => $username,
            'avatar' => $avatar,
            'csrf_token' => generate_csrf_token(),
            'current_page' => $page,
            'total_pages' => $total_pages
        ]);
    }

    // Handle Create Room
    public function create() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php?route=login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_room'])) {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                die('CSRF Validation Failed');
            }

            $user_id = $_SESSION['user_id'];
            $conn = connect_db();

            $room_name = trim($_POST['room_name']);
            $room_description = trim($_POST['room_description']);
            $room_password = trim($_POST['room_password']);
            $is_hidden = isset($_POST['is_hidden']) ? 1 : 0;
            
            if (empty($room_name)) {
                $conn->close();
                redirect('index.php?route=rooms&error=' . urlencode("请输入房间名称"));
            } else if (strlen($room_name) < 2 || strlen($room_name) > 100) {
                $conn->close();
                redirect('index.php?route=rooms&error=' . urlencode("房间名称长度必须在2-100个字符之间"));
            } else {
                $hashed_password = !empty($room_password) ? password_hash($room_password, PASSWORD_DEFAULT) : NULL;
                
                $stmt = $conn->prepare("INSERT INTO rooms (name, description, password, is_hidden, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $room_name, $room_description, $hashed_password, $is_hidden, $user_id);
                
                if ($stmt->execute()) {
                    $room_id = $conn->insert_id;
                    
                    // Join creator to room
                    $stmt = $conn->prepare("INSERT INTO room_members (room_id, user_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $room_id, $user_id);
                    $stmt->execute();
                    
                    $conn->close();
                    redirect("index.php?route=chat&room_id=$room_id");
                } else {
                    $conn->close();
                    redirect('index.php?route=rooms&error=' . urlencode("房间创建失败"));
                }
            }
        }
        redirect('index.php?route=rooms');
    }

    // Handle Join Room
    public function join() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php?route=login');
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['join_room'])) {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                die('CSRF Validation Failed');
            }

            $user_id = $_SESSION['user_id'];
            $room_id = $_POST['room_id'];
            $password = trim($_POST['password'] ?? '');

            $conn = connect_db();
            
            $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
            $stmt->bind_param("i", $room_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $room = $result->fetch_assoc();
            
            if (!$room) {
                $conn->close();
                redirect('index.php?route=rooms&error=' . urlencode("房间不存在"));
            } else {
                if (!empty($room['password']) && !password_verify($password, $room['password'])) {
                    $conn->close();
                    redirect('index.php?route=rooms&error=' . urlencode("密码错误"));
                } else {
                    // Check if already member
                    $stmt = $conn->prepare("SELECT * FROM room_members WHERE room_id = ? AND user_id = ?");
                    $stmt->bind_param("ii", $room_id, $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 0) {
                        $stmt = $conn->prepare("INSERT INTO room_members (room_id, user_id) VALUES (?, ?)");
                        $stmt->bind_param("ii", $room_id, $user_id);
                        $stmt->execute();
                    }
                    
                    $conn->close();
                    redirect("index.php?route=chat&room_id=$room_id");
                }
            }
        }
        redirect('index.php?route=rooms');
    }

    // Kick Member
    public function kick_member() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '未登录']);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'CSRF令牌验证失败']);
            return;
        }

        if (!isset($_POST['room_id']) || !is_numeric($_POST['room_id']) || 
            !isset($_POST['member_id']) || !is_numeric($_POST['member_id'])) {
            echo json_encode(['success' => false, 'error' => '参数无效']);
            return;
        }

        $room_id = intval($_POST['room_id']);
        $target_user_id = intval($_POST['member_id']);
        $current_user_id = $_SESSION['user_id'];

        if ($target_user_id == $current_user_id) {
            echo json_encode(['success' => false, 'error' => '不能踢出自己']);
            return;
        }

        $conn = connect_db();

        // Verify Owner
        $stmt = $conn->prepare("SELECT created_by FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => '房间不存在']);
            return;
        }

        $room = $result->fetch_assoc();
        if ($room['created_by'] != $current_user_id) {
            echo json_encode(['success' => false, 'error' => '只有房主可以踢人']);
            return;
        }
        $stmt->close();

        // Kick
        $stmt_kick = $conn->prepare("DELETE FROM room_members WHERE room_id = ? AND user_id = ?");
        $stmt_kick->bind_param("ii", $room_id, $target_user_id);

        if ($stmt_kick->execute()) {
            if ($stmt_kick->affected_rows > 0) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => '该用户不在房间内']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => '操作失败']);
        }

        $stmt_kick->close();
        $conn->close();
    }

    // Leave Room
    public function leave_room() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '未登录']);
            return;
        }

        if (!isset($_POST['room_id']) || !is_numeric($_POST['room_id'])) {
            echo json_encode(['success' => false, 'error' => '无效的房间ID']);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'CSRF令牌无效']);
            return;
        }

        $room_id = intval($_POST['room_id']);
        $user_id = $_SESSION['user_id'];

        // Mark offline logic only for simplicty, or delete? 
        // Original logic only updated status to 0. 
        // Wait, 'leave_room.php' only calls update_user_online_status(..., 0). 
        // It does NOT remove them from room_members?
        // Let's check logic: yes, original file only updates status.
        // But usually "leaving" means removing membership.
        // However, migration must be 1:1 first. 
        // Actually, looking at room_list.php, join logic checks room_members.
        // If leave_room.php only sets offline, then the user IS STIll a member.
        // Let's stick to original logic for safety, but typically leave means delete.
        // Re-reading leave_room.php:
        // update_user_online_status($user_id, $room_id, 0);
        // echo json_encode(['success' => true]);
        // That's it. It doesn't delete from room_members. 
        // Wait, if they are still a member, they will show up in sidebar.
        // Let's implement EXACTLY as it was to avoid side effects, then maybe improve later.
        
        // Actually, looking at the UI button "Quit Room" (退出房间), usually means properly quitting.
        // But if the legacy code only set status to 0, I will replicate that.
        // Note: update_user_online_status is a helper function.
        update_user_online_status($user_id, $room_id, 0);
        
        echo json_encode(['success' => true]);
    }

    // Delete Room
    public function delete_room() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '未登录']);
            return;
        }

        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'CSRF令牌验证失败']);
            return;
        }

        if (!isset($_POST['room_id']) || !is_numeric($_POST['room_id'])) {
            echo json_encode(['success' => false, 'error' => '参数无效']);
            return;
        }

        $room_id = intval($_POST['room_id']);
        $current_user_id = $_SESSION['user_id'];

        $conn = connect_db();

        // Verify Owner
        $stmt = $conn->prepare("SELECT created_by FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => '房间不存在']);
            return;
        }

        $room = $result->fetch_assoc();
        if ($room['created_by'] != $current_user_id) {
            echo json_encode(['success' => false, 'error' => '只有房主可以解散房间']);
            return;
        }
        $stmt->close();

        // Transaction
        $conn->begin_transaction();

        try {
            $conn->query("DELETE FROM messages WHERE room_id = $room_id");
            $conn->query("DELETE FROM room_members WHERE room_id = $room_id");
            $conn->query("DELETE FROM rooms WHERE id = $room_id");
            
            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'error' => '解散失败: ' . $e->getMessage()]);
        }

        $conn->close();
    }

    // Transfer Ownership
    public function transfer_ownership() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'error'=>'未登录']); return; }
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false, 'error'=>'CSRF Error']); return; }

        $room_id = intval($_POST['room_id'] ?? 0);
        $new_owner_id = intval($_POST['new_owner_id'] ?? 0);
        $user_id = $_SESSION['user_id'];

        $conn = connect_db();
        
        // Verify Owner
        $stmt = $conn->prepare("SELECT created_by FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        
        if (!$room || $room['created_by'] != $user_id) {
            echo json_encode(['success'=>false, 'error'=>'无权操作']);
            return;
        }

        if ($new_owner_id == $user_id) {
             echo json_encode(['success'=>false, 'error'=>'已经是房主']);
             return;
        }

        // Verify New Owner is Member
        $stmt->close();
        $stmt = $conn->prepare("SELECT * FROM room_members WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $new_owner_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            echo json_encode(['success'=>false, 'error'=>'目标用户不在房间内']);
            return;
        }

        // Update
        $stmt->close();
        $stmt = $conn->prepare("UPDATE rooms SET created_by = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_owner_id, $room_id);
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'error'=>'操作失败']);
        }
        $conn->close();
    }

    // Toggle Mute
    public function toggle_mute() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'error'=>'未登录']); return; }
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false, 'error'=>'CSRF Error']); return; }

        $room_id = intval($_POST['room_id'] ?? 0);
        $target_id = intval($_POST['target_id'] ?? 0);
        $user_id = $_SESSION['user_id'];

        $conn = connect_db();
        
        // Verify Owner
        $stmt = $conn->prepare("SELECT created_by FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        
        if (!$room || $room['created_by'] != $user_id) {
            echo json_encode(['success'=>false, 'error'=>'无权操作']);
            return;
        }

        if ($target_id == $user_id) {
             echo json_encode(['success'=>false, 'error'=>'也不能禁言自己']);
             return;
        }

        // Check current status
        $stmt->close();
        $stmt = $conn->prepare("SELECT is_muted FROM room_members WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $target_id);
        $stmt->execute();
        $mem = $stmt->get_result()->fetch_assoc();
        
        if (!$mem) {
            echo json_encode(['success'=>false, 'error'=>'用户不在房间']);
            return;
        }

        $new_status = $mem['is_muted'] ? 0 : 1;
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE room_members SET is_muted = ? WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("iii", $new_status, $room_id, $target_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success'=>true, 'is_muted' => $new_status]);
        } else {
            echo json_encode(['success'=>false, 'error'=>'操作失败']);
        }
        $conn->close();
    }

    // Update Room Info
    public function update_room() {
        header('Content-Type: application/json');
        if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false, 'error'=>'未登录']); return; }
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { echo json_encode(['success'=>false, 'error'=>'CSRF Error']); return; }

        $room_id = intval($_POST['room_id'] ?? 0);
        $name = trim($_POST['room_name'] ?? '');
        $desc = trim($_POST['room_description'] ?? '');
        $pass = trim($_POST['room_password'] ?? '');
        $user_id = $_SESSION['user_id'];

        if (empty($name)) { echo json_encode(['success'=>false, 'error'=>'房间名不能为空']); return; }
        if (mb_strlen($name) > 100) { echo json_encode(['success'=>false, 'error'=>'房间名过长']); return; }

        $conn = connect_db();
        
        // Verify Owner
        $stmt = $conn->prepare("SELECT created_by, password FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $room = $stmt->get_result()->fetch_assoc();
        
        if (!$room || $room['created_by'] != $user_id) {
            echo json_encode(['success'=>false, 'error'=>'无权操作']);
            return;
        }

        $hashed_password = $room['password'];
        if (isset($_POST['room_password'])) {
             // If input empty, clear password (public). If set, hash it
             if (empty($pass)) {
                 $hashed_password = NULL;
             } else {
                 $hashed_password = password_hash($pass, PASSWORD_DEFAULT);
             }
        }

        $stmt->close();
        $stmt = $conn->prepare("UPDATE rooms SET name = ?, description = ?, password = ? WHERE id = ?");
        $stmt->bind_param("sssi", $name, $desc, $hashed_password, $room_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success'=>true]);
        } else {
            echo json_encode(['success'=>false, 'error'=>'更新失败']);
        }
        $conn->close();
    }

    // Get Room Members (API)
    public function get_members() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '未登录']);
            return;
        }

        if (!isset($_GET['room_id']) || !is_numeric($_GET['room_id'])) {
            echo json_encode(['success' => false, 'error' => '无效的房间ID']);
            return;
        }

        $room_id = intval($_GET['room_id']);
        // Verify Membership
        if (!is_room_member($room_id, $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => '无权访问此房间']);
            return;
        }

        $conn = connect_db();
        $stmt = $conn->prepare("SELECT u.id, COALESCE(u.nickname, u.username) as display_name, u.username as login_name, u.avatar, rm.is_online, rm.is_muted 
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

        echo json_encode([
            'success' => true,
            'members' => $members
        ]);
    }

    // Handle Link Invite (e.g. index.php?route=invite&room_id=X)
    public function handle_invite() {
        if (!isset($_SESSION['user_id'])) {
            // Keep room_id in query param to redirect after login? 
            // For now just redirect to login
            redirect('index.php?route=login');
        }

        $room_id = intval($_GET['room_id'] ?? 0);
        if ($room_id <= 0) {
            redirect('index.php?route=rooms');
        }
        
        // Check if room exists
        $conn = connect_db();
        $stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
        $stmt->bind_param("i", $room_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $room = $res->fetch_assoc();
        $stmt->close();

        if (!$room) {
            $conn->close();
            redirect('index.php?route=rooms&error=' . urlencode('房间不存在'));
        }

        $user_id = $_SESSION['user_id'];
        
        // Check membership
        $stmt = $conn->prepare("SELECT * FROM room_members WHERE room_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $room_id, $user_id);
        $stmt->execute();
        $is_member = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        
        $conn->close();

        if ($is_member) {
            // Already member, go to chat
            redirect("index.php?route=chat&room_id=$room_id");
        } else {
            // Not member.
            // If has password -> Redirect to Rooms with "Join Modal" Trigger?
            // Or just redirect to rooms, and if we can, auto-open modal. 
            redirect("index.php?route=rooms&join_id=$room_id");
        }
    }
}
?>
