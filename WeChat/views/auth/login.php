<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'core/Storage.php'; $siteName = Storage::getSetting('site_name'); ?>
    <title>欢迎 - <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1', // Indigo
                        secondary: '#ec4899', // Pink
                    },
                    animation: {
                        'blob': 'blob 7s infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 min-h-screen flex items-start justify-center md:items-center pt-8 md:pt-0 relative overflow-hidden font-sans text-gray-800">

    <!-- Decorative Blobs -->
    <div class="absolute top-0 -left-4 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
    <div class="absolute top-0 -right-4 w-72 h-72 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
    <div class="absolute -bottom-8 left-20 w-72 h-72 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-4000"></div>

    <div class="container mx-auto px-4 relative z-10 max-w-5xl">
        <div class="flex flex-col md:flex-row items-center justify-center gap-6 md:gap-20">
            
            <!-- Left Side: Branding -->
            <div class="text-center md:text-left md:w-1/2 space-y-6">
                <div class="inline-block p-3 rounded-2xl bg-white/30 backdrop-blur-md border border-white/50 shadow-sm mb-2">
                    <i class="fa fa-comments text-4xl text-primary"></i>
                </div>
                <h1 class="text-5xl md:text-6xl font-extrabold text-gray-900 tracking-tight leading-tight">
                    连接 <span class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-secondary">每一刻</span>
                </h1>
                <p class="text-lg text-gray-600 leading-relaxed max-w-md mx-auto md:mx-0">
                    体验全新设计的即时通讯平台。极简主义风格，实时 WebSocket 传输，为了让沟通更纯粹。
                </p>
                <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm font-medium text-gray-500">
                    <span class="flex items-center gap-2 bg-white/40 px-3 py-1 rounded-full backdrop-blur-sm border border-white/40">
                        <i class="fa fa-bolt text-yellow-500"></i> 秒速直达
                    </span>
                    <span class="flex items-center gap-2 bg-white/40 px-3 py-1 rounded-full backdrop-blur-sm border border-white/40">
                        <i class="fa fa-shield text-green-500"></i> 安全加密
                    </span>
                    <span class="flex items-center gap-2 bg-white/40 px-3 py-1 rounded-full backdrop-blur-sm border border-white/40">
                        <i class="fa fa-magic text-purple-500"></i> 极致体验
                    </span>
                </div>
            </div>

            <!-- Right Side: Login Card -->
            <div class="w-full md:w-[420px]">
                <div class="bg-white/60 backdrop-blur-xl rounded-2xl p-8 shadow-2xl border border-white/50 transform transition-all hover:scale-[1.01]">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">欢迎回来</h2>
                        <p class="text-gray-500 text-sm mt-1">登录或创建一个新账号</p>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="bg-red-50/90 backdrop-blur-sm border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 flex items-center gap-2 text-sm shadow-sm animate-pulse">
                            <i class="fa fa-exclamation-circle text-lg"></i>
                            <span><?php echo $error; ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="index.php?route=login" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 ml-1">用户名</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 group-focus-within:text-primary transition-colors">
                                    <i class="fa fa-user"></i>
                                </span>
                                <input type="text" name="username" 
                                    class="w-full bg-white/50 border border-white/60 text-gray-800 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-white/80 transition-all shadow-sm placeholder-gray-400"
                                    placeholder="输入您的昵称" required>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1 ml-1">密码</label>
                            <div class="relative group">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 group-focus-within:text-primary transition-colors">
                                    <i class="fa fa-lock"></i>
                                </span>
                                <input type="password" name="password" 
                                    class="w-full bg-white/50 border border-white/60 text-gray-800 rounded-xl py-3 pl-10 pr-4 focus:outline-none focus:ring-2 focus:ring-primary/50 focus:bg-white/80 transition-all shadow-sm placeholder-gray-400"
                                    placeholder="输入密码 (新用户自动注册)" required>
                            </div>
                        </div>

                        <button type="submit" 
                            class="w-full bg-gradient-to-r from-primary to-indigo-600 hover:from-primary/90 hover:to-indigo-600/90 text-white font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl transform active:scale-[0.98] transition-all flex items-center justify-center gap-2 group">
                            <span>进入聊天室</span>
                            <i class="fa fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-400">
                            未注册账号？直接输入新密码即可自动创建。
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
