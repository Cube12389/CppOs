<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'core/Storage.php'; $siteName = Storage::getSetting('site_name'); ?>
    <title>大厅 - <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/font-awesome@4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1', // Indigo
                        secondary: '#ec4899', // Pink
                        glass: 'rgba(255, 255, 255, 0.7)',
                        glassBorder: 'rgba(255, 255, 255, 0.5)',
                    },
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen flex h-screen h-[100dvh] overflow-hidden text-gray-800 dark:text-gray-100 font-sans transition-colors duration-300">
    
    <!-- Shared Sidebar -->
    <?php require 'views/partials/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 min-w-0 flex flex-col relative w-full h-full overflow-hidden bg-white/40 dark:bg-gray-900/50 backdrop-blur-xl md:rounded-2xl shadow-2xl mr-0 md:my-2 md:mr-2 border border-white/20 dark:border-gray-700">
        
        <!-- Header -->
        <!-- Header -->
        <header class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-md px-6 py-4 flex justify-between items-center border-b border-white/20 dark:border-gray-700 shadow-sm z-10 shrink-0">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-600 hover:text-primary transition-colors">
                     <i class="fa fa-bars text-xl"></i>
                </button>
                <h1 class="font-bold text-xl text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fa fa-home text-primary"></i> 聊天大厅
                </h1>
            </div>
            <button id="createRoomBtn" class="bg-gradient-to-r from-primary to-indigo-600 text-white px-5 py-2 rounded-full shadow-lg hover:shadow-xl hover:scale-105 transition-all text-sm font-bold flex items-center gap-2">
                <i class="fa fa-plus"></i> <span class="hidden md:inline">创建房间</span>
            </button>
        </header>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto p-6 md:p-8 overscroll-contain">
            
            <!-- Messages -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-50/80 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-2">
                    <i class="fa fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-50/80 border border-green-200 text-green-600 px-4 py-3 rounded-xl mb-6 shadow-sm backdrop-blur-sm flex items-center gap-2">
                    <i class="fa fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Room Grid -->
            <?php if (empty($rooms)): ?>
                <div class="flex flex-col items-center justify-center h-64 text-center">
                    <div class="w-20 h-20 bg-white/50 dark:bg-gray-700/50 rounded-full flex items-center justify-center mb-4 shadow-inner">
                        <i class="fa fa-coffee text-4xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-600 dark:text-gray-300 mb-2">暂无房间</h3>
                    <p class="text-gray-400">创建一个新房间开始聊天吧！</p>
                </div>
            <?php else: ?>
                <!-- Desktop Grid View (Hidden on Mobile) -->
                <div class="hidden md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($rooms as $room): ?>
                        <div class="group bg-white/60 dark:bg-gray-800/60 backdrop-blur-md rounded-2xl p-5 border border-white/40 dark:border-gray-700 shadow-sm hover:shadow-xl hover:bg-white/80 dark:hover:bg-gray-800/80 transition-all duration-300 flex flex-col relative overflow-hidden">
                            <!-- Decor -->
                            <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-br from-indigo-500/10 to-pink-500/10 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-150"></div>
                            
                            <div class="flex justify-between items-start mb-4 relative z-10">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-xl font-bold shadow-md group-hover:rotate-6 transition-transform">
                                    <?php echo mb_substr($room['name'], 0, 1); ?>
                                </div>
                                <?php if (!empty($room['password'])): ?>
                                    <span class="bg-yellow-100 text-yellow-600 text-xs px-2 py-1 rounded-full border border-yellow-200" title="私密房间">
                                        <i class="fa fa-lock"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="font-bold text-lg text-gray-800 dark:text-white mb-1 truncate relative z-10"><?php echo htmlspecialchars($room['name']); ?></h3>
                            <p class="text-gray-500 dark:text-gray-400 text-sm mb-4 line-clamp-2 h-10 relative z-10">
                                <?php echo !empty($room['description']) ? htmlspecialchars($room['description']) : '暂无描述'; ?>
                            </p>
                            
                            <div class="mt-auto flex items-center justify-between relative z-10">
                                <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 gap-3">
                                    <span class="flex items-center gap-1"><i class="fa fa-user"></i> <?php echo htmlspecialchars($room['creator_name']); ?></span>
                                    <span class="flex items-center gap-1"><i class="fa fa-circle text-[8px] text-green-500"></i> <?php echo $room['online_count']; ?></span>
                                </div>
                                <button class="joinRoomBtn bg-white dark:bg-gray-700 text-primary dark:text-indigo-400 border border-primary/20 dark:border-indigo-500/30 hover:bg-primary hover:text-white px-3 py-1.5 rounded-lg text-sm font-medium transition-colors shadow-sm"
                                        data-room-id="<?php echo $room['id']; ?>" 
                                        data-requires-password="<?php echo !empty($room['password']) ? '1' : '0'; ?>">
                                    加入
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Mobile Compact List View (Visible only on Mobile) -->
                <div class="md:hidden space-y-3 pb-8">
                     <?php foreach ($rooms as $room): ?>
                        <div class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-md rounded-xl p-3 border border-white/40 dark:border-gray-700 shadow-sm flex items-center gap-3">
                            <!-- Icon -->
                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex-shrink-0 flex items-center justify-center text-white font-bold shadow-md">
                                <?php echo mb_substr($room['name'], 0, 1); ?>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-gray-800 dark:text-white truncate text-sm"><?php echo htmlspecialchars($room['name']); ?></h3>
                                    <?php if (!empty($room['password'])): ?>
                                        <i class="fa fa-lock text-yellow-500 text-xs"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-gray-500 mt-0.5">
                                    <span class="flex items-center gap-1"><i class="fa fa-user opacity-70"></i> <?php echo htmlspecialchars($room['creator_name']); ?></span>
                                    <span class="flex items-center gap-1"><i class="fa fa-circle text-[6px] text-green-500"></i> <?php echo $room['online_count']; ?></span>
                                </div>
                            </div>
                            
                            <!-- Action -->
                             <button class="joinRoomBtn flex-shrink-0 bg-primary/10 text-primary border border-primary/20 hover:bg-primary hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-colors"
                                    data-room-id="<?php echo $room['id']; ?>" 
                                    data-requires-password="<?php echo !empty($room['password']) ? '1' : '0'; ?>">
                                加入
                            </button>
                        </div>
                     <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center gap-4 mt-8 pb-40 md:pb-0">
                    <?php if ($current_page > 1): ?>
                        <a href="index.php?route=rooms&page=<?php echo $current_page - 1; ?>" class="px-4 py-2 bg-white/50 hover:bg-white rounded-lg shadow-sm border border-white/40 text-sm font-medium transition-all">
                            <i class="fa fa-chevron-left"></i> 上一页
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100/50 rounded-lg text-gray-400 text-sm font-medium cursor-not-allowed"><i class="fa fa-chevron-left"></i> 上一页</span>
                    <?php endif; ?>

                    <span class="text-sm font-bold text-gray-600 bg-white/40 px-3 py-1 rounded-md">
                        <?php echo $current_page; ?> / <?php echo $total_pages; ?>
                    </span>

                    <?php if ($current_page < $total_pages): ?>
                         <a href="index.php?route=rooms&page=<?php echo $current_page + 1; ?>" class="px-4 py-2 bg-white/50 hover:bg-white rounded-lg shadow-sm border border-white/40 text-sm font-medium transition-all">
                            下一页 <i class="fa fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-4 py-2 bg-gray-100/50 rounded-lg text-gray-400 text-sm font-medium cursor-not-allowed">下一页 <i class="fa fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php endif; ?>
            
            <!-- Bottom Spacer for Mobile -->
            <div class="h-20 w-full md:hidden"></div>
        </div>
    </main>

    <!-- Create Room Modal (Glassmorphism) -->
    <div id="createRoomModal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/20 backdrop-blur-sm transition-opacity" id="closeCreateModal"></div>
        
        <!-- Modal Content -->
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
            <div class="bg-white/90 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/50 dark:border-gray-700 p-6 relative">
                <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" id="closeCreateBtn">
                    <i class="fa fa-times"></i>
                </button>
                
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-1">创建新房间</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">设置您的专属聊天空间</p>
                
                <form method="post" action="index.php?route=rooms">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">房间名称</label>
                            <input type="text" name="room_name" class="w-full bg-white/50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary focus:outline-none transition-all placeholder-gray-400" placeholder="例如：周末聚会" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">描述 (可选)</label>
                            <textarea name="room_description" rows="2" class="w-full bg-white/50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary focus:outline-none transition-all placeholder-gray-400" placeholder="简要介绍一下..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">密码 (可选)</label>
                            <input type="password" name="room_password" class="w-full bg-white/50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-2 text-gray-800 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary focus:outline-none transition-all placeholder-gray-400" placeholder="留空则设为公开房间">
                        </div>

                        <div class="flex items-center gap-2">
                             <input type="checkbox" name="is_hidden" id="is_hidden" class="rounded text-primary focus:ring-primary bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600">
                             <label for="is_hidden" class="text-sm text-gray-700 dark:text-gray-300 select-none cursor-pointer">设为隐藏房间 (不在大厅显示)</label>
                        </div>
                    </div>
                    
                    <button type="submit" name="create_room" class="w-full mt-6 bg-primary text-white font-bold py-3 rounded-xl shadow-lg hover:shadow-xl hover:opacity-90 transition-all">
                        立即创建
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div id="passwordModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/20 backdrop-blur-sm" id="closePassModal"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-sm p-4">
            <div class="bg-white/90 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/50 dark:border-gray-700 p-6 text-center">
                <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4 text-yellow-600 text-2xl">
                    <i class="fa fa-lock"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-2">私密房间</h3>
                <p class="text-gray-500 dark:text-gray-400 text-sm mb-6">请输入密码加入该房间</p>
                
                <form id="joinRoomForm" method="post" action="index.php?route=rooms">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="join_room" value="1">
                    <input type="hidden" id="modal_room_id" name="room_id" value="">
                    
                    <input type="password" id="modal_password" name="password" class="w-full bg-white/50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-3 text-center tracking-widest text-gray-800 dark:text-white focus:ring-2 focus:ring-primary/50 focus:border-primary focus:outline-none transition-all mb-4" placeholder="••••••" required>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" id="cancelPassword" class="py-2 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 transition-colors">取消</button>
                        <button type="submit" class="py-2 rounded-xl bg-primary text-white shadow-md hover:opacity-90 transition-all">加入</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal Logic
        const createModal = document.getElementById('createRoomModal');
        const passModal = document.getElementById('passwordModal');
        const createBtn = document.getElementById('createRoomBtn');
        const closeCreateBtn = document.getElementById('closeCreateBtn');
        const closeCreateModal = document.getElementById('closeCreateModal');
        const cancelPassword = document.getElementById('cancelPassword');
        const closePassModal = document.getElementById('closePassModal');
        
        // Open Create
        if(createBtn) {
            createBtn.addEventListener('click', () => {
                createModal.classList.remove('hidden');
            });
        }
        
        // Close Create
        const closeCreate = () => createModal.classList.add('hidden');
        if(closeCreateBtn) closeCreateBtn.addEventListener('click', closeCreate);
        if(closeCreateModal) closeCreateModal.addEventListener('click', closeCreate);

        // Join Room Logic
        document.querySelectorAll('.joinRoomBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                const roomId = this.dataset.roomId;
                const hasPass = this.dataset.requiresPassword === '1';
                
                document.getElementById('modal_room_id').value = roomId;
                
                if(hasPass) {
                    passModal.classList.remove('hidden');
                    document.getElementById('modal_password').focus();
                } else {
                    document.getElementById('joinRoomForm').submit();
                }
            });
        });

        // Close Password Mode
        const closePass = () => passModal.classList.add('hidden');
        document.getElementById('cancelPassword').addEventListener('click', closePass);
        document.getElementById('closePassModal').addEventListener('click', closePass);

        // Auto Join via Link
        const urlParams = new URLSearchParams(window.location.search);
        const joinId = urlParams.get('join_id');
        if (joinId) {
             const btn = document.querySelector(`.joinRoomBtn[data-room-id="${joinId}"]`);
             if (btn) {
                 btn.click();
             } else {
                 // Might be hidden and not in list?
                 // But index.php filters hidden rooms unless already member.
                 // If I'm not member, it won't be in $rooms list...
                 // SO verify logic: 
                 // If I am NOT a member, hidden room query ignores it.
                 // So `btn` will be null.
                 // We need to handle this case: Create a temporary "Join Modal" for this ID even if not in list.
                 // We know ID and maybe password is required. 
                 // We don't know if password is required though without fetching.
                 // But wait, if I am redirected here from handle_invite, it means I am NOT a member.
                 // If it is hidden, it is NOT in the grid.
                 // So we must manually open the password modal (assuming it might need password)
                 // or just try to join directly?
                 // Let's assume it needs password modal. The user can try blank.
                 
                 document.getElementById('modal_room_id').value = joinId;
                 passModal.classList.remove('hidden');
                 document.getElementById('modal_password').focus();
                 
                 // Add a hint text
                 const hint = document.createElement('p');
                 hint.innerText = "正在加入房间 #" + joinId;
                 hint.className = "text-xs text-primary mb-2";
                 document.getElementById('joinRoomForm').insertBefore(hint, document.getElementById('modal_password'));
             }
        }
    </script>
</body>
</html>
