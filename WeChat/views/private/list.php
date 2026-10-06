<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'core/Storage.php'; $siteName = Storage::getSetting('site_name'); ?>
    <title>我的私信 - <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 min-h-screen flex text-gray-800 font-sans">

    <!-- Sidebar -->
    <?php require 'views/partials/sidebar.php'; ?>

    <main class="flex-1 flex flex-col relative w-full h-screen overflow-hidden bg-white/40 backdrop-blur-xl md:rounded-2xl shadow-2xl mr-0 md:my-2 md:mr-2">
        
        <header class="bg-white/60 backdrop-blur-md px-6 py-4 flex justify-between items-center border-b border-white/20 shadow-sm z-10">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-600 hover:text-indigo-500">
                     <i class="fa fa-bars text-xl"></i>
                </button>
                <h1 class="font-bold text-xl text-gray-800 flex items-center gap-2">
                    <i class="fa fa-envelope text-primary"></i> 我的私信
                </h1>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-6">
            <?php if (empty($conversations)): ?>
                <div class="text-center text-gray-400 mt-20">
                    <i class="fa fa-paper-plane-o text-6xl mb-4 opacity-50"></i>
                    <p>暂无私信记录</p>
                    <p class="text-sm mt-2">在聊天室点击好友头像即可发起私聊</p>
                </div>
            <?php else: ?>
                <div class="max-w-3xl mx-auto space-y-3">
                    <?php foreach ($conversations as $conv): ?>
                        <a href="index.php?route=chat_private&user_id=<?php echo $conv['partner']['id']; ?>" 
                           class="block bg-white/60 hover:bg-white/90 backdrop-blur-sm rounded-2xl p-4 transition-all shadow-sm hover:shadow-md border border-white/40 group">
                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <img src="<?php echo AVATAR_DIR . $conv['partner']['avatar']; ?>" class="w-14 h-14 rounded-full object-cover shadow border border-white">
                                    <?php if ($conv['unread'] > 0): ?>
                                        <div class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white">
                                            <?php echo $conv['unread']; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-gray-800 text-lg">
                                            <?php echo htmlspecialchars($conv['partner']['display_name']); ?>
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            <?php echo date('m-d H:i', strtotime($conv['last_message']['created_at'] ?? 'now')); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-500 text-sm truncate group-hover:text-primary transition-colors">
                                        <?php 
                                            if (!empty($conv['last_message']['content'])) {
                                                echo htmlspecialchars(mb_substr($conv['last_message']['content'], 0, 50));
                                            } elseif (!empty($conv['last_message']['image'])) {
                                                echo '<i class="fa fa-picture-o"></i> [图片]';
                                            } else {
                                                echo '...';
                                            }
                                        ?>
                                    </p>
                                </div>
                                
                                <div class="text-gray-300 group-hover:text-primary transition-colors">
                                    <i class="fa fa-angle-right text-xl"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
