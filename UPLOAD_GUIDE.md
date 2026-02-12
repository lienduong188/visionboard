# 📤 Hướng Dẫn Upload VisionBoard lên Sakura Server

## Bước 1: Chuẩn bị trên máy Local

### 1.1. Build assets
```bash
cd c:\laragon\www\visionboard2026
npm run build
```

### 1.2. Tạo file .zip để upload (Optional - nếu muốn nén trước)
Nén toàn bộ folder `visionboard2026` (trừ `node_modules/`)

**Loại trừ các folder không cần:**
- `node_modules/`
- `.git/`
- `storage/logs/*` (sẽ tạo mới trên server)

## Bước 2: Upload Code lên Server

### 🎯 Method 1: Dùng FileZilla (KHUYÊN DÙNG - DỄ NHẤT)

#### Bước 1: Download và cài FileZilla
- Download tại: https://filezilla-project.org/
- Chọn FileZilla Client (miễn phí)

#### Bước 2: Kết nối đến Sakura Server
1. Mở FileZilla
2. Ở thanh trên cùng, nhập:
   - **Host**: `sftp://duonglien.com` (hoặc IP server)
   - **Username**: `your_username` (username SSH)
   - **Password**: `your_password`
   - **Port**: `22` (hoặc port SSH của server)
3. Click **"Quickconnect"**

#### Bước 3: Upload files
1. **Bên trái** (Local): Tìm đến folder `c:\laragon\www\visionboard2026`
2. **Bên phải** (Server): Tìm đến folder `www/` hoặc `public_html/`
3. **Kéo thả** toàn bộ folder `visionboard2026` từ trái sang phải

**⚠️ Lưu ý:**
- Bỏ qua folder `node_modules/` (không upload)
- Upload mất 5-10 phút tùy tốc độ mạng
- Nếu có file `.env` trên server, KHÔNG ghi đè (Keep)

---

### 🎯 Method 2: Dùng Git (Deploy tự động)

#### Bước 2.1: SSH vào server
```bash
# Windows: Mở Command Prompt hoặc PowerShell
ssh username@duonglien.com

# Nhập password khi được hỏi
```

#### Bước 2.2: Clone repository từ GitHub
```bash
# Di chuyển đến thư mục www
cd ~/www

# Clone repository
git clone https://github.com/your-username/visionboard2026.git

# Hoặc nếu đã có repo, pull code mới nhất
cd visionboard2026
git pull origin main
```

#### Bước 2.3: Install dependencies và build trên server
```bash
cd ~/www/visionboard2026

# Install Composer dependencies
composer install --no-dev --optimize-autoloader

# Install npm dependencies (nếu muốn build trên server)
npm install
npm run build

# Hoặc chỉ upload build/ folder từ local (khuyên dùng)
```

**⚠️ Lưu ý với Method 2:**
- Cần có Git trên server
- Repository phải public hoặc setup SSH key
- Phải chạy `npm run build` trên LOCAL trước khi push lên Git

---

### 🎯 Method 3: Dùng SCP/SFTP Command (Advanced)

#### Windows (PowerShell):
```powershell
# Upload toàn bộ folder
scp -r c:\laragon\www\visionboard2026 username@duonglien.com:~/www/

# Hoặc upload file .zip rồi giải nén trên server
# 1. Nén folder local thành visionboard2026.zip
# 2. Upload:
scp c:\laragon\www\visionboard2026.zip username@duonglien.com:~/www/

# 3. SSH vào server và giải nén:
ssh username@duonglien.com
cd ~/www
unzip visionboard2026.zip
rm visionboard2026.zip
```

#### Mac/Linux:
```bash
# Upload toàn bộ folder
scp -r /path/to/visionboard2026 username@duonglien.com:~/www/
```

---

## Bước 3: Setup trên Server (sau khi upload xong)

### 3.1. SSH vào server
```bash
ssh username@duonglien.com
```

### 3.2. Di chuyển đến thư mục project
```bash
cd ~/www/visionboard2026
```

### 3.3. Kiểm tra files đã upload đầy đủ chưa
```bash
ls -la
# Phải thấy: app/, config/, public/, resources/, vendor/, ...
```

