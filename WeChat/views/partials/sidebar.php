<!-- views/partials/sidebar.php -->
<?php 
// Fetch global sidebar data
$sidebarData = get_sidebar_data($_SESSION['user_id'] ?? 0);
$sidebarRooms = $sidebarData['rooms'];
$sidebarConvs = $sidebarData['conversations'];

// Calculate total unread
$totalUnread = 0;
foreach($sidebarConvs as $c) $totalUnread += $c['unread'];
?>

<div id="sidebarOverlay" class="fixed inset-0 bg-black/50 z-40 hidden md:hidden glass-overlay" onclick="toggleSidebar()"></div>

<aside id="mainSidebar" class="flex-shrink-0 fixed md:static inset-y-0 left-0 w-64 bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl border-r border-white/20 dark:border-gray-700 flex flex-col transition-transform duration-300 z-50 transform -translate-x-full md:translate-x-0 shadow-2xl md:shadow-none">
    <!-- Mobile Close Button -->
    <button onclick="toggleSidebar()" class="absolute top-2 right-2 md:hidden text-gray-500 dark:text-gray-400 p-2">
        <i class="fa fa-times"></i>
    </button>
    <!-- Brand / User Profile -->
    <div class="p-4 border-b border-white/10 dark:border-gray-700 flex items-center justify-start gap-3 bg-white/10 dark:bg-black/20">
        <div class="relative group">
            <img src="<?php echo AVATAR_DIR . ($_SESSION['avatar'] ?? 'default.jpg'); ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover border-2 border-white/50 shadow-md">
            <a href="index.php?route=profile" class="absolute inset-0 bg-black/40 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer text-white text-xs">
                 <i class="fa fa-cog"></i>
            </a>
        </div>
        <div class="block truncate">
            <h3 class="font-bold text-gray-800 dark:text-gray-100 text-sm truncate"><?php echo htmlspecialchars($_SESSION['nickname'] ?? $_SESSION['username'] ?? 'GUEST'); ?></h3>
            <div class="flex items-center gap-2 mt-1">
                 <a href="index.php?route=logout" class="text-xs text-red-500 hover:text-red-700 font-semibold" title="<?php echo trans('logout'); ?>">
                    <i class="fa fa-sign-out"></i> <?php echo trans('logout'); ?>
                 </a>
            </div>
        </div>
    </div>

    <!-- Navigation Area -->
    <div class="flex-1 overflow-y-auto py-2 px-2 space-y-4 scrollbar-hide">
        
        <!-- Search Bar -->
        <div class="px-2 relative">
            <input type="text" id="globalSearchInput" placeholder="搜索消息..." class="w-full bg-gray-100 dark:bg-gray-800 border-0 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 text-gray-700 dark:text-gray-200">
            <i class="fa fa-search absolute right-5 top-3 text-gray-400 text-xs"></i>
            
            <!-- Search Results Dropdown -->
            <div id="searchResults" class="absolute left-2 right-2 top-full mt-2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-700 max-h-64 overflow-y-auto hidden z-50 p-2 space-y-2">
                <!-- Results injected here -->
            </div>
        </div>
        
        <!-- Site Announcement (Dynamic) -->
        <?php 
        require_once 'core/Storage.php'; 
        $announcement = Storage::getSetting('site_announcement');
        if(!empty($announcement)): 
        ?>
        <div class="mx-2 mb-4 bg-indigo-50 dark:bg-indigo-900/30 p-3 rounded-xl border border-indigo-100 dark:border-indigo-800">
            <h4 class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-1">
                <i class="fa fa-bullhorn mr-1"></i> 公告
            </h4>
            <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">
                <?php echo nl2br(htmlspecialchars($announcement)); ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- SECTION 1: PRIVATE MESSAGES -->
        <div>
            <div class="px-2 mb-2 flex justify-between items-center">
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?php echo trans('private_messages'); ?></h4>
                <button onclick="openAddFriendModal()" class="text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 px-2 py-1 rounded hover:bg-indigo-200 dark:hover:bg-indigo-800 transition-colors" title="<?php echo trans('add_friend'); ?>">
                    <i class="fa fa-plus"></i> <span class="hidden md:inline"><?php echo trans('add_friend'); ?></span>
                </button>
            </div>
            
            <div class="space-y-1">
                <?php if(empty($sidebarConvs)): ?>
                    <p class="text-center text-[10px] text-gray-400 py-2 hidden md:block"><?php echo trans('messages_empty'); ?></p>
                <?php else: ?>
                    <?php foreach($sidebarConvs as $c): ?>
                        <a href="index.php?route=chat_private&user_id=<?php echo $c['partner']['id']; ?>" 
                           class="relative group flex items-center gap-3 p-2 rounded-xl hover:bg-white/40 dark:hover:bg-white/10 transition-all <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $c['partner']['id']) ? 'bg-white/60 dark:bg-white/20 shadow-sm' : ''; ?>">
                            
                            <div class="relative w-10 h-10 flex-shrink-0">
                                <img src="<?php echo AVATAR_DIR . $c['partner']['avatar']; ?>" class="w-full h-full rounded-full object-cover border border-white/50">
                                <?php if($c['unread'] > 0): ?>
                                    <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full border border-white">
                                        <?php echo $c['unread'] > 9 ? '9+' : $c['unread']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="overflow-hidden flex-1">
                                <div class="flex justify-between items-center">
                                    <h4 class="font-medium text-gray-800 dark:text-gray-200 text-sm truncate"><?php echo htmlspecialchars($c['partner']['display_name']); ?></h4>
                                    <span class="text-[10px] text-gray-400"><?php echo date('H:i', strtotime($c['time'])); ?></span>
                                </div>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">
                                    <?php 
                                    $lastMsg = $c['last_message'];
                                    if (!empty($lastMsg['content'])) {
                                        echo htmlspecialchars($lastMsg['content']);
                                    } elseif (!empty($lastMsg['video_url'])) {
                                        echo '[视频]';
                                    } elseif (!empty($lastMsg['audio_url'])) {
                                        echo '[语音]';
                                    } elseif (!empty($lastMsg['image_url'])) {
                                        echo '[图片]';
                                    } else {
                                        echo '[消息]';
                                    }
                                    ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="border-t border-white/10 dark:border-gray-700 mx-2"></div>

        <!-- SECTION 2: ROOMS -->
        <div>
            <div class="px-2 mb-2 flex justify-between items-center">
                <h4 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?php echo trans('chat_rooms'); ?></h4>
            </div>
            
            <!-- Lobby Button -->
            <a href="index.php?route=rooms" class="flex items-center gap-3 p-2 rounded-xl transition-all mb-2 <?php echo (isset($_GET['route']) && $_GET['route'] == 'rooms') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-300 shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-white/40 dark:hover:bg-white/10'; ?>">
                <div class="w-8 h-8 rounded-lg bg-indigo-500 text-white flex items-center justify-center shadow-md transform transition-transform group-hover:scale-105">
                    <i class="fa fa-th-large"></i>
                </div>
                <span class="font-bold text-sm"><?php echo trans('hall'); ?></span>
            </a>

            <div class="space-y-1">
                <?php 
                $gradients = [
                    'from-blue-400 to-indigo-500', 
                    'from-pink-400 to-rose-500',
                    'from-emerald-400 to-teal-500',
                    'from-orange-400 to-amber-500',
                    'from-purple-400 to-violet-500',
                    'from-cyan-400 to-blue-500',
                    'from-fuchsia-400 to-purple-500', 
                    'from-lime-400 to-green-500'
                ];
                ?>
                <?php foreach ($sidebarRooms as $r): ?>
                    <?php 
                        $isActive = (isset($_GET['room_id']) && $_GET['room_id'] == $r['id']); 
                        $gIndex = $r['id'] % count($gradients);
                        $gradient = $gradients[$gIndex];
                    ?>
                    <a href="<?php echo (empty($r['password']) || isset($_SESSION['room_joined_'.$r['id']])) ? 'index.php?route=chat&room_id='.$r['id'] : 'index.php?route=rooms'; ?>" 
                       class="group flex items-center gap-3 p-2 rounded-xl transition-all <?php echo $isActive ? 'bg-white/60 dark:bg-white/20 shadow-sm' : 'hover:bg-white/40 dark:hover:bg-white/10'; ?>">
                        
                        <div class="relative w-10 h-10 flex-shrink-0 rounded-full bg-gradient-to-br <?php echo $gradient; ?> text-white flex items-center justify-center shadow-md transition-all font-bold text-sm <?php echo $isActive ? 'ring-2 ring-indigo-400 ring-offset-2 ring-offset-white dark:ring-offset-gray-900 scale-105' : 'opacity-90 group-hover:opacity-100 group-hover:scale-105'; ?>">
                            <?php echo mb_substr($r['name'], 0, 1); ?>
                            <?php if(!empty($r['password'])): ?>
                                <i class="fa fa-lock absolute -bottom-1 -right-1 text-[8px] bg-gray-900 text-white p-1 rounded-full border border-white/50"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="overflow-hidden">
                            <h4 class="font-medium text-gray-800 dark:text-gray-200 text-sm truncate <?php echo $isActive ? 'text-indigo-600 dark:text-indigo-300' : ''; ?>">
                                <?php echo htmlspecialchars($r['name']); ?>
                            </h4>
                             <div class="flex items-center text-[10px] text-gray-500 dark:text-gray-400 gap-2">
                                 <span><i class="fa fa-circle text-[6px] <?php echo $r['online_count'] > 0 ? 'text-green-500' : 'text-gray-400'; ?>"></i> <?php echo $r['online_count']; ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- UTILITIES FOOTER (New) -->
    <div class="p-4 border-t border-white/10 dark:border-gray-700 flex flex-col gap-2">
        
        <!-- Theme Toggle -->
        <button id="themeToggle" class="flex items-center justify-center md:justify-start gap-3 p-2 text-gray-600 dark:text-gray-300 hover:bg-white/30 dark:hover:bg-white/10 rounded-lg transition-colors">
            <i class="fa fa-moon-o dark:hidden"></i>
            <i class="fa fa-sun-o hidden dark:block text-yellow-500"></i>
            <span class="text-xs font-medium dark:hidden"><?php echo trans('theme_dark'); ?></span>
            <span class="text-xs font-medium hidden dark:block"><?php echo trans('theme_light'); ?></span>
        </button>

        <!-- Lang Toggle -->
        <div class="flex justify-center md:justify-start gap-1">
            <a href="index.php?route=switch_lang&lang=zh" class="text-[10px] px-2 py-1 rounded <?php echo ($_SESSION['lang'] ?? 'zh') == 'zh' ? 'bg-indigo-500 text-white' : 'text-gray-500 hover:text-indigo-500'; ?>">CN</a>
            <span class="text-gray-300">|</span>
            <a href="index.php?route=switch_lang&lang=en" class="text-[10px] px-2 py-1 rounded <?php echo ($_SESSION['lang'] ?? 'zh') == 'en' ? 'bg-indigo-500 text-white' : 'text-gray-500 hover:text-indigo-500'; ?>">EN</a>
        </div>
    </div>

