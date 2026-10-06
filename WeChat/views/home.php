<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang'] ?? 'zh'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($LOCALE['app_name']); ?> - <?php echo $LOCALE['hero_tag']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#6366f1',
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
    <style>
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-gray-900 text-white min-h-screen font-sans selection:bg-indigo-500 selection:text-white">

    <!-- Background Elements -->
    <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute top-0 right-1/4 w-96 h-96 bg-indigo-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-32 left-1/2 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <!-- Navbar -->
    <nav class="relative z-50 container mx-auto px-6 py-6 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center font-bold text-white shadow-lg">L</div>
            <span class="font-bold text-xl tracking-wide"><?php echo $LOCALE['app_name']; ?></span>
        </div>
        <div class="flex items-center gap-6">
            <!-- Language Switcher -->
            <div class="relative">
                <button id="langBtn" class="flex items-center gap-1 text-sm text-gray-300 hover:text-white transition-colors py-2">
                    <i class="fa fa-globe"></i> 
                    <span><?php echo ($_SESSION['lang'] ?? 'zh') == 'zh' ? '中文' : 'English'; ?></span>
                </button>
                
                <div id="langMenu" class="absolute right-0 top-full mt-2 w-32 bg-gray-900 border border-gray-700 rounded-lg shadow-2xl hidden transform origin-top-right z-[100] flex flex-col overflow-hidden">
                    <div onclick="location.href='index.php?route=switch_lang&lang=zh'" class="cursor-pointer block px-4 py-3 text-sm hover:bg-white/10 text-center <?php echo ($_SESSION['lang'] ?? 'zh') == 'zh' ? 'text-indigo-400 font-bold' : 'text-gray-300'; ?>">中文</div>
                    <div class="h-px bg-gray-700 w-full"></div>
                    <div onclick="location.href='index.php?route=switch_lang&lang=en'" class="cursor-pointer block px-4 py-3 text-sm hover:bg-white/10 text-center <?php echo ($_SESSION['lang'] ?? 'zh') == 'en' ? 'text-indigo-400 font-bold' : 'text-gray-300'; ?>">English</div>
                </div>

                <script>
                    const langBtn = document.getElementById('langBtn');
                    const langMenu = document.getElementById('langMenu');
                    
                    langBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        langMenu.classList.toggle('hidden');
                    });

                    document.addEventListener('click', (e) => {
                        if (!langBtn.contains(e.target) && !langMenu.contains(e.target)) {
                            langMenu.classList.add('hidden');
                        }
                    });
                </script>
            </div>
            
            <div class="h-4 w-px bg-gray-700"></div>

            <a href="index.php?route=login" class="text-sm font-medium hover:text-indigo-400 transition-colors"><?php echo $LOCALE['nav_login']; ?></a>
            <a href="index.php?route=login" class="px-5 py-2 bg-white text-gray-900 rounded-full font-bold text-sm hover:bg-gray-100 transition-all shadow-lg hover:shadow-white/20"><?php echo $LOCALE['nav_register']; ?></a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="relative z-10 container mx-auto px-6 pt-12 pb-20 md:pt-20 md:pb-32 text-center">
        
        <div class="inline-block mb-6 px-4 py-1.5 rounded-full border border-indigo-500/30 bg-indigo-500/10 text-indigo-300 text-xs font-medium tracking-wide uppercase">
            <?php echo $LOCALE['hero_tag']; ?>
        </div>

        <h1 class="text-4xl md:text-7xl font-extrabold mb-8 tracking-tight leading-tight">
            <?php echo $LOCALE['hero_title_1']; ?> <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400"><?php echo $LOCALE['hero_title_2']; ?></span>
        </h1>
        
        <p class="text-lg md:text-xl text-gray-400 mb-12 max-w-2xl mx-auto leading-relaxed">
            <?php echo $LOCALE['hero_desc']; ?>
        </p>

        <div class="flex flex-col md:flex-row justify-center gap-4 mb-20">
            <a href="index.php?route=login" class="px-6 py-3 md:px-8 md:py-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-2xl font-bold text-lg transition-all shadow-xl shadow-indigo-500/20 transform hover:-translate-y-1">
                <?php echo $LOCALE['hero_cta_start']; ?> <i class="fa fa-arrow-right ml-2 opacity-80"></i>
            </a>
            <a href="https://github.com/pandax-i/LiteTalk" target="_blank" class="px-6 py-3 md:px-8 md:py-4 glass text-white rounded-2xl font-bold text-lg hover:bg-white/10 transition-all">
                <?php echo $LOCALE['hero_cta_more']; ?>
            </a>
        </div>

        <!-- Float Mockup -->
        <div class="relative max-w-4xl mx-auto perspective-1000 group">
            <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500 to-pink-600 rounded-2xl blur opacity-30 group-hover:opacity-50 transition duration-1000"></div>
            <div class="relative glass rounded-2xl p-4 border border-white/10 shadow-2xl transform transition-transform duration-500 group-hover:rotate-x-2">
                <!-- Mock UI -->
                <div class="bg-gray-900 rounded-xl overflow-hidden aspect-video flex">
                    <!-- Sidebar Mock -->
                    <div class="w-1/4 bg-gray-800 border-r border-gray-700 p-3 hidden md:block">
                        <div class="flex gap-2 mb-4">
                            <div class="w-3 h-3 rounded-full bg-red-500"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        </div>
                        <div class="space-y-2">
                            <div class="h-8 bg-gray-700 rounded-lg w-full animate-pulse"></div>
                            <div class="h-8 bg-gray-700 rounded-lg w-3/4 animate-pulse"></div>
                        </div>
                    </div>
                    <!-- Chat Mock -->
                    <div class="flex-1 p-4 flex flex-col">
                        <div class="flex-1 space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-500"></div>
                                <div class="bg-gray-800 p-3 rounded-2xl rounded-tl-none text-xs text-gray-300 w-2/3">
                                    <?php echo ($_SESSION['lang'] ?? 'zh') == 'zh' ? '欢迎来到轻聊 LiteTalk！这是一个示例消息。' : 'Welcome to LiteTalk! This is a demo message.'; ?>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 flex-row-reverse">
                                <div class="w-8 h-8 rounded-full bg-pink-500"></div>
                                <div class="bg-indigo-600 p-3 rounded-2xl rounded-tr-none text-xs text-white w-1/2">
                                    <?php echo ($_SESSION['lang'] ?? 'zh') == 'zh' ? '看起来很不错！支持图片发送吗？' : 'Looks great! Does it support image sending?'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 h-10 bg-gray-800 rounded-lg border border-gray-700"></div>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- Features Section -->
    <section id="features" class="relative z-10 bg-gray-900/50 py-24 border-t border-white/5">
        <div class="container mx-auto px-6">
            <h2 class="text-3xl font-bold text-center mb-16"><?php echo $LOCALE['feat_title']; ?></h2>
            
            <div class="grid md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="glass p-8 rounded-3xl hover:bg-white/5 transition-colors group">
                    <div class="w-14 h-14 bg-indigo-500/20 rounded-2xl flex items-center justify-center text-indigo-400 text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa fa-comments"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3"><?php echo $LOCALE['feat_1_title']; ?></h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        <?php echo $LOCALE['feat_1_desc']; ?>
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="glass p-8 rounded-3xl hover:bg-white/5 transition-colors group">
                    <div class="w-14 h-14 bg-pink-500/20 rounded-2xl flex items-center justify-center text-pink-400 text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa fa-photo"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3"><?php echo $LOCALE['feat_2_title']; ?></h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        <?php echo $LOCALE['feat_2_desc']; ?>
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="glass p-8 rounded-3xl hover:bg-white/5 transition-colors group">
                    <div class="w-14 h-14 bg-amber-500/20 rounded-2xl flex items-center justify-center text-amber-400 text-2xl mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa fa-fire"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3"><?php echo $LOCALE['feat_3_title']; ?></h3>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        <?php echo $LOCALE['feat_3_desc']; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tech Stack Section (New) -->
    <section class="relative z-10 py-20 border-t border-white/5 bg-black/20">
        <div class="container mx-auto px-6 text-center">
            <h2 class="text-2xl font-bold mb-10 text-gray-300"><?php echo $LOCALE['tech_title']; ?></h2>
            <div class="flex flex-wrap justify-center gap-8 md:gap-16 opacity-70 grayscale hover:grayscale-0 transition-all duration-500">
                <div class="flex flex-col items-center gap-2 group">
                    <i class="fa fa-server text-4xl text-purple-400 group-hover:scale-110 transition-transform"></i>
                    <span class="text-xs font-mono">PHP 7.4+</span>
                </div>
                <div class="flex flex-col items-center gap-2 group">
                    <i class="fa fa-database text-4xl text-blue-400 group-hover:scale-110 transition-transform"></i>
                    <span class="text-xs font-mono">MySQL 8.0</span>
                </div>
                <div class="flex flex-col items-center gap-2 group">
                    <i class="fa fa-bolt text-4xl text-yellow-400 group-hover:scale-110 transition-transform"></i>
                    <span class="text-xs font-mono">WebSocket</span>
                </div>
                <div class="flex flex-col items-center gap-2 group">
                    <i class="fa fa-css3 text-4xl text-cyan-400 group-hover:scale-110 transition-transform"></i>
                    <span class="text-xs font-mono">Tailwind</span>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section (New) -->
    <section class="relative z-10 py-24 border-t border-white/5">
        <div class="container mx-auto px-6 max-w-4xl">
            <h2 class="text-3xl font-bold text-center mb-16"><?php echo $LOCALE['faq_title']; ?></h2>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="glass p-6 rounded-2xl">
                    <h3 class="font-bold text-lg mb-2 text-indigo-300"><?php echo $LOCALE['faq_1_q']; ?></h3>
                    <p class="text-gray-400 text-sm"><?php echo $LOCALE['faq_1_a']; ?></p>
                </div>
                <div class="glass p-6 rounded-2xl">
                    <h3 class="font-bold text-lg mb-2 text-indigo-300"><?php echo $LOCALE['faq_2_q']; ?></h3>
                    <p class="text-gray-400 text-sm"><?php echo $LOCALE['faq_2_a']; ?></p>
                </div>
                <div class="glass p-6 rounded-2xl">
                    <h3 class="font-bold text-lg mb-2 text-indigo-300"><?php echo $LOCALE['faq_3_q']; ?></h3>
                    <p class="text-gray-400 text-sm"><?php echo $LOCALE['faq_3_a']; ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative z-10 border-t border-white/5 py-12 text-center text-gray-500 text-sm bg-gray-900">
        <div class="container mx-auto px-6">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $LOCALE['app_name']; ?>. All rights reserved.</p>
            <div class="mt-4 flex justify-center gap-4">
                <a href="#" class="hover:text-white transition-colors"><?php echo $LOCALE['footer_privacy']; ?></a>
                <a href="#" class="hover:text-white transition-colors"><?php echo $LOCALE['footer_terms']; ?></a>
                <a href="https://github.com/pandax-i/LiteTalk" class="hover:text-white transition-colors" target="_blank"><i class="fa fa-github"></i> GitHub</a>
            </div>
        </div>
    </footer>

</body>
</html>
