# 🚀 LiteTalk - Lightweight Real-time Chat System

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://www.php.net/)
[![WebSocket](https://img.shields.io/badge/websocket-native-green.svg)](https://developer.mozilla.org/en-US/docs/Web/API/WebSocket)

[🇨🇳 中文文档](README.md) | [🇺🇸 English](README_EN.md)

A modern, high-performance, secure, and feature-rich online chat system built with native PHP + WebSocket. Supports public chat rooms, private messaging, multimedia, and various privacy features.

![Project Preview](https://via.placeholder.com/800x450.png?text=Preview+Image)

## ✨ Key Features

### 💬 Chat Experience
- **Real-time**: Millisecond-level message delivery powered by native PHP Socket implementation.
- **Responsive**: Mobile-first design using Tailwind CSS, perfect for PC and mobile.
- **Multimedia**: Send images, **videos** (with upload preview), and **voice messages**.
- **Interactive**: Built-in Emoji picker and Markdown support.

### 🔒 Privacy & Security
- **Burn After Read**: Self-destructing private messages to protect your privacy.
- **Recall**: Revoke messages in both public and private chats (bidirectional sync).
- **Encrypted/Hidden Rooms**: Password-protected private circles, or **Hidden Rooms** (access by ID/Invite only).
- **Security**: Full CSRF protection, XSS filtering, and randomized file naming.

### 🛠 Management
- **Visual Installer**: Built-in `install.php` wizard, deploy in 2 minutes.
- **Admin Panel**: User management (Ban/Unban), room management, audit logs, and **system settings**.
- **Bilingual Landing Page**: Beautiful product showcase with **English/Chinese switching**, togglable via Admin Panel.
- **Storage**: Support Local storage, S3/R2/COS/AWS (Extendable).

## ⚙️ Tech Stack

- **Backend**: PHP 7.4+ (Native Socket / MySQLi)
- **Frontend**: HTML5, JavaScript (ES6+), Tailwind CSS
- **Database**: MySQL 5.7 / 8.0
- **Real-time**: Native PHP WebSocket Server

## 🚀 Quick Start

### Requirements
- PHP >= 7.4 (Extensions: `sockets`, `mbstring`, `gd`)
- MySQL >= 5.7
- Web Server (Nginx/Apache/IIS)

### Installation Steps

1. **Clone Repo**
   ```bash
   git clone https://github.com/pandax-i/LiteTalk.git
   cd php-chat-room
   ```

2. **Permissions**
   Make sure `uploads/` directory is writable:
   ```bash
   chmod -R 755 uploads/
   chmod 777 . 
   ```

3. **Install**
   Visit `http://your-domain.com/install.php` and follow the wizard.

4. **Start WebSocket**
   Run via CLI (Use Supervisor/nohup for production):
   ```bash
   php server.php
   ```

### 📂 Baota Panel Deployment

1. **Create Site**: PHP 7.4+, MySQL 5.7+.
2. **Upload Code**: Upload and extract source code to web root.
3. **Permissions**: Set `uploads` directory permission to `755`, owner `www`.
4. **Install**: Access domain to run installer.
5. **Supervisor (Important)**:
   - Add daemon task in "Supervisor Manager".
   - Command: `php server.php`
   - User: `www`
   - Directory: `/www/wwwroot/your-site/`
6. **Firewall**: Open port `8080` in Baota Security and Cloud Provider Security Group.

## ⚙️ Configuration

### Toggle Landing Page
You can enable or disable the public landing page in the Admin Panel.
- **Enabled (Default)**: Unauthenticated users see a beautiful product showcase page.
- **Disabled**: Users are redirected directly to the Login page (Suitable for private/internal use).
- **Path**: `Admin Panel` -> `System Settings` -> `General` -> `Enable Landing Page`.

## 📂 Directory Structure

```
├── controllers/      # Controllers
├── core/            # Core Libraies (WebSocket, Auth, DB)
├── views/           # View Templates
├── uploads/         # Media Storage
├── index.php        # Entry Point
├── server.php       # WebSocket Server
├── install.php      # Installer
└── config.php       # Config File (Generated)
```

## 🔧 Nginx Config (Optional)

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# WebSocket Proxy
location /ws/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
}
```

## 📅 Future Roadmap (LiteTalk 2.0)

We are planning LiteTalk 2.0, aiming to build a more modern and powerful IM system.

### 🚀 Phase 1: Architecture Refactoring
- **High Performance Async**: Migrate to **Swoole** / **Workerman** for 10k+ concurrency.
- **Redis Integration**: Cache and Message Queue for better stability and speed.
- **Frontend/Backend Separation**: API-first backend with Vue3/React frontend.

### ✨ Phase 2: Core Enhancements
- **Audio/Video Calls**: WebRTC-based 1v1 calls and Group Voice/Live channels.
- **Bot Ecosystem**: Webhook support for message push and AI bots.
- **Social Feed**: Moments/Timeline feature for social networking.

### 🎨 Phase 3: Experience Upgrade
- **Cross-Platform**: PWA / Mobile App support.
- **Global Search**: Full-text search for messages and files.
- **Message Interactions**: Quote reply, Read receipts, and Link previews.

### 🛡️ Phase 4: Enterprise Features
- **Multi-Tenancy**: Workspace/Organization data isolation.
- **End-to-End Encryption**: Ultimate privacy, server stores only ciphertext.

## 🤝 Contribution

Issue and Pull Request are welcome!

## 📄 License

[MIT License](LICENSE).

## 📮 Contact & Support

If you have any questions, suggestions, or want to discuss collaboration, feel free to contact me:

- **📧 Email**: [your-email@example.com](mailto:your-email@example.com)
- **🌍 Website**: [https://your-website.com](https://your-website.com)
- **🐦 Twitter / X**: [@yourusername](https://twitter.com/yourusername)
- **💼 LinkedIn**: [Your Name](https://linkedin.com/in/yourprofile)

> 💡 **Tip**: If you find a bug in the code, please submit it directly in [GitHub Issues](../../issues) to help more people.
