-- =============================================================================
-- User khusus monitoring untuk mysqld-exporter.
-- Dijalankan OTOMATIS oleh MySQL hanya saat volume database masih KOSONG
-- (fresh install). Untuk database yang SUDAH ADA, jalankan manual:
--
--   docker compose exec mysql mysql -uroot -p
--   lalu tempel isi file ini (ganti 'exporter_password' sesuai .env).
--
-- Privilege minimal sesuai rekomendasi prometheus/mysqld-exporter.
-- =============================================================================

CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY 'exporter_password' WITH MAX_USER_CONNECTIONS 3;
GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';
FLUSH PRIVILEGES;
