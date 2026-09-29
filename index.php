<?php
/* ============================================================
 * 【关键】所有 PHP 处理逻辑必须放在任何 HTML 输出之前！
 * ============================================================ */

$servername = "localhost";
$username   = "root";
$password   = "123456";
$dbname     = "user";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

/* ============================================================
 * 贡献数 → 颜色 分级表
 * ============================================================ */
function num_to_color($num) {
    $tiers = [
        ['min' => 500, 'color' => '#c0392b'],
        ['min' => 200, 'color' => '#e74c3c'],
        ['min' => 100, 'color' => '#e67e22'],
        ['min' => 50,  'color' => '#f1c40f'],
        ['min' => 20,  'color' => '#2ecc71'],
        ['min' => 10,  'color' => '#1abc9c'],
        ['min' => 5,   'color' => '#3498db'],
        ['min' => 1,   'color' => '#95a5a6'],
    ];
    foreach ($tiers as $t) {
        if ($num >= $t['min']) {
            return $t['color'];
        }
    }
    return '#95a5a6';
}

function resolve_user_color($alc, $num) {
    if ((int)$alc === 2) {
        return '#8e44ad';
    }
    return num_to_color((int)$num);
}
/* ============================================================ */

$is_logged_in = false;
$user_info    = null;
$msg          = '';

/* ---------------- 1. 用 cookie 自动登录 ---------------- */
if (!empty($_COOKIE['login_cookie'])) {
    $token = $_COOKIE['login_cookie'];
    $stmt  = $conn->prepare("SELECT `uid` FROM `cookie` WHERE `cookie` = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($c = $res->fetch_assoc()) {
            $uid = (int)$c['uid'];
            $stmt->close();

            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `uid` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $is_logged_in = true;
                    $user_info    = $row;
                }
                $stmt->close();
            }
        } else {
            $stmt->close();
            setcookie("login_cookie", "", time() - 3600, "/", "", false, true);
        }
    }
}

