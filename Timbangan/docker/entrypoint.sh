#!/bin/sh
# =============================================================================
# Entrypoint container aplikasi Timbangan Digital.
#
#   1. Tunggu database siap menerima koneksi.
#   2. Hanya di container "app" (CONTAINER_ROLE=app): jalankan migrasi &
#      cache konfigurasi. Container lain (queue/reverb/scheduler) langsung
#      menjalankan perintahnya supaya migrasi tidak berjalan berkali-kali.
#   3. Exec perintah container (CMD), mis. php-fpm / queue:work / reverb:start.
# =============================================================================
set -e

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
ROLE="${CONTAINER_ROLE:-app}"

echo "[entrypoint] Menunggu database ${DB_HOST}:${DB_PORT} ..."
tries=0
until php -r "exit(@fsockopen('${DB_HOST}', ${DB_PORT}) ? 0 : 1);" 2>/dev/null; do
    tries=$((tries + 1))
    if [ "$tries" -ge 60 ]; then
        echo "[entrypoint] Database tidak merespons setelah 60 percobaan. Keluar."
        exit 1
    fi
    echo "[entrypoint]   belum siap, coba lagi dalam 3 detik... (${tries}/60)"
    sleep 3
done
echo "[entrypoint] Database siap."

if [ "$ROLE" = "app" ]; then
    echo "[entrypoint] Menjalankan release tasks (role=app) ..."

    # Migrasi database (aman untuk produksi dengan --force)
    php artisan migrate --force

    # Pastikan symlink storage ada (untuk file publik)
    php artisan storage:link || true

    # Cache konfigurasi & view untuk performa.
    # route:cache di-skip / guarded karena ada closure route (health check)
    # yang tidak bisa di-serialize.
    php artisan config:cache
    php artisan view:cache
    php artisan route:cache || echo "[entrypoint] route:cache dilewati (ada closure route)."
    php artisan event:cache || true

    echo "[entrypoint] Release tasks selesai."
fi

echo "[entrypoint] Menjalankan: $*"
exec "$@"
