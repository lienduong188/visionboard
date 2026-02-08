# 🚀 GitHub Actions Auto-Deploy Setup

Hướng dẫn cài đặt GitHub Actions để tự động deploy VisionBoard lên Sakura server mỗi khi push code lên GitHub.

## ✨ Workflow tự động:

1. Push code lên GitHub (branch `main`)
2. GitHub Actions tự động:
   - Build assets (`npm run build`)
   - Upload files lên server qua SFTP
   - Chạy migrations, cache configs qua SSH
3. ✅ Done! Trang web tự động cập nhật

---

## 📋 Bước 1: Chuẩn bị thông tin Server

Bạn cần các thông tin sau từ Sakura server:

### 1.1. FTP/SFTP Info (để upload files)
- **FTP_SERVER**: Domain hoặc IP server (VD: `duonglien.com` hoặc `xxx.xxx.xxx.xxx`)
- **FTP_USERNAME**: Username FTP
- **FTP_PASSWORD**: Password FTP
- **Port**: Thường là `21` (FTP) hoặc `22` (SFTP)

### 1.2. SSH Info (để chạy commands)
- **SSH_HOST**: Domain hoặc IP server
- **SSH_USERNAME**: Username SSH
- **SSH_PORT**: Port SSH (thường là `22`)
- **SSH_PRIVATE_KEY**: Private SSH key

---

## 🔑 Bước 2: Tạo SSH Key (nếu chưa có)

### Trên máy local (Windows):

```bash
# Mở PowerShell/Command Prompt
ssh-keygen -t ed25519 -C "github-actions-visionboard"

# Lưu tại: C:\Users\YourName\.ssh\github_actions_visionboard
# Không cần passphrase (Enter 2 lần)
```

Sẽ tạo 2 files:
- `github_actions_visionboard` → **Private key** (để trong GitHub Secrets)
- `github_actions_visionboard.pub` → **Public key** (copy lên server)

### Copy Public Key lên Server:

```bash
# SSH vào server
ssh username@duonglien.com

# Tạo folder .ssh nếu chưa có
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Edit authorized_keys
nano ~/.ssh/authorized_keys

# Paste nội dung của file github_actions_visionboard.pub vào
# Save: Ctrl+X, Y, Enter

# Set permissions
chmod 600 ~/.ssh/authorized_keys
```

**Hoặc dùng lệnh (nếu có ssh-copy-id):**
```bash
ssh-copy-id -i ~/.ssh/github_actions_visionboard.pub username@duonglien.com
```

---

## 🔐 Bước 3: Add Secrets vào GitHub Repository

### 3.1. Vào GitHub Repository
1. Mở repository: `https://github.com/your-username/visionboard2026`
2. Click **Settings** (tab trên cùng)
3. Sidebar trái → **Secrets and variables** → **Actions**
4. Click **New repository secret**

### 3.2. Thêm các Secrets sau:

#### Secret 1: `FTP_SERVER`
- **Name**: `FTP_SERVER`
- **Value**: `duonglien.com` (hoặc IP server)
- Click **Add secret**

#### Secret 2: `FTP_USERNAME`
- **Name**: `FTP_USERNAME`
- **Value**: `your_ftp_username`
- Click **Add secret**

#### Secret 3: `FTP_PASSWORD`
- **Name**: `FTP_PASSWORD`
- **Value**: `your_ftp_password`
- Click **Add secret**

#### Secret 4: `SSH_HOST`
- **Name**: `SSH_HOST`
- **Value**: `duonglien.com` (hoặc IP server)
- Click **Add secret**

#### Secret 5: `SSH_USERNAME`
- **Name**: `SSH_USERNAME`
- **Value**: `your_ssh_username`
- Click **Add secret**

#### Secret 6: `SSH_PORT`
- **Name**: `SSH_PORT`
- **Value**: `22` (hoặc port SSH của bạn)
- Click **Add secret**

#### Secret 7: `SSH_PRIVATE_KEY`
- **Name**: `SSH_PRIVATE_KEY`
- **Value**: Copy toàn bộ nội dung file `github_actions_visionboard` (private key)
  ```
  -----BEGIN OPENSSH PRIVATE KEY-----
  b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
  ... (nhiều dòng)
  -----END OPENSSH PRIVATE KEY-----
  ```
- Click **Add secret**

**⚠️ Lưu ý:**
- Copy toàn bộ nội dung kể cả dòng BEGIN và END
- KHÔNG share private key với ai
- KHÔNG commit private key vào Git

---

## 🎬 Bước 4: Upload code lần đầu lên Server

Vì GitHub Actions chỉ upload **files thay đổi**, lần đầu tiên bạn cần upload thủ công:

### Option 1: Dùng FileZilla (khuyên dùng)
Xem hướng dẫn trong [UPLOAD_GUIDE.md](UPLOAD_GUIDE.md)