</aside>

<!-- Add Friend Modal (Hidden) -->
<div id="addFriendModal" class="fixed inset-0 z-50 hidden bg-black/30 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white/80 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-2xl max-w-sm w-full p-6 border border-white dark:border-gray-700">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white mb-4"><?php echo trans('add_friend_title'); ?></h3>
        <form action="index.php?route=check_user" method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 mb-1"><?php echo trans('input_username'); ?></label>
                <input type="text" name="username" required class="w-full px-4 py-2 rounded-xl bg-white dark:bg-gray-700 dark:text-white border border-gray-200 dark:border-gray-600 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 outline-none transition-all">
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeAddFriendModal()" class="flex-1 py-2 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors"><?php echo trans('cancel'); ?></button>
                <button type="submit" class="flex-1 py-2 bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl shadow-lg transition-all"><?php echo trans('find_chat'); ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddFriendModal() {
    document.getElementById('addFriendModal').classList.remove('hidden');
}
function closeAddFriendModal() {
    document.getElementById('addFriendModal').classList.add('hidden');
}

// --- Theme Logic ---
(function() {
    const toggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    // Check saved theme
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
    
    toggle.addEventListener('click', () => {
        if (html.classList.contains('dark')) {
            html.classList.remove('dark');
            localStorage.theme = 'light';
        } else {
            html.classList.add('dark');
            localStorage.theme = 'dark';
        }
    });

    // We also need to configure Tailwind if using CDN to interpret Class Strategy
    // This is often needed if default is media. But most CDNs handle 'class' if configured.
})();