/* ---------------- 2. 处理 POST ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ---------- 退出登录 ---------- */
    if ($action === 'logout') {
        if ($is_logged_in) {
            $del = $conn->prepare("DELETE FROM `cookie` WHERE `uid` = ?");
            if ($del) {
                $uid_del = (int)$user_info['uid'];
                $del->bind_param("i", $uid_del);
                $del->execute();
                $del->close();
            }
        }
        setcookie("login_cookie", "", time() - 3600, "/", "", false, true);
        $here = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        header("Location: " . $here);
        exit;
    }

    /* ---------- 修改密码 ---------- */
    else if ($action === 'change_password') {
        if (!$is_logged_in) {
            $msg = "<div class='alert alert-error'>请先登录</div>";
        } else {
            $old   = $_POST['old_password']  ?? '';
            $new   = $_POST['new_password']  ?? '';
            $new2  = $_POST['new_password2'] ?? '';

            if ($old === '' || $new === '' || $new2 === '') {
                $msg = "<div class='alert alert-error'>请填写完整</div>";
            } else if ($new !== $new2) {
                $msg = "<div class='alert alert-error'>两次新密码不一致</div>";
            } else if (strlen($new) < 6) {
                $msg = "<div class='alert alert-error'>新密码至少 6 位</div>";
            } else if (!password_verify($old, $user_info['password'])) {
                $msg = "<div class='alert alert-error'>旧密码错误</div>";
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `uid` = ?");
                if ($upd) {
                    $uid_cp = (int)$user_info['uid'];
                    $upd->bind_param("si", $newHash, $uid_cp);
                    if ($upd->execute()) {
                        $newToken = bin2hex(random_bytes(32));

                        $ck = $conn->prepare("UPDATE `cookie` SET `cookie` = ? WHERE `uid` = ?");
                        if ($ck) {
                            $ck->bind_param("si", $newToken, $uid_cp);
                            $ck->execute();
                            $ck->close();
                        } else {
                            $ck2 = $conn->prepare("INSERT INTO `cookie` (`cookie`, `uid`) VALUES (?, ?)");
                            if ($ck2) {
                                $ck2->bind_param("si", $newToken, $uid_cp);
                                $ck2->execute();
                                $ck2->close();
                            }
                        }

                        setcookie("login_cookie", $newToken, time() + 86400 * 30, "/", "", false, true);

                        $user_info['password'] = $newHash;

                        $msg = "<div class='alert alert-success'>密码修改成功，其他设备已被强制下线</div>";
                    } else {
                        $msg = "<div class='alert alert-error'>修改失败: " . htmlspecialchars($upd->error) . "</div>";
                    }
                    $upd->close();
                } else {
                    $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    }

    /* ---------- 登录 ---------- */
    else if ($action === 'login') {
        $name         = $_POST['name'] ?? '';
        $UserPassword = $_POST['password'] ?? '';

        if ($name === '' || $UserPassword === '') {
            $msg = "<div class='alert alert-error'>请输入用户名和密码</div>";
        } else {
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `name` = ?");
            if (!$stmt) {
                $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
            } else {
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    if (password_verify($UserPassword, $row['password'])) {

                        if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                            $newHash = password_hash($UserPassword, PASSWORD_DEFAULT);
                            $upd = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `uid` = ?");
                            if ($upd) {
                                $upd->bind_param("si", $newHash, $row['uid']);
                                $upd->execute();
                                $upd->close();
                            }
                        }

                        $is_logged_in = true;
                        $user_info    = $row;

                        $token = bin2hex(random_bytes(32));

                        $uid_for_cookie = (int)$row['uid'];

                        $chk = $conn->prepare("SELECT `cookie` FROM `cookie` WHERE `uid` = ? LIMIT 1");
                        if ($chk) {
                            $chk->bind_param("i", $uid_for_cookie);
                            $chk->execute();
                            $chkRes = $chk->get_result();
                            $existing = $chkRes->fetch_assoc();
                            $chk->close();

                            if ($existing) {
                                $stmt2 = $conn->prepare("UPDATE `cookie` SET `cookie` = ? WHERE `uid` = ?");
                                if ($stmt2) {
                                    $stmt2->bind_param("si", $token, $uid_for_cookie);
                                    $stmt2->execute();
                                    $stmt2->close();
                                }
                            } else {
                                $stmt2 = $conn->prepare("INSERT INTO `cookie` (`cookie`, `uid`) VALUES (?, ?)");
                                if ($stmt2) {
                                    $stmt2->bind_param("si", $token, $uid_for_cookie);
                                    $stmt2->execute();
                                    $stmt2->close();
                                }
                            }

                            setcookie("login_cookie", $token, time() + 86400 * 30, "/", "", false, true);
                        }
                    } else {
                        $msg = "<div class='alert alert-error'>用户名或密码错误</div>";
                    }
                } else {
                    $msg = "<div class='alert alert-error'>用户不存在</div>";
                }
                $stmt->close();
            }
        }
    }

    /* ---------- 注册 ---------- */
    else if ($action === 'reslogin') {
        $name          = $_POST['name'] ?? '';
        $UserPassword  = $_POST['password'] ?? '';
        $UserPassword2 = $_POST['passwordag'] ?? '';

        if ($name === '' || $UserPassword === '' || $UserPassword2 === '') {
            $msg = "<div class='alert alert-error'>请输入用户名和密码</div>";
        } else if ($UserPassword !== $UserPassword2) {
            $msg = "<div class='alert alert-error'>两次密码不一致</div>";
        } else {
            $chk = $conn->prepare("SELECT `uid` FROM `user` WHERE `name` = ?");
            $exists = false;
            if ($chk) {
                $chk->bind_param("s", $name);
                $chk->execute();
                $chkRes = $chk->get_result();
                $exists = (bool)$chkRes->fetch_assoc();
                $chk->close();
            }

            if ($exists) {
                $msg = "<div class='alert alert-error'>用户名已存在</div>";
            } else {
                $hashed = password_hash($UserPassword, PASSWORD_DEFAULT);
                $stmt1  = $conn->prepare("INSERT INTO `user` (`name`, `calling`, `password`, `alc`, `color`, `num`) VALUES (?, 'none', ?, '1', '#0e90d2', '0')");
                if ($stmt1) {
                    $stmt1->bind_param("ss", $name, $hashed);
                    if ($stmt1->execute()) {
                        $msg = "<div class='alert alert-success'>注册成功，请登录</div>";
                    } else {
                        $msg = "<div class='alert alert-error'>注册失败: " . htmlspecialchars($stmt1->error) . "</div>";
                    }
                    $stmt1->close();
                } else {
                    $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    }

    /* ---------- 上传 ---------- */
    else if ($action === 'upload') {
        if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)) {
            $uid     = intval($_POST['uid'] ?? 0);
            $field   = $_POST['field'] ?? '';
            $content = $_POST['content'] ?? '';
            $title   = trim($_POST['title'] ?? '');
            $allowed = ['A','B','C','D','E','F','G','H','I','J','K'];

            if ($uid > 0 && in_array($field, $allowed, true)) {

                $has_content = false;
                $cur_value   = '';
                $chk2 = $conn->prepare("SELECT `$field` AS `cur` FROM `cook` WHERE `uid` = ?");
                if ($chk2) {
                    $chk2->bind_param("i", $uid);
                    $chk2->execute();
                    $c2r = $chk2->get_result()->fetch_assoc();
                    $chk2->close();
                    if ($c2r) {
                        $cur_value = (string)$c2r['cur'];
                        if ($cur_value !== '' && strtolower(trim($cur_value)) !== 'none') {
                            $has_content = true;
                        }
                    }
                }

                if ($has_content && (int)$user_info['alc'] !== 2) {
                    $msg = "<div class='alert alert-error'>该字段已被他人填写，普通用户只能新增空白字段，不能覆盖。如需修改请联系管理员。</div>";
                } else {
                    $contributor = (string)$user_info['uid'];

                    if ($title !== '') {
                        $content = $contributor . ' ' . $title . "\n" . $content;
                    } else {
                        $content = $contributor . "\n" . $content;
                    }

                    $stmt = $conn->prepare("UPDATE `cook` SET `$field` = ? WHERE `uid` = ?");
                    if ($stmt) {
                        $stmt->bind_param("si", $content, $uid);
                        if ($stmt->execute()) {
                            $msg = "<div class='alert alert-success'>上传成功</div>";

                            $contributor_uid = (int)$user_info['uid'];

                            $inc = $conn->prepare("UPDATE `user` SET `num` = COALESCE(`num`, 0) + 1 WHERE `uid` = ?");
                            if ($inc) {
                                $inc->bind_param("i", $contributor_uid);
                                $inc->execute();
                                $inc->close();

                                $get_stmt = $conn->prepare("SELECT `num`, `alc` FROM `user` WHERE `uid` = ?");
                                if ($get_stmt) {
                                    $get_stmt->bind_param("i", $contributor_uid);
                                    $get_stmt->execute();
                                    $g = $get_stmt->get_result()->fetch_assoc();
                                    $get_stmt->close();

                                    if ($g) {
                                        $new_num = (int)$g['num'];
                                        $new_alc = (int)$g['alc'];
                                        $new_color = resolve_user_color($new_alc, $new_num);

                                        $col_stmt = $conn->prepare("UPDATE `user` SET `color` = ? WHERE `uid` = ?");
                                        if ($col_stmt) {
                                            $col_stmt->bind_param("si", $new_color, $contributor_uid);
                                            $col_stmt->execute();
                                            $col_stmt->close();
                                        }

                                        $user_info['num']   = $new_num;
                                        $user_info['color'] = $new_color;
                                    }
                                }
                            }

                        } else {
                            $msg = "<div class='alert alert-error'>上传失败: " . htmlspecialchars($stmt->error) . "</div>";
                        }
                        $stmt->close();
                    } else {
                        $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
            } else {
                $msg = "<div class='alert alert-error'>参数错误</div>";
            }
        } else {
            $msg = "<div class='alert alert-error'>无权限上传</div>";
        }
    }

    /* ---------- 新建 cook（仅 alc=2 管理员） ---------- */
    else if ($action === 'create_cook') {
        if ($is_logged_in && (int)$user_info['alc'] === 2) {
            $new_uid  = intval($_POST['new_uid']  ?? 0);
            $new_name = trim($_POST['new_name']   ?? '');

            if ($new_uid <= 0) {
                $msg = "<div class='alert alert-error'>请输入合法的 UID（正整数）</div>";
            } else if ($new_name === '') {
                $msg = "<div class='alert alert-error'>请输入名称</div>";
            } else {
                $chk = $conn->prepare("SELECT `uid` FROM `cook` WHERE `uid` = ? LIMIT 1");
                $exists = false;
                if ($chk) {
                    $chk->bind_param("i", $new_uid);
                    $chk->execute();
                    $chkRes = $chk->get_result();
                    $exists = (bool)$chkRes->fetch_assoc();
                    $chk->close();
                }

                if ($exists) {
                    $msg = "<div class='alert alert-error'>UID " . $new_uid . " 已存在，不能重复新建</div>";
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO `cook`
                           (`uid`, `name`, `A`, `B`, `C`, `D`, `E`, `F`, `G`, `H`, `I`, `J`, `K`, `L`, `M`, `N`, `O`, `P`, `Q`, `R`, `S`, `T`, `U`, `V`, `W`, `X`, `Y`, `Z`)
                         VALUES
                           (?, ?, 'none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none','none')"
                    );
                    if ($stmt) {
                        $stmt->bind_param("is", $new_uid, $new_name);
                        if ($stmt->execute()) {
                            $msg = "<div class='alert alert-success'>新建 cook 成功：UID=" . $new_uid . "</div>";
                        } else {
                            $msg = "<div class='alert alert-error'>新建失败: " . htmlspecialchars($stmt->error) . "</div>";
                        }
                        $stmt->close();
                    } else {
                        $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
            }
        } else {
            $msg = "<div class='alert alert-error'>无权限新建 cook</div>";
        }
    }
}

/* ---------------- 3. 排行榜数据 ---------------- */
$rank_rows  = [];
$rank_error = '';
if ($is_logged_in) {
    $rank_stmt = $conn->prepare(
        "SELECT `uid`, `name`, `calling`, `color`, `alc`, COALESCE(`num`, 0) AS `num`
         FROM `user`
         ORDER BY COALESCE(`num`, 0) DESC, `uid` ASC
         LIMIT 50"
    );
    if ($rank_stmt) {
        $rank_stmt->execute();
        $rank_res = $rank_stmt->get_result();
        while ($rr = $rank_res->fetch_assoc()) {
            $rank_rows[] = $rr;
        }
        $rank_stmt->close();
    } else {
        $rank_error = '排行榜查询失败: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>欢迎</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <style>
        /* ================= 主题变量 ================= */
        :root {
            --bg: #eef2f8;

            /* 玻璃层参数 */
            --glass-bg: rgba(255, 255, 255, 0.55);
            --glass-bg-strong: rgba(255, 255, 255, 0.72);
            --glass-bg-soft: rgba(255, 255, 255, 0.40);
            --glass-border: rgba(255, 255, 255, 0.75);
            --glass-border-soft: rgba(255, 255, 255, 0.55);
            --glass-shadow:
                0 8px 32px rgba(31, 38, 135, 0.10),
                0 2px 8px rgba(31, 38, 135, 0.06);
            --glass-shadow-hover:
                0 12px 40px rgba(31, 38, 135, 0.16),
                0 4px 12px rgba(31, 38, 135, 0.10);

            --text: #262626;
            --text-muted: #8c8c8c;
            --text-soft: #595959;
            --text-faint: #b0b0b0;

            --card-bg: rgba(255, 255, 255, 0.55);
            --card-border: rgba(255, 255, 255, 0.75);
            --card-shadow:
                0 8px 32px rgba(31, 38, 135, 0.10),
                0 2px 8px rgba(31, 38, 135, 0.06);

            --divider: rgba(0, 0, 0, 0.06);
            --divider-strong: rgba(0, 0, 0, 0.08);

            --input-bg: rgba(255, 255, 255, 0.65);
            --input-border: rgba(0, 0, 0, 0.10);

            --hover-bg: rgba(52, 152, 219, 0.10);
            --hover-bg-strong: rgba(52, 152, 219, 0.16);
            --row-hover: rgba(255, 255, 255, 0.45);

            --primary: #3498db;
            --primary-hover: #2980b9;
            --primary-active: #2471a3;

            --code-bg: rgba(255, 255, 255, 0.5);
            --code-bar-bg: rgba(240, 242, 245, 0.65);
            --code-text: #262626;
            --field-head-bg: rgba(255, 255, 255, 0.4);

            --alert-error-bg: rgba(255, 241, 240, 0.72);
            --alert-error-border: rgba(255, 163, 158, 0.75);
            --alert-error-text: #a8071a;
            --alert-success-bg: rgba(246, 255, 237, 0.72);
            --alert-success-border: rgba(183, 235, 143, 0.75);
            --alert-success-text: #135200;

            --rank-me-bg: rgba(52, 152, 219, 0.12);
            --rank-me-bg-hover: rgba(52, 152, 219, 0.18);

            --btn-logout-hover-bg: rgba(255, 245, 245, 0.85);
            --btn-danger: #cf222e;

            --tok-kw: #cf222e;
            --tok-type: #0550ae;
            --tok-str: #0a3069;
            --tok-num: #0550ae;
            --tok-com: #6e7781;
            --tok-pre: #8250df;

            /* 动效时长 */
            --dur-fast: .18s;
            --dur: .32s;
            --dur-slow: .5s;
            --ease: cubic-bezier(.4, 0, .2, 1);
            --ease-spring: cubic-bezier(.34, 1.56, .64, 1);
        }

        /* ================= 深色主题 ================= */
        html[data-theme="dark"] {
            --bg: #101317;

            --glass-bg: rgba(36, 40, 46, 0.55);
            --glass-bg-strong: rgba(36, 40, 46, 0.75);
            --glass-bg-soft: rgba(36, 40, 46, 0.35);
            --glass-border: rgba(255, 255, 255, 0.10);
            --glass-border-soft: rgba(255, 255, 255, 0.06);
            --glass-shadow:
                0 8px 32px rgba(0, 0, 0, 0.45),
                0 2px 8px rgba(0, 0, 0, 0.30);
            --glass-shadow-hover:
                0 12px 40px rgba(0, 0, 0, 0.55),
                0 4px 12px rgba(0, 0, 0, 0.38);

            --text: #e6e6e6;
            --text-muted: #9aa0a6;
            --text-soft: #b8bcc2;
            --text-faint: #6a7078;

            --card-bg: rgba(36, 40, 46, 0.55);
            --card-border: rgba(255, 255, 255, 0.10);
            --card-shadow:
                0 8px 32px rgba(0, 0, 0, 0.45),
                0 2px 8px rgba(0, 0, 0, 0.30);

            --divider: rgba(255, 255, 255, 0.06);
            --divider-strong: rgba(255, 255, 255, 0.10);

            --input-bg: rgba(30, 34, 39, 0.75);
            --input-border: rgba(255, 255, 255, 0.10);

            --hover-bg: rgba(74, 168, 232, 0.14);
            --hover-bg-strong: rgba(74, 168, 232, 0.22);
            --row-hover: rgba(255, 255, 255, 0.04);

            --primary: #4aa8e8;
            --primary-hover: #5cb5ef;
            --primary-active: #3a91cf;

            --code-bg: rgba(30, 34, 39, 0.65);
            --code-bar-bg: rgba(42, 47, 54, 0.65);
            --code-text: #e6e6e6;
            --field-head-bg: rgba(42, 47, 54, 0.55);

            --alert-error-bg: rgba(44, 26, 28, 0.72);
            --alert-error-border: rgba(110, 42, 46, 0.85);
            --alert-error-text: #ff9aa0;
            --alert-success-bg: rgba(22, 40, 26, 0.72);
            --alert-success-border: rgba(46, 94, 58, 0.85);
            --alert-success-text: #86d99e;

            --rank-me-bg: rgba(74, 168, 232, 0.14);
            --rank-me-bg-hover: rgba(74, 168, 232, 0.22);

            --btn-logout-hover-bg: rgba(44, 26, 28, 0.85);
            --btn-danger: #ff7b72;

            --tok-kw: #ff7b72;
            --tok-type: #79c0ff;
            --tok-str: #a5d6ff;
            --tok-num: #79c0ff;
            --tok-com: #8b949e;
            --tok-pre: #d2a8ff;
        }

        /* ================= 全局 ================= */
        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            margin: 0;
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC",
                         "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            font-size: 14px;
            line-height: 1.6;
            background: var(--bg);
            background-attachment: fixed;
            background-image:
                radial-gradient(1200px 800px at 8% 0%,   rgba(120, 180, 255, 0.42), transparent 55%),
                radial-gradient(1000px 700px at 92% 12%, rgba(255, 150, 200, 0.32), transparent 55%),
                radial-gradient(900px 700px at 25% 95%,  rgba(150, 230, 200, 0.32), transparent 55%),
                radial-gradient(1000px 800px at 95% 92%, rgba(200, 170, 255, 0.32), transparent 55%);
            transition: background-color var(--dur) var(--ease), color var(--dur) var(--ease);
            position: relative;
            overflow-x: hidden;
        }
        html[data-theme="dark"] body {
            background-image:
                radial-gradient(1200px 800px at 8% 0%,   rgba(60, 100, 200, 0.32), transparent 55%),
                radial-gradient(1000px 700px at 92% 12%, rgba(180, 60, 140, 0.22), transparent 55%),
                radial-gradient(900px 700px at 25% 95%,  rgba(60, 180, 150, 0.22), transparent 55%),
                radial-gradient(1000px 800px at 95% 92%, rgba(120, 90, 220, 0.28), transparent 55%);
        }

        a { color: var(--primary); text-decoration: none; transition: color var(--dur-fast) var(--ease); }
        a:hover { color: var(--primary-hover); text-decoration: underline; }

        /* ================= 顶部导航栏（玻璃） ================= */
        .navbar {
            background: var(--glass-bg-strong);
            backdrop-filter: saturate(180%) blur(22px);
            -webkit-backdrop-filter: saturate(180%) blur(22px);
            border-bottom: 1px solid var(--glass-border-soft);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(31,38,135,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
            transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease);
        }
        .navbar-inner {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-brand {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            letter-spacing: .5px;
            transition: transform var(--dur-fast) var(--ease);
        }
        .navbar-brand:hover { transform: translateY(-1px); }
        .navbar-user {
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-user strong { font-weight: 600; }

        .navbar-user form { display: inline; margin: 0; }

        .navbar-user .btn-logout,
        .navbar-user .btn-theme {
            font-size: 12px;
            line-height: 1;
            padding: 5px 11px;
            color: var(--text-soft);
            background: var(--glass-bg-soft);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border-soft);
            border-radius: 8px;
            cursor: pointer;
            transition:
                color var(--dur-fast) var(--ease),
                border-color var(--dur-fast) var(--ease),
                background-color var(--dur-fast) var(--ease),
                transform var(--dur-fast) var(--ease-spring),
                box-shadow var(--dur-fast) var(--ease);
        }
        .navbar-user .btn-logout:hover {
            color: var(--btn-danger);
            border-color: var(--btn-danger);
            background: var(--btn-logout-hover-bg);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(207, 34, 46, 0.15);
        }
        .navbar-user .btn-theme {
            font-size: 14px;
            padding: 4px 9px;
            min-width: 34px;
            text-align: center;
        }
        .navbar-user .btn-theme:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--hover-bg);
            transform: translateY(-1px) scale(1.05);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.18);
        }
        .navbar-user .btn-theme:active {
            transform: translateY(0) scale(0.96);
        }

        /* ================= 页面容器 ================= */
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 24px 20px 60px;
            position: relative;
            z-index: 1;
        }

        /* ================= 卡片（玻璃） ================= */
        .card {
            background: var(--card-bg);
            backdrop-filter: saturate(180%) blur(18px);
            -webkit-backdrop-filter: saturate(180%) blur(18px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 22px 26px;
            margin-bottom: 18px;
            box-shadow: var(--card-shadow);
            transition:
                background-color var(--dur) var(--ease),
                border-color var(--dur) var(--ease),
                box-shadow var(--dur) var(--ease),
                transform var(--dur) var(--ease);
            animation: cardIn var(--dur-slow) var(--ease-spring) both;
        }
        .card:hover {
            box-shadow: var(--glass-shadow-hover);
            transform: translateY(-2px);
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text);
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--divider);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-title .sub {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 400;
        }

        /* ================= 表单（玻璃） ================= */
        label {
            display: inline-block;
            color: var(--text-soft);
            font-size: 13px;
            margin-bottom: 4px;
        }
        input[type="text"],
        input[type="password"],
        input[type="number"],
        textarea,
        select {
            font-family: inherit;
            font-size: 13px;
            color: var(--text);
            background: var(--input-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--input-border);
            border-radius: 10px;
            padding: 8px 12px;
            outline: none;
            transition:
                border-color var(--dur-fast) var(--ease),
                box-shadow var(--dur-fast) var(--ease),
                background-color var(--dur) var(--ease),
                color var(--dur) var(--ease),
                transform var(--dur-fast) var(--ease);
            vertical-align: middle;
        }
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="number"]:focus,
        textarea:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow:
                0 0 0 3px rgba(52,152,219,0.18),
                0 4px 14px rgba(52,152,219,0.12);
            transform: translateY(-1px);
        }
        textarea {
            width: 100%;
            font-family: Consolas, Monaco, "Courier New", monospace;
            resize: vertical;
            line-height: 1.65;
        }
        select { padding: 7px 10px; }

        /* ================= 按钮（玻璃 + 弹簧） ================= */
        button, .btn {
            font-family: inherit;
            font-size: 13px;
            line-height: 1;
            padding: 8px 18px;
            color: #ffffff;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-hover) 100%);
            border: 1px solid var(--primary);
            border-radius: 10px;
            cursor: pointer;
            transition:
                background-color var(--dur-fast) var(--ease),
                border-color var(--dur-fast) var(--ease),
                transform var(--dur-fast) var(--ease-spring),
                box-shadow var(--dur-fast) var(--ease);
            display: inline-block;
            box-shadow:
                0 2px 8px rgba(52, 152, 219, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }
        button:hover, .btn:hover {
            background: linear-gradient(180deg, var(--primary-hover) 0%, var(--primary-active) 100%);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow:
                0 6px 18px rgba(52, 152, 219, 0.32),
                inset 0 1px 0 rgba(255, 255, 255, 0.30);
        }
        button:active, .btn:active {
            transform: translateY(0) scale(0.97);
            box-shadow:
                0 2px 6px rgba(52, 152, 219, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.20);
        }

        button.btn-ghost {
            color: var(--text-soft);
            background: var(--glass-bg-soft);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-color: var(--glass-border-soft);
            box-shadow:
                0 2px 8px rgba(31, 38, 135, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.35);
        }
        html[data-theme="dark"] button.btn-ghost {
            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.25),
                inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }
        button.btn-ghost:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--hover-bg);
            transform: translateY(-1px);
            box-shadow:
                0 6px 18px rgba(52, 152, 219, 0.18),
                inset 0 1px 0 rgba(255, 255, 255, 0.40);
        }
        button.btn-ghost:active {
            background: var(--hover-bg-strong);
            transform: translateY(0) scale(0.97);
        }

        button.btn-danger {
            color: var(--btn-danger);
            background: var(--glass-bg-soft);
            border-color: var(--btn-danger);
            box-shadow: 0 2px 8px rgba(207, 34, 46, 0.08);
        }
        button.btn-danger:hover {
            background: var(--btn-logout-hover-bg);
            transform: translateY(-1px);
        }

        /* ================= 提示条（玻璃） ================= */
        .alert {
            border-radius: 12px;
            padding: 12px 16px;
            margin: 14px 0;
            font-size: 13px;
            border: 1px solid transparent;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            animation: alertIn var(--dur) var(--ease-spring) both;
        }
        @keyframes alertIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            color: var(--alert-error-text);
            background: var(--alert-error-bg);
            border-color: var(--alert-error-border);
            box-shadow: 0 4px 14px rgba(207, 34, 46, 0.10);
        }
        .alert-success {
            color: var(--alert-success-text);
            background: var(--alert-success-bg);
            border-color: var(--alert-success-border);
            box-shadow: 0 4px 14px rgba(26, 127, 55, 0.10);
        }

        /* ================= 排行榜 ================= */
        .rank-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }
        .rank-table th,
        .rank-table td {
            padding: 10px 14px;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid var(--divider);
            transition: background-color var(--dur-fast) var(--ease);
        }
        .rank-table thead th {
            background: var(--glass-bg-soft);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            color: var(--text-muted);
            font-weight: 500;
            font-size: 12px;
            border-bottom: 1px solid var(--card-border);
            user-select: none;
        }
        .rank-table thead th:first-child { border-top-left-radius: 10px; }
        .rank-table thead th:last-child  { border-top-right-radius: 10px; }
        .rank-table tbody tr { transition: background-color var(--dur-fast) var(--ease); }
        .rank-table tbody tr:hover { background: var(--row-hover); }
        .rank-table tbody tr:last-child td { border-bottom: none; }

        .rank-no {
            width: 60px;
            font-family: Consolas, Monaco, "Courier New", monospace;
            color: var(--text-muted);
            text-align: center;
        }
        .rank-no.top1 { color: #d4a017; font-weight: 700; font-size: 15px; }
        .rank-no.top2 { color: #8a8f98; font-weight: 700; font-size: 15px; }
        .rank-no.top3 { color: #b06e3f; font-weight: 700; font-size: 15px; }
        .rank-num {
            width: 90px;
            text-align: right;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-weight: 600;
            color: var(--primary);
        }
        .rank-me { background: var(--rank-me-bg) !important; }
        .rank-me:hover { background: var(--rank-me-bg-hover) !important; }

        .rank-tag {
            font-size: 11px;
            line-height: 1.4;
            color: var(--primary);
            border: 1px solid var(--primary);
            border-radius: 6px;
            padding: 1px 6px;
            margin-left: 6px;
            background: rgba(52, 152, 219, 0.08);
        }
        .rank-admin-tag {
            font-size: 11px;
            line-height: 1.4;
            color: #ffffff;
            background: linear-gradient(180deg, #9b59b6 0%, #8e44ad 100%);
            border-radius: 6px;
            padding: 1px 6px;
            margin-left: 6px;
            box-shadow: 0 2px 6px rgba(142, 68, 173, 0.25);
        }
        .rank-uid {
            font-size: 12px;
            color: var(--text-faint);
            margin-left: 8px;
            font-family: Consolas, Monaco, "Courier New", monospace;
        }

        /* ================= Cook 列表（玻璃） ================= */
        .cook-item {
            background: var(--glass-bg);
            backdrop-filter: saturate(160%) blur(16px);
            -webkit-backdrop-filter: saturate(160%) blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            margin-bottom: 14px;
            overflow: hidden;
            box-shadow: var(--glass-shadow);
            transition:
                background-color var(--dur) var(--ease),
                border-color var(--dur) var(--ease),
                box-shadow var(--dur) var(--ease),
                transform var(--dur) var(--ease);
            animation: cardIn var(--dur-slow) var(--ease-spring) both;
        }
        .cook-item:hover {
            box-shadow: var(--glass-shadow-hover);
            transform: translateY(-2px);
        }
        .cook-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 18px;
            background: var(--glass-bg-soft);
            border-bottom: 1px solid var(--divider);
            transition: background-color var(--dur) var(--ease);
        }
        .cook-head-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }
        .cook-head-title .idx {
            display: inline-block;
            min-width: 28px;
            color: var(--primary);
            font-family: Consolas, Monaco, "Courier New", monospace;
            margin-right: 6px;
        }
        .cook-body {
            padding: 12px 18px 6px 18px;
            max-height: 12000px;
            overflow: hidden;
            transition: max-height var(--dur-slow) var(--ease), opacity var(--dur) var(--ease), padding var(--dur) var(--ease);
            opacity: 1;
        }
        .cook-body.collapsed {
            max-height: 0 !important;
            padding-top: 0;
            padding-bottom: 0;
            opacity: 0;
        }

        /* ================= 字段折叠（玻璃 + 动效） ================= */
        .field-block {
            border: 1px solid var(--glass-border-soft);
            border-radius: 12px;
            margin-bottom: 10px;
            overflow: hidden;
            background: var(--glass-bg-soft);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            transition:
                background-color var(--dur) var(--ease),
                border-color var(--dur) var(--ease),
                box-shadow var(--dur) var(--ease);
        }
        .field-block:hover {
            box-shadow: 0 4px 14px rgba(31, 38, 135, 0.08);
        }
        html[data-theme="dark"] .field-block:hover {
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.28);
        }
        .field-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 14px;
            background: var(--field-head-bg);
            border-bottom: 1px solid transparent;
            transition:
                background-color var(--dur) var(--ease),
                border-color var(--dur) var(--ease);
        }
        .field-block.expanded .field-head {
            border-bottom-color: var(--divider-strong);
        }
        .field-title {
            display: inline-flex;
            align-items: baseline;
            gap: 10px;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-weight: 700;
            font-size: 14px;
            color: var(--primary);
            letter-spacing: .5px;
            min-width: 0;
        }
        .field-title-text {
            font-family: -apple-system, "PingFang SC", "Microsoft YaHei", sans-serif;
            font-weight: 400;
            font-size: 13px;
            color: var(--text-soft);
            letter-spacing: 0;
            max-width: 620px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .field-toggle {
            flex-shrink: 0;
            font-size: 12px;
            padding: 5px 14px;
            color: var(--text-soft);
            background: var(--glass-bg-soft);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--glass-border-soft);
            border-radius: 8px;
            transition:
                color var(--dur-fast) var(--ease),
                border-color var(--dur-fast) var(--ease),
                background-color var(--dur-fast) var(--ease),
                transform var(--dur-fast) var(--ease-spring),
                box-shadow var(--dur-fast) var(--ease);
        }
        .field-toggle:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--hover-bg);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
        }
        .field-toggle:active { transform: translateY(0) scale(0.95); }

        /* ★ 展开/折叠动画（用 max-height + opacity 实现平滑过渡） */
        .field-body {
            padding: 0 14px;
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition:
                max-height var(--dur-slow) var(--ease),
                opacity var(--dur) var(--ease),
                padding var(--dur) var(--ease);
        }
        .field-body.show {
            max-height: 12000px;
            opacity: 1;
            padding: 12px 14px 6px 14px;
        }

        /* ================= 代码块 + 工具条 ================= */
        .code-block {
            position: relative;
            margin: 4px 0 12px 0;
            background: var(--code-bg);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--glass-border-soft);
            border-radius: 12px;
            overflow: hidden;
            transition:
                background-color var(--dur) var(--ease),
                border-color var(--dur) var(--ease),
                box-shadow var(--dur) var(--ease);
        }
        .code-block:hover {
            box-shadow: 0 6px 20px rgba(31, 38, 135, 0.10);
        }
        html[data-theme="dark"] .code-block:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
        }
        .code-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px 6px 14px;
            background: var(--code-bar-bg);
            border-bottom: 1px solid var(--divider-strong);
            font-size: 12px;
            color: var(--text-muted);
        }
        .code-bar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .code-lang {
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-weight: 600;
            color: var(--text-soft);
            letter-spacing: .5px;
        }
        .code-contributor {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            line-height: 1.4;
            color: var(--text-soft);
            background: var(--glass-bg-soft);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--glass-border-soft);
            border-radius: 8px;
            padding: 2px 9px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 480px;
        }
        .copy-btn {
            font-size: 12px;
            padding: 5px 12px;
            color: var(--text-soft);
            background: var(--glass-bg-soft);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid var(--glass-border-soft);
            border-radius: 8px;
            flex-shrink: 0;
            box-shadow: none;
            transition:
                color var(--dur-fast) var(--ease),
                border-color var(--dur-fast) var(--ease),
                background-color var(--dur-fast) var(--ease),
                transform var(--dur-fast) var(--ease-spring);
        }
        .copy-btn:hover {
            color: var(--primary);
            border-color: var(--primary);
            background: var(--hover-bg);
            transform: translateY(-1px);
            box-shadow: none;
        }
        .copy-btn:active { transform: translateY(0) scale(0.95); }
        .copy-btn.ok   { color: var(--alert-success-text); border-color: var(--alert-success-border); background: var(--alert-success-bg); }
        .copy-btn.fail { color: var(--alert-error-text); border-color: var(--alert-error-border); background: var(--alert-error-bg); }

        pre { margin: 0; }
        code.language-cpp {
            display: block;
            background: transparent;
            color: var(--code-text);
            border: none;
            padding: 14px 18px;
            margin: 0;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-size: 13px;
            line-height: 1.65;
            overflow-x: auto;
            white-space: pre;
            tab-size: 4;
        }
        code.language-cpp .tok-kw   { color: var(--tok-kw); font-weight: 600; }
        code.language-cpp .tok-type { color: var(--tok-type); }
        code.language-cpp .tok-str  { color: var(--tok-str); }
        code.language-cpp .tok-num  { color: var(--tok-num); }
        code.language-cpp .tok-com  { color: var(--tok-com); font-style: italic; }
        code.language-cpp .tok-pre  { color: var(--tok-pre); }

        /* ================= 工具类 ================= */
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .muted { color: var(--text-muted); font-size: 12px; }

        /* ================= 滚动条（iOS 风格） ================= */
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.15);
            border-radius: 999px;
            border: 2px solid transparent;
            background-clip: content-box;
        }
        html[data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            background-clip: content-box;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.25);
            background-clip: content-box;
        }
        html[data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.25);
            background-clip: content-box;
        }

        /* ================= 无障碍：尊重系统减弱动效偏好 ================= */
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
        }
    </style>

    <!-- ★ 在 <body> 之前应用已保存的主题，避免闪烁 -->
    <script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || t === 'light') {
                document.documentElement.setAttribute('data-theme', t);
            }
        } catch (e) {}
    })();
    </script>