### Option 2: Dùng Git clone trên server
```bash
ssh username@duonglien.com
cd ~/www
git clone https://github.com/your-username/visionboard2026.git
cd visionboard2026
composer install --no-dev --optimize-autoloader
```

### Sau khi upload, setup trên server:
```bash
cd ~/www/visionboard2026

# Tạo .env
cp .env.production .env
nano .env  # Sửa database credentials

# Generate key
php artisan key:generate

# Permissions
chmod -R 775 storage bootstrap/cache

# Migrations
php artisan migrate --force
php artisan storage:link

# Cache
php artisan config:cache
php artisan route:cache

# Symlink
cd ~/www
ln -s ~/www/visionboard2026/public visionboard2026
```

---

## ✅ Bước 5: Test GitHub Actions

### 5.1. Commit và push workflow file
```bash
# Local
cd c:\laragon\www\visionboard2026

git add .github/workflows/deploy.yml
git commit -m "Add GitHub Actions auto-deploy workflow"
git push origin main
```

### 5.2. Xem workflow chạy
1. Vào GitHub repository
2. Click tab **Actions**
3. Sẽ thấy workflow "Deploy to Sakura Server" đang chạy
4. Click vào để xem chi tiết

### 5.3. Nếu thành công
✅ Thấy dấu tích xanh → Deploy thành công!
🌐 Mở https://duonglien.com/visionboard2026/ để kiểm tra

### 5.4. Nếu lỗi
❌ Thấy dấu X đỏ → Click vào xem log lỗi:
- **FTP error**: Check `FTP_*` secrets
- **SSH error**: Check `SSH_*` secrets, đặc biệt là `SSH_PRIVATE_KEY`
- **Permission denied**: Check SSH key đã copy lên server chưa

---

## 🔄 Sử dụng hàng ngày

Từ giờ, mỗi khi bạn muốn deploy code mới:

```bash
# 1. Code như bình thường
# ... edit files ...

# 2. Commit và push
git add .
git commit -m "Your commit message"
git push origin main

# 3. GitHub Actions tự động deploy!
# Chờ 2-3 phút, vào https://duonglien.com/visionboard2026/ để xem
```

**Không cần:**
- ❌ Build local (`npm run build`)
- ❌ Upload FTP thủ công
- ❌ SSH vào server chạy commands

**GitHub Actions làm tất cả tự động!** 🎉

---

## 🛠️ Troubleshooting

### Lỗi: "Host key verification failed"
```bash
# SSH vào server lần đầu để add vào known_hosts
ssh username@duonglien.com
# Type "yes" và Enter
exit
```

### Lỗi: "Permission denied (publickey)"
- Check SSH public key đã copy lên server chưa
- Check permissions: `chmod 600 ~/.ssh/authorized_keys`
- Check `SSH_PRIVATE_KEY` secret có đúng không

### Lỗi: "FTP connection failed"
- Check `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`
- Check protocol: `ftp`, `ftps`, hoặc `sftp`
- Thử đổi port: `21` (FTP), `22` (SFTP)

### Lỗi: "php: command not found"
- PHP không có trong PATH
- Sửa lại command trong workflow:
  ```yaml
  script: |
    cd ~/www/visionboard2026
    /usr/bin/php artisan migrate --force
    # Hoặc
    php8.2 artisan migrate --force
  ```

### Workflow không chạy khi push
- Check branch name: Phải push lên `main` (hoặc sửa trong `deploy.yml`)
- Check file `.github/workflows/deploy.yml` đã commit và push chưa

### Muốn chạy deploy thủ công (không push code)
1. Vào tab **Actions** trên GitHub
2. Click workflow "Deploy to Sakura Server"
3. Click **Run workflow** → **Run workflow**

---

## 🎯 Advanced: Deploy Multiple Branches

Nếu muốn deploy nhiều branches (dev, staging, production):

```yaml
on:
  push:
    branches:
      - main      # Production
      - staging   # Staging environment
      - dev       # Development environment
```

Và dùng conditional để deploy đến folder khác:
```yaml
- name: Deploy based on branch
  run: |
    if [ "${{ github.ref }}" == "refs/heads/main" ]; then
      echo "server-dir=/www/visionboard2026/" >> $GITHUB_ENV
    elif [ "${{ github.ref }}" == "refs/heads/staging" ]; then
      echo "server-dir=/www/visionboard2026-staging/" >> $GITHUB_ENV
    fi
```

---

## 📊 Monitoring

### Xem logs trên GitHub
1. GitHub repo → **Actions** tab
2. Click vào workflow run
3. Xem từng step (Build, Deploy, SSH commands)

### Xem logs trên Server
```bash
ssh username@duonglien.com
tail -f ~/www/visionboard2026/storage/logs/laravel.log
```

---

## 🎉 Xong!

Bây giờ mỗi lần push code, GitHub Actions tự động deploy lên production trong vài phút! 🚀

**Workflow:**
```
Code → Commit → Push → GitHub Actions → Auto Deploy → Live! 🎊
```
