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

    # -------------------------------------------------------------------------
    # Seeding (isi data awal: admin, operator, produk, master data, dll.)
    #
    # PENTING: DatabaseSeeder melakukan truncate() pada tabel users/produk/
    # device/penimbangan sebelum mengisi ulang. Menjalankannya tiap start akan
    # MENGHAPUS data produksi. Karena itu perilaku default aman:
    #
    #   DB_SEED=auto   (default) -> seed HANYA bila database masih kosong
    #                               (tabel users belum ada isinya / fresh install).
    #   DB_SEED=always           -> paksa seed tiap start (SADAR akan menghapus
    #                               & mengisi ulang data — pakai hanya bila perlu).
    #   DB_SEED=never|false|0     -> jangan pernah seed otomatis.
    # -------------------------------------------------------------------------
    DB_SEED="${DB_SEED:-auto}"
    case "$DB_SEED" in
        always)
            echo "[entrypoint] DB_SEED=always -> menjalankan db:seed (data akan di-reset)."
            php artisan db:seed --force
            ;;
        never|false|0)
            echo "[entrypoint] DB_SEED=$DB_SEED -> seeding otomatis dilewati."
            ;;
        *)
            # auto: hitung baris tabel users. Marker COUNT:...:END dipakai agar
            # parsing kebal terhadap output tambahan dari tinker.
            user_count=$(php artisan tinker --execute="echo 'COUNT:'.\App\Models\User::count().':END';" 2>/dev/null \
                | sed -n 's/.*COUNT:\([0-9][0-9]*\):END.*/\1/p' | head -n1)

            if [ -z "$user_count" ]; then
                echo "[entrypoint] DB_SEED=auto -> gagal menentukan isi DB, seeding dilewati (aman). Set DB_SEED=always untuk memaksa."
            elif [ "$user_count" -eq 0 ]; then
                echo "[entrypoint] DB_SEED=auto -> database kosong, menjalankan db:seed ..."
                php artisan db:seed --force
            else
                echo "[entrypoint] DB_SEED=auto -> database sudah berisi data ($user_count user), seeding dilewati."
            fi
            ;;
    esac

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