// --- Sidebar Toggle Logic ---
function toggleSidebar() {
    const sidebar = document.getElementById('mainSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar.classList.contains('-translate-x-full')) {
        // Open
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
    } else {
        // Close
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
}

// --- GLOBAL WEBSOCKET (Sidebar) ---
(function() {
    const userId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    if (userId <= 0) return;

    let ws;
    const reconnectInterval = 3000;

    function connectGlobalWS() {
        // Use wss if https, but localhost is typically ws
        const isHttps = window.location.protocol === 'https:';
        const wsProtocol = isHttps ? 'wss://' : 'ws://';
        const wsHost = window.location.hostname;
        // Optimization: Use port 8080 for WS, but /ws proxy path for WSS is standard for Nginx setups
        const wsPath = isHttps ? '/ws' : ':8080'; 
        
        ws = new WebSocket(wsProtocol + wsHost + wsPath);

        ws.onopen = () => {
            console.log('Global WS Connected');
            ws.send(JSON.stringify({
                type: 'login',
                user_id: userId
            }));
            window.dispatchEvent(new CustomEvent('ws_open'));
        };

        ws.onmessage = (e) => {
            const packet = JSON.parse(e.data);
            if (!packet) return;

            if (packet.type === 'private_message') {
                handleNewPrivateMessage(packet.data);
            }
            if (packet.type === 'private_recall') {
                handleRecallMessage(packet);
            }
            if (packet.type === 'private_burn') {
                handleBurnMessage(packet);
            }
            if (packet.type === 'typing') {
                handleTypingMessage(packet);
            }
            if (packet.type === 'mark_read') {
                 window.dispatchEvent(new CustomEvent('private_read', { detail: packet }));
            }
        };

        ws.onclose = () => {
            setTimeout(connectGlobalWS, reconnectInterval);
        };
    }

    function handleNewPrivateMessage(msg) {
        const urlParams = new URLSearchParams(window.location.search);
        const route = urlParams.get('route');
        const currentChatId = urlParams.get('user_id');

        // Check if I am in chat with the Sender OR if I am the Sender (echo) and in chat with Receiver
        // Wait, msg.sender_id is the Sender.
        // If I am sender, msg.sender_id is ME.
        // I need to know who the receiver was to know if I should show it in current window.
        // But the WS packet structure for private_message (echo) usually only contains the message data.
        // server.php sends: 'data' => $msgData. $msgData usually has receiver_id.
        // Let's check server.php $data['data'] construction in views/private/chat.php send logic?
        // In views/private/chat.php send logic (WS send), I sent:
        // receiver_id: partner_id, data: { sender_id: ME, content: ... }
        // BUT server.php takes 'data' and passes it through.
        // It does NOT automatically add receiver_id to the inner data if I didn't put it there.
        // In chat.php, I constructed data: { sender_id: ME, content: ... }
        // I did NOT put receiver_id inside 'data'.
        // So here in sidebar, I can't check if I am chatting with the receiver if I don't know who the receiver is from the packet.
        
        // HOWEVER, if msg.sender_id == ME, then I know it's an echo.
        // If I am currently at ?route=chat_private&user_id=PARTNER
        // I want to show message if:
        // 1. msg.sender_id == PARTNER (Incoming)
        // 2. msg.sender_id == ME (Outgoing Echo) AND I am chatting with the intended receiver.
        
        // To support #2, I strictly need to know the receiver_id in the packet.
        // I must update views/private/chat.php to include receiver_id in the inner data object sent to WS.
        
        // TEMPORARY FIX: If sender is ME, just dispatch it?
        // If I have multiple tabs open with different chats?
        // Tab A: Chat with Bob. Tab B: Chat with Alice.
        // Send to Bob from Tab A. Echo arrives at Tab B.
        // Tab B checks: sender_id = Me.
        // Tab B is chatting with Alice. Should it show? NO.
        // So I MUST know receiver_id.
        
        // Plan:
        // 1. Update views/private/chat.php to include receiver_id in 'data'.
        // 2. Update this sidebar logic to check receiver_id.
        
        // For now, let's just implement the check assuming msg.receiver_id exists.
        
        // If it's incoming (sender != me), check sender_id vs currentChatId.
        // If it's outgoing (sender == me), check receiver_id vs currentChatId.
        
        const isIncoming = msg.sender_id != <?php echo $_SESSION['user_id']; ?>;
        const relevantId = isIncoming ? msg.sender_id : (msg.receiver_id || 0);
        
        if (route === 'chat_private' && currentChatId == relevantId) {
            const event = new CustomEvent('new_private_message', { detail: msg });
            window.dispatchEvent(event);
            return; 
        }
        
        if (isIncoming) {
            updateSidebarBadge(msg);
        }
    }

    function updateSidebarBadge(msg) {
        const link = document.querySelector(`a[href*="user_id=${msg.sender_id}"]`);
        if (link) {
            const p = link.querySelector('p');
            if(p) p.textContent = msg.content || '[图片]';
            
            let badge = link.querySelector('.bg-red-500');
            if (badge) {
                let count = parseInt(badge.textContent) || 0;
                badge.textContent = count + 1;
            } else {
                const imgCont = link.querySelector('.relative.w-10');
                if(imgCont) {
                    const b = document.createElement('span');
                    b.className = 'absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-4 h-4 flex items-center justify-center rounded-full border border-white';
                    b.textContent = '1';
                    imgCont.appendChild(b);
                }
            }
        }
    }

    function handleRecallMessage(packet) {
        const event = new CustomEvent('private_recall', { detail: packet.message_id });
        window.dispatchEvent(event);
    }

    function handleBurnMessage(packet) {
        const event = new CustomEvent('private_burn', { 
            detail: { 
                message_id: packet.message_id, 
                duration: packet.duration 
            } 
        });
        window.dispatchEvent(event);
    }

    function handleTypingMessage(packet) {
         const event = new CustomEvent('private_typing', { detail: packet.sender_id });
         window.dispatchEvent(event);
    }

    connectGlobalWS();
    window.globalWS = ws;

    // --- SEARCH LOGIC ---
    const searchInput = document.getElementById('globalSearchInput');
    const searchResults = document.getElementById('searchResults');
    let searchDebounce;

    if(searchInput && searchResults) {
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchDebounce);
            const keyword = e.target.value.trim();
            
            if(keyword.length === 0) {
                searchResults.classList.add('hidden');
                searchResults.innerHTML = '';
                return;
            }

            searchDebounce = setTimeout(async () => {
                try {
                    const res = await fetch(`index.php?route=search_messages&keyword=${encodeURIComponent(keyword)}`);
                    const data = await res.json();
                    
                    searchResults.innerHTML = '';
                    if(data.success && data.results.length > 0) {
                        data.results.forEach(item => {
                            const div = document.createElement('div');
                            div.className = 'p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg cursor-pointer flex gap-3 items-center border-b border-gray-100 dark:border-gray-700 last:border-0';
                            
                            const link = item.type === 'public' 
                                ? `index.php?route=chat&room_id=${item.source_id}` 
                                : `index.php?route=chat_private&user_id=${item.source_id}`;
                            
                            div.onclick = () => window.location.href = link;
                            
                            div.innerHTML = `
                                <img src="uploads/avatars/${item.avatar || 'default.jpg'}" class="w-8 h-8 rounded-full object-cover">
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[100px]">${item.source_name}</span>
                                        <span class="text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-600 px-1 rounded">${item.type === 'public' ? '群聊' : '私聊'}</span>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">${item.content}</p>
                                </div>
                            `;
                            searchResults.appendChild(div);
                        });
                        searchResults.classList.remove('hidden');
                    } else {
                        searchResults.innerHTML = '<div class="text-center text-xs text-gray-400 p-2">未找到相关消息</div>';
                        searchResults.classList.remove('hidden');
                    }
                } catch(err) {
                    console.error('Search failed', err);
                }
            }, 300);
        });
        
        // Blur to close (delayed)
        searchInput.addEventListener('blur', () => {
            setTimeout(() => {
                searchResults.classList.add('hidden');
            }, 200);
        });
        searchInput.addEventListener('focus', () => {
             if(searchInput.value.trim().length > 0) {
                 searchResults.classList.remove('hidden');
             }
        });
    }

})();
</script>
