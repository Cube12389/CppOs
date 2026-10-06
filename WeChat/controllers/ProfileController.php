<?php
// controllers/ProfileController.php

require_once 'core/functions.php';
require_once 'core/Storage.php';

class ProfileController {
    
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php');
        }

        $default_avatars = [
            'default_avatar1.png', 'default_avatar2.png', 'default_avatar3.png',
            'default_avatar4.png', 'default_avatar5.png', 'default_avatar6.png',
            'avatar1.png', 'avatar2.png', 'avatar3.png', 'avatar4.png'
        ];

        $conn = connect_db();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $conn->close();

        view('profile/index', [
            'user' => $user,
            'default_avatars' => $default_avatars,
            'csrf_token' => generate_csrf_token(),
            'success' => $_GET['success'] ?? '',
            'error' => $_GET['error'] ?? ''
        ]);
    }

    public function update() {
        if (!isset($_SESSION['user_id'])) {
            redirect('index.php');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                die("CSRF Error");
            }

            $user_id = $_SESSION['user_id'];
            $conn = connect_db();

            // ACTION: Update Nickname
            if (isset($_POST['new_nickname'])) {
                $new_name = trim($_POST['new_nickname']);
                // ... (Nickname logic remains same)
                // Validation
                if (mb_strlen($new_name) < 2 || mb_strlen($new_name) > 20) {
                     $conn->close();
                     redirect('index.php?route=profile&error=' . urlencode('昵称长度需在2-20字之间'));
                }
                
                $stmt = $conn->prepare("UPDATE users SET nickname = ? WHERE id = ?");
                $stmt->bind_param("si", $new_name, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['nickname'] = $new_name; 
                    $conn->close();
                    redirect('index.php?route=profile&success=' . urlencode('昵称修改成功'));
                } else {
                     $conn->close();
                     redirect('index.php?route=profile&error=' . urlencode('更新失败'));
                }
            }

            // ACTION: Change Password
            if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
                $old_pass = $_POST['old_password'] ?? '';
                $new_pass = $_POST['new_password'] ?? '';
                
                if (strlen($new_pass) < 6) {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('新密码至少需要6位'));
                }

                // Verify Old Password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $res = $stmt->get_result();
                $userData = $res->fetch_assoc();
                
                if (!password_verify($old_pass, $userData['password'])) {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('当前密码错误'));
                }
                
                // Update Password
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $new_hash, $user_id);
                
                if ($update->execute()) {
                    $conn->close();
                    redirect('index.php?route=profile&success=' . urlencode('密码修改成功'));
                } else {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('密码更新失败'));
                }
            }

            // ACTION: Change Username
            if (isset($_POST['action']) && $_POST['action'] === 'change_username') {
                $new_username = trim($_POST['new_username'] ?? '');

                if (mb_strlen($new_username) < 2 || mb_strlen($new_username) > 50) {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('账号长度需在2-50位之间'));
                }

                // Check Uniqueness
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $stmt->bind_param("si", $new_username, $user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('该账号已被注册'));
                }

                // Update
                $stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
                $stmt->bind_param("si", $new_username, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['username'] = $new_username;
                    $conn->close();
                    redirect('index.php?route=profile&success=' . urlencode('登录账号已修改，请牢记新账号'));
                } else {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('修改失败'));
                }
            }

            // ACTION: Update Avatar
            $new_avatar = null;

            // Scenario 1: File Upload
            if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
                try {
                    $new_avatar = Storage::upload($_FILES['avatar_file'], 'avatar');
                } catch (Exception $e) {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('上传失败: ' . $e->getMessage()));
                }
            } 
            // Scenario 2: Select Preset
            elseif (isset($_POST['avatar_select']) && !empty($_POST['avatar_select'])) {
                $new_avatar = $_POST['avatar_select'];
            }

            if ($new_avatar) {
                $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->bind_param("si", $new_avatar, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['avatar'] = $new_avatar;
                    $conn->close();
                    redirect('index.php?route=profile&success=' . urlencode('头像已更新'));
                } else {
                    $conn->close();
                    redirect('index.php?route=profile&error=' . urlencode('数据库更新失败'));
                }
            } else {
                $conn->close();
                redirect('index.php?route=profile&error=' . urlencode('无效的操作'));
            }
        }
    }
}
?>
