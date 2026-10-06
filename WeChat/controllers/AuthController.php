<?php
// controllers/AuthController.php

class AuthController {
    
    public function index() {
        global $default_avatars; // From config.php/db_connect.php context if included, but better to ensure access.
        // Actually $default_avatars is defined in db_connect.php? No, let's check. 
        // It was usually in db_connect.php global scope.
        
        // If user is logged in, redirect to rooms
        if (isset($_SESSION['user_id'])) {
            redirect('index.php?route=rooms');
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
            // Verify CSRF
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                die('CSRF Validation Failed');
            }

            $username = trim($_POST['username']);
            $password = $_POST['password'] ?? '';
            
            if (empty($username)) {
                $error = "请输入用户名";
            } else if (strlen($username) < 2 || strlen($username) > 50) {
                $error = "用户名长度必须在2-50个字符之间";
            } else if (empty($password)) {
                $error = "请输入密码";
            } else if (strlen($password) < 6) {
                $error = "密码长度至少需要6位";
            } else {
                $conn = connect_db();
                
                // Check if user exists
                $stmt = $conn->prepare("SELECT id, avatar, password, nickname, is_banned FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    // User exists
                    $user = $result->fetch_assoc();
                    
                    if ($user['is_banned']) {
                        $error = "该账号已被封禁，请联系管理员";
                    } else if ($user['password'] === NULL) {
                        // First time login for old user
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $hashed_password, $user['id']);
                        $update_stmt->execute();
                        $update_stmt->close();
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $username;
                        $_SESSION['nickname'] = $user['nickname'] ?: $username; // Fallback
                        $_SESSION['avatar'] = $user['avatar'];
                        
                        redirect('index.php?route=rooms');
                    } else {
                        // Verify password
                        if (password_verify($password, $user['password'])) {
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $username;
                            $_SESSION['nickname'] = $user['nickname'] ?: $username;
                            $_SESSION['avatar'] = $user['avatar'];
                            redirect('index.php?route=rooms');
                        } else {
                            $error = "密码错误";
                        }
                    }
                } else {
                    // Register new user
                    require_once 'core/Storage.php'; // Ensure Storage class is loaded for getSetting
                    
                    // --- Validation Start ---
                    // 1. Regex Validation: Allow letters, numbers, underscores, and Chinese characters
                    if (!preg_match('/^[A-Za-z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
                        $error = "用户名只能包含汉字、字母、数字和下划线，不能包含特殊符号";
                    } 
                    // 2. Blacklist Validation: Block reserved words
                    else {
                        $blacklist = ['admin', 'administrator', 'system', 'root', 'support', 'moderator', 'operator', 'bot', 'server', '管理', '系统', '房主', '官方'];
                        $is_blacklisted = false;
                        $lower_username = mb_strtolower($username, 'UTF-8');
                        
                        foreach ($blacklist as $bad_word) {
                            if (mb_strpos($lower_username, $bad_word) !== false) {
                                $is_blacklisted = true;
                                break;
                            }
                        }
                        
                        if ($is_blacklisted) {
                             $error = "用户名包含敏感词或保留字，不可使用";
                        } else if (Storage::getSetting('registration_enabled') == '0') {
                            $error = "当前已停止新用户注册";
                        } else {
                            // --- Validation Passed, Proceed ---
                         // Need $default_avatars...
                    // Need $default_avatars. Re-defining here just in case or ensure it's available.
                    // It is in db_connect.php usually at top level? No, let's look.
                    // Assuming it's available or I'll define it here.
                    $avatars = [
                        'avatar1.png', 'avatar2.png', 'avatar3.png', 'avatar4.png', 
                        'avatar5.png', 'avatar6.png', 'avatar7.png', 'avatar8.png'
                    ];
                    
                    $random_avatar = $avatars[array_rand($avatars)];
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $nickname = $username; // Default nickname
                    $stmt = $conn->prepare("INSERT INTO users (username, nickname, password, avatar) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $nickname, $hashed_password, $random_avatar);
                    
                    if ($stmt->execute()) {
                        $_SESSION['user_id'] = $conn->insert_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['nickname'] = $nickname;
                        $_SESSION['avatar'] = $random_avatar;
                        redirect('index.php?route=rooms');
                    } else {
                        $error = "注册失败，请重试";
                    }
                    }
                        }
                    }

                
                $stmt->close();
                $conn->close();
            }
        }

        // Render view
        view('auth/login', [
            'error' => $error,
            'csrf_token' => generate_csrf_token()
        ]);
    }
    
    public function logout() {
        session_start();
        session_destroy();
        redirect('index.php?route=login');
    }
}
?>
