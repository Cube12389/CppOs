<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'core/Storage.php'; $siteName = Storage::getSetting('site_name'); ?>
    <title><?php echo htmlspecialchars($room['name']); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.6/viewer.min.js"></script>
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
                        ownMessage: '#eef2ff',
                        otherMessage: '#ffffff'
                    },
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .scrollbar-hide {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
            .scrollbar-hide::-webkit-scrollbar {
                display: none;
            }
            .message-appear {
                animation: fadeIn 0.3s ease forwards;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }
    </style>
</head>
<!-- Gradient Background -->
<body class="bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-[100dvh] flex h-[100dvh] overflow-hidden text-gray-800 dark:text-gray-100 font-sans transition-colors duration-300">
    
    <!-- Shared Sidebar -->
    <?php require 'views/partials/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="flex-1 min-w-0 flex flex-col relative w-full h-[100dvh] overflow-hidden bg-white/40 dark:bg-gray-900/50 backdrop-blur-xl md:rounded-2xl shadow-2xl mr-0 md:my-2 md:mr-2 border border-white/20 dark:border-gray-700">
        
        <!-- Chat Header -->
        <header class="bg-white/60 dark:bg-gray-800/60 backdrop-blur-md px-6 py-4 flex justify-between items-center border-b border-white/20 dark:border-gray-700 shadow-sm z-10">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="md:hidden text-gray-600 dark:text-gray-300 hover:text-primary transition-colors">
                     <i class="fa fa-bars text-xl"></i>
                </button>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-primary to-secondary flex items-center justify-center text-white shadow-lg">
                        <i class="fa fa-comments text-lg"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-lg text-gray-800 dark:text-white leading-tight">
                            <?php echo htmlspecialchars($room['name']); ?>
                        </h1>
                        <p class="text-xs text-gray-500 flex items-center gap-1">
                            <span id="connectionStatus" class="w-2 h-2 bg-gray-400 rounded-full"></span> 
                            <span id="connectionText">连接中...</span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <!-- Action Buttons with Tooltips -->
                <?php if ($room['created_by'] == $user_id): ?>
                    <button onclick="openRoomSettings()" class="group relative w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 hover:bg-gray-800 hover:text-white dark:hover:bg-gray-600 transition-all shadow-sm">
                        <i class="fa fa-cog"></i>
                        <span class="absolute top-10 right-0 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none">设置</span>
                    </button>
                    <button id="dissolveRoomBtn" class="group relative w-9 h-9 flex items-center justify-center rounded-full bg-red-50 dark:bg-red-900/20 text-red-500 hover:bg-red-500 hover:text-white transition-all shadow-sm">
                        <i class="fa fa-trash"></i>
                        <span class="absolute top-10 right-0 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none">解散房间</span>
                    </button>
                <?php endif; ?>

                <button onclick="copyInviteLink()" class="group relative w-9 h-9 flex items-center justify-center rounded-full bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white transition-all shadow-sm">
                    <i class="fa fa-user-plus"></i>
                    <span class="absolute top-10 right-0 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none">邀请加入</span>
                </button>

                <button id="leaveRoomBtn" class="group relative w-9 h-9 flex items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-800 hover:text-white transition-all shadow-sm">
                    <i class="fa fa-sign-out"></i>
                    <span class="absolute top-10 right-0 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none">退出房间</span>
                </button>
                
                <button id="toggleMembersBtn" class="md:hidden w-9 h-9 flex items-center justify-center rounded-full bg-indigo-50 text-indigo-500 hover:bg-indigo-500 hover:text-white transition-all shadow-sm">
                    <i class="fa fa-users"></i>
                </button>
            </div>
        </header>

        <div class="flex flex-1 overflow-hidden">
            <!-- Messages Area -->
            <div id="messagesContainer" class="flex-1 p-6 overflow-y-auto w-full">
                <!-- Header divider -->
                <div class="flex justify-center my-6 opacity-60">
                    <span class="bg-white/60 backdrop-blur-sm px-4 py-1.5 rounded-full text-xs font-medium text-gray-500 shadow-sm border border-white/40">
                        今天 <?php echo date('H:i'); ?>
                    </span>
                </div>
                <!-- JS will inject messages here -->
            </div>
            
            <!-- Members Panel (Right Sidebar) -->
            <aside id="membersPanel" class="hidden md:block w-64 bg-white/30 dark:bg-gray-800/30 backdrop-blur-md border-l border-white/20 dark:border-gray-700 p-4 transition-all duration-300">
                <h2 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-4 px-2">
                    成员 - <span id="memberCount"><?php echo count($members); ?></span>
                </h2>
                
                <div class="space-y-1 overflow-y-auto h-full pb-10">
                    <?php foreach ($members as $member): ?>
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/40 dark:hover:bg-gray-700/50 transition-colors group cursor-default">
                            <div class="relative">
                                <img src="<?php echo AVATAR_DIR . $member['avatar']; ?>" class="w-9 h-9 rounded-full object-cover border border-white dark:border-gray-600 shadow-sm">
                                <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full <?php echo $member['is_online'] ? 'bg-green-500' : 'bg-gray-300'; ?> border-2 border-white"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate flex items-center gap-1">
                                    <?php echo htmlspecialchars($member['display_name']); ?>
                                </div>
                                <div class="text-[10px] text-gray-400 truncate">
                                    <?php echo $member['is_online'] ? '在线' : '离线'; ?>
                                </div>
                            </div>
                            <div class="ml-auto flex items-center gap-1 relative">
                                <?php if ($member['id'] != $user_id): ?>
                                    <?php if ($room['created_by'] == $user_id): ?>
                                        <!-- Admin Menu Trigger (Data Attributes) -->
                                        <button class="admin-menu-trigger w-7 h-7 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-300 transition-colors"
                                                data-id="<?php echo $member['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($member['display_name'] ?? $member['username']); ?>"
                                                data-muted="<?php echo $member['is_muted'] ?? 0; ?>">
                                            <i class="fa fa-ellipsis-h text-xs pointer-events-none"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="index.php?route=chat_private&user_id=<?php echo $member['id']; ?>" 
                                           class="w-7 h-7 flex items-center justify-center rounded-full bg-indigo-50 dark:bg-gray-700 text-indigo-500 dark:text-gray-300 hover:bg-indigo-500 hover:text-white transition-all shadow-sm"
                                           title="发起私聊">
                                            <i class="fa fa-envelope text-xs"></i>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </aside>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white/60 dark:bg-gray-800/60 backdrop-blur-md border-t border-white/20 dark:border-gray-700">
             <form id="messageForm" class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl shadow-inner focus-within:ring-2 focus-within:ring-indigo-300 transition-all">
                
                <!-- Wrapper: Column Layout -->
                <div class="flex flex-col gap-2 p-3">
                    
                    <!-- Preview (Moved Inside) -->
                    <div id="imagePreviewContainer" class="hidden p-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 rounded-t-xl relative">
                        <div class="relative inline-block border border-gray-200 rounded-lg overflow-hidden group w-20">
                            <img id="imagePreview" src="" class="h-20 w-auto object-cover">
                            <button id="cancelImage" type="button" class="absolute inset-0 bg-black/40 flex items-center justify-center text-white opacity-0 group-hover:opacity-100 transition-opacity">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Audio Preview -->
                    <div id="audioPreviewContainer" class="hidden p-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 rounded-t-xl relative">
                         <div class="flex items-center gap-3">
                             <audio id="audioPreview" controls class="h-8 max-w-[200px]"></audio>
                             <button type="button" id="cancelAudio" class="text-red-500 hover:text-red-600 p-1">
                                 <i class="fa fa-times-circle text-xl"></i>
                             </button>
                         </div>
                    </div>

                    <!-- Video Preview -->
                    <div id="videoPreviewContainer" class="hidden p-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 rounded-t-xl relative">
                        <div class="relative inline-block border border-gray-200 rounded-lg overflow-hidden group">
                            <video id="videoPreview" controls class="h-32 w-auto bg-black"></video>
                            <button id="cancelVideo" type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-1 shadow-md hover:bg-red-600 w-6 h-6 flex items-center justify-center transition-transform hover:scale-110 opacity-0 group-hover:opacity-100">
                                <i class="fa fa-times text-xs"></i>
                            </button>
                        </div>
                        <div id="videoInfo" class="text-xs text-gray-400 mt-1 font-mono"></div>
                    </div>

                    <!-- Top Row: Toolbar -->
                    <div class="flex items-center gap-4 overflow-x-auto no-scrollbar pb-1">
                        <!-- Emoji -->
                        <button type="button" id="emojiBtn" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 hover:text-yellow-600 dark:hover:text-yellow-400 text-gray-500 dark:text-gray-400 rounded-full transition-colors flex-shrink-0">
                            <i class="fa fa-smile-o text-sm"></i>
                            <span class="text-xs font-medium">表情</span>
                        </button>

                        <!-- Image -->
                        <button type="button" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 hover:text-indigo-600 dark:hover:text-indigo-400 text-gray-500 dark:text-gray-400 rounded-full transition-colors relative flex-shrink-0" id="imageBtn">
                             <i class="fa fa-picture-o text-sm"></i>
                             <span class="text-xs font-medium">图片</span>
                             <input type="file" id="imageUpload" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer">
                        </button>

                        <!-- Video -->
                        <button type="button" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 text-gray-500 dark:text-gray-400 rounded-full transition-colors relative flex-shrink-0" id="videoBtn" title="发送视频">
                             <i class="fa fa-video-camera text-sm"></i>
                             <span class="text-xs font-medium">视频</span>
                             <input type="file" id="videoUpload" accept="video/mp4,video/webm,video/quicktime" class="absolute inset-0 opacity-0 cursor-pointer">
                        </button>
                        
                        <!-- Audio / Mic -->
                         <button type="button" id="micBtn" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-green-50 dark:hover:bg-green-900/30 hover:text-green-600 dark:hover:text-green-400 text-gray-500 dark:text-gray-400 rounded-full transition-colors flex-shrink-0 relative" title="录制语音">
                             <i class="fa fa-microphone text-sm"></i>
                             <span class="text-xs font-medium" id="micText">语音</span>
                         </button>

                        <!-- Burn Timer -->
                        <div class="relative flex-shrink-0">
                            <button type="button" id="burnTimerBtn" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-orange-50 dark:hover:bg-orange-900/30 hover:text-orange-600 dark:hover:text-orange-400 text-gray-500 dark:text-gray-400 rounded-full transition-colors" title="阅后即焚">
                                <i class="fa fa-fire text-sm"></i>
                                <span class="text-xs font-medium">阅后即焚</span>
                                <span id="burnBadge" class="absolute -top-1 -right-1 bg-orange-500 text-white text-[10px] px-1.5 h-4 flex items-center justify-center rounded-full hidden border border-white">0s</span>
                            </button>
                            <input type="hidden" id="burnTimeInput" value="0">
                        </div>
                    </div>

                    <!-- Bottom Row: Input + Send -->
                    <div class="flex items-end gap-2">
                         <textarea id="messageInput" rows="1" class="flex-1 bg-gray-100 dark:bg-gray-700 border-0 focus:ring-2 focus:ring-indigo-500 rounded-xl text-gray-800 dark:text-gray-100 placeholder-gray-400 px-4 py-3 resize-none max-h-32 transition-all" placeholder="发消息..."></textarea>
                        
                        <button type="submit" class="p-3 bg-indigo-500 text-white rounded-xl shadow-md hover:bg-indigo-600 hover:scale-105 active:scale-95 transition-all flex-shrink-0 w-12 h-12 flex items-center justify-center">
                            <i class="fa fa-paper-plane text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Emoji Picker (Absolute to Form) -->
                <div id="emojiPicker" class="absolute bottom-full left-0 mb-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/40 dark:border-gray-700 shadow-2xl rounded-2xl p-3 w-72 h-60 overflow-y-auto hidden z-50 grid grid-cols-8 gap-1 custom-scrollbar">
                   <!-- Emojis injected via JS -->
                </div>
            </form>
            <div class="text-center mt-2 text-[10px] text-gray-400">
                支持: **加粗**, *斜体*, `代码`
            </div>
        </div>
    </main>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" onclick="closeRoomSettings()"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md p-4">
             <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-white/50 dark:border-gray-700 p-6 relative">
                 <button onclick="closeRoomSettings()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fa fa-times"></i>
                </button>
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-6">房间设置</h2>
                
                <form id="editRoomForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">房间名称</label>
                        <input type="text" name="room_name" value="<?php echo htmlspecialchars($room['name']); ?>" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-2 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">描述</label>
                        <textarea name="room_description" rows="2" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-2 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all"><?php echo htmlspecialchars($room['description']); ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">新密码 (留空不修改，输入空格清空)</label>
                        <input type="password" name="room_password" placeholder="若不修改请留空" class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl px-4 py-2 dark:text-white focus:ring-2 focus:ring-indigo-500 transition-all">
                        <p class="text-[10px] text-gray-400 mt-1">* 若想设为公开，请输入一个空格</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-indigo-500 text-white font-bold py-2 rounded-xl hover:bg-indigo-600 transition-all shadow-md">保存修改</button>
                </form>
             </div>
        </div>
    <!-- Global Burn Menu (Moved out to avoid clipping) -->
    <div id="burnMenu" class="fixed w-32 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl hidden z-[100] overflow-hidden">
        <div class="text-xs font-bold text-gray-400 px-3 py-2 bg-gray-50 dark:bg-gray-700">销毁时间</div>
        <button type="button" onclick="setBurnTime(0)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">关闭</button>
        <button type="button" onclick="setBurnTime(10)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">10秒</button>
        <button type="button" onclick="setBurnTime(30)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">30秒</button>
        <button type="button" onclick="setBurnTime(60)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">1分钟</button>
    </div>
    


    <script>
        // GLOBALS
        const roomId = <?php echo json_encode((int)$room_id); ?>;
        const userId = <?php echo json_encode((int)$user_id); ?>;
        const username = <?php echo json_encode($_SESSION['nickname'] ?? $username); ?>;
        const avatar = <?php echo json_encode($avatar); ?>;
        const csrfToken = <?php echo json_encode(generate_csrf_token()); ?>;
        const isOwner = <?php echo json_encode($room['created_by'] == $user_id); ?>;
        let lastMessageId = 0;
        let isAtBottom = true;
        let isLoadingHistory = false;
        let firstMessageId = 0; // Smallest ID loaded
        let ws;
        let reconnectInterval = 3000;
        
        // Audio Globals
        let mediaRecorder;
        let audioChunks = [];
        let audioBlob = null;
        let viewer;

        // Emojis
        const commonEmojis = [
            '😀','😂','🤣','😉','😊','😎','😍','😘','🤪','😡',
            '😭','😱','👍','👎','👋','👌','🙏','🎉','❤️','💔',
            '🔥','✨','⭐','🌙','☀️','☁️','🍎','🍔','🍺','🚗',
            '✈️','🚀','💡','💻','📱','📷','🎵','🎮','⚽','🏀'
        ];

        document.addEventListener('DOMContentLoaded', () => {
             // 1. Initial Load
             loadMessages(); 
             
             // 2. Connect WebSocket
             connectWS();

             // Viewer.js Init
             const gallery = document.getElementById('messagesContainer');
             viewer = new Viewer(gallery, {
                 url: 'data-original',
                 toolbar: {
                     zoomIn: 1, zoomOut: 1, oneToOne: 1, reset: 1, 
                     prev: 1, play: 1, next: 1, 
                     rotateLeft: 1, rotateRight: 1, flipHorizontal: 1, flipVertical: 1,
                 },
                 filter(image) {
                     return image.classList.contains('chat-image'); 
                 }
             });

             // 3. Fallback Polling (Reduced frequency to 60s)
             setInterval(loadNewMessages, 60000); 
             setInterval(refreshMembers, 60000);

             // DOM Elements Mapping
             const dom = {
                container: document.getElementById('messagesContainer'),
                form: document.getElementById('messageForm'),
                input: document.getElementById('messageInput'),
                fileInput: document.getElementById('imageUpload'),
                previewCont: document.getElementById('imagePreviewContainer'),
                previewImg: document.getElementById('imagePreview'),
                cancelImg: document.getElementById('cancelImage'),
                emojiBtn: document.getElementById('emojiBtn'),
                emojiPicker: document.getElementById('emojiPicker'),
                leaveBtn: document.getElementById('leaveRoomBtn'),
                dissolveBtn: document.getElementById('dissolveRoomBtn'),
                membersPanel: document.getElementById('membersPanel'),
                toggleMembers: document.getElementById('toggleMembersBtn'),
                videoInput: document.getElementById('videoUpload'),
                videoBtn: document.getElementById('videoBtn'),
                videoPreview: document.getElementById('videoPreview'),
                videoPreviewCont: document.getElementById('videoPreviewContainer'),
                cancelVideoBtn: document.getElementById('cancelVideo'),
                micBtn: document.getElementById('micBtn'),
                micText: document.getElementById('micText'),
                audioPreview: document.getElementById('audioPreview'),
                audioPreviewCont: document.getElementById('audioPreviewContainer'),
                cancelAudioBtn: document.getElementById('cancelAudio')
             };

             // Scroll Listener
             dom.container.addEventListener('scroll', function() {
                isAtBottom = (this.scrollHeight - this.scrollTop - this.clientHeight) < 100;
             });

             // Auto Resize
             dom.input.addEventListener('input', function() {
                 this.style.height = 'auto';
                 this.style.height = (this.scrollHeight) + 'px';
                 if(this.value === '') this.style.height = 'auto';
             });

             // Submit Form
             dom.form.addEventListener('submit', (e) => {
                 e.preventDefault();
                 dom.input.style.height = 'auto';
                 sendMessage();
             });

             // Image Preview
             dom.fileInput.addEventListener('change', (e) => {
                 if(e.target.files[0]) {
                     const reader = new FileReader();
                     reader.onload = (ev) => {
                         dom.previewImg.src = ev.target.result;
                         dom.previewCont.classList.remove('hidden');
                     }
                     reader.readAsDataURL(e.target.files[0]);
                 }
             });
             dom.cancelImg.addEventListener('click', () => {
                 dom.previewImg.src = '';
                 dom.previewCont.classList.add('hidden');
                 dom.fileInput.value = '';
             });

             // Video Preview Logic
             if(dom.videoInput) {
                 dom.videoInput.addEventListener('change', function() {
                     if (this.files && this.files[0]) {
                         const file = this.files[0];
                         
                         // UI Highlight
                         dom.videoBtn.classList.add('text-red-600', 'bg-red-100');
                         const icon = dom.videoBtn.querySelector('i');
                         if(icon) icon.className = 'fa fa-check-circle text-xl';
                         
                         // Preview
                         const url = URL.createObjectURL(file);
                         if(dom.videoPreview) {
                             dom.videoPreview.src = url;
                             dom.videoPreviewCont.classList.remove('hidden');
                             // Info
                             const size = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                             document.getElementById('videoInfo').innerText = `${file.name} (${size})`;
                         }
                     } else {
                         resetVideoBtn();
                     }
                 });
             }
             
             if(dom.cancelVideoBtn) {
                 dom.cancelVideoBtn.addEventListener('click', function() {
                     resetVideoBtn();
                 });
             }
             
             window.resetVideoBtn = function() {
                 if(!dom.videoBtn) return;
                 dom.videoBtn.classList.remove('text-red-600', 'bg-red-100');
                 const icon = dom.videoBtn.querySelector('i');
                 if(icon) icon.className = 'fa fa-video-camera text-xl';
                 if(dom.videoInput) dom.videoInput.value = '';
                 
                 // Clear Preview
                 if(dom.videoPreview) dom.videoPreview.src = '';
                 if(dom.videoPreviewCont) dom.videoPreviewCont.classList.add('hidden');
             };

             // Emoji
             initEmojiPicker();
             dom.emojiBtn.addEventListener('click', (e) => {
                 e.stopPropagation();
                 dom.emojiPicker.classList.toggle('hidden');
             });
             document.addEventListener('click', (e) => {
                 if(!dom.emojiPicker.contains(e.target) && e.target !== dom.emojiBtn) {
                     dom.emojiPicker.classList.add('hidden');
                 }
             });

             // Buttons
             dom.leaveBtn.addEventListener('click', leaveRoom);
             if(dom.dissolveBtn) dom.dissolveBtn.addEventListener('click', deleteRoom);
             if(dom.toggleMembers) {
                 dom.toggleMembers.addEventListener('click', () => {
                     dom.membersPanel.classList.toggle('hidden');
                     dom.membersPanel.classList.toggle('fixed');
                     dom.membersPanel.classList.toggle('inset-0');
                     dom.membersPanel.classList.toggle('z-50');
                     dom.membersPanel.classList.toggle('bg-white');
                 });
             }
             
             // --- Audio Logic ---
             if(dom.micBtn) {
                 dom.micBtn.addEventListener('click', async (e) => {
                     e.preventDefault();
                     
                     // Security Check: HTTPS required for enumerateDevices/getUserMedia
                     if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                         alert('无法访问麦克风。\n\n原因：浏览器安全限制，语音录制功能仅支持 HTTPS 协议或 localhost。\n\n解决方法：请使用 HTTPS 访问此网站。');
                         return;
                     }

                     if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                         try {
                             const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                             mediaRecorder = new MediaRecorder(stream);
                             audioChunks = [];
                             
                             mediaRecorder.ondataavailable = (e) => {
                                 audioChunks.push(e.data);
                             };
                             
                             mediaRecorder.onstop = () => {
                                 audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                                 const audioUrl = URL.createObjectURL(audioBlob);
                                 dom.audioPreview.src = audioUrl;
                                 dom.audioPreviewCont.classList.remove('hidden');
                                 
                                 dom.micBtn.classList.remove('bg-red-500', 'text-white', 'animate-pulse');
                                 dom.micBtn.classList.add('bg-gray-100', 'text-gray-500');
                                 dom.micText.innerText = '语音';
                             };
                             
                             mediaRecorder.start();
                             
                             dom.micBtn.classList.remove('bg-gray-100', 'text-gray-500');
                             dom.micBtn.classList.add('bg-red-500', 'text-white', 'animate-pulse');
                             dom.micText.innerText = '停止';
                             
                         } catch (err) {
                             console.error('Mic Error:', err);
                             alert('无法访问麦克风: ' + err.message);
                         }
                     } else {
                         mediaRecorder.stop();
                         mediaRecorder.stream.getTracks().forEach(track => track.stop());
                     }
                 });
                 
                 dom.cancelAudioBtn.addEventListener('click', () => {
                     dom.audioPreview.src = '';
                     dom.audioPreviewCont.classList.add('hidden');
                     audioBlob = null;
                     audioChunks = [];
                 });
             }
        });

        // --- WebSocket Logic ---
        function connectWS() {
            const statusDot = document.getElementById('connectionStatus');
            const statusText = document.getElementById('connectionText');

            const isHttps = window.location.protocol === 'https:';
            const wsProtocol = isHttps ? 'wss://' : 'ws://';
            const wsHost = window.location.hostname;
            const wsPath = isHttps ? '/ws' : ':8080';
            
            ws = new WebSocket(wsProtocol + wsHost + wsPath);
            
            ws.onopen = () => {
                console.log('WS Connected');
                statusDot.className = 'w-2 h-2 bg-green-500 rounded-full';
                statusText.innerText = '实时连接';
                // Join Room
                ws.send(JSON.stringify({
                    type: 'join',
                    room_id: roomId,
                    user_id: userId
                }));
            };

            ws.onmessage = (e) => {
                const packet = JSON.parse(e.data);
                if (!packet) return;

                switch(packet.type) {
                    case 'new_message':
                        const container = document.getElementById('messagesContainer');
                        const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 150;
                        
                        addMessageToDOM(packet.data);
                        
                        if (isAtBottom) scrollToBottom();
                        if(viewer) viewer.update();
                        break;
                    case 'update_room':
                        if(packet.room_id == roomId) {
                             window.location.reload();
                        }
                        break;
                    case 'mute':
                        if (packet.data && packet.data.user_id == userId) {
                             const isMuted = packet.data.is_muted == 1;
                             document.getElementById('messageInput').disabled = isMuted;
                             document.getElementById('messageInput').placeholder = isMuted ? '您已被禁言' : '发消息...';
                             alert(isMuted ? '您已被房主禁言' : '您已解除禁言');
                             refreshMembers();
                        } else {
                            // Update other's status in list
                            refreshMembers();
                        }
                        break;
                    case 'recall':
                        // Reload to update state
                        loadMessages(); 
                        break;
                    case 'kick':
                         if (packet.data && packet.data.user_id == userId) {
                             alert('您已被踢出房间');
                             window.location.href = 'index.php?route=rooms';
                         }
                         refreshMembers();
                         break;
                }
            };

            ws.onclose = () => {
                console.log('WS Disconnected');
                statusDot.className = 'w-2 h-2 bg-red-400 rounded-full';
                statusText.innerText = '离线 (尝试重连...)';
                setTimeout(connectWS, reconnectInterval);
            };
            
            ws.onerror = (err) => {
                console.error('WS Error', err);
                ws.close();
            };
        }

        // --- Core Functions ---

        function sendMessage() {
            const content = document.getElementById('messageInput').value.trim();
            const file = document.getElementById('imageUpload').files[0];
            const videoInput = document.getElementById('videoUpload');
            const videoFileItem = videoInput ? videoInput.files[0] : null;
            
            if(!content && !file && !videoFileItem) return;

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('room_id', roomId);
            formData.append('content', content);
            if(file) formData.append('image', file);
            
            const videoFile = document.getElementById('videoUpload').files[0];
            if(videoFile) formData.append('video', videoFile);
            
            if(audioBlob) formData.append('audio', audioBlob, 'voice.webm');
            
            // UI Feedback: Sending
            const sendBtn = document.querySelector('button[type="submit"]');
            const originalIcon = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
            
            // Burn-on-Read
            const burnVal = document.getElementById('burnTimeInput').value;
            formData.append('burn_after_read', burnVal);

            fetch('index.php?route=chat_send_message', { method: 'POST', body: formData })
            .then(r=>r.json())
            .then(d => {
                 // Reset UI
                 sendBtn.disabled = false;
                 sendBtn.innerHTML = originalIcon;

                 if(d.success) {
                     // Clear Input
                     document.getElementById('messageInput').value = '';
                     document.getElementById('cancelImage').click();
                     if(typeof resetVideoBtn === 'function') resetVideoBtn(); 
                     
                     // Reset Audio
                     const audioP = document.getElementById('audioPreview');
                     const audioC = document.getElementById('audioPreviewContainer');
                     if(audioP) audioP.src = '';
                     if(audioC) audioC.classList.add('hidden');
                     audioBlob = null;
                     audioChunks = [];
                     
                     // WS Broadcast
                     if(ws && ws.readyState === WebSocket.OPEN) {
                         ws.send(JSON.stringify({
                             type: 'new_message',
                             data: d.message // Full message object from PHP
                         }));
                     }
                 } else {
                     alert(d.error || '发送失败');
                 }
            })
            .catch(e => {
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalIcon;
                console.error(e);
                alert('网络错误');
            });
        }
        

        // Toggle Logic
        const burnBtn = document.getElementById('burnTimerBtn');
        const burnMenu = document.getElementById('burnMenu');
        
        if(burnBtn && burnMenu) {
            burnBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (burnMenu.classList.contains('hidden')) {
                    // Show & Position
                    const rect = burnBtn.getBoundingClientRect();
                    const bottomSpace = window.innerHeight - rect.top;
                    
                    burnMenu.style.bottom = (bottomSpace + 8) + 'px';
                    burnMenu.style.left = rect.left + 'px';
                    burnMenu.style.top = 'auto'; 
                    burnMenu.classList.remove('hidden');
                } else {
                    burnMenu.classList.add('hidden');
                }
            });
            
            // Adjust on scroll/resize? maybe close it
            // window.addEventListener('resize', () => burnMenu.classList.add('hidden'));
            
            document.addEventListener('click', (e) => {
                if(!burnMenu.contains(e.target) && !burnBtn.contains(e.target)) {
                     burnMenu.classList.add('hidden');
                }
            });
        }

        window.setBurnTime = function(val) {
             document.getElementById('burnTimeInput').value = val;
             updateBurnIcon(val);
             if(burnMenu) burnMenu.classList.add('hidden');
        };

        function updateBurnIcon(val) {
            const btn = document.getElementById('burnTimerBtn');
            const badge = document.getElementById('burnBadge');
            
            if (val > 0) {
                btn.classList.add('bg-orange-50', 'text-orange-600');
                btn.classList.remove('bg-gray-100', 'text-gray-500');
                badge.innerText = val + 's';
                badge.classList.remove('hidden');
            } else {
                btn.classList.remove('bg-orange-50', 'text-orange-600');
                btn.classList.add('bg-gray-100', 'text-gray-500');
                badge.classList.add('hidden');
            }
        }

        function loadMessages() {
             // Initial Load: Latest 20
             fetch(`index.php?route=chat_get_messages&room_id=${roomId}`)
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        const container = document.getElementById('messagesContainer');
                         container.innerHTML = `
                            <div class="flex justify-center my-6 opacity-60">
                                <span class="bg-white/60 backdrop-blur-sm px-4 py-1.5 rounded-full text-xs font-medium text-gray-500 shadow-sm border border-white/40">
                                    今天 ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                </span>
                            </div>
                         `;
                         
                        data.messages.forEach((m, idx) => {
                            if(idx === 0) firstMessageId = m.id; // Oldest
                            addMessageToDOM(m);
                            lastMessageId = Math.max(lastMessageId, m.id);
                        });
                        
                        scrollToBottom('instant');
                        
                        // Handle images
                        if (container) {
                            container.querySelectorAll('img').forEach(img => {
                                if (img.complete) return;
                                img.addEventListener('load', () => scrollToBottom('instant'));
                                img.addEventListener('error', () => scrollToBottom('instant'));
                            });
                            
                            // Scroll Listener for History
                            container.addEventListener('scroll', () => {
                                if(container.scrollTop === 0) {
                                    loadMoreMessages();
                                }
                            });
                        }

                        if(viewer) viewer.update();
                    }
                });
        }
        
        function loadMoreMessages() {
            if(isLoadingHistory || firstMessageId <= 1) return;
            isLoadingHistory = true;
            
            const container = document.getElementById('messagesContainer');
            const oldScrollHeight = container.scrollHeight;
            
            // Loader
            const loader = document.createElement('div');
            loader.className = 'text-center py-2 text-xs text-gray-400';
            loader.innerHTML = '<i class="fa fa-spinner fa-spin"></i> 加载中...';
            // Insert loader at top (after welcome text potentially, but for now simple prepend)
            container.prepend(loader);

            fetch(`index.php?route=chat_get_messages&room_id=${roomId}&before_id=${firstMessageId}`)
                .then(r => r.json())
                .then(data => {
                    loader.remove(); // Remove loader first
                    
                    if(data.success && data.messages.length > 0) {
                        // Data from server is Chronological: [Oldest ... Newest in batch]
                        // We need to insert them BEFORE the current messages.
                        // And we need to insert them in that order.
                        
                        const firstChild = container.firstChild; // Insertion point
                        
                        // BUT: logic above had data.messages reversed to ASC by backend.
                        // So data.messages is [Message 10, Message 11...].
                        // If we prepend Message 10, then Message 11... Message 11 will be "above" Message 10 if we use prepend? No.
                        // If we use insertBefore(node, firstChild) repeatedly:
                        // insert M10 before X -> [M10, X]
                        // insert M11 before X -> [M10, M11, X]
                        // YES. Correct.
                        
                        data.messages.forEach(m => {
                            if(m.id < firstMessageId) {
                                firstMessageId = m.id;
                            }
                            const div = createMessageElement(m);
                            container.insertBefore(div, firstChild); 
                        });
                        
                        // If firstChild was NOT null, it works. If null (empty), it appends.
                        // Wait, we removed loader. firstChild might be the welcome text or the first message.
                        // If we insertBefore the firstChild, we are preserving order.
                        
                        // Fix First ID logic:
                        if(data.messages[0].id < firstMessageId) firstMessageId = data.messages[0].id;

                        // Restore Scroll Position
                        const newScrollHeight = container.scrollHeight;
                        container.scrollTop = newScrollHeight - oldScrollHeight;
                    }
                    
                    isLoadingHistory = false;
                    if(viewer) viewer.update();
                })
                .catch(() => {
                    loader.remove();
                    isLoadingHistory = false;
                });
        }
        
        // Initial scroll when everything (including images) is loaded
        window.addEventListener('load', () => scrollToBottom('instant'));
        
        // Backup Polling
        function loadNewMessages() {
            fetch(`index.php?route=chat_get_messages&room_id=${roomId}&last_id=${lastMessageId}`)
                .then(r => r.json())
                .then(data => {
                    if(data.success && data.messages.length > 0) {
                        data.messages.forEach(m => {
                            addMessageToDOM(m);
                            lastMessageId = Math.max(lastMessageId, m.id);
                        });
                        if(isAtBottom) scrollToBottom();
                        if(viewer) viewer.update();
                    }
                });
        }

        // ... [Rest of Helper Functions Kept Same] ...
        // kickMember, deleteRoom, recallMessage need to broadcast too?
        // Ideally yes. But for now they rely on polling/refresh.
        // Let's add WS broadcast to them if we want perfection.
        // Server handles 'kick' and 'recall' types!
        
        function kickMember(id, name) {
             if(!confirm('Kick ' + name + '?')) return;
             const fd = new FormData(); fd.append('csrf_token', csrfToken); fd.append('room_id', roomId); fd.append('member_id', id);
             fetch('index.php?route=room_kick', { method:'POST', body: fd }).then(r=>r.json()).then(d=>{ 
                 if(d.success) {
                     ws.send(JSON.stringify({ type: 'kick', user_id: id })); // Notify Server
                     refreshMembers(); 
                 }
             });
        }
        function openBurnImage(el, msgId, seconds) {
             const img = el.querySelector('img');
             const label = el.querySelector('.bg-red-500');
             const timerDiv = el.querySelector('.countdown-timer');
             
             if (el.dataset.opened) return; // Already opened
             el.dataset.opened = "true";

             // 1. Notify Server
             const fd = new FormData();
             fd.append('message_id', msgId);
             fd.append('csrf_token', csrfToken);
             // We use 'sendBeacon' or fetch. fetch is fine.
             fetch('index.php?route=mark_public_opened', { method: 'POST', body: fd });

             // 2. Unblur & UI Change
             img.classList.remove('blur-xl', 'scale-110');
             label.classList.add('hidden');
             timerDiv.classList.remove('hidden');
             
             // 3. Countdown
             let left = seconds;
             timerDiv.innerText = left + 's';
             
             const interval = setInterval(() => {
                 left--;
                 if (left <= 0) {
                     clearInterval(interval);
                     el.innerHTML = `<div class="italic text-gray-400 text-sm bg-gray-50 border p-2 rounded">[图片已销毁]</div>`;
                     el.onclick = null;
                     el.classList.remove('cursor-pointer');
                 } else {
                     timerDiv.innerText = left + 's';
                 }
             }, 1000);

             // 4. Anti-Save (Basic)
             el.oncontextmenu = (e) => e.preventDefault();
        }

        // function viewImage(src) { ... } // Removed
        
        function recallMessage(id) {
             const fd = new FormData(); fd.append('csrf_token', csrfToken); fd.append('message_id', id);
             fetch('index.php?route=chat_recall', { method: 'POST', body: fd }).then(r=>r.json()).then(d=> { 
                 if(d.success) {
                     ws.send(JSON.stringify({ type: 'recall', message_id: id })); // Notify Server
                     loadMessages(); 
                 }
             });
        }
        
        function leaveRoom() {
            if(!confirm('确定要退出房间吗?')) return;
             const fd = new FormData();
             fd.append('room_id', roomId);
             fd.append('csrf_token', csrfToken);
             
             fetch('index.php?route=room_leave', { method:'POST', body: fd })
             .then(r=>r.json())
             .then(d => {
                 if(d.success) {
                     window.location.href='index.php?route=rooms';
                 } else {
                     alert(d.error || '退出失败');
                 }
             })
             .catch(e => {
                 console.error(e);
                 window.location.href='index.php?route=rooms'; // Fallback
             });
        }

        function deleteRoom() {
             if(!confirm('确定要解散房间吗? 此操作不可恢复。')) return;
             const fd = new FormData(); fd.append('csrf_token', csrfToken); fd.append('room_id', roomId);
             fetch('index.php?route=room_delete', { method:'POST', body: fd }).then(r=>r.json()).then(d=>{ if(d.success) window.location.href='index.php?route=rooms'; });
        }

        function refreshMembers() {
             fetch(`index.php?route=room_members&room_id=${roomId}`)
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        const list = document.querySelector('#membersPanel .space-y-1');
                        if(list) {
                            list.innerHTML = '';
                            document.getElementById('memberCount').textContent = data.members.length;
                            data.members.forEach(member => {
                                const isCurrentUser = member.id == userId;
                                const div = document.createElement('div');
                                div.className = 'flex items-center gap-3 p-2 rounded-lg hover:bg-white/40 transition-colors group cursor-default';
                                
                                let actionBtn = '';
                                if (!isCurrentUser) {
                                    if (isOwner) {
                                        // Admin Menu (Trigger Global)
                                        // Use data attributes for robustness
                                        actionBtn = `
                                            <button class="admin-menu-trigger w-7 h-7 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-300 transition-colors"
                                                    data-id="${member.id}"
                                                    data-name="${escapeHtml(member.display_name)}"
                                                    data-muted="${member.is_muted}">
                                                <i class="fa fa-ellipsis-h text-xs pointer-events-none"></i>
                                            </button>
                                        `;
                                    } else {
                                        actionBtn = `
                                            <a href="index.php?route=chat_private&user_id=${member.id}" target="_blank"
                                                    class="w-7 h-7 flex items-center justify-center rounded-full bg-indigo-50 dark:bg-gray-700 text-indigo-500 dark:text-gray-300 hover:bg-indigo-500 hover:text-white transition-all shadow-sm" title="发送私信">
                                                <i class="fa fa-envelope text-xs"></i>
                                            </a>
                                        `;
                                    }
                                }

                                div.innerHTML = `
                                    <div class="relative">
                                        <img src="${<?php echo json_encode(AVATAR_DIR); ?>}${member.avatar}" class="w-9 h-9 rounded-full object-cover border border-white dark:border-gray-600 shadow-sm">
                                        <span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ${member.is_online?'bg-green-500':'bg-gray-300'} border-2 border-white"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate flex items-center gap-1">
                                            ${escapeHtml(member.display_name || member.username)}
                                        </div>
                                        <div class="text-[10px] text-gray-400 truncate">${member.is_online?'在线':'离线'}</div>
                                    </div>
                                    <div class="ml-auto flex items-center gap-1">
                                        ${actionBtn}
                                    </div>
                                `;
                                list.appendChild(div);
                            });
                        }
                    }
                });
        }
        
        // Helper to Create Div (No Insertion)
        function createMessageElement(message) {
            const isMe = message.user_id == userId;
            const div = document.createElement('div');
            div.className = `flex gap-4 mb-6 message-appear ${isMe ? 'flex-row-reverse' : ''}`;
            div.id = `msg-${message.id}`;
            
            let content = '';
            if(message.is_recalled == 1) {
                content = `<div class="italic text-gray-400 text-sm bg-gray-100/50 px-3 py-1 rounded border border-gray-100">消息已撤回</div>`;
                div.innerHTML = `<div class="">${content}</div>`;
            } else {
                 let body = '';
                 if(message.image_url) {
                     if (message.is_burned) {
                          body += `<div class="mb-2 italic text-gray-400 text-sm bg-gray-50 border p-2 rounded">[图片已销毁]</div>`;
                     } else if (message.burn_after_read > 0) {
                         const burnTime = message.burn_after_read;
                         body += `
                            <div class="mb-2 relative cursor-pointer group" onclick="openBurnImage(this, ${message.id}, ${burnTime})">
                                <div class="relative overflow-hidden rounded-xl max-w-[280px]">
                                    <img src="${<?php echo json_encode(MESSAGE_IMAGE_DIR); ?>}${message.image_url}" class="rounded-xl filter blur-xl scale-110 transition-all duration-500">
                                    <div class="absolute inset-0 flex items-center justify-center bg-black/20 group-hover:bg-black/10 transition-colors">
                                        <div class="bg-red-500 text-white text-xs px-2 py-1 rounded shadow-lg flex items-center gap-1">
                                            <i class="fa fa-fire"></i> <span>阅后即焚 (${burnTime}s)</span>
                                        </div>
                                    </div>
                                    <div class="absolute top-2 right-2 bg-black/50 text-white text-xs px-2 py-1 rounded hidden countdown-timer"></div>
                                </div>
                            </div>
                         `;
                     } else {
                         body += `<div class="mb-2"><img src="${<?php echo json_encode(MESSAGE_IMAGE_DIR); ?>}${message.image_url}" data-original="${<?php echo json_encode(MESSAGE_IMAGE_DIR); ?>}${message.image_url}" class="chat-image rounded-xl max-w-[280px] shadow-sm hover:shadow-md transition-shadow cursor-pointer"></div>`;
                     }
                 }
                 if(message.video_url) {
                     body += `<div class="mb-2"><video src="${<?php echo json_encode(MESSAGE_VIDEO_DIR); ?>}${message.video_url}" controls class="rounded-xl max-w-[280px] shadow-sm"></video></div>`;
                 }
                 if(message.audio_url) {
                      body += `<div class="mb-2"><audio src="${<?php echo json_encode(MESSAGE_AUDIO_DIR); ?>}${message.audio_url}" controls class="max-w-[280px]"></audio></div>`;
                 }
                 if(message.content) {
                     body += `<p class="leading-relaxed">${parseMarkdown(escapeHtml(message.content))}</p>`;
                 }
                 
                 let recall = '';
                 const isRecent = (new Date().getTime() - new Date(message.created_at).getTime() < 120000);

                 if(isMe && isRecent) {
                      recall = `<button onclick="recallMessage(${message.id})" class="text-xs text-indigo-500 hover:text-indigo-700 ml-2 font-medium bg-indigo-50 px-2 py-0.5 rounded cursor-pointer" title="2分钟内可撤回">撤回</button>`;
                 } else if (isOwner && !isMe) {
                      // Owner Override Button
                      recall = `<button onclick="recallMessage(${message.id})" class="text-xs text-red-500 hover:text-red-700 ml-2 font-medium bg-red-50 px-2 py-0.5 rounded cursor-pointer" title="管理员撤回">撤回</button>`;
                 }

                 const avatarSrc = message.avatar ? `<?php echo AVATAR_DIR; ?>${message.avatar}` : 'default.jpg';
                 const userName = message.display_name || message.username || 'Unknown';

                 div.innerHTML = `
                    <div class="flex-shrink-0 flex flex-col items-center gap-1">
                        <img src="${avatarSrc}" class="w-10 h-10 rounded-full object-cover shadow-sm border-2 border-white">
                    </div>
                    
                    <div class="group max-w-[70%]">
                        <div class="flex items-baseline gap-2 mb-1 ${isMe ? 'flex-row-reverse' : ''}">
                             <span class="text-xs font-bold text-gray-700">${isMe ? '我' : escapeHtml(userName)}</span>
                             <span class="text-[10px] text-gray-400">${new Date(message.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</span>
                             ${recall}
                        </div>
                        
                        <div class="relative px-5 py-3 shadow-sm rounded-2xl ${isMe ? 'bg-indigo-500 text-white rounded-tr-none' : 'bg-white text-gray-800 rounded-tl-none'}">
                             ${body}
                        </div>
                    </div>
                 `;
            }
            return div;
        }

        function addMessageToDOM(message) {
            const div = createMessageElement(message);
            const existing = document.getElementById(`msg-${message.id}`);
            if(existing && existing.innerHTML !== div.innerHTML) {
                existing.parentNode.replaceChild(div, existing);
            } else if(!existing) {
                document.getElementById('messagesContainer').appendChild(div);
            }
        }
        
        function initEmojiPicker() {
            const picker = document.getElementById('emojiPicker');
            commonEmojis.forEach(emoji => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'text-xl hover:bg-gray-100 p-2 rounded transition-colors';
                btn.textContent = emoji;
                btn.onclick = (e) => {
                    e.preventDefault();
                    insertAtCursor(document.getElementById('messageInput'), emoji);
                    picker.classList.add('hidden');
                };
                picker.appendChild(btn);
            });
        }

        function insertAtCursor(el, text) {
            const val = el.value;
            const start = el.selectionStart;
            const end = el.selectionEnd;
            el.value = val.substring(0, start) + text + val.substring(end);
            el.selectionStart = el.selectionEnd = start + text.length;
            el.focus();
        }

        function scrollToBottom(behavior = 'auto') {
            const container = document.getElementById('messagesContainer');
            if (!container) return;
            
            // Scroll using scrollTop
            container.scrollTop = container.scrollHeight;
            
            // Also try scrolling last message into view
            const lastMsg = container.lastElementChild;
            if (lastMsg) {
                lastMsg.scrollIntoView({ behavior: behavior, block: 'end' });
            }
        }

        function escapeHtml(text) {
             const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
             return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
        }

        function parseMarkdown(text) {
             if(!text) return '';
             text = text.replace(/`([^`]+)`/g, '<code class="bg-black/10 rounded px-1 font-mono text-sm">$1</code>');
             text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
             text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
             return text;
        }

        window.addEventListener('beforeunload', function() {
              navigator.sendBeacon('index.php?route=user_status', new URLSearchParams(`room_id=${roomId}&status=0&csrf_token=${csrfToken}`));
        });
        function copyInviteLink() {
        const link = window.location.origin + window.location.pathname + '?route=invite&room_id=<?php echo $room_id; ?>';
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link).then(() => {
                showToast('邀请链接已复制');
            }).catch(err => {
                console.error('Copy failed', err);
                fallbackCopy(link);
            });
        } else {
            fallbackCopy(link);
        }
    }

    function fallbackCopy(text) {
        const textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showToast('邀请链接已复制');
        } catch (err) {
            console.error('Fallback copy failed', err);
            prompt("请手动复制链接:", text);
        }
        
        document.body.removeChild(textArea);
    }
    
    function showToast(msg) {
        const div = document.createElement('div');
        div.className = 'fixed bottom-20 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white px-4 py-2 rounded-lg text-sm shadow-xl z-50 animate-fade-in-up';
        div.innerText = msg;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 2000);
    }

        // Owner Actions
        function openRoomSettings() {
            document.getElementById('editRoomModal').classList.remove('hidden');
        }
        function closeRoomSettings() {
            document.getElementById('editRoomModal').classList.add('hidden');
        }
        
        document.getElementById('editRoomForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('csrf_token', csrfToken); 
            fd.append('room_id', roomId);
            
            fetch('index.php?route=room_update', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                if(d.success) {
                    alert('修改成功');
                    closeRoomSettings();
                    window.location.reload(); 
                } else {
                    alert(d.error);
                }
            });
        });

        function toggleMute(id, name, currentMuted) {
             const action = currentMuted ? '解除禁言' : '禁言';
             if(!confirm('确定要 ' + action + ' ' + name + ' 吗?')) return;
             
             const fd = new FormData(); fd.append('csrf_token', csrfToken); fd.append('room_id', roomId); fd.append('target_id', id);
             fetch('index.php?route=room_toggle_mute', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                 if(d.success) {
                      ws.send(JSON.stringify({ type: 'mute', user_id: id, is_muted: d.is_muted }));
                      refreshMembers(); 
                 } else {
                     alert(d.error || '操作失败');
                 }
             });
        }

        function transferOwner(id, name) {
             const verify = prompt('确定要将房主权限转让给 ' + name + ' 吗？\n转让后您将失去管理权限！\n\n请输入 "确认转让" 继续:');
             if(verify !== '确认转让') return;

             const fd = new FormData(); fd.append('csrf_token', csrfToken); fd.append('room_id', roomId); fd.append('new_owner_id', id);
             fetch('index.php?route=room_transfer', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{
                 if(d.success) {
                      alert('转让成功');
                      ws.send(JSON.stringify({ type: 'update_room', room_id: roomId })); 
                      window.location.reload();
                 } else {
                     alert(d.error || '操作失败');
                 }
             });
        }


        // --- Global Admin Menu Logic ---
        // --- Global Admin Menu Logic (Event Delegation) ---
        // --- Global Admin Menu Logic (Event Delegation) ---
        // Moved lookup inside to ensure DOM readiness
        
        // Delegated Click Listener
        document.addEventListener('click', (e) => {
            const adminMenu = document.getElementById('globalAdminMenu');
            // Close menu if clicking outside
            if(adminMenu && !adminMenu.classList.contains('hidden') && !adminMenu.contains(e.target)) {
                 adminMenu.classList.add('hidden');
            }
            
            // Check for trigger click
            const trigger = e.target.closest('.admin-menu-trigger');
            if (trigger) {

                e.stopPropagation();
                openAdminMenuFromTrigger(trigger);
            }
        });

        function openAdminMenuFromTrigger(btn) {
            const adminMenu = document.getElementById('globalAdminMenu');
            if(!adminMenu) {
                console.error('Global Admin Menu element not found!');
                return;
            }

            // Extract Data
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const isMuted = parseInt(btn.dataset.muted || 0);
            
            // Populate Menu
            const linkPM = document.getElementById('adminMenuPM');
            const btnMute = document.getElementById('adminMenuMute');
            const btnTransfer = document.getElementById('adminMenuTransfer');
            const btnKick = document.getElementById('adminMenuKick');
            
            // 1. PM
            linkPM.href = `index.php?route=chat_private&user_id=${id}`;
            
            // 2. Mute
            btnMute.innerHTML = `<i class="fa ${isMuted ? 'fa-volume-up' : 'fa-ban'} w-4"></i> ${isMuted ? '解除禁言' : '禁言'}`;
            btnMute.onclick = () => {
                 toggleMute(id, name, isMuted);
                 adminMenu.classList.add('hidden');
            };
            
            // 3. Transfer
            btnTransfer.onclick = () => {
                 transferOwner(id, name);
                 adminMenu.classList.add('hidden');
            };
            
            // 4. Kick
            btnKick.onclick = () => {
                 kickMember(id, name);
                 adminMenu.classList.add('hidden');
            };
            
            // Move to body to avoid stacking context issues
            if (adminMenu.parentNode !== document.body) {
                document.body.appendChild(adminMenu);
            }
            
            try {
                // Position & Show
                adminMenu.classList.remove('hidden');
                adminMenu.style.zIndex = '9999'; // Ensure top validation
                
                const rect = btn.getBoundingClientRect();
                
                const menuWidth = 128; // w-32
                let left = rect.right - menuWidth;
                let top = rect.bottom + 5;
                
                // Edge detection
                if (top + 150 > window.innerHeight) {
                    top = rect.top - 150;
                }
                
                adminMenu.style.left = left + 'px';
                adminMenu.style.top = top + 'px';
                
            } catch (err) {
                console.error('Error positioning admin menu:', err);
            }
        }
</script>
    <!-- Global Admin Menu (Fixed) -->
    <div id="globalAdminMenu" class="fixed w-32 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-100 dark:border-gray-700 z-[100] overflow-hidden text-xs hidden">
         <a id="adminMenuPM" href="#" target="_blank" class="block w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center gap-2">
             <i class="fa fa-envelope w-4"></i> 私信
         </a>
         <button id="adminMenuMute" class="block w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center gap-2">
             <i class="fa fa-ban w-4"></i> 禁言
         </button>
         <button id="adminMenuTransfer" class="block w-full text-left px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center gap-2">
             <i class="fa fa-exchange w-4"></i> 转让房主
         </button>
         <button id="adminMenuKick" class="block w-full text-left px-3 py-2 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500 flex items-center gap-2 border-t border-gray-100 dark:border-gray-700">
             <i class="fa fa-times w-4"></i> 踢出
         </button>
    </div>
</body>
</html>
