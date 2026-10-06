-- 数据库升级脚本 (如果自动升级失败，请手动运行此脚本)

-- 1. 创建系统设置表
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 插入默认设置
INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES 
('storage_driver', 'local'),
('upload_max_size', '5');

-- 3. 添加用户角色字段 (如果报错说明已存在)
-- 注意：MySQL 5.7+ 不支持 IF NOT EXISTS 用于列，如果运行报错请忽略
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user' AFTER username;

-- 4. 添加用户昵称字段
ALTER TABLE users ADD COLUMN nickname VARCHAR(50) DEFAULT NULL AFTER username;
UPDATE users SET nickname = username WHERE nickname IS NULL;

-- 5. 消息撤回和密码字段 (历史补丁)
ALTER TABLE messages ADD COLUMN is_recalled BOOLEAN DEFAULT 0;
ALTER TABLE messages ADD COLUMN recalled_at DATETIME NULL;
ALTER TABLE users ADD COLUMN password VARCHAR(255) DEFAULT NULL;

-- 6. 设置默认管理员 (将 'admin' 用户设为管理员)
UPDATE users SET role = 'admin' WHERE username = 'admin';