</head>
<body>

<!-- ============ 顶部导航 ============ -->
<div class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">Cook&nbsp;Panel</div>
    <div class="navbar-user">
      <?php if ($is_logged_in): ?>
        <span>已登录：</span>
        <?php $my_display_color = resolve_user_color($user_info['alc'] ?? 0, $user_info['num'] ?? 0); ?>
        <strong style="color: <?php echo htmlspecialchars($my_display_color); ?>">
          <?php echo htmlspecialchars($user_info['name']); ?>
        </strong>
        <span style="color: var(--text-faint);">UID <?php echo (int)$user_info['uid']; ?></span>

        <!-- ★ 主题切换 -->
        <button type="button" class="btn-theme" id="theme-toggle-btn" onclick="toggleTheme();" title="切换浅色 / 深色模式">
          <span id="theme-icon">🌙</span>
        </button>

        <form method="POST" onsubmit="return confirm('确认退出登录吗？');">
          <input type="hidden" name="action" value="logout">
          <button type="submit" class="btn-logout">退出登录</button>
        </form>
      <?php else: ?>
        <span>未登录</span>
        <button type="button" class="btn-theme" id="theme-toggle-btn" onclick="toggleTheme();" title="切换浅色 / 深色模式">
          <span id="theme-icon">🌙</span>
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container">

  <!-- ============ 消息 ============ -->
  <?php echo $msg; ?>

  <!-- ============ 登录 / 注册 ============ -->
  <?php if (!$is_logged_in): ?>
    <div class="card" style="max-width: 420px; margin: 40px auto;">
      <div class="card-title"><?php echo '登录 / 注册'; ?></div>

      <form method="POST" id="l">
          <input type="hidden" name="action" value="login">
          <div style="margin-bottom:10px;">
              <label>用户名</label>
              <input type="text" name="name" required style="width:100%;">
          </div>
          <div style="margin-bottom:14px;">
              <label>密&emsp;码</label>
              <input type="password" name="password" required style="width:100%;">
          </div>
          <div class="row">
              <button type="submit">登录</button>
              <button type="button" class="btn-ghost" onclick="ToL();">转到注册</button>
          </div>
      </form>

      <form method="POST" id="r" style="display: none">
          <input type="hidden" name="action" value="reslogin">
          <div style="margin-bottom:10px;">
              <label>用户名</label>
              <input type="text" name="name" required style="width:100%;">
          </div>
          <div style="margin-bottom:10px;">
              <label>密&emsp;码</label>
              <input type="password" name="password" required style="width:100%;">
          </div>
          <div style="margin-bottom:14px;">
              <label>确认密码</label>
              <input type="password" name="passwordag" required style="width:100%;">
          </div>
          <div class="row">
              <button type="submit">注册</button>
              <button type="button" class="btn-ghost" onclick="ToR();">转到登录</button>
          </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- ============ 用户信息卡 ============ -->
  <?php if ($is_logged_in): ?>
    <div class="card">
      <div class="card-title">
        <span>我的信息</span>
      </div>
      <?php $my_display_color = resolve_user_color($user_info['alc'] ?? 0, $user_info['num'] ?? 0); ?>
      <div class="row" style="gap: 20px;">
        <div>
          <div class="muted">UID</div>
          <div style="font-family: Consolas, Monaco, monospace;"><?php echo (int)$user_info['uid']; ?></div>
        </div>
        <div>
          <div class="muted">用户名</div>
          <div>
            <strong style="color: <?php echo htmlspecialchars($my_display_color); ?>">
              <?php echo htmlspecialchars($user_info['name']); ?>
            </strong>
            <?php if ($user_info['calling'] !== 'none' && $user_info['calling'] !== ''): ?>
              <strong style="font-size: 80%; border-radius: 6px; color: white; background-color: <?php echo htmlspecialchars($my_display_color); ?>">&ensp;<?php echo htmlspecialchars($user_info['calling']); ?>&ensp;</strong>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <div class="muted">权限</div>
          <div>
            <?php
              if ($user_info['alc'] == 2) echo "管理员";
              else if ($user_info['alc'] == 1) echo "普通用户";
              else echo "受限";
            ?>
          </div>
        </div>
        <div>
          <div class="muted">贡献数</div>
          <div style="font-family: Consolas, Monaco, monospace; font-weight: 600; color: var(--primary);">
            <?php echo (int)($user_info['num'] ?? 0); ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- ============ 修改密码 ============ -->
  <?php if ($is_logged_in): ?>
  <div class="card">
    <div class="card-title">
      <span>修改密码</span>
      <span class="sub">修改成功后其他设备会被强制下线</span>
    </div>
    <form method="POST" onsubmit="return confirm('确认修改密码吗？');">
      <input type="hidden" name="action" value="change_password">
      <div class="row" style="gap: 20px; margin-bottom: 12px;">
        <div style="flex:1; min-width:220px;">
          <label>旧密码</label><br>
          <input type="password" name="old_password" required style="width:100%;">
        </div>
        <div style="flex:1; min-width:220px;">
          <label>新密码（至少 6 位）</label><br>
          <input type="password" name="new_password" required minlength="6" style="width:100%;">
        </div>
        <div style="flex:1; min-width:220px;">
          <label>确认新密码</label><br>
          <input type="password" name="new_password2" required minlength="6" style="width:100%;">
        </div>
      </div>
      <button type="submit">修改密码</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- ============ Cook 列表 ============ -->
  <?php
      if ($is_logged_in) {
        if ($user_info['alc'] == 1 || $user_info['alc'] == 2) {
          if ($user_info['alc'] == 2) {
              echo "<div class='card'><a href='http://192.168.21.229/phpMyAdmin4.8.5/'>→ 管理员界面（phpMyAdmin）</a></div>";
          }

          $user_cache = [];

          $stmt1 = $conn->prepare("SELECT * FROM `cook`");
          if ($stmt1) {
              $stmt1->bind_param("i", $i1);
              $stmt1->execute();
              $result1 = $stmt1->get_result();
              for ($i1 = 1; $i1 <= $result1->num_rows; $i1++) {
            $stmt = $conn->prepare("SELECT * FROM `cook` WHERE `uid` = ?");
            if ($stmt) {
              $stmt->bind_param("i", $i1);
              $stmt->execute();
              $result = $stmt->get_result();

              if ($row = $result->fetch_assoc()) {
                echo "<div class='cook-item'>";
                echo "  <div class='cook-head'>";
                echo "    <div class='cook-head-title'><span class='idx'>#" . $i1 . "</span>" . htmlspecialchars($row['name']) . "</div>";
                echo "    <button type='button' class='btn-ghost' id='but$i1' onclick='ks($i1);'>显示</button>";
                echo "  </div>";
                echo "  <div class='cook-body collapsed' id='$i1'>";

                foreach (range('A', 'Z') as $i) {
                    if ($row[$i] == "none" or $row[$i] == "") continue;

                    $raw = $row[$i];
                    $nl  = strpos($raw, "\n");

                    if ($nl === false) {
                        $first = trim($raw);
                        $code  = '';
                    } else {
                        $first = trim(substr($raw, 0, $nl));
                        $code  = substr($raw, $nl + 1);
                    }

                    $contributor = null;
                    $field_title = '';

                    if ($first !== '') {
                        $sp = strpos($first, ' ');
                        if ($sp === false) {
                            if (ctype_digit($first)) {
                                $contributor = $first;
                            } else {
                                $code = $raw;
                            }
                        } else {
                            $uid_part   = substr($first, 0, $sp);
                            $title_part = trim(substr($first, $sp + 1));
                            if (ctype_digit($uid_part)) {
                                $contributor = $uid_part;
                                $field_title = $title_part;
                            } else {
                                $code = $raw;
                            }
                        }
                    }

                    $field_key = $i1 . '_' . $i;

                    echo "<div class='field-block' id='fb_" . $field_key . "'>";
                    echo "  <div class='field-head'>";
                    echo "    <span class='field-title'>" . $i;
                    if ($field_title !== '') {
                        echo "<span class='field-title-text'>" . htmlspecialchars($field_title) . "</span>";
                    }
                    echo "    </span>";
                    echo "    <button type='button' class='field-toggle' id='ft_but_" . $field_key . "' onclick='toggleField(\"" . $field_key . "\");'>展开</button>";
                    echo "  </div>";
                    echo "  <div class='field-body' id='ft_body_" . $field_key . "'>";

                    if ($contributor !== null) {
                        $cu = null;
                        if (array_key_exists($contributor, $user_cache)) {
                            $cu = $user_cache[$contributor];
                        } else {
                            $cu_stmt = $conn->prepare("SELECT `name`, `calling`, `color`, `alc`, `num` FROM `user` WHERE `uid` = ?");
                            if ($cu_stmt) {
                                $cu_uid = intval($contributor);
                                $cu_stmt->bind_param("i", $cu_uid);
                                $cu_stmt->execute();
                                $cu_res = $cu_stmt->get_result();
                                $cu = $cu_res->fetch_assoc();
                                $cu_stmt->close();
                            }
                            $user_cache[$contributor] = $cu;
                        }

                        if ($cu) {
                            $cu_color = resolve_user_color($cu['alc'] ?? 0, $cu['num'] ?? 0);
                            $ch = "贡献者：<strong style='color: " . htmlspecialchars($cu_color) . "'>"
                                . htmlspecialchars($cu['name']) . "</strong>";
                            if ($cu['calling'] !== 'none' && $cu['calling'] !== '') {
                                $ch .= "&thinsp;<strong style='font-size: 80%; border-radius: 4px; color: white; background-color: "
                                     . htmlspecialchars($cu_color) . "'>&ensp;"
                                     . htmlspecialchars($cu['calling']) . "&ensp;</strong>";
                            }
                        } else {
                            $ch = "UID: " . htmlspecialchars($contributor);
                        }

                        echo "<span class='code-contributor-source' style='display:none'>" . $ch . "</span>";
                    }

                    echo "<pre><code class=\"language-cpp\">";
                    echo htmlspecialchars($code);
                    echo "</code></pre>";

                    echo "  </div>";
                    echo "</div>";
                }

                echo "  </div>";
                echo "</div>";
              } else {
                  echo "<div class='card'><span class='muted'>#" . $i1 . " 暂无数据</span></div>";
              }
              $stmt->close();
            } else {
              echo "<div class='card'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
            }
          }
          }

        } else {
          echo "<div class='card'>请联系管理员提权</div>";
          echo "<div class='card'>你无权限查看</div>";
        }
      } else {
          echo "<div class='card' style='text-align:center; color: var(--text-muted);'>请先登录以查看内容</div>";
      }
  ?>

  <!-- ============ 贡献排行榜 ============ -->
  <?php if ($is_logged_in): ?>
  <div class="card" id="rank">
    <div class="card-title">
      <span>贡献排行榜</span>
      <span class="sub">按 num 降序 · 最多 50 名</span>
    </div>

    <?php if ($rank_error !== ''): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($rank_error); ?></div>
    <?php elseif (empty($rank_rows)): ?>
      <p class="muted">暂无排行数据。</p>
    <?php else: ?>
      <table class="rank-table">
        <thead>
          <tr>
            <th style="text-align:center;">排名</th>
            <th>用户</th>
            <th style="text-align:right;">贡献数</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $rank_no = 0;
          foreach ($rank_rows as $rr):
            $rank_no++;
            $is_me    = ((int)$rr['uid'] === (int)$user_info['uid']);
            $no_class = '';
            if ($rank_no === 1)      $no_class = ' top1';
            else if ($rank_no === 2) $no_class = ' top2';
            else if ($rank_no === 3) $no_class = ' top3';
            $r_color    = resolve_user_color($rr['alc'] ?? 0, $rr['num'] ?? 0);
            $r_calling  = (string)($rr['calling'] ?? '');
            $r_is_admin = ((int)($rr['alc'] ?? 0) === 2);
        ?>
          <tr class="<?php echo $is_me ? 'rank-me' : ''; ?>">
            <td class="rank-no<?php echo $no_class; ?>"><?php echo $rank_no; ?></td>
            <td>
              <strong style="color: <?php echo htmlspecialchars($r_color); ?>">
                <?php echo htmlspecialchars((string)$rr['name']); ?>
              </strong>
              <?php if ($r_calling !== 'none' && $r_calling !== ''): ?>
                <strong style="font-size: 80%; border-radius: 4px; color: white; background-color: <?php echo htmlspecialchars($r_color); ?>">&ensp;<?php echo htmlspecialchars($r_calling); ?>&ensp;</strong>
              <?php endif; ?>
              <?php if ($r_is_admin): ?><span class="rank-admin-tag">管理员</span><?php endif; ?>
              <?php if ($is_me): ?><span class="rank-tag">我</span><?php endif; ?>
              <span class="rank-uid">UID <?php echo (int)$rr['uid']; ?></span>
            </td>
            <td class="rank-num"><?php echo (int)$rr['num']; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ============ 上传 ============ -->
  <?php if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)): ?>
  <div class="card">
    <div class="card-title">
      <span>上传 / 修改 cook 内容</span>
      <span class="sub">
        <?php if ((int)$user_info['alc'] === 2): ?>
          管理员：可覆盖任意内容
        <?php else: ?>
          普通用户：只能填充空白字段，不能覆盖
        <?php endif; ?>
      </span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="upload">
      <div class="row" style="gap: 20px; margin-bottom: 12px;">
        <div>
          <label>目标 UID</label><br>
          <input type="number" name="uid" min="1" required style="width:120px;">
        </div>
        <div>
          <label>字段</label><br>
          <select name="field">
              <?php foreach (['A','B','C','D','E','F','G','H','I','J','K'] as $f): ?>
                <option value="<?php echo $f; ?>"><?php echo $f; ?></option>
              <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="margin-bottom: 12px;">
        <label>标题（可空，显示在题号后面）</label><br>
        <input type="text" name="title" maxlength="100" style="width: 100%;" placeholder="例如：两数之和">
      </div>
      <div style="margin-bottom: 12px;">
        <label>内容</label>
        <textarea name="content" rows="8" placeholder="在此粘贴代码..."></textarea>
      </div>
      <button type="submit">上传</button>
      <?php if ((int)$user_info['alc'] !== 2): ?>
        <span class="muted" style="margin-left:10px;">提示：若目标字段已被他人填写，上传会被拒绝。</span>
      <?php endif; ?>
    </form>
  </div>
  <?php endif; ?>

  <!-- ============ 新建 cook ============ -->
  <?php if ($is_logged_in && (int)$user_info['alc'] === 2): ?>
  <div class="card">
    <div class="card-title">
      <span>新建 cook</span>
      <span class="sub" style="color:#8e44ad;">仅管理员</span>
    </div>
    <form method="POST" onsubmit="return confirm('确认新建该 cook 吗？');">
      <input type="hidden" name="action" value="create_cook">
      <div class="row" style="gap: 20px; margin-bottom: 12px;">
        <div>
          <label>新 UID</label><br>
          <input type="number" name="new_uid" min="1" required style="width:120px;">
        </div>
        <div style="flex:1; min-width:260px;">
          <label>名称</label><br>
          <input type="text" name="new_name" maxlength="100" required style="width:100%;">
        </div>
      </div>
      <button type="submit">新建</button>
    </form>
  </div>
  <?php endif; ?>

  <!-- ============ New World ============ -->
  <?php if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)): ?>
    <div style="text-align:center; margin: 20px 0;">
      <button type="button" class="btn-ghost" onclick="NewWorld();">New World?</button>
    </div>
  <?php endif; ?>

