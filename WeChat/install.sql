-- 在线聊天室 安装脚本 (install.sql)
-- 包含所有核心表结构及最新功能字段

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. 用户表 (users)
-- 包含角色、昵称、密码哈希
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `nickname` varchar(50) DEFAULT NULL COMMENT '显示昵称',
  `role` enum('user','admin') DEFAULT 'user' COMMENT '用户角色',
  `password` varchar(255) DEFAULT NULL COMMENT '密码哈希',
  `avatar` varchar(100) DEFAULT 'default_avatar.png' COMMENT '头像路径',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户列表';

-- ----------------------------
-- 2. 房间表 (rooms)
-- 包含所有的聊天室信息
-- ----------------------------
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '房间名称',
  `description` text COMMENT '房间描述',
  `password` varchar(255) DEFAULT NULL COMMENT '房间密码(加密)',
  `created_by` int(11) NOT NULL COMMENT '创建者ID',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='聊天房间';

-- ----------------------------
-- 3. 房间成员表 (room_members)
-- 记录用户与房间的关系，以及在线/禁言状态
-- ----------------------------
DROP TABLE IF EXISTS `room_members`;
CREATE TABLE `room_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_online` tinyint(1) DEFAULT '1' COMMENT '在线状态',
  `is_muted` tinyint(1) DEFAULT '0' COMMENT '是否禁言',
  `joined_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_active` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_user` (`room_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `room_members_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='房间成员关联';

-- ----------------------------
-- 4. 消息表 (messages)
-- 包含文本、图片、音视频及阅后即焚、撤回状态
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text COMMENT '文本内容',
  `image_url` varchar(255) DEFAULT NULL COMMENT '图片路径',
  `video_url` varchar(255) DEFAULT NULL COMMENT '视频路径',
  `audio_url` varchar(255) DEFAULT NULL COMMENT '音频路径',
  `burn_after_read` int(11) DEFAULT '0' COMMENT '阅后即焚时间(秒)',
  `is_recalled` tinyint(1) DEFAULT '0' COMMENT '是否撤回',
  `recalled_at` datetime DEFAULT NULL COMMENT '撤回时间',
  `opened_at` datetime DEFAULT NULL COMMENT '首次查看时间(用于阅后即焚)',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='聊天记录';

-- ----------------------------
-- 5. 系统设置表 (system_settings)
-- ----------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统全局设置';

-- 初始设置数据
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('site_name', '轻聊 LiteTalk'),
('site_announcement', '欢迎来到聊天室！请文明发言。'),
('landing_page_enabled', '1'),
('registration_enabled', '1'),
('storage_driver', 'local'),
('upload_max_size', '10'),
('upload_image_limit', '5'),
('upload_video_limit', '50');

SET FOREIGN_KEY_CHECKS = 1;
