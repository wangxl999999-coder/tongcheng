# 部署文档

## 环境要求

### 服务器环境
- **操作系统**: Linux (推荐 CentOS 7+ / Ubuntu 18.04+)
- **Web服务器**: Nginx 1.14+ 或 Apache 2.4+
- **PHP版本**: PHP 7.4 ~ PHP 8.1
- **MySQL版本**: MySQL 5.7+ 或 MariaDB 10.2+
- **内存**: 最低 2GB，推荐 4GB+
- **硬盘**: 最低 20GB 可用空间

### PHP扩展要求
- mysqli
- pdo_mysql
- json
- mbstring
- curl
- gd
- openssl
- zip
- fileinfo

### 小程序环境
- 微信小程序开发工具
- 已认证的微信小程序账号
- 已申请的微信支付商户号（如需支付功能）

---

## 一、服务器环境搭建

### 1.1 Linux服务器推荐配置

#### 使用宝塔面板（推荐）
1. 安装宝塔面板
```bash
# CentOS
yum install -y wget && wget -O install.sh http://download.bt.cn/install/install_6.0.sh && sh install.sh

# Ubuntu
wget -O install.sh http://download.bt.cn/install/install-ubuntu_6.0.sh && sudo bash install.sh
```

2. 安装软件
- 安装 Nginx 1.20+
- 安装 PHP 7.4
- 安装 MySQL 5.7+
- 安装 phpMyAdmin

#### 手动安装（CentOS 7）

```bash
# 安装Nginx
yum install -y nginx
systemctl start nginx
systemctl enable nginx

# 安装PHP 7.4
yum install -y epel-release
yum install -y https://rpms.remirepo.net/enterprise/remi-release-7.rpm
yum install -y yum-utils
yum-config-manager --enable remi-php74
yum install -y php php-fpm php-mysqlnd php-json php-mbstring php-curl php-gd php-openssl php-zip php-fileinfo

# 启动PHP-FPM
systemctl start php-fpm
systemctl enable php-fpm

# 安装MySQL 5.7
wget https://dev.mysql.com/get/mysql57-community-release-el7-11.noarch.rpm
rpm -ivh mysql57-community-release-el7-11.noarch.rpm
yum install -y mysql-server
systemctl start mysqld
systemctl enable mysqld
```

---

## 二、数据库部署

### 2.1 创建数据库

```sql
-- 登录MySQL
mysql -u root -p

-- 创建数据库
CREATE DATABASE tongcheng DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 创建数据库用户
CREATE USER 'tongcheng'@'localhost' IDENTIFIED BY 'your_password';

-- 授权
GRANT ALL PRIVILEGES ON tongcheng.* TO 'tongcheng'@'localhost';
FLUSH PRIVILEGES;
```

### 2.2 导入数据库脚本

```bash
# 方式1：命令行导入
mysql -u tongcheng -p tongcheng < /path/to/database/tongcheng.sql

# 方式2：使用phpMyAdmin导入
# 登录phpMyAdmin -> 选择tongcheng数据库 -> 导入 -> 选择tongcheng.sql文件
```

### 2.3 修改数据库配置

**管理后台配置文件**: `admin/config/database.php`
```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'tongcheng',
    'username' => 'tongcheng',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
];
```

**API接口配置文件**: `api/config/database.php`
```php
<?php
return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'dbname' => 'tongcheng',
    'username' => 'tongcheng',
    'password' => 'your_password',
    'charset' => 'utf8mb4'
];
```

---

## 三、PHP后端部署

### 3.1 上传项目文件

将项目文件上传到服务器的Web根目录，例如：
- `/www/wwwroot/tongcheng/`

### 3.2 目录结构

```
/www/wwwroot/tongcheng/
├── admin/              # 管理后台
├── api/                # API接口
├── database/           # 数据库脚本
└── docs/               # 文档
```

### 3.3 配置Nginx

