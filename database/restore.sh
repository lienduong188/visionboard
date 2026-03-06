#!/bin/sh
set -e
DB="$HOME/www/visionboard2026/database/database.sqlite"
SQL="$HOME/restore.sql"

if [ -f "$DB" ]; then
  cp "$DB" "$HOME/db_backup_$(date +%Y%m%d_%H%M%S).sqlite"
  echo "Backed up current DB"
fi

echo "Running restore..."
sqlite3 "$DB" < "$SQL"
rm "$SQL"
echo "Restore complete!"

echo "users=$(sqlite3 $DB 'SELECT COUNT(*) FROM users;') goals=$(sqlite3 $DB 'SELECT COUNT(*) FROM goals;') outputs=$(sqlite3 $DB 'SELECT COUNT(*) FROM daily_outputs;')"
