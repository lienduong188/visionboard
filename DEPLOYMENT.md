# 🚀 VisionBoard 2026 - Production Deployment Guide

## 📋 Checklist Deploy lên Sakura Server (Subfolder)

### 1. ⚙️ Cấu hình .env trên Server

Tạo file `.env` trên server với nội dung:

```env
APP_NAME="VisionBoard 2026"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://duonglien.com/visionboard2026

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

# Session (QUAN TRỌNG cho subfolder)
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_PATH=/visionboard2026/
SESSION_DOMAIN=.duonglien.com
SESSION_SECURE_COOKIE=true

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_STORE=database

# Mail (cho reminders)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@duonglien.com"
MAIL_FROM_NAME="${APP_NAME}"

# Asset URL (QUAN TRỌNG cho subfolder)
ASSET_URL=https://duonglien.com/visionboard2026

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error
```

**⚠️ Lưu ý quan trọng:**
- `APP_URL` phải là full URL với subfolder
- `SESSION_PATH` phải là `/visionboard2026/` để cookies hoạt động
- `ASSET_URL` để Laravel biết đường dẫn assets
- `APP_DEBUG=false` trên production

### 2. 🏗️ Build Assets (chạy local trước khi upload)

```bash
# Build với base path cho subfolder
npm run build
```

Vite sẽ tự động dùng base path `/visionboard2026/build/` (đã config trong vite.config.js)

### 3. 📤 Upload Files lên Server

Upload toàn bộ folder `visionboard2026` vào thư mục:
```
/home/username/www/visionboard2026/
```

**⚠️ KHÔNG upload vào thư mục portfolio!**

### 4. 🔐 Set File Permissions

```bash
# SSH vào server
ssh your_server

# Đi đến thư mục visionboard
cd ~/www/visionboard2026

# Set permissions
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 5. 🗄️ Setup Database

```bash
cd ~/www/visionboard2026

# Chạy migrations
php artisan migrate --force

# (Optional) Seed dữ liệu mẫu
php artisan db:seed --force

# Tạo symbolic link cho storage
php artisan storage:link
```

### 6. 🔑 Generate APP_KEY (nếu chưa có)

```bash
php artisan key:generate
```

Copy key từ `.env` và lưu lại an toàn.

### 7. ⚡ Optimize Laravel

```bash
# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Cache config cho production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. 🌐 Cấu hình Web Server

#### A. Nếu dùng Apache (.htaccess)

File `.htaccess` ở root đã có (forward sang public/):
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

#### B. Cấu hình VirtualHost (hoặc trong panel)

**QUAN TRỌNG:** Để không ảnh hưởng portfolio, bạn cần:

1. **Document root chính** (portfolio): `/home/username/www/`
2. **VisionBoard subfolder**: `/home/username/www/visionboard2026/public`

**Option 1: Dùng Alias trong Apache config**
```apache
# Trong config của duonglien.com
<VirtualHost *:443>
    ServerName duonglien.com
    DocumentRoot /home/username/www/portfolio

    # Portfolio giữ nguyên
    <Directory /home/username/www/portfolio>
        AllowOverride All
        Require all granted
    </Directory>

    # VisionBoard subfolder
    Alias /visionboard2026 /home/username/www/visionboard2026/public
    <Directory /home/username/www/visionboard2026/public>
        AllowOverride All
        Require all granted
        Options -MultiViews -Indexes
    </Directory>
</VirtualHost>
```

**Option 2: Symlink (dễ hơn nếu không access được config)**
```bash
# Từ thư mục portfolio root
cd ~/www/portfolio  # hoặc document root chính
ln -s ~/www/visionboard2026/public visionboard2026
```

### 9. ✅ Kiểm tra

1. **Check trang web**: https://duonglien.com/visionboard2026/
2. **Check assets load**: Mở DevTools → Network, xem các file CSS/JS có load được không
3. **Check login**: Thử đăng nhập/đăng ký
4. **Check portfolio**: Đảm bảo https://duonglien.com/ vẫn hoạt động bình thường

### 10. 🐛 Troubleshooting

#### Trang trắng / 500 Error
```bash
# Xem logs
tail -f storage/logs/laravel.log

# Hoặc web server logs
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx
```

**Nguyên nhân thường gặp:**
- ✗ Chưa set `APP_KEY`
- ✗ File permissions sai (storage/ không writable)
- ✗ Chưa chạy `php artisan storage:link`
- ✗ Database connection sai
- ✗ `.env` không đúng `APP_URL` hoặc `SESSION_PATH`

#### Assets không load (404)
```bash
# Check .env
APP_URL=https://duonglien.com/visionboard2026
ASSET_URL=https://duonglien.com/visionboard2026

# Clear cache
php artisan config:clear
php artisan config:cache
```

#### Login không hoạt động (session mất)
```bash
# Check .env
SESSION_PATH=/visionboard2026/
SESSION_DOMAIN=.duonglien.com

# Clear session
php artisan cache:clear
```

#### CSS/JS bị mixed content (http thay vì https)
Thêm vào `app/Providers/AppServiceProvider.php`:
```php
public function boot()
{
    if (config('app.env') === 'production') {
        \URL::forceScheme('https');
    }
}
```

### 11. 📧 Setup Cron Jobs (cho Reminders)

```bash
# Mở crontab
crontab -e

# Thêm dòng (chạy Laravel scheduler mỗi phút)
* * * * * cd /home/username/www/visionboard2026 && php artisan schedule:run >> /dev/null 2>&1
```

Laravel scheduler sẽ tự động gửi reminders theo lịch đã cấu hình.

## 🎯 Quick Commands Reference

```bash
# Deploy checklist
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update code
git pull
npm run build
php artisan migrate --force
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache

# Debug
tail -f storage/logs/laravel.log
php artisan config:clear
php artisan cache:clear
```

## 📝 Notes

- **KHÔNG bao giờ** commit file `.env` lên Git
- **LUÔN** backup database trước khi migrate
- **Test kỹ** trên local trước khi deploy
- **Monitor** logs sau khi deploy
