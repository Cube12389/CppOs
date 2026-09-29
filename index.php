<?php
$servername = "localhost";
$username = "root";
$password = "123456";
$dbname = "user"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
$is_logged_in = false;
$user_info = null;

// 用 cookie 自动登录（GET 和 POST 都生效）
if (!empty($_COOKIE['login_cookie'])) {
    $token = $_COOKIE['login_cookie'];
    $stmt = $conn->prepare("SELECT `uid` FROM `cookie` WHERE `cookie` = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($c = $res->fetch_assoc()) {
            $uid = $c['uid'];
            $stmt->close();

            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `uid` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $is_logged_in = true;
                    $user_info = $row;
                }
                $stmt->close();
            }
        } else {
            $stmt->close();
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $name = $_POST['name'] ?? '';
        $UserPassword = $_POST['password'] ?? '';
        if (!empty($name) && !empty($UserPassword)) {
            $sql = "SELECT * FROM `user` WHERE `name` = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    if ($row['password'] == $UserPassword) {
                      $is_logged_in = true;
                      $user_info = $row;

                      $token = bin2hex(random_bytes(32));
                      $stmt2 = $conn->prepare("INSERT INTO `cookie` (`cookie`, `uid`) VALUES (?, ?)");
                      if ($stmt2) {
                          $stmt2->bind_param("si", $token, $row['uid']);
                          $stmt2->execute();
                          $stmt2->close();
                          setcookie("login_cookie", $token, time() + 86400 * 30, "/");
                      }
                    }
                    else {
                      echo "用户名或密码错误";
                    }
                } else {
                    echo "用户不存在";
                } $stmt->close();
            } else {
                echo "SQL 准备失败: " . $conn->error;
            }
        } else {
            echo "请输入用户名和密码";
        }
    } else if (isset($_POST['action']) && $_POST['action'] === 'reslogin') {
        $name = $_POST['name'] ?? '';
        $UserPassword = $_POST['password'] ?? '';
        $UserPassword2 = $_POST['passwordag'] ?? '';
        if (!empty($name) && !empty($UserPassword) && !empty($UserPassword2)) {
              if ($UserPassword == $UserPassword2) {

            $result = $conn->query("SELECT COUNT(*) AS total FROM `user`");
            $row1 = $result->fetch_assoc();
              $sql = "INSERT INTO `user` (`name`, `calling`, `password`, `alc`) VALUES (?, 'none', ?, '0');";
              $stmt1 = $conn->prepare($sql);
              if ($stmt1) {
                  $stmt1->bind_param("ss", $name, $UserPassword);
                  if ($stmt1->execute()) {
                      echo "注册成功";
                  } else {
                      echo "注册失败: " . $stmt1->error;
                  }
                  $stmt1->close();
              } else {
                  echo "SQL 准备失败: " . $conn->error;
              }
            } else {
              echo "两次密码不一致";
            }
            
        } else {
            echo "请输入用户名和密码";
        }
    } else if (isset($_POST['action']) && $_POST['action'] === 'upload') {
        // 只有权限为 1 的登录用户才能上传
        if ($is_logged_in && $user_info['alc'] == 1) {
            $uid     = intval($_POST['uid'] ?? 0);
            $field   = $_POST['field'] ?? '';
            $content = $_POST['content'] ?? '';
            $allowed = ['A','B','C','D','E','F','G','H','I','J','K'];

            if ($uid > 0 && in_array($field, $allowed, true)) {
                $sql = "UPDATE `cook` SET `$field` = ? WHERE `uid` = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("si", $content, $uid);
                    if ($stmt->execute()) {
                        echo "上传成功";
                    } else {
                        echo "上传失败: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    echo "SQL 准备失败: " . $conn->error;
                }
            } else {
                echo "参数错误";
            }
        } else {
            echo "无权限上传";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>登录示例</title>
    <meta charset="utf-8"/>
    <!-- ★ 不再引用任何外部 CDN -->
    <style>
        body {
            background: #ffffff;
            color: #24292e;
            font-family: -apple-system, "Segoe UI", "Microsoft YaHei", sans-serif;
        }
        /* ★ 全局 div 只保留布局，去掉 1px 边框，避免满屏"框框线线" */
        div {
            position: relative;
            padding: 12px 0;
        }
        .div {
            position: relative;
            padding: 20px;
            border: 1px solid #ccc;
            margin-top: 20px;
        }
        .hidden { display: none; }
        .visible { display: block; }
        textarea {
            width: 100%;
            box-sizing: border-box;
            font-family: monospace;
        }

        /* ================= 代码块容器 + 顶部工具条 ================= */
        .code-block {
            position: relative;
            padding: 0;                 /* 覆盖全局 div 的 padding */
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
        .code-lang {
            font-family: Consolas, Monaco, "Courier New", monospace;
            letter-spacing: .5px;
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
            background: transparent;    /* 背景交给 .code-block */
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
        /* token 配色：鲜明但不刺眼 */
        code.language-cpp .tok-kw   { color: #cf222e; font-weight: 600; } /* 关键字 红 */
        code.language-cpp .tok-type { color: #0550ae; }                   /* 类型/常用名 蓝 */
        code.language-cpp .tok-str  { color: #0a3069; }                   /* 字符串 深蓝 */
        code.language-cpp .tok-num  { color: #0550ae; }                   /* 数字 蓝 */
        code.language-cpp .tok-com  { color: #6e7781; font-style: italic; } /* 注释 灰斜 */
        code.language-cpp .tok-pre  { color: #8250df; }                   /* 预处理 紫 */
    </style>
</head>
<body>
    <h1>欢迎！</h1>

    <!-- 登录表单 -->
    <form method="POST" id="l">
        <input type="hidden" name="action" value="login">
        <label>用户名: <input type="text" name="name" required></label><br>
        <label>密&emsp;码: <input type="text" name="password" required></label><br><br>
        &emsp;&emsp;&emsp;&ensp;<button type="submit">登录</button>&emsp;<button type="button" onclick="ToL();">转换到注册页</button>
    </form>
    <form method="POST" id="r" style="display: none">
        <input type="hidden" name="action" value="reslogin">
        <label>用户名: <input type="text" name="name" required></label><br>
        <label>密&emsp;码: <input type="text" name="password" required></label><br>
        <label>确&emsp;认: <input type="text" name="passwordag" required></label><br><br>
        &emsp;&emsp;&emsp;&ensp;<button type="submit">注册</button>&emsp;<button type="button" onclick="ToR();">转换到登录页</button>
    </form>

    <div class="<?php echo $is_logged_in ? 'visible' : 'hidden'; ?> div">
        <?php if ($is_logged_in): ?>
            <p>登录成功!</p>
            <p>ID: <?php echo htmlspecialchars($user_info['uid']); ?></p>
            <p>name: <?php echo htmlspecialchars($user_info['name']); ?></p>
            <p>权限: <?php if ($user_info['alc'] == 1) echo "all."; else echo "none." ?></p>
        <?php else: ?>
            <p>请先登录。</p>
        <?php endif; ?>
    </div>
    
    <?php
        if ($is_logged_in) {
          if ($user_info['alc'] == 1) {
            echo "<div class='div'><a href='http://192.168.21.229/phpMyAdmin4.8.5/'>管理员界面</a></div>";
            for ($i1 = 1; $i1 <= 2; $i1++) {
              $sql = "SELECT * FROM `cook` WHERE `uid` = $i1";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                  echo "<div class='div'>";
                  echo htmlspecialchars($row['name']);
                  echo " uid:$i1<br><br><button id='but$i1' onclick='ks($i1);'>显示</button><br><br>";
                  echo "<div class='div' style='display: none' id='$i1'>";
                  for ($i = 'A'; $i <= 'K'; $i++) {
                      if ($row[$i] == "none") continue;
                      echo "<div class='div'><h3>" . $i . "</h3>";
                      echo "<pre><code class=\"language-cpp\">";
                      echo htmlspecialchars($row[$i]);
                      echo "</code></pre></div>";
                  }
                  echo "</div></div>";
                } else {
                    echo "<div class='div'>暂无数据 uid:$i1</div>";
                } $stmt->close();
            } else {
                echo "SQL 准备失败: " . $conn->error;
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

    <?php if ($is_logged_in && $user_info['alc'] == 1): ?>
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
            <label>内容:<br><textarea name="content" rows="8"></textarea></label><br><br>
            <button type="submit">上传</button>
        </form>
    </div>
    <?php endif; ?>

<script>
/* =========================================================
   内联 C++ 语法高亮器（零依赖）
   顺序：注释 → 字符串/字符 → 预处理 → 数字 → 标识符
   所有 token 输出前先做 HTML 转义，避免实体冲突
   ========================================================= */
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

  /* ---------- 复制到剪贴板（兼容 http 非安全上下文） ---------- */
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

    // 兜底方案：execCommand（http 页面同样可用）
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

  /* ---------- 给每个代码块套上工具条 + 复制按钮 ---------- */
  function wrapCodeBlock(pre, raw) {
    var wrap = document.createElement('div');
    wrap.className = 'code-block';

    var bar = document.createElement('div');
    bar.className = 'code-bar';

    var lang = document.createElement('span');
    lang.className = 'code-lang';
    lang.textContent = 'C++';

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'copy-btn';
    btn.textContent = DEFAULT_TIP;
    btn.addEventListener('click', function () {
      copyText(raw, btn);
    });

    bar.appendChild(lang);
    bar.appendChild(btn);

    pre.parentNode.insertBefore(wrap, pre);
    wrap.appendChild(bar);
    wrap.appendChild(pre);
  }

  document.querySelectorAll('code.language-cpp').forEach(function (el) {
    var raw = el.textContent;      // 先取纯文本，再高亮
    el.innerHTML = highlight(raw);

    var pre = el.parentNode;
    if (pre && pre.tagName === 'PRE') {
      wrapCodeBlock(pre, raw);
    }
  });
})();

/* 原来的交互函数保持不变 */
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