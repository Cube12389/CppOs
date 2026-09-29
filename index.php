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
            setcookie("login_cookie", "", time() - 3600, "/");
        }
    }
}

/* ---------------- 2. 处理 POST ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ---------- 登录 ---------- */
    if ($action === 'login') {
        $name         = $_POST['name'] ?? '';
        $UserPassword = $_POST['password'] ?? '';

        if ($name === '' || $UserPassword === '') {
            $msg = "<br><span style='color:red'>请输入用户名和密码</span>";
        } else {
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `name` = ?");
            if (!$stmt) {
                $msg = "<br><span style='color:red'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</span>";
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
                        $msg = "<br><span style='color:red'>用户名或密码错误</span>";
                    }
                } else {
                    $msg = "<br><span style='color:red'>用户不存在</span>";
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
            $msg = "<br><span style='color:red'>请输入用户名和密码</span>";
        } else if ($UserPassword !== $UserPassword2) {
            $msg = "<br><span style='color:red'>两次密码不一致</span>";
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
                $msg = "<br><span style='color:red'>用户名已存在</span>";
            } else {
                $hashed = password_hash($UserPassword, PASSWORD_DEFAULT);
                $stmt1  = $conn->prepare("INSERT INTO `user` (`name`, `calling`, `password`, `alc`, `color`, `num`) VALUES (?, 'none', ?, '1', '#0e90d2', '0')");
                if ($stmt1) {
                    $stmt1->bind_param("ss", $name, $hashed);
                    if ($stmt1->execute()) {
                        $msg = "<br><span style='color:green'>注册成功，请登录</span>";
                    } else {
                        $msg = "<br><span style='color:red'>注册失败: " . htmlspecialchars($stmt1->error) . "</span>";
                    }
                    $stmt1->close();
                } else {
                    $msg = "<br><span style='color:red'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</span>";
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
            /* ★ 新增：标题 */
            $title   = trim($_POST['title'] ?? '');
            $allowed = ['A','B','C','D','E','F','G','H','I','J','K'];

            if ($uid > 0 && in_array($field, $allowed, true)) {
                $contributor = (string)$user_info['uid'];

                /* ★ 新格式：首行 = "uid 标题"（标题可空），第二行开始为代码
                 *   标题为空时退化为老的 "uid\n"，保证向后兼容 */
                if ($title !== '') {
                    $content = $contributor . ' ' . $title . "\n" . $content;
                } else {
                    $content = $contributor . "\n" . $content;
                }

                $stmt = $conn->prepare("UPDATE `cook` SET `$field` = ? WHERE `uid` = ?");
                if ($stmt) {
                    $stmt->bind_param("si", $content, $uid);
                    if ($stmt->execute()) {
                        $msg = "<br><span style='color:green'>上传成功</span>";

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
                        $msg = "<br><span style='color:red'>上传失败: " . htmlspecialchars($stmt->error) . "</span>";
                    }
                    $stmt->close();
                } else {
                    $msg = "<br><span style='color:red'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</span>";
                }
            } else {
                $msg = "<br><span style='color:red'>参数错误</span>";
            }
        } else {
            $msg = "<br><span style='color:red'>无权限上传</span>";
        }
    }

    /* ---------- 新建 cook（仅 alc=2 管理员） ---------- */
    else if ($action === 'create_cook') {
        if ($is_logged_in && (int)$user_info['alc'] === 2) {
            $new_uid  = intval($_POST['new_uid']  ?? 0);
            $new_name = trim($_POST['new_name']   ?? '');

            if ($new_uid <= 0) {
                $msg = "<br><span style='color:red'>请输入合法的 UID（正整数）</span>";
            } else if ($new_name === '') {
                $msg = "<br><span style='color:red'>请输入名称</span>";
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
                    $msg = "<br><span style='color:red'>UID " . $new_uid . " 已存在，不能重复新建</span>";
                } else {
                    $stmt = $conn->prepare(
                        "INSERT INTO `cook`
                           (`uid`, `name`, `A`, `B`, `C`, `D`, `E`, `F`, `G`, `H`, `I`, `J`, `K`)
                         VALUES
                           (?, ?, 'none','none','none','none','none','none','none','none','none','none','none')"
                    );
                    if ($stmt) {
                        $stmt->bind_param("is", $new_uid, $new_name);
                        if ($stmt->execute()) {
                            $msg = "<br><span style='color:green'>新建 cook 成功：UID=" . $new_uid . "</span>";
                        } else {
                            $msg = "<br><span style='color:red'>新建失败: " . htmlspecialchars($stmt->error) . "</span>";
                        }
                        $stmt->close();
                    } else {
                        $msg = "<br><span style='color:red'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</span>";
                    }
                }
            }
        } else {
            $msg = "<br><span style='color:red'>无权限新建 cook</span>";
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
    <style>
        body {
            background: #ffffff;
            color: #24292e;
            font-family: -apple-system, "Segoe UI", "Microsoft YaHei", sans-serif;
        }
        div {
            position: relative;
            padding: 12px 0;
        }
        .div {
            position: relative;
            padding: 20px;
            border: 1px solid #ccc;
            margin-top: 20px;
            background-color: rgba(255, 255, 255, 0.75);
        }
        .div-h {
            position: relative;
            padding: 0;
            border: 0;
            margin-top: 0;
        }
        .hidden { display: none; }
        .visible { display: block; }
        textarea {
            width: 100%;
            box-sizing: border-box;
            font-family: monospace;
        }

        /* ================= 排行榜 ================= */
        .rank-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .rank-table th,
        .rank-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #e1e4e8;
            text-align: left;
            vertical-align: middle;
        }
        .rank-table thead th {
            background: #f6f8fa;
            color: #57606a;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 1px solid #d0d7de;
        }
        .rank-table tbody tr:hover { background: #f6f8fa; }
        .rank-table .rank-no {
            width: 64px;
            font-family: Consolas, Monaco, "Courier New", monospace;
            color: #57606a;
        }
        .rank-table .rank-num {
            width: 90px;
            text-align: right;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-weight: 600;
            color: #0550ae;
        }
        .rank-no.top1 { color: #b8860b; font-weight: 700; }
        .rank-no.top2 { color: #8a8f98; font-weight: 700; }
        .rank-no.top3 { color: #a0522d; font-weight: 700; }
        .rank-me { background: #fff8e5 !important; }
        .rank-tag {
            font-size: 11px;
            line-height: 1.4;
            color: #1a7f37;
            border: 1px solid #1a7f37;
            border-radius: 3px;
            padding: 0 4px;
            margin-left: 6px;
        }
        .rank-uid {
            font-size: 12px;
            color: #8b949e;
            margin-left: 6px;
        }
        .rank-admin-tag {
            font-size: 11px;
            line-height: 1.4;
            color: #ffffff;
            background: #8e44ad;
            border-radius: 3px;
            padding: 0 5px;
            margin-left: 6px;
        }

        /* ================= 字段折叠（题号 + 按钮） ================= */
        .field-block {
            margin: 8px 0 14px 0;
            border: 1px solid #e1e4e8;
            border-radius: 6px;
            background: #ffffff;
            overflow: hidden;
        }
        .field-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 12px;
            background: #f6f8fa;
        }
        .field-block.expanded .field-head {
            border-bottom: 1px solid #e1e4e8;
        }
        .field-title {
            display: inline-flex;
            align-items: baseline;
            gap: 8px;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-weight: 600;
            font-size: 14px;
            color: #24292e;
            letter-spacing: .5px;
        }
        /* ★ 题号后紧跟的标题 */
        .field-title-text {
            font-family: -apple-system, "Segoe UI", "Microsoft YaHei", sans-serif;
            font-weight: 400;
            font-size: 13px;
            color: #57606a;
            letter-spacing: 0;
            max-width: 620px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .field-toggle {
            font-size: 12px;
            line-height: 1;
            padding: 5px 10px;
            color: #24292e;
            background: #ffffff;
            border: 1px solid #d0d7de;
            border-radius: 5px;
            cursor: pointer;
            transition: background .15s, border-color .15s;
            flex-shrink: 0;
        }
        .field-toggle:hover  { background: #f3f4f6; border-color: #afb8c1; }
        .field-toggle:active { background: #ebecf0; }
        .field-body {
            padding: 10px 12px 0 12px;
        }

        /* ================= 代码块容器 + 顶部工具条 ================= */
        .code-block {
            position: relative;
            padding: 0;
            margin: 6px 0 14px 0;
            background: #f8f9fa;
            border-radius: 6px;
            overflow: hidden;
        }
        .code-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 8px 4px 12px;
            background: #eef1f4;
            border-bottom: 1px solid #e1e4e8;
            font-size: 12px;
            color: #57606a;
        }
        .code-bar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .code-lang {
            font-family: Consolas, Monaco, "Courier New", monospace;
            letter-spacing: .5px;
        }
        .code-contributor {
            display: inline-flex;
            align-items: center;
            font-size: 12px;
            line-height: 1.4;
            color: #57606a;
            background: #ffffff;
            border: 1px solid #d0d7de;
            border-radius: 4px;
            padding: 1px 6px;
        }
        .copy-btn {
            font-size: 12px;
            line-height: 1;
            padding: 5px 10px;
            color: #24292e;
            background: #ffffff;
            border: 1px solid #d0d7de;
            border-radius: 5px;
            cursor: pointer;
            transition: background .15s, color .15s, border-color .15s;
        }
        .copy-btn:hover  { background: #f3f4f6; border-color: #afb8c1; }
        .copy-btn:active { background: #ebecf0; }
        .copy-btn.ok     { color: #1a7f37; border-color: #1a7f37; }
        .copy-btn.fail   { color: #cf222e; border-color: #cf222e; }

        /* ================= C++ 代码块样式（浅色） ================= */
        pre { margin: 0; }
        code.language-cpp {
            display: block;
            background: transparent;
            color: #24292e;
            border: none;
            border-radius: 0;
            padding: 12px 16px;
            margin: 0;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-size: 13px;
            line-height: 1.65;
            overflow-x: auto;
            white-space: pre;
            tab-size: 4;
        }
        code.language-cpp .tok-kw   { color: #cf222e; font-weight: 600; }
        code.language-cpp .tok-type { color: #0550ae; }
        code.language-cpp .tok-str  { color: #0a3069; }
        code.language-cpp .tok-num  { color: #0550ae; }
        code.language-cpp .tok-com  { color: #6e7781; font-style: italic; }
        code.language-cpp .tok-pre  { color: #8250df; }
    </style>
</head>
<body>
  <div class='div' style="width:1000px; left: calc(50% - 500px)">
    <h1>欢迎！</h1>

    <form method="POST" id="l">
        <input type="hidden" name="action" value="login">
        <label>用户名: <input type="text" name="name" required></label><br>
        <label>密&emsp;码: <input type="password" name="password" required></label><br><br>
        &emsp;&emsp;&emsp;&ensp;<button type="submit">登录</button>&emsp;<button type="button" onclick="ToL();">转换到注册页</button>
    </form>
    <form method="POST" id="r" style="display: none">
        <input type="hidden" name="action" value="reslogin">
        <label>用户名: <input type="text" name="name" required></label><br>
        <label>密&emsp;码: <input type="password" name="password" required></label><br>
        <label>确&emsp;认: <input type="password" name="passwordag" required></label><br><br>
        &emsp;&emsp;&emsp;&ensp;<button type="submit">注册</button>&emsp;<button type="button" onclick="ToR();">转换到登录页</button>
    </form>

    <?php echo $msg; ?>

    <div class="<?php echo $is_logged_in ? 'visible' : 'hidden'; ?> div">
        <?php if ($is_logged_in): ?>
            <?php $my_display_color = resolve_user_color($user_info['alc'] ?? 0, $user_info['num'] ?? 0); ?>
            <p>登录成功!</p>
            <p>ID: <?php echo htmlspecialchars($user_info['uid']); ?></p>
            <p>name: <?php echo "<strong style='color: " . htmlspecialchars($my_display_color) . "'>" . htmlspecialchars($user_info['name']) . "</strong>";
                if ($user_info['calling'] !== 'none' && $user_info['calling'] !== '') {
                    echo "&thinsp;<strong style='font-size: 80%; border-radius: 3px; color: white; background-color: " . htmlspecialchars($my_display_color) . "'>&ensp;" . htmlspecialchars($user_info['calling']) . "&ensp;</strong>";
                } ?></p>
            <p>权限: <?php if ($user_info['alc'] == 2) echo "all."; else if ($user_info['alc'] == 1) echo "普通用户."; else echo "none." ?></p>
            <p>贡献数: <?php echo (int)($user_info['num'] ?? 0); ?></p>
        <?php else: ?>
            <p>请先登录。</p>
        <?php endif; ?>
    </div>

    <?php
        if ($is_logged_in) {
          if ($user_info['alc'] == 1 || $user_info['alc'] == 2) {
            if ($user_info['alc'] == 2) echo "<div class='div'><a href='http://192.168.21.229/phpMyAdmin4.8.5/'>管理员界面</a></div>";

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
                  echo "<div class='div'>$i1. ";
                  echo htmlspecialchars($row['name']);
                  echo "<button style='position: absolute; right: 20px;' id='but$i1' onclick='ks($i1);'>显示</button>";
                  echo "<div class='div-h' style='display: none' id='$i1'>";

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

                      /* ============================================================
                       * ★ 首行解析（新格式：uid 标题）
                       *   - "123 两数之和"     → uid=123, title="两数之和"
                       *   - "123"（旧格式）    → uid=123, title=""
                       *   - 其它（老代码无前缀）→ 整段当代码显示
                       * ============================================================ */
                      $contributor = null;
                      $field_title = '';

                      if ($first !== '') {
                          $sp = strpos($first, ' ');
                          if ($sp === false) {
                              // 首行只有一个 token
                              if (ctype_digit($first)) {
                                  $contributor = $first;
                              } else {
                                  // 不是 uid，按老数据处理
                                  $code = $raw;
                              }
                          } else {
                              $uid_part   = substr($first, 0, $sp);
                              $title_part = trim(substr($first, $sp + 1));
                              if (ctype_digit($uid_part)) {
                                  $contributor = $uid_part;
                                  $field_title = $title_part;
                              } else {
                                  // 首 token 不是纯数字 → 老数据整段当代码
                                  $code = $raw;
                              }
                          }
                      }

                      /* ★ 每个字段一个唯一 key：cookUid_字母 */
                      $field_key = $i1 . '_' . $i;

                      /* ★ 折叠容器：head 里放题号 + 标题 + 按钮，body 里放贡献者+代码，默认隐藏 */
                      echo "<div class='field-block' id='fb_" . $field_key . "'>";
                      echo "  <div class='field-head'>";
                      echo "    <span class='field-title'>" . $i;
                      if ($field_title !== '') {
                          echo "<span class='field-title-text'>" . htmlspecialchars($field_title) . "</span>";
                      }
                      echo "    </span>";
                      echo "    <button type='button' class='field-toggle' id='ft_but_" . $field_key . "' onclick='toggleField(\"" . $field_key . "\");'>展开</button>";
                      echo "  </div>";
                      echo "  <div class='field-body' id='ft_body_" . $field_key . "' style='display:none;'>";

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
                                  $ch .= "&thinsp;<strong style='font-size: 80%; border-radius: 2px; color: white; background-color: "
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

                      echo "  </div>";  // /field-body
                      echo "</div>";    // /field-block
                  }

                  echo "</div></div>";
                } else {
                    echo "<div class='div'>$i1. 暂无数据</div>";
                }
                $stmt->close();
              } else {
                echo "<br>SQL 准备失败: " . htmlspecialchars($conn->error);
              }
            }
            }

          } else {
            echo "<div class='div'>请联系管理员提权</div>";
            echo "<div class='div'>你无权限查看</div>";
          }
        } else {
            echo "<div class='div'>请先登录</div>";
        }
      ?>

    <?php /* ================= 贡献排行榜 ================= */ ?>
    <?php if ($is_logged_in): ?>
    <div class="div" id="rank">
        <h3>贡献排行榜</h3>

        <?php if ($rank_error !== ''): ?>
            <p style="color:#cf222e"><?php echo htmlspecialchars($rank_error); ?></p>
        <?php elseif (empty($rank_rows)): ?>
            <p>暂无排行数据。</p>
        <?php else: ?>
            <table class="rank-table">
                <thead>
                    <tr>
                        <th>排名</th>
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
                        $r_color   = resolve_user_color($rr['alc'] ?? 0, $rr['num'] ?? 0);
                        $r_calling = (string)($rr['calling'] ?? '');
                        $r_is_admin = ((int)($rr['alc'] ?? 0) === 2);
                ?>
                    <tr class="<?php echo $is_me ? 'rank-me' : ''; ?>">
                        <td class="rank-no<?php echo $no_class; ?>"><?php echo $rank_no; ?></td>
                        <td>
                            <strong style="color: <?php echo htmlspecialchars($r_color); ?>">
                                <?php echo htmlspecialchars((string)$rr['name']); ?>
                            </strong>
                            <?php if ($r_calling !== 'none' && $r_calling !== ''): ?>
                                <strong style="font-size: 80%; border-radius: 3px; color: white; background-color: <?php echo htmlspecialchars($r_color); ?>">&ensp;<?php echo htmlspecialchars($r_calling); ?>&ensp;</strong>
                            <?php endif; ?>
                            <?php if ($is_me): ?><span class="rank-tag">我</span><?php endif; ?>
                            <span class="rank-uid">UID: <?php echo (int)$rr['uid']; ?></span>
                        </td>
                        <td class="rank-num"><?php echo (int)$rr['num']; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)): ?>
    <div class="div">
        <h3>上传 / 修改 cook 内容</h3>
        <form method="POST">
            <input type="hidden" name="action" value="upload">
            <label>目标 UID: <input type="number" name="uid" min="1" required></label><br><br>
            <label>字段:
                <select name="field">
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                    <option value="F">F</option>
                    <option value="G">G</option>
                    <option value="H">H</option>
                    <option value="I">I</option>
                    <option value="J">J</option>
                    <option value="K">K</option>
                </select>
            </label><br><br>
            <label>标题: <input type="text" name="title" maxlength="100" style="width:400px;" placeholder="（可空）显示在题号后面"></label><br><br>
            <label>内容:<br><textarea name="content" rows="8"></textarea></label><br><br>
            <button type="submit">上传</button>
        </form>
    </div>
    <?php endif; ?>

    <?php /* ================= 新建 cook（仅 alc=2 管理员） ================= */ ?>
    <?php if ($is_logged_in && (int)$user_info['alc'] === 2): ?>
    <div class="div">
        <h3>新建 cook </h3>
        <form method="POST" onsubmit="return confirm('确认新建该 cook 吗？');">
            <input type="hidden" name="action" value="create_cook">
            <label>新 UID: <input type="number" name="new_uid" min="1" required></label><br><br>
            <label>名称: <input type="text" name="new_name" maxlength="100" required style="width:320px;"></label><br><br>
            <button type="submit">新建</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)): ?>
    <br><button onclick="NewWorld();">New World?</button>
    <?php endif; ?>
    </div>
<script>

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

/* =========================================================
   字段折叠：题号旁边的按钮切换 body 的显示
   ========================================================= */
function toggleField(key) {
  var body = document.getElementById('ft_body_' + key);
  var btn  = document.getElementById('ft_but_'  + key);
  var box  = document.getElementById('fb_'      + key);
  if (!body || !btn) return;

  if (body.style.display === 'none') {
    body.style.display = 'block';
    btn.textContent = '折叠';
    if (box) box.classList.add('expanded');
  } else {
    body.style.display = 'none';
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

  var DEFAULT_TIP = '复制代码';

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
      try {
        ok = document.execCommand('copy');
      } catch (e) {
        ok = false;
      }
      document.body.removeChild(ta);
      tip(ok ? '已复制' : '复制失败', ok ? 'ok' : 'fail');
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
  if (j.style.display == 'none') {
    j.style.display = 'block';
    k.innerHTML = '折叠';
  } else {
    j.style.display = 'none';
    k.innerHTML = '显示';
  }
}
</script>
</body>
</html>