### 3.4. Tạo file .env
```bash
# Copy từ template
cp .env.production.example .env

# Edit file .env
nano .env
# Hoặc
vi .env
```

**Sửa các dòng sau trong .env:**
```env
APP_KEY=base64:...  # Sẽ generate ở bước sau
APP_URL=https://visionboard.duonglien.com

DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

MAIL_HOST=your_smtp_host
MAIL_USERNAME=your_email
MAIL_PASSWORD=your_smtp_password
```

**Save file:**
- Nano: Ctrl+X, Y, Enter
- Vi: ESC, `:wq`, Enter

### 3.5. Generate APP_KEY
```bash
php artisan key:generate
```

### 3.6. Set permissions
```bash
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

**Nếu lỗi permission, cần chown:**
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
# Hoặc
sudo chown -R nginx:nginx storage bootstrap/cache
# Tùy web server (Apache/Nginx)
```

### 3.7. Run migrations
```bash
php artisan migrate --force
```

### 3.8. Link storage
```bash
php artisan storage:link
```

### 3.9. Cache configs
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3.10. Setup web server cho subdomain
Cần config VirtualHost cho `visionboard.duonglien.com` trỏ DocumentRoot đến `/home/username/www/visionboard2026/public`.
Xem chi tiết trong [DEPLOYMENT.md](DEPLOYMENT.md#8--cấu-hình-web-server).

---

## Bước 4: Kiểm tra

### 4.1. Test trên browser
Mở: https://visionboard.duonglien.com/

### 4.2. Nếu vẫn lỗi, check logs
```bash
tail -f ~/www/visionboard2026/storage/logs/laravel.log
```

### 4.3. Check portfolio không bị ảnh hưởng
Mở: https://duonglien.com/

---

## 🔧 Troubleshooting

### Lỗi: "500 Internal Server Error"
```bash
# Check logs
tail -50 storage/logs/laravel.log

# Clear caches
php artisan config:clear
php artisan cache:clear

# Re-cache
php artisan config:cache
```

### Lỗi: "Permission denied"
```bash
# Fix permissions
chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

### Lỗi: "No application encryption key has been specified"
```bash
php artisan key:generate
php artisan config:cache
```

### Assets không load (404)
```bash
# Kiểm tra .env
APP_URL=https://visionboard.duonglien.com
ASSET_URL=https://visionboard.duonglien.com

# Clear cache
php artisan config:clear && php artisan config:cache
```

### Trang trắng, không báo lỗi gì
```bash
# Bật debug mode tạm thời
nano .env
# Đổi: APP_DEBUG=true

# Refresh browser để xem lỗi chi tiết
# Nhớ đổi lại APP_DEBUG=false sau khi fix!
```

---

## 📋 Quick Command Reference

```bash
# SSH vào server
ssh username@duonglien.com

# Di chuyển đến project
cd ~/www/visionboard2026

# Pull code mới (nếu dùng Git)
git pull origin main

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Re-cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Check logs
tail -f storage/logs/laravel.log

# Fix permissions
chmod -R 775 storage bootstrap/cache

# Exit SSH
exit
```

---

## 🎯 Deploy lần sau (Update code)

### Nếu dùng Git:
```bash
ssh username@duonglien.com
cd ~/www/visionboard2026
git pull origin main
npm run build  # Nếu có thay đổi frontend
php artisan migrate --force  # Nếu có migration mới
php artisan config:cache
php artisan route:cache
```

### Nếu dùng FileZilla:
1. Build local: `npm run build`
2. Upload files đã thay đổi qua FileZilla
3. SSH vào server:
   ```bash
   cd ~/www/visionboard2026
   php artisan config:cache
   php artisan route:cache
   ```

---

## 📞 Liên hệ Sakura Support

Nếu gặp vấn đề về server configuration (Apache Alias, permissions, etc.):
- Check Sakura control panel
- Hoặc contact support với thông tin: Cần setup subdomain `visionboard.duonglien.com` trỏ đến `/home/username/www/visionboard2026/public`
