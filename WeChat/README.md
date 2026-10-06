# 🚀 LiteTalk (轻聊) - Copyright (c) 2025 LiteTalk TeamSystem

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://www.php.net/)
[![WebSocket](https://img.shields.io/badge/websocket-native-green.svg)](https://developer.mozilla.org/en-US/docs/Web/API/WebSocket)

[🇨🇳 中文文档](README.md) | [🇺🇸 English](README_EN.md)

一个基于原生 PHP + WebSocket 实现的现代化、高性能、安全且功能丰富的在线聊天系统。支持公共聊天室、私聊、多媒体发送及多项隐私安全特性。

![Project Preview](https://via.placeholder.com/800x450.png?text=Preview+Image)

## ✨ 核心特性

### 💬 聊天体验
- **即时通讯**：基于原生 PHP Socket 实现 WebSocket 服务，毫秒级消息投递。
- **多端适配**：响应式设计（Tailwind CSS），完美支持 PC 与移动端。
- **多媒体支持**：支持发送图片、视频及 **语音消息**，发送前提供**视频/图片预览**。
- **表情互动**：内置 Emoji 选择器与 Markdown 基础语法支持。

### 🔒 隐私与安全
- **阅后即焚**：支持私聊消息“阅后即焚”模式，倒计时自动销毁，保护隐私。
- **消息撤回**：支持全员/私聊消息撤回（双向同步）。
- **加密/隐藏房间**：支持设置房间密码，或创建**隐藏房间**（仅可通过 ID/邀请 访问）。
- **安全防护**：全站 CSRF 防护，XSS 过滤，文件名随机化处理。

### 🛠 管理与维护
- **可视化安装**：内置 `install.php` 安装向导，两分钟快速部署。
- **后台管理**：功能完善的后台管理系统，支持用户封禁、房间管理、聊天记录审计。
- **多语言落地页**：精美的首页落地页，支持**中/英双语切换**，后台可一键开启/关闭。
- **存储扩展**：支持本地存储、阿里云 OSS 等多种存储驱动（可扩展）。

## ⚙️ 技术栈

- **后端**：PHP 7.4+ (Native Socket / MySQLi)
- **前端**：HTML5, JavaScript (ES6+), Tailwind CSS
- **数据库**：MySQL 5.7 / 8.0
- **实时通信**：Native PHP WebSocket Server

## 🚀 快速开始

### 环境要求
- PHP >= 7.4 (需开启 `sockets`, `mbstring`, `gd` 扩展)
- MySQL >= 5.7
- Web 服务器 (Nginx/Apache/IIS)

### 安装步骤

1. **克隆项目**
   ```bash
   git clone https://github.com/pandax-i/LiteTalk.git
   cd php-chat-room
   ```

2. **配置目录权限**
   确保 `uploads/` 目录及根目录可写（用于生成 `config.php`）：
   ```bash
   chmod -R 755 uploads/
   chmod 777 . 
   ```

3. **运行安装向导**
   访问网站首页，系统将自动跳转至安装页面：
   `http://your-domain.com/install.php`
   *(按照指引完成数据库配置及管理员创建)*

4. **启动 WebSocket 服务**
   在服务器终端运行：
   ```bash
   php server.php
   ```
   *(建议使用 Supervisor 或 nohup 保持后台运行)*

### 📂 宝塔面板部署教程

1. **创建站点**
   - 在宝塔面板“网站”菜单添加站点，PHP 版本选择 **7.4+**。
   - 创建数据库 MySQL 5.7+。

2. **上传源码**
   - 进入网站根目录，上传并解压源码。
   - **权限设置**：将 `uploads` 目录权限设为 `755`，用户组设为 `www`。

3. **安装系统**
   - 访问域名 `http://yourdomain.com/install.php`。
   - 填写数据库信息及管理员账号，完成安装。

4. **配置 WebSocket (重要)**
   - 在宝塔“软件商店”安装 **Supervisor管理器**。
   - 添加守护进程：
     - **名称**：`chat_server`
     - **启动用户**：`www`
     - **运行目录**：`/www/wwwroot/你的网站目录/`
     - **启动命令**：`php server.php`
   - 点击“启动”，查看日志显示 `New client connected` 等信息即成功。

5. **放行端口**
   - 在宝塔“安全”菜单放行 `8080` 端口（或你设置的其他端口）。
   - 如果使用阿里云/腾讯云，记得在云厂商的“安全组”中也放行该端口。

## ⚙️ 系统配置

### 落地页开关 (Landing Page)
您可以在后台自由开启或关闭首页落地页：
- **开启 (默认)**：未登录用户访问首页将展示精美的产品介绍页（含技术栈、FAQ等）。
- **关闭**：未登录用户直接跳转至登录页面（适合内部私有化部署）。
- **设置路径**：`管理后台` -> `系统设置` -> `基础设置` -> `启用首页落地页`。

## 📂 目录结构

```
├── controllers/      # 业务逻辑控制器
├── core/            # 核心类库 (WebSocket, Auth, DB)
├── views/           # 前端视图模板
├── uploads/         # 媒体文件存储
├── index.php        # Web 入口
├── server.php       # WebSocket 服务端入口
├── install.php      # 安装引导
└── config.php       # 配置文件 (安装后生成)
```

## 🔧 Nginx 配置示例

为了美化 URL，建议配置伪静态（可选）：

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# WebSocket 代理 (可选，如果通过 Nginx 转发 WS)
location /ws/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
}
```

## 📅 未来规划 (LiteTalk 2.0)

我们正在规划 LiteTalk 2.0 版本，致力于打造更现代、更强大的即时通讯系统。

### 🚀 第一阶段：架构重构
- **高性能异步框架**: 迁移至 **Swoole** / **Workerman**，支持万级并发。
- **Redis 深度集成**: 引入缓存与消息队列，提升系统响应速度与稳定性。
- **前后端分离**: 后端 API 化，前端重构为 Vue3 / React，支持更丝滑的交互体验。

### ✨ 第二阶段：核心增强
- **音视频通话**: 基于 WebRTC 实现 1v1 通话及群组语音直播。
- **机器人系统**: 开放 Webhook 接口，支持消息推送与 AI 机器人接入。
- **动态广场**: 新增朋友圈功能，打造更完整的社交生态。

### 🎨 第三阶段：体验升级
- **全平台支持**: PWA / Mobile App 适配。
- **全局搜索**: 引入全文检索，支持毫秒级查找聊天记录。
- **消息互动**: 支持引用回复、消息已读回执及链接预览。

### 🛡️ 第四阶段：企业级特性
- **多租户架构**: 支持多组织/工作区数据隔离。
- **端到端加密**: 极致隐私保护，服务器仅存储密文。

## 🤝 贡献参与

欢迎提交 Issue 或 Pull Request！我们欢迎任何形式的贡献，包括但不限于新功能开发、Bug 修复或文档完善。

## 📄 开源协议

本项目采用 [MIT License](LICENSE) 开源。

## 📮 联系与支持 (Contact)

如果你有任何问题、建议，或者想探讨合作，欢迎通过以下方式联系我：

- **📧 Email**: [email](mailto:bitekaola@coze.email)
- **🌍 Website**: [https://www.bitekaola.com](https://www.bitekaola.com)

> 💡 **提示**: 如果你发现了代码中的 Bug，建议直接在 [GitHub Issues](../../issues) 中提交，这样能帮助到更多人。