**管理后台配置** (`/etc/nginx/conf.d/admin.tongcheng.com.conf`):
```nginx
server {
    listen 80;
    server_name admin.tongcheng.com;
    root /www/wwwroot/tongcheng/admin;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**API接口配置** (`/etc/nginx/conf.d/api.tongcheng.com.conf`):
```nginx
server {
    listen 80;
    server_name api.tongcheng.com;
    root /www/wwwroot/tongcheng/api;
    index index.php index.html;

    # CORS配置
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods GET,POST,OPTIONS;
    add_header Access-Control-Allow-Headers Content-Type,Authorization;

    if ($request_method = OPTIONS) {
        return 204;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 3.4 配置Apache

创建 `.htaccess` 文件：

**管理后台** (`admin/.htaccess`):
```apache
Options +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php?$1 [QSA,L]
```

**API接口** (`api/.htaccess`):
```apache
Options +FollowSymLinks
RewriteEngine On

# CORS配置
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"

RewriteCond %{REQUEST_METHOD} OPTIONS
RewriteRule ^(.*)$ $1 [R=204,L]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php?$1 [QSA,L]
```

### 3.5 目录权限设置

```bash
# 设置文件所有者
chown -R www:www /www/wwwroot/tongcheng/

# 设置目录权限
find /www/wwwroot/tongcheng -type d -exec chmod 755 {} \;

# 设置文件权限
find /www/wwwroot/tongcheng -type f -exec chmod 644 {} \;

# 设置上传目录可写
chmod -R 755 /www/wwwroot/tongcheng/api/uploads
chmod -R 755 /www/wwwroot/tongcheng/admin/uploads
```

### 3.6 修改系统配置

**管理后台配置**: `admin/config/config.php`
```php
<?php
return [
    'site_name' => '同城预约上门服务系统',
    'site_url' => 'http://admin.tongcheng.com',
    'upload_path' => '/uploads/',
    'upload_max_size' => 10 * 1024 * 1024,
    'allowed_upload_types' => ['jpg', 'jpeg', 'png', 'gif'],
    'admin_prefix' => 'admin',
    'session_name' => 'tc_admin_session',
    'session_expire' => 7200,
    'debug' => false
];
```

**API配置**: `api/config/config.php`
```php
<?php
return [
    'site_name' => '同城预约上门服务系统',
    'base_url' => 'http://api.tongcheng.com',
    'upload_path' => '/uploads/',
    'upload_max_size' => 10 * 1024 * 1024,
    'allowed_upload_types' => ['jpg', 'jpeg', 'png', 'gif'],
    
    // JWT配置
    'jwt_secret' => 'your_jwt_secret_key_here',
    'jwt_expire' => 7 * 24 * 3600,
    
    // 微信小程序配置
    'wechat' => [
        'app_id' => 'your_wechat_app_id',
        'app_secret' => 'your_wechat_app_secret',
        'login_url' => 'https://api.weixin.qq.com/sns/jscode2session'
    ],
    
    // 微信支付配置
    'wxpay' => [
        'mch_id' => 'your_merchant_id',
        'key' => 'your_api_key',
        'cert_path' => '/path/to/apiclient_cert.pem',
        'key_path' => '/path/to/apiclient_key.pem',
        'notify_url' => 'http://api.tongcheng.com/?c=pay&a=notify'
    ],
    
    // 派单配置
    'dispatch' => [
        'mode' => 'auto', // auto=自动派单, manual=手动派单
        'grab_timeout' => 300, // 抢单超时时间（秒）
        'auto_assign_timeout' => 60 // 自动派单超时时间
    ],
    
    'debug' => false
];
```

---

## 四、小程序部署

### 4.1 小程序基础配置

1. **注册小程序账号**
   - 访问 [微信公众平台](https://mp.weixin.qq.com/) 注册小程序
   - 完成小程序认证
   - 获取 AppID 和 AppSecret

2. **配置小程序服务器域名**
   - 登录微信公众平台 → 开发 → 开发管理 → 开发设置
   - 在服务器域名中配置：
     - request合法域名：`https://api.tongcheng.com`
     - uploadFile合法域名：`https://api.tongcheng.com`
     - downloadFile合法域名：`https://api.tongcheng.com`
   - 必须使用HTTPS协议

### 4.2 修改小程序配置

**用户端**: `miniprogram/user/app.js`
```javascript
globalData: {
    baseUrl: 'https://api.tongcheng.com'
}
```

**技师端**: `miniprogram/technician/app.js`
```javascript
globalData: {
    baseUrl: 'https://api.tongcheng.com'
}
```

**商家端**: `miniprogram/merchant/app.js`
```javascript
globalData: {
    baseUrl: 'https://api.tongcheng.com'
}
```

### 4.3 修改project.config.json

每个端都需要修改 `project.config.json` 中的 `appid` 字段：
```json
{
  "appid": "your_wechat_app_id",
  "projectname": "tongcheng-user"
}
```

### 4.4 小程序开发工具

1. 下载安装 [微信开发者工具](https://developers.weixin.qq.com/miniprogram/dev/devtools/download.html)
2. 导入小程序项目：
   - 打开微信开发者工具 → 导入项目
   - 选择对应的小程序目录（user/technician/merchant）
   - 填入AppID
3. 测试运行，确认接口连接正常

### 4.5 小程序发布

1. 在微信开发者工具中点击「上传」
2. 填写版本号和项目备注
3. 登录微信公众平台 → 版本管理
4. 提交审核
5. 审核通过后发布

---

## 五、HTTPS配置（必须）

微信小程序要求必须使用HTTPS协议。

### 使用Let's Encrypt免费证书

```bash
# 安装certbot
yum install -y certbot python2-certbot-nginx

# 申请证书
certbot --nginx -d api.tongcheng.com -d admin.tongcheng.com

# 自动续期
certbot renew --dry-run
```

### 配置Nginx HTTPS

```nginx
server {
    listen 443 ssl http2;
    server_name api.tongcheng.com;
    
    ssl_certificate /etc/letsencrypt/live/api.tongcheng.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.tongcheng.com/privkey.pem;
    ssl_protocols TLSv1 TLSv1.1 TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    root /www/wwwroot/tongcheng/api;
    index index.php index.html;
    
    # ... 其他配置同上 ...
}

# HTTP跳转到HTTPS
server {
    listen 80;
    server_name api.tongcheng.com;
    return 301 https://$server_name$request_uri;
}
```

---

## 六、微信支付配置

1. **申请微信支付商户号**
   - 访问 [微信支付商户平台](https://pay.weixin.qq.com/) 注册
   - 完成商户认证
   - 关联小程序

2. **配置API密钥**
   - 登录商户平台 → 账户中心 → API安全
   - 设置API密钥
   - 下载API证书

3. **上传证书到服务器**
   - 将 `apiclient_cert.pem` 和 `apiclient_key.pem` 上传到服务器
   - 保存路径如：`/www/wwwroot/tongcheng/api/cert/`
   - 设置权限：`chmod 600 *.pem`

4. **配置支付回调**
   - 在商户平台配置支付回调地址：`https://api.tongcheng.com/?c=pay&a=notify`

---

## 七、测试与验收

### 7.1 管理后台测试
- [ ] 登录功能正常
- [ ] 数据概览数据正确
- [ ] 数据大屏图表显示正常
- [ ] 城市、分类、服务项目管理功能正常
- [ ] 订单管理功能正常
- [ ] 用户、技师、商家管理功能正常

### 7.2 API接口测试
- [ ] 登录接口正常
- [ ] 首页数据接口正常
- [ ] 服务列表、详情接口正常
- [ ] 订单创建、支付、取消接口正常
- [ ] 用户中心接口正常
- [ ] 技师端、商家端接口正常

### 7.3 小程序测试
- [ ] 用户端所有页面正常访问
- [ ] 下单流程正常
- [ ] 支付流程正常
- [ ] 技师端抢单、接单、服务流程正常
- [ ] 商家端订单管理、核销流程正常

---

## 八、常见问题

### 8.1 接口请求失败
- 检查域名是否正确配置
- 检查HTTPS证书是否有效
- 检查Nginx配置是否正确
- 检查PHP-FPM是否正常运行

### 8.2 小程序无法登录
- 检查AppID和AppSecret是否正确
- 检查服务器是否能访问微信接口
- 检查接口返回的code是否正确

### 8.3 支付失败
- 检查商户号配置是否正确
- 检查API证书是否正确上传
- 检查支付回调地址是否可访问
- 检查服务器时间是否正确

### 8.4 图片上传失败
- 检查上传目录是否存在且有写入权限
- 检查PHP配置的上传大小限制
- 检查文件类型是否在白名单内

### 8.5 数据库连接失败
- 检查数据库服务是否正常运行
- 检查数据库配置信息是否正确
- 检查数据库用户权限是否正确

---

## 九、安全加固建议

1. **修改默认管理员密码**
   - 登录后台后立即修改admin用户的默认密码

2. **禁止目录遍历**
   - 在Nginx配置中添加：`autoindex off;`

3. **限制敏感文件访问**
   ```nginx
   location ~* \.(env|log|sql|md)$ {
       deny all;
   }
   ```

4. **配置WAF防火墙**
   - 推荐使用宝塔面板的Nginx防火墙

5. **定期备份数据**
   ```bash
   # 数据库备份脚本
   mysqldump -u tongcheng -p'password' tongcheng > /backup/tongcheng_$(date +%Y%m%d).sql
   ```

6. **开启PHP错误日志**
   - 便于排查问题，但生产环境关闭错误显示

7. **修改JWT密钥**
   - 修改 `api/config/config.php` 中的 `jwt_secret` 为随机字符串

---

## 十、默认账号

| 系统 | 用户名 | 密码 |
|------|--------|------|
| 管理后台 | admin | 123456 |

> 重要：请在正式部署后立即修改默认密码！
