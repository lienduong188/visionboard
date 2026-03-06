#!/bin/sh
set -e
APP="$HOME/www/visionboard2026"
DB="$APP/database/database.sqlite"
SQL="$HOME/restore.sql"

# Debug: xem app đang dùng DB nào
echo "=== DEBUG ==="
echo "App path: $APP"
echo "DB path: $DB"
if [ -f "$APP/.env" ]; then
  grep "DB_" "$APP/.env" | head -5
fi
echo "DB file exists: $(test -f $DB && echo YES || echo NO)"
echo "============="

if [ -f "$DB" ]; then
  cp "$DB" "$HOME/db_backup_$(date +%Y%m%d_%H%M%S).sqlite"
  echo "Backed up current DB"
fi

echo "Running restore..."
sqlite3 "$DB" < "$SQL"
rm "$SQL"
echo "Restore complete!"

# Clear Laravel config cache để app đọc lại .env
cd "$APP"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
echo "Config cache refreshed"

echo "users=$(sqlite3 $DB 'SELECT COUNT(*) FROM users;') goals=$(sqlite3 $DB 'SELECT COUNT(*) FROM goals;') outputs=$(sqlite3 $DB 'SELECT COUNT(*) FROM daily_outputs;')"