</div>

<script>

/* =========================================================
   ★ 主题切换
   ========================================================= */
function applyThemeIcon() {
  var icon = document.getElementById('theme-icon');
  if (!icon) return;
  var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  icon.textContent = isDark ? '☀️' : '🌙';
  var btn = document.getElementById('theme-toggle-btn');
  if (btn) btn.title = isDark ? '切换到浅色模式' : '切换到深色模式';
}

function toggleTheme() {
  var html = document.documentElement;
  var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

  /* 切换时短暂添加一个 class，让背景色过渡更自然 */
  document.body.style.transition = 'background-color .3s cubic-bezier(.4,0,.2,1), color .3s cubic-bezier(.4,0,.2,1)';
  html.setAttribute('data-theme', next);
  try { localStorage.setItem('theme', next); } catch (e) {}
  applyThemeIcon();
}

document.addEventListener('DOMContentLoaded', applyThemeIcon);

/* ========================================================= */

function NewWorld() {
  var url = 'url("https://cdn.luogu.com.cn/upload/image_hosting/uds71m1q.png")';
  [document.documentElement, document.body].forEach(function (el) {
    el.style.backgroundImage      = url;
    el.style.backgroundSize       = 'cover';
    el.style.backgroundPosition   = 'center center';
    el.style.backgroundRepeat     = 'no-repeat';
    el.style.backgroundAttachment = 'fixed';
  });
}

