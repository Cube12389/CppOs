<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'core/Storage.php'; $siteName = Storage::getSetting('site_name'); ?>
    <title>系统设置 - <?php echo htmlspecialchars($siteName); ?></title>
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

        // --- ROOMS ---
        async function loadRooms() {
             const tbody = document.getElementById('roomTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">加载中...</td></tr>';
            
            try {
                const res = await fetch('index.php?route=admin_get_rooms');
                const data = await res.json();
                
                if (data.success) {
                    tbody.innerHTML = '';
                    data.rooms.forEach(r => {
                        const row = `
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-4 text-gray-400 font-mono text-xs">#${r.id}</td>
                                <td class="p-4 font-bold text-indigo-700">${r.name}</td>
                                <td class="p-4">
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                        ${r.owner_name || 'System'}
                                    </span>
                                </td>
                                <td class="p-4 text-gray-500">${r.member_count} 人</td>
                                <td class="p-4 text-gray-400 text-xs">${r.created_at}</td>
                                <td class="p-4 text-right space-x-2">
                                    <button onclick="clearRoomHistory(${r.id})" class="text-xs bg-orange-100 text-orange-600 px-3 py-1 rounded hover:bg-orange-200 transition">清空记录</button>
                                    <button onclick="deleteRoom(${r.id})" class="text-xs bg-red-100 text-red-600 px-3 py-1 rounded hover:bg-red-200 transition">解散</button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-red-500">加载失败</td></tr>';
            }
        }

        async function deleteRoom(id) {
            if(!confirm('确定要解散并删除该房间吗？所有数据将丢失！')) return;
            postAction('admin_delete_room', {room_id: id}, loadRooms);
        }

        async function clearRoomHistory(id) {
            if(!confirm('确定要清空该房间的聊天记录吗？此操作不可撤销！')) return;
            postAction('admin_clear_room_history', {room_id: id}, loadRooms);
        }

        async function postAction(route, data, callback) {
            const formData = new FormData();
            for(let key in data) formData.append(key, data[key]);
            
            try {
                const res = await fetch('index.php?route=' + route, { method: 'POST', body: formData });
                const json = await res.json();
                if(json.success) callback();
                else alert(json.error || '操作失败');
            } catch(e) {
                alert('请求错误');
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-100 to-gray-200 min-h-screen p-6 font-sans text-gray-800">

    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <a href="index.php?route=rooms" class="bg-white p-2 rounded-full shadow hover:shadow-md transition">
                    <i class="fa fa-arrow-left text-gray-600"></i>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">系统设置</h1>
            </div>
            <div class="bg-indigo-100 text-indigo-700 px-4 py-1 rounded-full text-sm font-bold">
                管理员模式
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fa fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            
            <!-- Tabs (Always Visible) -->
            <div class="flex border-b border-gray-100">
                <button type="button" onclick="switchTab('storage')" id="tab_storage" class="px-6 py-4 font-bold text-primary border-b-2 border-primary bg-indigo-50/50 transition-colors">
                    <i class="fa fa-hdd-o mr-2"></i>存储策略
                </button>
                <button type="button" onclick="switchTab('general')" id="tab_general" class="px-6 py-4 font-bold text-gray-500 hover:bg-gray-50 transition-colors">
                    <i class="fa fa-cog mr-2"></i>基础设置
                </button>
                <button type="button" onclick="switchTab('users')" id="tab_users" class="px-6 py-4 font-bold text-gray-500 hover:bg-gray-50 transition-colors">
                    <i class="fa fa-users mr-2"></i>用户管理
                </button>
                <button type="button" onclick="switchTab('rooms')" id="tab_rooms" class="px-6 py-4 font-bold text-gray-500 hover:bg-gray-50 transition-colors">
                    <i class="fa fa-comments mr-2"></i>房间管理
                </button>
            </div>

            <!-- Content: Storage Settings -->
            <form id="form_storage" method="post" action="index.php?route=admin_update">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="p-8">
                    <!-- Upload Limits Section -->
                    <div class="mb-8 border-b border-gray-100 pb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-4">上传限制 (MB)</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">图片限制</label>
                                <input type="number" name="upload_image_limit" value="<?php echo htmlspecialchars($settings['upload_image_limit'] ?? '5'); ?>" class="w-full border rounded p-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">视频限制</label>
                                <input type="number" name="upload_video_limit" value="<?php echo htmlspecialchars($settings['upload_video_limit'] ?? '50'); ?>" class="w-full border rounded p-2 text-sm">
                            </div>
                        </div>
                    </div>

                    <!-- Storage Driver Selection -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-gray-700 mb-4">选择存储方式</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            
                            <!-- Local -->
                            <label class="cursor-pointer relative">
                                <input type="radio" name="storage_driver" value="local" class="peer sr-only" 
                                    <?php echo ($settings['storage_driver'] ?? 'local') == 'local' ? 'checked' : ''; ?>>
                                <div class="p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 peer-checked:border-primary peer-checked:bg-indigo-50 transition-all text-center">
                                    <i class="fa fa-server text-2xl mb-2 text-gray-400 peer-checked:text-primary"></i>
                                    <div class="font-bold">本地存储</div>
                                    <div class="text-xs text-gray-500 mt-1">默认方案</div>
                                </div>
                            </label>

                            <!-- S3 Compatible -->
                            <label class="cursor-pointer relative">
                                <input type="radio" name="storage_driver" value="s3" class="peer sr-only"
                                    <?php echo ($settings['storage_driver'] ?? '') == 's3' ? 'checked' : ''; ?>>
                                <div class="p-4 rounded-xl border-2 border-gray-200 hover:border-indigo-300 peer-checked:border-primary peer-checked:bg-indigo-50 transition-all text-center">
                                    <i class="fa fa-cloud text-2xl mb-2 text-gray-400 peer-checked:text-primary"></i>
                                    <div class="font-bold">S3 兼容</div>
                                    <div class="text-xs text-gray-500 mt-1">R2/COS/AWS</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Config Sections -->
                    
                    <!-- S3 Config -->
                    <div id="config_s3" class="config-section hidden space-y-4 bg-gray-50 p-6 rounded-xl border border-gray-200">
                        <h3 class="font-bold text-gray-700 flex items-center gap-2">
                            <i class="fa fa-cog"></i> S3 兼容配置 (R2 / COS / AWS)
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Endpoint (API 地址)</label>
                                <input type="text" name="s3_endpoint" value="<?php echo htmlspecialchars($settings['s3_endpoint'] ?? ''); ?>" placeholder="https://<account>.r2.cloudflarestorage.com" class="w-full border rounded p-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Region (地区)</label>
                                <input type="text" name="s3_region" value="<?php echo htmlspecialchars($settings['s3_region'] ?? 'auto'); ?>" placeholder="auto, us-east-1, ap-shanghai..." class="w-full border rounded p-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Access Key ID</label>
                                <input type="text" name="s3_access_key" value="<?php echo htmlspecialchars($settings['s3_access_key'] ?? ''); ?>" class="w-full border rounded p-2 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1">Secret Access Key</label>
                                <input type="password" name="s3_secret_key" value="<?php echo htmlspecialchars($settings['s3_secret_key'] ?? ''); ?>" class="w-full border rounded p-2 text-sm">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Bucket Name (存储桶名称)</label>
                                <input type="text" name="s3_bucket" value="<?php echo htmlspecialchars($settings['s3_bucket'] ?? ''); ?>" class="w-full border rounded p-2 text-sm">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="bg-primary text-white font-bold py-2 px-6 rounded-lg shadow hover:bg-indigo-600 transition">
                        保存配置
                    </button>
                </div>
            </form>

            <!-- Content: General Settings (Uses same form endpoint but separated for UI) -->
            <form id="form_general" method="post" action="index.php?route=admin_update" class="hidden">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="p-8 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">网站名称</label>
                        <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? '轻聊 LiteTalk'); ?>" class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">侧边栏公告</label>
                        <textarea name="site_announcement" rows="3" class="w-full border rounded-lg p-3 text-sm focus:ring-2 focus:ring-primary focus:outline-none"><?php echo htmlspecialchars($settings['site_announcement'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">用户注册</label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="registration_enabled" value="1" class="sr-only peer" <?php echo ($settings['registration_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900">允许新用户注册</span>
                        </label>
                        <input type="hidden" name="registration_enabled" value="0">
                        <input type="checkbox" name="registration_enabled" value="1" class="sr-only peer" <?php echo ($settings['registration_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">着陆页 (Landing Page)</label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="hidden" name="landing_page_enabled" value="0">
                            <input type="checkbox" name="landing_page_enabled" value="1" class="sr-only peer" <?php echo ($settings['landing_page_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                             <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                            <span class="ml-3 text-sm font-medium text-gray-900">启用首页落地页</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1">关闭后，未登录用户访问首页将直接跳转到登录页面。</p>
                    </div>
                </div>
                 <div class="bg-gray-50 px-8 py-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="bg-primary text-white font-bold py-2 px-6 rounded-lg shadow hover:bg-indigo-600 transition">
                        保存基础设置
                    </button>
                </div>
            </form>

            <!-- Content: User Management -->
            <div id="panel_users" class="hidden">
                 <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-bold text-lg text-gray-700">用户列表</h2>
                        <button onclick="loadUsers()" class="text-sm text-primary hover:underline">
                            <i class="fa fa-refresh"></i> 刷新列表
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                    <th class="p-4 rounded-tl-lg">ID</th>
                                    <th class="p-4">用户</th>
                                    <th class="p-4">角色</th>
                                    <th class="p-4">注册时间</th>
                                    <th class="p-4">状态</th>
                                    <th class="p-4 rounded-tr-lg text-right">操作</th>
                                </tr>
                            </thead>
                            <tbody id="userTableBody" class="text-sm text-gray-700 divide-y divide-gray-100">
                                <!-- JS populated -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Content: Room Management -->
            <div id="panel_rooms" class="hidden">
                 <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-bold text-lg text-gray-700">房间列表</h2>
                        <button onclick="loadRooms()" class="text-sm text-primary hover:underline">
                            <i class="fa fa-refresh"></i> 刷新列表
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                                    <th class="p-4 rounded-tl-lg">ID</th>
                                    <th class="p-4">房间名</th>
                                    <th class="p-4">群主</th>
                                    <th class="p-4">成员数</th>
                                    <th class="p-4">创建时间</th>
                                    <th class="p-4 rounded-tr-lg text-right">操作</th>
                                </tr>
                            </thead>
                            <tbody id="roomTableBody" class="text-sm text-gray-700 divide-y divide-gray-100">
                                <!-- JS populated -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>



    </div>

    <script>
        // --- TABS ---
        // --- TABS ---
        function switchTab(tab) {
            // Update Headers
            ['storage', 'general', 'users', 'rooms'].forEach(t => {
                const btn = document.getElementById('tab_' + t);
                if (t === tab) {
                    btn.className = "px-6 py-4 font-bold text-primary border-b-2 border-primary bg-indigo-50/50 transition-colors";
                } else {
                    btn.className = "px-6 py-4 font-bold text-gray-500 hover:bg-gray-50 transition-colors";
                }
            });

            // Toggle Visibility
            const storageForm = document.getElementById('form_storage');
            const generalForm = document.getElementById('form_general');
            const usersPanel = document.getElementById('panel_users');
            const roomsPanel = document.getElementById('panel_rooms');

            storageForm.classList.add('hidden');
            generalForm.classList.add('hidden');
            usersPanel.classList.add('hidden');
            roomsPanel.classList.add('hidden');

            if (tab === 'storage') {
                storageForm.classList.remove('hidden');
            } else if (tab === 'general') {
                generalForm.classList.remove('hidden');
            } else if (tab === 'users') {
                usersPanel.classList.remove('hidden');
                loadUsers();
            } else if (tab === 'rooms') {
                roomsPanel.classList.remove('hidden');
                loadRooms();
            }
        }

        // --- USERS ---
        async function loadUsers() {
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-400">加载中...</td></tr>';
            
            try {
                const res = await fetch('index.php?route=admin_get_users');
                const data = await res.json();
                
                if (data.success) {
                    tbody.innerHTML = '';
                    data.users.forEach(u => {
                        const isBanned = u.is_banned == 1;
                        const roleBadge = u.role === 'admin' 
                            ? '<span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-xs font-bold">管理员</span>'
                            : '<span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded text-xs">用户</span>';
                            
                        const statusBadge = isBanned
                            ? '<span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-bold">已封禁</span>'
                            : '<span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs">正常</span>';
                        
                        let actionBtn = '';
                        if (u.role !== 'admin') {
                            if (isBanned) {
                                actionBtn = `<button onclick="toggleBan(${u.id}, 0)" class="text-xs bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 transition">解封</button>`;
                            } else {
                                actionBtn = `<button onclick="toggleBan(${u.id}, 1)" class="text-xs bg-red-400 text-white px-3 py-1 rounded hover:bg-red-500 transition">封禁</button>`;
                            }
                        }

                        const row = `
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-4 text-gray-400 font-mono text-xs">#${u.id}</td>
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden shrink-0">
                                            <img src="<?php echo AVATAR_DIR; ?>${u.avatar}" class="w-full h-full object-cover">
                                        </div>
                                        <div>
                                            <div class="font-bold">${u.nickname || u.username}</div>
                                            <div class="text-xs text-gray-400">@${u.username}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">${roleBadge}</td>
                                <td class="p-4 text-gray-400 text-xs">${u.created_at}</td>
                                <td class="p-4">${statusBadge}</td>
                                <td class="p-4 text-right">${actionBtn}</td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                }
            } catch (e) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-red-500">加载失败</td></tr>';
            }
        }

        async function toggleBan(userId, status) {
            if (!confirm(status ? '确定要封禁该用户吗？' : '确定要解除封禁吗？')) return;
            
            const action = status ? 'admin_ban_user' : 'admin_unban_user';
            const formData = new FormData();
            formData.append('user_id', userId);
            
            try {
                const res = await fetch('index.php?route=' + action, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if(data.success) {
                    loadUsers(); // Reload
                } else {
                    alert(data.error);
                }
            } catch(e) {
                alert('操作失败');
            }
        }

        const driverRadios = document.getElementsByName('storage_driver');
        const sections = document.querySelectorAll('.config-section');

        function updateVisibility() {
            sections.forEach(s => s.classList.add('hidden'));
            const selected = document.querySelector('input[name="storage_driver"]:checked').value;
            const target = document.getElementById('config_' + selected);
            if(target) target.classList.remove('hidden');
        }

        driverRadios.forEach(r => r.addEventListener('change', updateVisibility));
        updateVisibility(); // Init
    </script>
</body>
</html>
