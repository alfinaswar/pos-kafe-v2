# Backup dan Restore Database

## Backup manual

```bash
php artisan db:backup
```

File backup tersimpan di `storage/backups/`.

```bash
php artisan db:backup --output="storage/backups/manual.sql"
```

## Restore manual

1. Hentikan worker dan akses aplikasi.
2. Pastikan file backup berasal dari sumber tepercaya.
3. Pilih database target.
4. Jalankan:

```bash
mysql --host=127.0.0.1 --port=3306 --user=root --password kafe-pos-v2 < storage/backups/manual.sql
```

5. Jalankan `php artisan migrate:status`.
6. Buka `/health`.
7. Jalankan test aplikasi.

Jangan commit backup berisi data produksi.
