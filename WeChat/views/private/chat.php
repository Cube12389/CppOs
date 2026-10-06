<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require_once 'core/Storage.php'; $siteName = Storage::getSetting('site_name'); ?>
    <title>与 <?php echo htmlspecialchars($partner['display_name']); ?> 的私聊 - <?php echo htmlspecialchars($siteName); ?></title>
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
                        primary: '#6366f1',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-indigo-100 via-purple-100 to-pink-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-[100dvh] flex h-[100dvh] overflow-hidden text-gray-800 dark:text-gray-100 font-sans transition-colors duration-300">
    
    <!-- Sidebar (Unified) -->
    <style>
        /* Burn-on-Read Styles */
        .blur-sm-custom { filter: blur(12px); transform: scale(1.1); }
        .secure-layer { -webkit-touch-callout: none; -webkit-user-select: none; -khtml-user-select: none; -moz-user-select: none; -ms-user-select: none; user-select: none; pointer-events: none; }
        .burn-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.6); display: flex; flex-direction: column; align-items: center; justify-content: center; z-index: 10; cursor: pointer; border-radius: 0.75rem; transition: opacity 0.3s; }
        .burn-timer { position: absolute; top: 10px; right: 10px; background: rgba(255,50,50,0.8); color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; z-index: 20; }
        .flame-icon { font-size: 2rem; color: #fb923c; margin-bottom: 0.5rem; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 0.8; transform: scale(1); } 50% { opacity: 1; transform: scale(1.1); } 100% { opacity: 0.8; transform: scale(1); } }
    </style>
    <?php require 'views/partials/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col relative w-full h-full overflow-hidden bg-white/40 dark:bg-gray-900/50 backdrop-blur-xl md:rounded-2xl shadow-2xl mr-0 md:my-2 md:mr-2 border border-white/20 dark:border-gray-700">
        
        <!-- Header -->
        <header class="bg-white/60 dark:bg-gray-800/80 backdrop-blur-md px-4 md:px-6 py-4 flex justify-between items-center border-b border-white/20 dark:border-gray-700 shadow-sm z-10">
            <div class="flex items-center gap-3">
                <!-- Mobile Toggle -->
                <button onclick="toggleSidebar()" class="md:hidden text-gray-600 dark:text-gray-300 hover:text-indigo-500 mr-1">
                     <i class="fa fa-bars"></i>
                </button>

                <a href="index.php?route=messages" class="bg-white dark:bg-gray-700 p-2 rounded-full shadow hover:shadow-md transition text-gray-600 dark:text-gray-300">
                    <i class="fa fa-arrow-left"></i>
                </a>
                <div class="flex items-center gap-3">
                    <img src="<?php echo AVATAR_DIR . $partner['avatar']; ?>" class="w-10 h-10 rounded-full object-cover shadow border-2 border-white">
                    <div>
                        <h1 class="font-bold text-lg text-gray-800 leading-tight">
                            <?php echo htmlspecialchars($partner['display_name']); ?>
                        </h1>
                        <p class="text-xs text-gray-500 flex items-center gap-2">
                            <span>ID: <?php echo $partner['id']; ?></span>
                            <span id="typingIndicator" class="hidden text-indigo-500 font-bold animate-pulse transition-opacity duration-300">
                                <i class="fa fa-pencil"></i> 对方正在输入...
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Settings Dropdown -->
                <div class="relative group">
                    <button class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition text-gray-500 dark:text-gray-300">
                        <i class="fa fa-ellipsis-v"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl py-2 hidden group-hover:block z-50 border border-gray-100 dark:border-gray-700">
                        <button onclick="clearHistory()" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                            <i class="fa fa-eraser text-orange-400"></i> 清空记录
                        </button>
                        <button onclick="deleteSession()" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2">
                            <i class="fa fa-trash"></i> 删除会话
                        </button>
                    </div>
                </div>

                <button onclick="location.reload()" class="text-gray-500 hover:text-primary transition-colors" title="刷新消息">
                    <i class="fa fa-refresh"></i>
                </button>
            </div>
        </header>

        <!-- Messages -->
        <div id="messagesContainer" class="flex-1 p-6 overflow-y-auto w-full">
            <?php foreach($messages as $msg): 
                $isMe = $msg['sender_id'] == $user_id;
                $isRecalled = !empty($msg['is_recalled']);
            ?>
                <div class="flex gap-4 mb-6 <?php echo $isMe ? 'flex-row-reverse' : ''; ?>" id="msg-<?php echo $msg['id']; ?>">
                    <div class="flex-shrink-0">
                        <img src="<?php echo AVATAR_DIR . ($isMe ? $_SESSION['avatar'] : $partner['avatar']); ?>" class="w-10 h-10 rounded-full object-cover shadow-sm border-2 border-white">
                    </div>
                    
                    <div class="max-w-[70%] group">
                         <div class="relative px-5 py-3 shadow-sm rounded-2xl <?php echo $isMe ? 'bg-indigo-500 text-white rounded-tr-none' : 'bg-white text-gray-800 rounded-tl-none'; ?>">
                             <?php if ($isRecalled): ?>
                                 <p class="italic opacity-60"><i class="fa fa-ban mr-1"></i> <?php echo $isMe ? '你撤回了一条消息' : '对方撤回了一条消息'; ?></p>
                             <?php else: ?>
                                 <?php if ($msg['image_url']): ?>
                                     <?php if ($msg['image_url'] === 'burned'): ?>
                                         <div class="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg flex items-center justify-center gap-2 text-gray-400">
                                             <i class="fa fa-fire"></i> 图片已销毁
                                         </div>
                                     <?php elseif (!empty($msg['burn_after_read']) && $msg['burn_after_read'] > 0): ?>
                                         <?php 
                                            // Calculate remaining
                                            $isOpened = !empty($msg['opened_at']);
                                            $timeLeft = $isOpened ? ($msg['burn_after_read'] - (time() - strtotime($msg['opened_at']))) : $msg['burn_after_read'];
                                            if($timeLeft < 0) $timeLeft = 0; 
                                            $msgId = $msg['id'];
                                         ?>
                                         <div class="relative overflow-hidden rounded-lg max-w-[200px]" id="img-container-<?php echo $msgId; ?>" oncontextmenu="return false;">
                                             <?php if(!$isMe && !$isOpened): ?>
                                                 <img src="<?php echo MESSAGE_IMAGE_DIR . $msg['image_url']; ?>" class="rounded-lg blur-sm-custom w-full">
                                                 <div class="burn-overlay" onclick="viewBurnImage(<?php echo $msgId; ?>, <?php echo $msg['burn_after_read']; ?>, this)">
                                                     <i class="fa fa-fire flame-icon"></i>
                                                     <span class="text-white text-xs font-bold">点击查看</span>
                                                     <span class="text-orange-200 text-[10px] mt-1">阅后即焚 (<?php echo $msg['burn_after_read']; ?>s)</span>
                                                 </div>
                                             <?php else: ?>
                                                <div class="relative">
                                                    <img src="<?php echo MESSAGE_IMAGE_DIR . $msg['image_url']; ?>" class="rounded-lg w-full secure-layer">
                                                    <div class="absolute inset-0 z-20" oncontextmenu="return false;"></div>
                                                    <?php if($isOpened && $timeLeft > 0): ?>
                                                        <div class="burn-timer" id="timer-<?php echo $msgId; ?>">
                                                            <i class="fa fa-fire"></i> <span class="countdown"><?php echo round($timeLeft); ?></span>s
                                                        </div>
                                                        <script>setTimeout(() => startBurnCountdown(<?php echo $msgId; ?>, <?php echo $timeLeft; ?>), 0);</script>
                                                    <?php endif; ?>
                                                </div>
                                             <?php endif; ?>
                                         </div>
                                     <?php else: ?>
                                         <img src="<?php echo MESSAGE_IMAGE_DIR . $msg['image_url']; ?>" 
                                      data-original="<?php echo MESSAGE_IMAGE_DIR . $msg['image_url']; ?>"
                                      class="chat-image rounded-lg max-w-full mb-2">
                                     <?php endif; ?>
                                 <?php endif; ?>
                                 <?php if (!empty($msg['video_url'])): ?>
                                     <video src="<?php echo MESSAGE_VIDEO_DIR . $msg['video_url']; ?>" controls class="rounded-lg max-w-full mb-2"></video>
                                 <?php endif; ?>
                                 <?php if ($msg['content']): ?>
                                    <p class="leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($msg['content']); ?></p>
                                 <?php endif; ?>

                                 <?php if ($isMe && (time() - strtotime($msg['created_at']) < 120)): ?>
                                     <button onclick="recallMessage(<?php echo $msg['id']; ?>)" class="absolute -left-10 top-2 p-1 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity" title="撤回">
                                         <i class="fa fa-undo"></i>
                                     </button>
                                 <?php endif; ?>
                             <?php endif; ?>
                         </div>
                         <div class="text-[10px] text-gray-400 mt-1 <?php echo $isMe ? 'text-right' : ''; ?>">
                             <?php echo date('H:i', strtotime($msg['created_at'])); ?>
                         </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Input -->
        <div class="p-4 bg-white/60 dark:bg-gray-800/80 backdrop-blur-md border-t border-white/20 dark:border-gray-700">
             <form id="privateMsgForm" class="relative bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl shadow-inner focus-within:ring-2 focus-within:ring-indigo-300 transition-all">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="receiver_id" value="<?php echo $partner['id']; ?>">
                
                <div id="imagePreviewContainer" class="hidden px-4 pt-3 pb-2 bg-gray-50 dark:bg-gray-800 rounded-t-xl border-b border-gray-100 dark:border-gray-600 relative">
                    <div class="relative inline-block">
                        <img id="imagePreview" src="" class="h-24 w-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 object-cover">
                        <button type="button" id="cancelImage" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-md hover:bg-red-600 w-6 h-6 flex items-center justify-center transition-transform hover:scale-110">
                            <i class="fa fa-times text-xs"></i>
                        </button>
                    </div>
                </div>

                <div id="audioPreviewContainer" class="hidden px-4 pt-3 pb-2 bg-gray-50 dark:bg-gray-800 rounded-t-xl border-b border-gray-100 dark:border-gray-600 relative">
                     <div class="flex items-center gap-3">
                         <audio id="audioPreview" controls class="h-8 max-w-[200px]"></audio>
                         <button type="button" id="cancelAudio" class="text-red-500 hover:text-red-600 p-1">
                             <i class="fa fa-times-circle text-xl"></i>
                         </button>
                     </div>
                </div>

                <div id="videoPreviewContainer" class="hidden px-4 pt-3 pb-2 bg-gray-50 dark:bg-gray-800 rounded-t-xl border-b border-gray-100 dark:border-gray-600 relative">
                    <div class="relative inline-block">
                        <video id="videoPreview" controls class="h-32 w-auto rounded-lg shadow-sm border border-gray-200 dark:border-gray-600 bg-black"></video>
                        <button type="button" id="cancelVideo" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 shadow-md hover:bg-red-600 w-6 h-6 flex items-center justify-center transition-transform hover:scale-110">
                            <i class="fa fa-times text-xs"></i>
                        </button>
                    </div>
                    <div id="videoInfo" class="text-xs text-gray-500 mt-1 font-mono"></div>
                </div>

                <!-- Wrapper: Column Layout for Mobile-First -->
                <div class="flex flex-col gap-2 p-3">
                    
                    <!-- Top Row: Toolbar (Image, Video, Burn) -->
                    <div class="flex items-center gap-4 overflow-x-auto no-scrollbar pb-1">
                        <!-- Emoji -->
                        <button type="button" id="emojiBtn" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-600 rounded-full text-gray-500 dark:text-gray-300 hover:bg-yellow-50 hover:text-yellow-600 transition-colors flex-shrink-0" title="发送表情">
                            <i class="fa fa-smile-o text-sm"></i>
                            <span class="text-xs font-medium">表情</span>
                        </button>

                        <!-- Image -->
                        <button type="button" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-600 rounded-full text-gray-500 dark:text-gray-300 hover:bg-indigo-50 hover:text-indigo-500 transition-colors relative flex-shrink-0" title="发送图片">
                             <i class="fa fa-picture-o text-sm"></i>
                             <span class="text-xs font-medium">图片</span>
                             <input type="file" name="image" id="imageInput" class="absolute inset-0 opacity-0 cursor-pointer" accept="image/*">
                        </button>

                        <!-- Video -->
                        <button type="button" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-600 rounded-full text-gray-500 dark:text-gray-300 hover:bg-red-50 hover:text-red-500 transition-colors relative flex-shrink-0" title="发送视频">
                             <i class="fa fa-video-camera text-sm"></i>
                             <span class="text-xs font-medium">视频</span>
                             <input type="file" name="video" id="videoInput" class="absolute inset-0 opacity-0 cursor-pointer" accept="video/mp4,video/webm,video/quicktime">
                        </button>
                        
                        <!-- Audio / Mic -->
                         <button type="button" id="micBtn" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-600 rounded-full text-gray-500 dark:text-gray-300 hover:bg-green-50 hover:text-green-600 transition-colors flex-shrink-0 relative" title="录制语音">
                             <i class="fa fa-microphone text-sm"></i>
                             <span class="text-xs font-medium" id="micText">语音</span>
                         </button>
                        
                        <!-- Burn Timer -->
                        <div class="relative flex-shrink-0">
                            <button type="button" id="burnTimerBtn" class="flex items-center gap-1 px-3 py-1.5 bg-gray-100 dark:bg-gray-600 rounded-full text-gray-500 dark:text-gray-300 hover:bg-orange-50 hover:text-orange-500 transition-colors" title="阅后即焚">
                                <i class="fa fa-fire text-sm"></i>
                                <span class="text-xs font-medium">阅后即焚</span>
                                <span id="burnBadge" class="absolute -top-1 -right-1 bg-orange-500 text-white text-[10px] px-1.5 h-4 flex items-center justify-center rounded-full hidden border border-white dark:border-gray-700">0s</span>
                            </button>
                            <input type="hidden" id="burnTimeInput" value="0">
                        </div>
                    </div>

                    <!-- Bottom Row: Input & Send -->
                    <div class="flex items-end gap-2">
                        <textarea name="content" id="messageInput" rows="1" class="flex-1 bg-gray-100 dark:bg-gray-600 border-0 focus:ring-2 focus:ring-indigo-500 rounded-xl text-gray-800 dark:text-white placeholder-gray-400 py-3 px-4 resize-none transition-all" placeholder="<?php echo trans('type_message'); ?>"></textarea>
                        
                        <button type="submit" class="p-3 bg-indigo-500 text-white rounded-xl shadow-md hover:bg-indigo-600 transition-all flex-shrink-0 w-12 h-12 flex items-center justify-center">
                            <i class="fa fa-paper-plane text-lg"></i>
                        </button>
                    </div>
                </div>
                <!-- Emoji Picker -->
                <div id="emojiPicker" class="absolute bottom-full left-0 mb-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl border border-white/40 dark:border-gray-600 shadow-2xl rounded-2xl p-3 w-72 h-60 overflow-y-auto hidden z-50 grid grid-cols-8 gap-1 custom-scrollbar"></div>
            </form>
        </div>

    </main>

    <!-- Global Burn Menu -->
    <div id="burnMenu" class="fixed w-32 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-xl hidden z-50 overflow-hidden">
        <div class="text-xs font-bold text-gray-400 px-3 py-2 bg-gray-50 dark:bg-gray-700">销毁时间</div>
        <button type="button" onclick="setBurnTime(0)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">关闭</button>
        <button type="button" onclick="setBurnTime(10)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">10秒</button>
        <button type="button" onclick="setBurnTime(30)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">30秒</button>
        <button type="button" onclick="setBurnTime(60)" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200">1分钟</button>
    </div>

    <script>
        // Emoji Config
        const commonEmojis = [
            '😀','😂','🤣','😉','😊','😎','😍','😘','🤪','😡',
            '😭','😱','👍','👎','👋','👌','🙏','🎉','❤️','💔',
            '🔥','✨','⭐','🌙','☀️','☁️','🍎','🍔','🍺','🚗',
            '✈️','🚀','💡','💻','📱','📷','🎵','🎮','⚽','🏀'
        ];

        // Init Emojis
        const emojiBtn = document.getElementById('emojiBtn');
        const emojiPicker = document.getElementById('emojiPicker');
        const messageInput = document.getElementById('messageInput');

        if(emojiBtn && emojiPicker) {
            // Populate
            commonEmojis.forEach(emoji => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'text-xl hover:bg-gray-100 dark:hover:bg-gray-700 p-2 rounded transition-colors';
                btn.textContent = emoji;
                btn.onclick = (e) => {
                    e.preventDefault();
                    insertAtCursor(messageInput, emoji);
                    emojiPicker.classList.add('hidden');
                };
                emojiPicker.appendChild(btn);
            });

            // Toggle
            emojiBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                emojiPicker.classList.toggle('hidden');
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if(!emojiPicker.contains(e.target) && e.target !== emojiBtn) {
                    emojiPicker.classList.add('hidden');
                }
            });
        }

        function insertAtCursor(el, text) {
            const val = el.value;
            const start = el.selectionStart;
            const end = el.selectionEnd;
            el.value = val.substring(0, start) + text + val.substring(end);
            el.selectionStart = el.selectionEnd = start + text.length;
            el.focus();
            // Trigger auto-height if needed
            el.style.height = 'auto';
            el.style.height = (el.scrollHeight) + 'px';
        }

        const container = document.getElementById('messagesContainer');
        
        function scrollToBottom(behavior = 'auto') {
            if (!container) return;
            // Scroll using scrollTop
            container.scrollTop = container.scrollHeight;
            
            // Also try scrolling last message into view for extra insurance
            const lastMsg = container.lastElementChild;
            if (lastMsg) {
                lastMsg.scrollIntoView({ behavior: behavior, block: 'end' });
            }
        }

        // 1. Immediate scroll
        scrollToBottom('instant');
        
        // 2. Scroll on DOM ready
        document.addEventListener('DOMContentLoaded', () => scrollToBottom('instant'));
        
        // 3. Scroll when everything (images) loaded
        window.addEventListener('load', () => scrollToBottom('instant'));
        
        // 4. Repeated delayed scrolls to catch any layout shifts
        [50, 100, 300, 500, 1000, 2000].forEach(delay => {
            setTimeout(() => scrollToBottom('instant'), delay);
        });

        // 5. Special handler for images within the container
        container.querySelectorAll('img').forEach(img => {
            if (img.complete) return;
            img.addEventListener('load', () => scrollToBottom('instant'));
            img.addEventListener('error', () => scrollToBottom('instant'));
        });

        // Simple Polling (Backup)
        // setInterval(() => { ... }, 5000);

        // --- Real-time Logic ---
        
        // 1. Listen for Incoming Messages (Dispatched by Sidebar Global WS)
        // 1. Listen for Incoming Messages (Dispatched by Sidebar Global WS)
        window.addEventListener('new_private_message', (e) => {
            const container = document.getElementById('messagesContainer');
            // Check if near bottom BEFORE adding content
            const isAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 150;
            
            const msg = e.detail;
            // Pass global isAtBottom to function or use it locally?
            // Since addPrivateMessageToDOM is called, let's modify addPrivateMessageToDOM signature OR rely on the fact that we can do checking there?
            // Actually, addPrivateMessageToDOM logic above needs 'isAtBottom'. 
            // Let's refactor: Calculate here, pass to function? 
            // The function `addPrivateMessageToDOM` is used by sending (isMe=true) and receiving.
            // If isMe=true, always scroll.
            // If receiving, use isAtBottom.
            
            addPrivateMessageToDOM(msg, isAtBottom); 
        });

        // Listen for Recall
        window.addEventListener('private_recall', (e) => {
             const msgId = e.detail;
             const msgEl = document.getElementById(`msg-${msgId}`);
             if(msgEl) {
                 const bubble = msgEl.querySelector('.relative.px-5');
                 if(bubble) {
                     // Determine if it was MY message or Other
                     const isMe = msgEl.classList.contains('flex-row-reverse');
                     bubble.innerHTML = `<p class="italic opacity-60"><i class="fa fa-ban mr-1"></i> ${isMe ? '你撤回了一条消息' : '对方撤回了一条消息'}</p>`;
                 }
             }
        });

        // Listen for Burn Event
        window.addEventListener('private_burn', (e) => {
             const { message_id, duration } = e.detail;
             const msgEl = document.getElementById(`msg-${message_id}`);
             if(msgEl) {
                 const container = msgEl.querySelector(`[id^="img-container-"]`);
                 if(container && !container.querySelector('.burn-timer')) {
                      // Only if it doesn't have a timer yet (Sender sync or Late Receiver sync)
                      const isMe = msgEl.classList.contains('flex-row-reverse');
                      
                      // Add Timer Badge
                      const timerDiv = document.createElement('div');
                      timerDiv.className = 'burn-timer';
                      timerDiv.id = 'timer-' + message_id;
                      timerDiv.innerHTML = '<i class="fa fa-fire"></i> <span class="countdown">' + duration + '</span>s';
                      container.appendChild(timerDiv); 

                      if(isMe) {
                          // For Sender: Just show the timer on top of the image
                          // The image is already visible but secure
                      } else {
                          // For Receiver who might have this open on another tab:
                          // Remove Blur and overlay if they exist
                          const img = container.querySelector('img');
                          if(img) {
                              img.classList.remove('blur-sm-custom');
                              img.classList.add('secure-layer');
                          }
                          const overlay = container.querySelector('.burn-overlay');
                          if(overlay) overlay.remove();
                      }
                      
                      // Start Countdown locally
                      window.startBurnCountdown(message_id, duration);
                 }
             }
        });

        // --- Read Status Logic ---
        function sendReadReceipt() {
            if(window.globalWS && window.globalWS.readyState === WebSocket.OPEN) {
                window.globalWS.send(JSON.stringify({
                    type: 'mark_read',
                    receiver_id: <?php echo $partner['id']; ?> // Notify partner I read their msgs
                }));
            }
        }
        
        // Send on load, focus, and WS connect
        sendReadReceipt();
        window.addEventListener('focus', sendReadReceipt);
        window.addEventListener('ws_open', sendReadReceipt); // Custom event from sidebar
        
        // Listen for Partner Marking Read
        window.addEventListener('private_read', (e) => {
            const readerId = e.detail.reader_id;
            if(readerId == <?php echo $partner['id']; ?>) {
                // Partner read my messages -> Update UI
                // Find all "Unread" markers and change to "Read"
                const unreadSpans = document.querySelectorAll('span > i.fa-circle-o');
                unreadSpans.forEach(icon => {
                    const span = icon.parentElement;
                    span.innerHTML = '<i class="fa fa-check-circle text-green-500"></i> 已读';
                });
            }
        });

        function addPrivateMessageToDOM(msg, isAtBottom = true) {
            const div = document.createElement('div');
            const isMe = msg.sender_id == <?php echo $user_id; ?>;
            
            // Define timeStr and allowRecall
            const msgDate = new Date(msg.created_at.replace(/-/g, "/")); // Cross-browser fix
            const timeStr = msgDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const isRecent = (Date.now() - msgDate.getTime()) < 120000; // 2 minutes
            const allowRecall = isMe && isRecent;

            // Updated: Restore flex-row-reverse for correct avatar alignment
            div.className = `flex ${isMe ? 'flex-row-reverse' : ''} mb-4 animate-fade-in`;
            div.id = `msg-${msg.id}`;
            
            let contentStr = '';
            // Handle Image with Burn Logic
            if(msg.image_url) {
                 if(msg.burn_after_read > 0 && !isMe) {
                     // Receiver View (Burn)
                     contentStr += `
                        <div class="relative overflow-hidden rounded-lg max-w-[200px]" id="img-container-${msg.id}" oncontextmenu="return false;">
                             <img src="<?php echo MESSAGE_IMAGE_DIR; ?>${msg.image_url}" class="rounded-lg blur-sm-custom w-full">
                             <div class="burn-overlay" onclick="viewBurnImage(${msg.id}, ${msg.burn_after_read}, this)">
                                 <i class="fa fa-fire flame-icon"></i>
                                 <span class="text-white text-xs font-bold">点击查看</span>
                                 <span class="text-orange-200 text-[10px] mt-1">阅后即焚 (${msg.burn_after_read}s)</span>
                             </div>
                        </div>`;
                 } else if (msg.burn_after_read > 0 && isMe) {
                     // Sender View (Burn - Visible but Secure)
                     contentStr += `
                        <div class="relative overflow-hidden rounded-lg max-w-[200px]" id="img-container-${msg.id}">
                            <div class="relative">
                                <img src="<?php echo MESSAGE_IMAGE_DIR; ?>${msg.image_url}" class="rounded-lg w-full secure-layer">
                                <div class="absolute inset-0 z-20" oncontextmenu="return false;"></div>
                            </div>
                        </div>`;
                 } else {
                     // Normal Image
                     contentStr += `<div class="mb-2"><img src="<?php echo MESSAGE_IMAGE_DIR; ?>${msg.image_url}" data-original="<?php echo MESSAGE_IMAGE_DIR; ?>${msg.image_url}" class="chat-image rounded-xl max-w-[200px]"></div>`;
                 }
            }
            if(msg.video_url) {
                 contentStr += `<div class="mb-2"><video src="<?php echo MESSAGE_VIDEO_DIR; ?>${msg.video_url}" controls class="rounded-xl max-w-[200px]"></video></div>`;
            }
            if(msg.audio_url) {
                 contentStr += `<div class="mb-2"><audio src="<?php echo MESSAGE_AUDIO_DIR; ?>${msg.audio_url}" controls class="max-w-[200px]"></audio></div>`;
            }
            if(msg.content) {
                 contentStr += `<p class="leading-relaxed whitespace-pre-wrap">${escapeHtml(msg.content)}</p>`;
            }

            const avatarSrc = isMe ? '<?php echo AVATAR_DIR . $_SESSION['avatar']; ?>' : '<?php echo AVATAR_DIR . $partner['avatar']; ?>';

            div.innerHTML = `
                <div class="flex-shrink-0">
                    <img src="${avatarSrc}" class="w-10 h-10 rounded-full object-cover shadow-sm border-2 border-white">
                </div>
                <div class="max-w-[70%] group">
                     <div class="relative px-5 py-3 shadow-sm rounded-2xl ${isMe ? 'bg-indigo-500 text-white rounded-tr-none' : 'bg-white text-gray-800 rounded-tl-none'}">
                         ${contentStr}
                     </div>
                     <div class="text-[10px] text-gray-400 mt-1 ${isMe ? 'text-right' : ''} flex items-center ${isMe ? 'justify-end' : 'justify-start'} gap-2">
                        <span class="mr-1">${ isMe && msg.is_read ? '<i class="fa fa-check-circle text-green-500"></i> 已读' : (isMe ? '<i class="fa fa-circle-o"></i> 未读' : '') }</span>
                        <span>${timeStr}</span>
                        ${ isMe && allowRecall ? `<button onclick="recallMessage(${msg.id})" class="text-xs text-indigo-500 hover:text-indigo-700 ml-2 font-medium bg-indigo-50 px-2 py-0.5 rounded cursor-pointer" title="2分钟内可撤回">撤回</button>` : '' }
                     </div>
                </div>
            `;
            // Add ID
            div.id = `msg-${msg.id}`;
            
            container.appendChild(div);
            if(viewer) viewer.update();
            
            // Smart Scroll: If user was at bottom (or it's ME sending), scroll to new bottom
            if(isMe || isAtBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }

        function escapeHtml(text) {
             const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
             return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
        }

        // 2. Update Sending Logic
        // 2. Update Sending Logic with Upload Feedback
        document.getElementById('privateMsgForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = this.querySelector('button[type="submit"]');
            const originalIcon = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';

            const formData = new FormData(this);
            // Append Burn After Read Value
            // Append Burn After Read Value
            const burnVal = document.getElementById('burnTimeInput').value;
            formData.append('burn_after_read', burnVal);

            // Append Audio
            if(audioBlob) {
                formData.append('audio', audioBlob, 'voice.webm');
            }

            fetch('index.php?route=send_private', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(d => {
                btn.disabled = false;
                btn.innerHTML = originalIcon;

                if(d.success) {
                    if(d.message) {
                        const msgData = d.message;
                        // Broadcast via Global WS
                        if(window.globalWS && window.globalWS.readyState === WebSocket.OPEN) {
                            window.globalWS.send(JSON.stringify({
                                type: 'private_message',
                                receiver_id: <?php echo $partner['id']; ?>,
                                data: msgData
                            }));
                        }
                        
                        addPrivateMessageToDOM(msgData, true);
                        
                        document.getElementById('messageInput').value = '';
                        document.getElementById('imageInput').value = '';
                        document.getElementById('videoInput').value = '';
                        // Clear Audio
                        if(audioPreview) audioPreview.src = '';
                        if(audioPreviewCont) audioPreviewCont.classList.add('hidden');
                        audioBlob = null;
                        audioChunks = [];
                        
                        // Clear Preview
                        const cancelBtn = document.getElementById('cancelImage');
                        if(cancelBtn) cancelBtn.click();
                        
                        resetVideoBtn(); // Reset UI
                        setBurnTime(0);
                    }
                } else {
                    alert(d.error || '发送失败');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = originalIcon;
                alert('网络错误');
            });
        });

        // Video Preview Logic
        const videoInput = document.getElementById('videoInput');
        const videoBtn = videoInput ? videoInput.parentElement : null;
        const videoPreview = document.getElementById('videoPreview');
        const videoPreviewCont = document.getElementById('videoPreviewContainer');
        const cancelVideoBtn = document.getElementById('cancelVideo');
        
        if(videoInput) {
            videoInput.addEventListener('change', function() {
                if(this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // UI Feedback
                    videoBtn.classList.add('text-red-500', 'bg-red-50');
                    const i = videoBtn.querySelector('i');
                    if(i) i.className = 'fa fa-check-circle text-xl'; 
                    
                    // Preview
                    const url = URL.createObjectURL(file);
                    if(videoPreview) {
                        videoPreview.src = url;
                        videoPreviewCont.classList.remove('hidden');
                        
                        // Show info
                        const size = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                        document.getElementById('videoInfo').innerText = `${file.name} (${size})`;
                    }
                } else {
                    resetVideoBtn();
                }
            });
        }
        
        if(cancelVideoBtn) {
            cancelVideoBtn.addEventListener('click', () => {
                resetVideoBtn();
            });
        }

        // --- Image Preview Logic ---
        const imageInput = document.getElementById('imageInput');
        const previewCont = document.getElementById('imagePreviewContainer');
        const previewImg = document.getElementById('imagePreview');
        const cancelImg = document.getElementById('cancelImage');

        if(imageInput && previewCont && previewImg && cancelImg) {
            imageInput.addEventListener('change', function(e) {
                if(this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        previewImg.src = ev.target.result;
                        previewCont.classList.remove('hidden');
                    }
                    reader.readAsDataURL(this.files[0]);
                }
            });

            cancelImg.addEventListener('click', function() {
                imageInput.value = ''; // Clear file input
                previewImg.src = '';
                previewCont.classList.add('hidden');
            });
        }

        function resetVideoBtn() {
            if(!videoBtn) return;
            videoBtn.classList.remove('text-red-500', 'bg-red-50');
            const i = videoBtn.querySelector('i');
            if(i) i.className = 'fa fa-video-camera text-xl';
            if(videoInput) videoInput.value = '';
            
            // Clear Preview
            const videoPreview = document.getElementById('videoPreview');
            const videoPreviewCont = document.getElementById('videoPreviewContainer');
            if(videoPreview) videoPreview.src = '';
            if(videoPreviewCont) videoPreviewCont.classList.add('hidden');
        }

        // Recall Function
        window.recallMessage = function(msgId) {
            if(!confirm('确定撤回这条消息吗？')) return;
            
            const fd = new FormData();
            fd.append('message_id', msgId);
            fd.append('csrf_token', '<?php echo $csrf_token; ?>');

            fetch('index.php?route=private_recall', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(d => {
                if(d.success) {
                    // Send WS Broadcast
                    if(window.globalWS && window.globalWS.readyState === WebSocket.OPEN) {
                         window.globalWS.send(JSON.stringify({
                             type: 'private_recall',
                             receiver_id: <?php echo $partner['id']; ?>,
                             message_id: msgId
                         }));
                    }
                    // Update UI internally
                    const event = new CustomEvent('private_recall', { detail: msgId });
                    window.dispatchEvent(event);
                } else {
                    alert(d.error || '撤回失败');
                }
            })
            .catch(e => {
                console.error(e);
                alert('网络错误');
            });
        }

        // --- Burn-on-Read Logic ---
        // --- Audio Recording Logic ---
        let mediaRecorder;
        let audioChunks = [];
        let audioBlob = null;
        let viewer;

        // Init Viewer.js
        document.addEventListener('DOMContentLoaded', () => {
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
        });
        
        const micBtn = document.getElementById('micBtn');
        const micText = document.getElementById('micText');
        const audioPreview = document.getElementById('audioPreview');
        const audioPreviewCont = document.getElementById('audioPreviewContainer');
        const cancelAudioBtn = document.getElementById('cancelAudio');
        
        if(micBtn) {
            micBtn.addEventListener('click', async (e) => {
                e.preventDefault();
                
                // Security Check
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                     alert('无法访问麦克风。\n\n原因：浏览器安全限制，语音录制功能仅支持 HTTPS 协议或 localhost。\n\n解决方法：请使用 HTTPS 访问此网站。');
                     return;
                }
                
                if (!mediaRecorder || mediaRecorder.state === 'inactive') {
                    // Start Recording
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        mediaRecorder = new MediaRecorder(stream);
                        audioChunks = [];
                        
                        mediaRecorder.ondataavailable = (e) => {
                            audioChunks.push(e.data);
                        };
                        
                        mediaRecorder.onstop = () => {
                            audioBlob = new Blob(audioChunks, { type: 'audio/webm' }); // webm is standard
                            const audioUrl = URL.createObjectURL(audioBlob);
                            audioPreview.src = audioUrl;
                            audioPreviewCont.classList.remove('hidden');
                            
                            // Reset Mic UI
                            micBtn.classList.remove('bg-red-500', 'text-white', 'animate-pulse');
                            micBtn.classList.add('bg-gray-100', 'text-gray-500', 'dark:bg-gray-600', 'dark:text-gray-300');
                            micText.innerText = '语音';
                            
                            // Hide others? No, just show preview
                        };
                        
                        mediaRecorder.start();
                        
                        // UI update: Recording
                        micBtn.classList.remove('bg-gray-100', 'text-gray-500', 'dark:bg-gray-600', 'dark:text-gray-300');
                        micBtn.classList.add('bg-red-500', 'text-white', 'animate-pulse');
                        micText.innerText = '停止';
                        
                    } catch (err) {
                        console.error('Mic Error:', err);
                        alert('无法访问麦克风: ' + err.message);
                    }
                } else {
                    // Stop Recording
                    mediaRecorder.stop();
                    // Stop all tracks to release mic
                    mediaRecorder.stream.getTracks().forEach(track => track.stop());
                }
            });
            
            cancelAudioBtn.addEventListener('click', () => {
                audioPreview.src = '';
                audioPreviewCont.classList.add('hidden');
                audioBlob = null;
                audioChunks = [];
            });
        }
        
        // --- Burn-on-Read Logic ---
        // Toggle Menu
        const burnBtn = document.getElementById('burnTimerBtn');
        const burnMenu = document.getElementById('burnMenu');
        
        if(burnBtn && burnMenu) {
            // Toggle on click
            burnBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Stop bubbling to document
                
                if (burnMenu.classList.contains('hidden')) {
                    // Show & Position
                    const rect = burnBtn.getBoundingClientRect();
                    // On Mobile: If keyboard is up, innerHeight is small.
                    // If button is near bottom, rect.bottom might be close to window edge.
                    // Position ABOVE the button:
                    const bottomSpace = window.innerHeight - rect.top; 
                    
                    burnMenu.style.bottom = (bottomSpace + 8) + 'px';
                    burnMenu.style.left = rect.left + 'px';
                    burnMenu.style.top = 'auto'; // Reset top
                    
                    burnMenu.classList.remove('hidden');
                } else {
                    burnMenu.classList.add('hidden');
                }
            });
            
            // Close on outside click
            document.addEventListener('click', (e) => {
                // Check if click is inside Menu OR inside Button (including children like icons)
                if(!burnMenu.contains(e.target) && !burnBtn.contains(e.target)) {
                    burnMenu.classList.add('hidden');
                }
            });
        }

        window.setBurnTime = function(val) {
             document.getElementById('burnTimeInput').value = val;
             updateBurnIcon(val);
             // Close menu
             if(burnMenu) burnMenu.classList.add('hidden');
        }

        function updateBurnIcon(val) {
            const btn = document.getElementById('burnTimerBtn');
            const badge = document.getElementById('burnBadge');
            
            // Note: We changed classes in the refactor, so we just toggle Badge visibility and maybe Text color.
            // Reset base classes is risky if we don't match.
            // Let's just update styles dynamically or toggle specific classes.
            
            if(val > 0) {
                btn.classList.add('bg-orange-50', 'text-orange-500');
                btn.classList.remove('bg-gray-100', 'text-gray-500', 'dark:bg-gray-600', 'dark:text-gray-300');
                badge.innerText = val + 's';
                badge.classList.remove('hidden');
            } else {
                btn.classList.remove('bg-orange-50', 'text-orange-500');
                btn.classList.add('bg-gray-100', 'text-gray-500', 'dark:bg-gray-600', 'dark:text-gray-300');
                badge.classList.add('hidden');
            }
        }

        window.viewBurnImage = async function(msgId, duration, overlayEl) {
            // 1. Notify Server (Mark as opened)
            const fd = new FormData();
            fd.append('message_id', msgId);
            fetch('index.php?route=private_mark_opened', { method: 'POST', body: fd });

            // 1.5 Broadcast Burn Event via WS
            if(window.globalWS && window.globalWS.readyState === WebSocket.OPEN) {
                 window.globalWS.send(JSON.stringify({
                     type: 'private_burn',
                     receiver_id: <?php echo $partner['id']; ?>,
                     message_id: msgId,
                     duration: duration
                 }));
            }

        // --- Typing Indicator ---
        const typingIndicator = document.getElementById('typingIndicator');
        let typingTimeout;

        // 1. Receive Typing Event
        window.addEventListener('private_typing', (e) => {
            const senderId = e.detail;
            if (senderId == <?php echo $partner['id']; ?>) {
                if(typingIndicator) {
                    typingIndicator.classList.remove('hidden');
                    // Clear existing timeout to reset timer
                    if(typingTimeout) clearTimeout(typingTimeout);
                    // Hide after 3 seconds
                    typingTimeout = setTimeout(() => {
                        typingIndicator.classList.add('hidden');
                    }, 3000);
                }
            }
        });

        // 2. Send Typing Event (Throttled)
        const msgInput = document.getElementById('messageInput');
        let lastTypedTime = 0;
        const typingThrottle = 2000; // Send at most every 2s

        if(msgInput) {
            msgInput.addEventListener('input', () => {
                const now = Date.now();
                if (now - lastTypedTime > typingThrottle) {
                    lastTypedTime = now;
                    if(window.globalWS && window.globalWS.readyState === WebSocket.OPEN) {
                         window.globalWS.send(JSON.stringify({
                             type: 'typing',
                             receiver_id: <?php echo $partner['id']; ?>
                         }));
                    }
                }
            });
        }

            // 2. UI Update
            const container = document.getElementById('img-container-' + msgId);
            const img = container.querySelector('img');
            
            // Remove Blur
            img.classList.remove('blur-sm-custom');
            img.classList.add('secure-layer'); // Add protection
            
            // Remove Overlay
            overlayEl.remove();
            
            // Add Timer Badge
            const timerDiv = document.createElement('div');
            timerDiv.className = 'burn-timer';
            timerDiv.id = 'timer-' + msgId;
            timerDiv.innerHTML = '<i class="fa fa-fire"></i> <span class="countdown">' + duration + '</span>s';
            // container IS the relative wrapper for unopened images
            container.appendChild(timerDiv); 
            // Wait, structure change slightly in PHP check step.
            // My JS implies creating the timer div dynamically. 
            // In PHP I wrapped generic images in relative div? No, I wrapped `img` in `relative` only for opened ones.
            // For closed ones, the `img` is direct child of `img-container-ID`.
            // So I need to wrap it specifically or just append to container.
            // Container is `relative`, so `absolute` timer works.
            
            // Add transparent blocker
            const blocker = document.createElement('div');
            blocker.className = 'absolute inset-0 z-20';
            blocker.oncontextmenu = () => false;
            container.appendChild(blocker);

            // 3. Start Countdown
            startBurnCountdown(msgId, duration);
        }

        window.startBurnCountdown = function(msgId, timeLeft) {
            const timerEl = document.getElementById('timer-' + msgId);
            if(!timerEl) return;
            const span = timerEl.querySelector('.countdown');
            
            const interval = setInterval(() => {
                timeLeft--;
                if(span) span.innerText = timeLeft;
                
                if(timeLeft <= 0) {
                    clearInterval(interval);
                    // Burn it
                    const container = document.getElementById('img-container-' + msgId);
                    if(container) {
                        container.innerHTML = `
                            <div class="bg-gray-200 dark:bg-gray-700 p-4 rounded-lg flex items-center justify-center gap-2 text-gray-400 transition-all duration-500">
                                <i class="fa fa-fire"></i> 图片已销毁
                            </div>
                        `;
                    }
                }
            }, 1000);
        }

        const partnerId = <?php echo $partner['id']; ?>;


        window.clearHistory = async function() {
            if(!confirm('确定要清空与该用户的聊天记录吗？(仅对自己不可见)')) return;
            postAction('private_clear_history', () => location.reload());
        }

        window.deleteSession = async function() {
            if(!confirm('确定要删除该会话吗？(会话将从列表移除)')) return;
            postAction('private_delete_session', () => location.href='index.php?route=messages');
        }

        async function postAction(action, successCallback) {
            const formData = new FormData();
            formData.append('partner_id', partnerId);
            // Append CSRF if we had it available easily in JS, or relying on session checks
            // The controller send() uses csrf, but clear_history() I implemented only checks session & post.
            // For better security I should pass CSRF.
            // Let's grab it from the form
            formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
            
            try {
                const res = await fetch('index.php?route=' + action, { method: 'POST', body: formData });
                // Check if response is JSON (it might be HTML error if something wrong)
                const text = await res.text();
                try {
                    const json = JSON.parse(text);
                    if(json.success) successCallback();
                    else alert(json.error || '操作失败');
                } catch(e) {
                    console.error(text);
                    alert('服务器响应错误');
                }
            } catch(e) {
                alert('请求出错');
            }
        }
    </script>
</body>
</html>
