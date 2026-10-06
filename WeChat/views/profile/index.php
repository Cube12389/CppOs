<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'core/Storage.php'; $siteName = Storage::getSetting('site_name'); ?>
    <title>个人设置 - <?php echo htmlspecialchars($siteName); ?></title>
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
<body class="bg-gradient-to-br from-indigo-50 to-pink-50 min-h-screen p-6 font-sans text-gray-800">

    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="index.php?route=rooms" class="bg-white p-2 rounded-full shadow hover:shadow-md transition">
                    <i class="fa fa-arrow-left text-gray-600"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">个人设置</h1>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-xl mb-6 shadow-sm">
                <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-6 shadow-sm">
                <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="bg-white/80 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden border border-white/50">
            
            <div class="p-8 pb-0 flex flex-col items-center">
                <!-- Current Avatar Large -->
                <div class="relative group">
                    <?php 
                        $avatarUrl = strpos($user['avatar'], 'http') === 0 ? $user['avatar'] : AVATAR_DIR . $user['avatar'];
                    ?>
                    <img src="<?php echo $avatarUrl; ?>" class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg">
                    <div class="absolute bottom-1 right-1 bg-green-500 w-6 h-6 rounded-full border-2 border-white"></div>
                </div>
                
                <form action="index.php?route=profile_update" method="post" class="mt-6 w-full max-w-sm flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fa fa-pencil"></i>
                        </span>
                        <input type="text" name="new_nickname" value="<?php echo htmlspecialchars($user['nickname'] ?? $user['username']); ?>" 
                            class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-center font-bold text-gray-800"
                            placeholder="输入新昵称" required minlength="2" maxlength="20">
                    </div>
                    <button type="submit" class="bg-indigo-50 text-indigo-600 hover:bg-indigo-100 px-4 py-2 rounded-lg font-medium transition-colors whitespace-nowrap">
                        修改昵称
                    </button>
                </form>
                
                
                <!-- Login Username Edit Form -->
                <form action="index.php?route=profile_update" method="post" class="mt-4 w-full max-w-sm flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="action" value="change_username">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fa fa-user-circle-o"></i>
                        </span>
                        <input type="text" name="new_username" value="<?php echo htmlspecialchars($user['username']); ?>" 
                            class="w-full pl-9 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent outline-none transition-all text-sm font-medium text-gray-600 bg-gray-50 focus:bg-white"
                            placeholder="登录账号" required minlength="2" maxlength="50">
                    </div>
                    <button type="submit" class="bg-gray-100 text-gray-600 hover:bg-gray-200 px-3 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap border border-gray-200" onclick="return confirm('修改登录账号后，下次必须使用新账号登录。确定修改吗？')">
                        换账号
                    </button>
                </form>

                <div class="mt-2 text-xs text-gray-400">
                    <i class="fa fa-info-circle mr-1"></i> ID: <?php echo $user['id']; ?> (系统唯一标识)
                </div>
            </div>

            <div class="p-8">
                
                <!-- Password Change Section -->
                 <div class="mb-8 bg-gray-50 rounded-2xl p-6 border border-gray-100">
                    <h3 class="font-bold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fa fa-lock text-primary"></i> 修改密码
                    </h3>
                    <form action="index.php?route=profile_update" method="post" class="space-y-3">
                         <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                         <input type="hidden" name="action" value="change_password">
                         
                         <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                             <input type="password" name="old_password" placeholder="当前密码" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-primary outline-none">
                             <input type="password" name="new_password" placeholder="新密码 (至少6位)" required minlength="6" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-primary outline-none">
                             <button type="submit" class="bg-gray-800 text-white hover:bg-black px-4 py-2 rounded-xl font-medium transition-colors">
                                 确认修改
                             </button>
                         </div>
                    </form>
                 </div>

                <div class="flex items-center gap-4 mb-6">
                    <div class="h-px bg-gray-200 flex-1"></div>
                    <span class="font-bold text-gray-700 flex items-center gap-2"><i class="fa fa-picture-o text-primary"></i> 更换头像</span>
                    <div class="h-px bg-gray-200 flex-1"></div>
                </div>

                <!-- TABS UI could go here, but let's keep it simple vertical flow -->

                <!-- 1. Custom Upload -->
                <form action="index.php?route=profile_update" method="post" enctype="multipart/form-data" class="mb-8">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-primary hover:bg-indigo-50 transition-colors group cursor-pointer relative">
                        <input type="file" name="avatar_file" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" onchange="this.form.submit()">
                        <i class="fa fa-cloud-upload text-3xl text-gray-400 group-hover:text-primary mb-2 transition-colors"></i>
                        <div class="text-sm font-medium text-gray-600 group-hover:text-primary">点击上传自定义图片</div>
                        <div class="text-xs text-gray-400 mt-1">支持 JPG, PNG, GIF</div>
                    </div>
                </form>

                <div class="flex items-center gap-4 mb-6">
                    <div class="h-px bg-gray-200 flex-1"></div>
                    <span class="text-xs text-gray-400 font-medium">或选择默认头像</span>
                    <div class="h-px bg-gray-200 flex-1"></div>
                </div>

                <!-- 2. Preset Grid -->
                <form action="index.php?route=profile_update" method="post" id="presetForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="avatar_select" id="avatarSelectInput">
                    
                    <div class="grid grid-cols-5 sm:grid-cols-6 gap-4">
                        <?php foreach($default_avatars as $av): ?>
                            <div class="relative group cursor-pointer" onclick="selectAvatar('<?php echo $av; ?>')">
                                <img src="<?php echo AVATAR_DIR . $av; ?>" class="w-full aspect-square rounded-full object-cover border-2 border-transparent hover:border-primary hover:scale-110 transition-all shadow-sm">
                                <?php if($user['avatar'] == $av): ?>
                                    <div class="absolute inset-0 bg-black/20 rounded-full flex items-center justify-center">
                                        <i class="fa fa-check text-white font-bold"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function selectAvatar(filename) {
            document.getElementById('avatarSelectInput').value = filename;
            document.getElementById('presetForm').submit();
        }
    </script>
</body>
</html>
