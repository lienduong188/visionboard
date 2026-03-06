#!/bin/sh
set -e
APP="$HOME/www/visionboard2026"
DB="$APP/database/database.sqlite"
BACKUP_DIR="$APP/storage/app/backups"

echo "=== Available backups ==="
ls -la "$BACKUP_DIR/" 2>/dev/null || echo "No backups dir found"
echo "========================="

# Dùng backup mới nhất trong storage/app/backups/
LATEST_BACKUP=$(ls -t "$BACKUP_DIR"/*.sqlite 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
  echo "ERROR: No backup files found in $BACKUP_DIR"
  exit 1
fi

echo "Restoring from: $LATEST_BACKUP"

# Backup DB hiện tại trước
cp "$DB" "$HOME/db_before_restore_$(date +%Y%m%d_%H%M%S).sqlite"
echo "Backed up current DB"

# Restore bằng cách copy backup file trực tiếp
cp "$LATEST_BACKUP" "$DB"
echo "Restore complete!"

# Clear Laravel config cache
cd "$APP"
php artisan config:clear
php artisan config:cache
echo "Config cache refreshed"

echo "users=$(sqlite3 $DB 'SELECT COUNT(*) FROM users;') goals=$(sqlite3 $DB 'SELECT COUNT(*) FROM goals;') outputs=$(sqlite3 $DB 'SELECT COUNT(*) FROM daily_outputs;')"
