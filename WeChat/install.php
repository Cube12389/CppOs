<?php
// install.php - 用于初始化系统配置和数据库
// 安全检查：如果已安装则禁止访问
if (file_exists('install.lock')) {
    die('<div style="text-align:center;margin-top:50px;font-family:sans-serif;">
            <h1>系统已安装</h1>
            <p>如需重新安装，请删除根目录下的 <code>install.lock</code> 文件。</p>
            <a href="index.php">返回首页</a>
         </div>');
}

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 3) {
        $db_host = $_POST['db_host'] ?? 'localhost';
        $db_port = $_POST['db_port'] ?? '3306';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $admin_user = $_POST['admin_user'] ?? 'admin';
        $admin_pass = $_POST['admin_pass'] ?? '';
        $site_name = $_POST['site_name'] ?? '轻聊 LiteTalk';

        // 1. 验证数据库连接
        $conn = @new mysqli($db_host, $db_user, $db_pass, '', $db_port);
        if ($conn->connect_error) {
            $error = "数据库连接失败: " . $conn->connect_error;
        } else {
            // 2. 创建数据库（如果不存在）
            $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (!$conn->query($sql)) {
                $error = "创建数据库失败: " . $conn->error;
            } else {
                $conn->select_db($db_name);
                
                // 3. 导入 install.sql
                $sqlContent = file_get_contents('install.sql');
                if (!$sqlContent) {
                    $error = "找不到 install.sql 文件";
                } else {
                    // 分割SQL语句
                    $queries = explode(';', $sqlContent);
                    foreach ($queries as $query) {
                        $query = trim($query);
                        if (!empty($query)) {
                            $conn->query($query);
                        }
                    }

                    // 4. 创建管理员账户
                    $passwordHash = password_hash($admin_pass, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (username, nickname, password, role, avatar) VALUES (?, ?, ?, 'admin', 'default_avatar.png')");
                    $stmt->bind_param("sss", $admin_user, $admin_user, $passwordHash);
                    if ($stmt->execute()) {
                        
                        // 5. 写入 config.php
                        $configContent = "<?php\n\n";
                        $configContent .= "define('DB_HOST', '$db_host');\n";
                        $configContent .= "define('DB_PORT', '$db_port');\n";
                        $configContent .= "define('DB_USER', '$db_user');\n";
                        $configContent .= "define('DB_PASS', '$db_pass');\n";
                        $configContent .= "define('DB_NAME', '$db_name');\n";
                        $configContent .= "\n// 目录常量\n";
                        $configContent .= "define('BASE_DIR', __DIR__);\n";
                        $configContent .= "define('UPLOAD_DIR', 'uploads/');\n";
                        $configContent .= "define('AVATAR_DIR', 'uploads/avatars/');\n";
                        $configContent .= "define('MESSAGE_IMAGE_DIR', 'uploads/images/');\n";
                        $configContent .= "define('MESSAGE_VIDEO_DIR', 'uploads/videos/');\n";
                        $configContent .= "define('MESSAGE_AUDIO_DIR', 'uploads/audio/');\n";
                        
                        // 确保 system_settings 包含站点名
                        $conn->query("UPDATE system_settings SET setting_value = '$site_name' WHERE setting_key = 'site_name'");

                        if (file_put_contents('config.php', $configContent)) {
                            // 6. 创建 install.lock
                            file_put_contents('install.lock', 'INSTALLED ON ' . date('Y-m-d H:i:s'));
                            $step = 4; // 完成
                        } else {
                            $error = "无法写入 config.php，请手动创建。";
                        }
                    } else {
                        $error = "创建管理员失败: " . $conn->error;
                    }
                }
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系统安装 - 聊天室</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen flex items-center justify-center p-4">

<div class="max-w-xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
    <!-- Header -->
    <div class="bg-indigo-600 p-6 text-center">
        <h1 class="text-2xl font-bold text-white mb-2">轻聊 (LiteTalk) 安装向导</h1>
        <p class="text-indigo-100 text-sm">简单几步，即可完成部署</p>
    </div>

    <!-- Steps Indicator -->
    <div class="flex border-b text-sm">
        <div class="flex-1 py-3 text-center <?php echo $step == 1 ? 'text-indigo-600 font-bold border-b-2 border-indigo-600' : 'text-gray-400'; ?>">环境检测</div>
        <div class="flex-1 py-3 text-center <?php echo $step == 2 ? 'text-indigo-600 font-bold border-b-2 border-indigo-600' : 'text-gray-400'; ?>">配置数据库</div>
        <div class="flex-1 py-3 text-center <?php echo $step == 4 ? 'text-green-600 font-bold border-b-2 border-green-600' : 'text-gray-400'; ?>">完成</div>
    </div>

    <div class="p-8">
        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" role="alert">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <!-- STEP 1: Environment Check -->
        <?php if ($step == 1): ?>
            <?php
            $checks = [
                'PHP 版本 >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'Nysqli 扩展' => extension_loaded('mysqli'),
                'GD 库 (图片处理)' => extension_loaded('gd'),
                'Mbstring 扩展' => extension_loaded('mbstring'),
                'config.php 可写' => is_writable(__DIR__) || (!file_exists('config.php') && is_writable(__DIR__)),
                'uploads/ 目录可写' => is_writable(__DIR__ . '/uploads') || (!file_exists('uploads') && is_writable(__DIR__)),
            ];
            $allOk = !in_array(false, $checks);
            ?>
            <h2 class="text-lg font-bold mb-4">服务器环境检测</h2>
            <div class="space-y-3 mb-6">
                <?php foreach ($checks as $name => $ok): ?>
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded">
                        <span><?php echo $name; ?></span>
                        <?php if ($ok): ?>
                            <span class="text-green-500 font-bold"><i class="fa fa-check"></i> 通过</span>
                        <?php else: ?>
                            <span class="text-red-500 font-bold">未通过</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($allOk): ?>
                <a href="install.php?step=2" class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition duration-200">下一步：配置系统</a>
            <?php else: ?>
                <button disabled class="w-full bg-gray-300 text-gray-500 font-bold py-3 rounded-xl cursor-not-allowed">环境不满足，无法继续</button>
            <?php endif; ?>

        <!-- STEP 2: Database & Admin Config -->
        <?php elseif ($step == 2): ?>
            <form action="install.php?step=3" method="POST" class="space-y-4">
                
                <div>
                    <h3 class="font-bold text-gray-700 mb-2 border-l-4 border-indigo-500 pl-2">数据库配置</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">数据库主机</label>
                            <input type="text" name="db_host" value="localhost" class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">端口</label>
                            <input type="text" name="db_port" value="3306" class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="block text-sm text-gray-500 mb-1">数据库名</label>
                        <input type="text" name="db_name" value="chat_room" required class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">若不存在将尝试自动创建</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-3">
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">数据库用户名</label>
                            <input type="text" name="db_user" value="root" required class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-500 mb-1">数据库密码</label>
                            <input type="password" name="db_pass" class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <hr class="border-gray-100 my-4">

                <div>
                    <h3 class="font-bold text-gray-700 mb-2 border-l-4 border-indigo-500 pl-2">管理员账号</h3>
                    <div>
                        <label class="block text-sm text-gray-500 mb-1">管理员用户名</label>
                        <input type="text" name="admin_user" value="admin" required class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div class="mt-3">
                        <label class="block text-sm text-gray-500 mb-1">管理员密码</label>
                        <input type="text" name="admin_pass" value="admin888" required class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>

                 <hr class="border-gray-100 my-4">
                 
                 <div>
                     <h3 class="font-bold text-gray-700 mb-2 border-l-4 border-indigo-500 pl-2">站点设置</h3>
                     <div>
                        <label class="block text-sm text-gray-500 mb-1">网站名称</label>
                        <input type="text" name="site_name" value="轻聊 LiteTalk" required class="w-full border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-indigo-500">
                     </div>
                 </div>

                <button type="submit" class="w-full mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl transition duration-200">开始安装</button>
            </form>

        <!-- STEP 4: Success -->
        <?php elseif ($step == 4): ?>
            <div class="text-center py-10">
                <div class="w-20 h-20 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 text-3xl">
                    ✓
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">安装成功！</h2>
                <p class="text-gray-500 mb-8">系统已成功配置，管理员账户已创建。</p>
                <div class="bg-yellow-50 text-yellow-800 p-4 rounded-lg mb-8 text-sm text-left">
                    <strong>安全提示：</strong><br>安装完成后请删除 <code>install.php</code> 和 <code>install.sql</code> 文件，防止被重复安装。
                </div>
                <a href="index.php?route=login" class="inline-block px-8 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl transition shadow-lg shadow-indigo-200">前往登录</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="bg-gray-50 p-4 text-center text-xs text-gray-400">
        &copy; <?php echo date('Y'); ?> 轻聊 LiteTalk 系统
    </div>
</div>

</body>
</html>