/* 字段折叠：使用 max-height 过渡，展开/折叠更平滑 */
function toggleField(key) {
  var body = document.getElementById('ft_body_' + key);
  var btn  = document.getElementById('ft_but_'  + key);
  var box  = document.getElementById('fb_'      + key);
  if (!body || !btn) return;

  var hidden = !body.classList.contains('show');
  if (hidden) {
    body.classList.add('show');
    btn.textContent = '折叠';
    if (box) box.classList.add('expanded');
  } else {
    body.classList.remove('show');
    btn.textContent = '展开';
    if (box) box.classList.remove('expanded');
  }
}

(function () {
  var KEYWORDS = new Set([
    'alignas','alignof','asm','auto','bool','break','case','catch','char','char16_t','char32_t',
    'class','const','constexpr','const_cast','continue','decltype','default','delete','do','double',
    'dynamic_cast','else','enum','explicit','export','extern','false','float','for','friend','goto',
    'if','inline','int','long','mutable','namespace','new','noexcept','nullptr','operator','private',
    'protected','public','register','reinterpret_cast','return','short','signed','sizeof','static',
    'static_assert','static_cast','struct','switch','template','this','throw','true','try','typedef',
    'typeid','typename','union','unsigned','using','virtual','void','volatile','wchar_t','while',
    'and','or','not','xor','bitand','bitor','compl','and_eq','or_eq','xor_eq','not_eq',
    'override','final','constinit','consteval','concept','requires'
  ]);
  var TYPES = new Set([
    'size_t','string','wstring','vector','map','set','unordered_map','unordered_set','pair','tuple',
    'array','deque','list','queue','stack','priority_queue','iostream','fstream','sstream','istream',
    'ostream','cin','cout','cerr','clog','endl','std','printf','scanf','malloc','free','memcpy',
    'memset','INT_MAX','INT_MIN','LLONG_MAX','ULLONG_MAX','NULL'
  ]);

  function esc(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  function highlight(src) {
    var re = /(\/\/[^\n]*|\/\*[\s\S]*?\*\/)|("(?:\\.|[^"\\\n])*"|'(?:\\.|[^'\\\n])*')|(^[ \t]*#[^\n]*)|(\b(?:0[xX][0-9a-fA-F]+|\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)[uUlLfF]*\b)|(\b[A-Za-z_]\w*\b)/gm;
    var out = '', last = 0, m;
    while ((m = re.exec(src)) !== null) {
      out += esc(src.slice(last, m.index));
      var cm = m[1], str = m[2], pre = m[3], num = m[4], id = m[5];
      if (cm)       out += '<span class="tok-com">'  + esc(cm)  + '</span>';
      else if (str) out += '<span class="tok-str">'  + esc(str) + '</span>';
      else if (pre) out += '<span class="tok-pre">'  + esc(pre) + '</span>';
      else if (num) out += '<span class="tok-num">'  + esc(num) + '</span>';
      else if (id) {
        if (KEYWORDS.has(id))   out += '<span class="tok-kw">'   + id + '</span>';
        else if (TYPES.has(id)) out += '<span class="tok-type">' + id + '</span>';
        else                    out += esc(id);
      } else {
        out += esc(m[0]);
      }
      last = m.index + m[0].length;
    }
    out += esc(src.slice(last));
    return out;
  }

  var DEFAULT_TIP = '复制';

  function copyText(text, btn) {
    var timer = null;

    function tip(msg, cls) {
      btn.textContent = msg;
      btn.classList.remove('ok', 'fail');
      if (cls) btn.classList.add(cls);
      if (timer) clearTimeout(timer);
      timer = setTimeout(function () {
        btn.textContent = DEFAULT_TIP;
        btn.classList.remove('ok', 'fail');
      }, 1500);
    }

    function fallback() {
      var ta = document.createElement('textarea');
      ta.value = text;
      ta.setAttribute('readonly', '');
      ta.style.position = 'fixed';
      ta.style.top = '0';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      ta.setSelectionRange(0, ta.value.length);
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      document.body.removeChild(ta);
      tip(ok ? '已复制' : '失败', ok ? 'ok' : 'fail');
    }

    if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(
        function () { tip('已复制', 'ok'); },
        function () { fallback(); }
      );
    } else {
      fallback();
    }
  }

  function wrapCodeBlock(pre, contributorEl) {
    var wrap = document.createElement('div');
    wrap.className = 'code-block';

    var bar = document.createElement('div');
    bar.className = 'code-bar';

    var left = document.createElement('span');
    left.className = 'code-bar-left';

    var lang = document.createElement('span');
    lang.className = 'code-lang';
    lang.textContent = 'C++';
    left.appendChild(lang);

    if (contributorEl && contributorEl.innerHTML.trim() !== '') {
      var c = document.createElement('span');
      c.className = 'code-contributor';
      c.innerHTML = contributorEl.innerHTML;
      left.appendChild(c);
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'copy-btn';
    btn.textContent = DEFAULT_TIP;
    btn.addEventListener('click', function () {
      copyText(pre.textContent, btn);
    });

    bar.appendChild(left);
    bar.appendChild(btn);

    pre.parentNode.insertBefore(wrap, pre);
    wrap.appendChild(bar);
    wrap.appendChild(pre);
  }

  document.querySelectorAll('code.language-cpp').forEach(function (el) {
    var raw = el.textContent;
    el.innerHTML = highlight(raw);

    var pre = el.parentNode;
    if (pre && pre.tagName === 'PRE') {
      var prev = pre.previousElementSibling;
      var contributorEl = (prev && prev.classList.contains('code-contributor-source')) ? prev : null;

      wrapCodeBlock(pre, contributorEl);

      if (contributorEl) contributorEl.remove();
    }
  });
})();

function ToL() {
  document.getElementById('l').style.display = "none";
  document.getElementById('r').style.display = "block";
}
function ToR() {
  document.getElementById('l').style.display = "block";
  document.getElementById('r').style.display = "none";
}
function ks(i) {
  var j = document.getElementById(i.toString());
  var k = document.getElementById("but" + i.toString());
  if (j.classList.contains('collapsed')) {
    j.classList.remove('collapsed');
    k.innerHTML = '折叠';
  } else {
    j.classList.add('collapsed');
    k.innerHTML = '显示';
  }
}
</script>
</body>
</html>