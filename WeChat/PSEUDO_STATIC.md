# 伪静态配置指南 (URL Rewrite)

本项目默认使用 RESTful 风格的 URL 参数（如 `index.php?route=chat`）。如果您希望使用更美观的伪静态 URL（如 `/chat`），请根据您的服务器环境进行以下配置。

**注意**：配置伪静态仅支持通过短链接访问，系统生成的默认链接（如菜单导航）可能仍会包含 `index.php`，这属于正常现象。

---

## 1. Nginx 配置
在您的站点配置文件（通常是 `nginx.conf` 或 `vhost` 文件）的 `server` 块中添加以下规则：

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^/(.*)$ /index.php?route=$1 last;
    }
}
```

*说明：如果文件或目录不存在，则将请求重写到 `index.php` 并将路径作为 `route` 参数传递。*

---

## 2. Apache 配置
在项目根目录下创建或修改 `.htaccess` 文件：

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  
  # 如果请求的不是文件
  RewriteCond %{REQUEST_FILENAME} !-f
  # 如果请求的不是目录
  RewriteCond %{REQUEST_FILENAME} !-d
  
  # 重写规则
  RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]
</IfModule>
```

---

## 3. IIS 配置 (Windows)
在项目根目录下创建 `web.config` 文件：

```xml
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <rewrite>
            <rules>
                <rule name="PseudoStatic" stopProcessing="true">
                    <match url="^(.*)$" />
                    <conditions>
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true" />
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true" />
                    </conditions>
                    <action type="Rewrite" url="index.php?route={R:1}" />
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
```

---

## 4. 宝塔面板 (BT Panel)
如果您使用的是宝塔面板：
1. 进入 **网站** -> **设置**。
2. 点击左侧菜单的 **伪静态**。
3. 下拉菜单选择 **thinkphp** (虽然本项目不是ThinkPHP，但规则类似) 或者直接将上述 Nginx/Apache 规则复制进去保存即可。

---

## 5. 常见问题
- **404 错误**：请检查伪静态规则是否生效，以及 rewrite 模块是否已开启。
- **静态资源丢失**：规则中已包含 `!-f` 和 `!-d` 判断，确保真实存在的 CSS/JS/图片文件不会被重写。
