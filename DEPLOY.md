# Deployment UMKMKu

## Persyaratan

- PHP 8.2+ dengan ekstensi `pdo_sqlite`, `mbstring`, `fileinfo`, `openssl`, dan `gd`.
- Composer 2, Node.js 20+, npm, dan web server yang menunjuk ke folder `public`.
- SQLite berada di luar web root dan direktori `storage` serta `bootstrap/cache` dapat ditulis oleh proses aplikasi.

## Instalasi produksi

```powershell
composer install --no-dev --classmap-authoritative
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File database\database.sqlite -Force
php artisan migrate --force
php artisan db:seed --class=Database\Seeders\ProductionSeeder --force
npm ci
npm run build
php artisan storage:link
php artisan optimize
```

Atur `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, kredensial mail bila diperlukan, serta `ADMIN_EMAIL`, `ADMIN_PASSWORD`, dan opsi `STORE_*` sebelum menjalankan `ProductionSeeder`. Seeder produksi hanya membuat akun admin dan pengaturan toko; tidak ada data contoh, produk, transaksi, atau password bawaan.

## Operasional

- Jalankan `php artisan backup:database` untuk membuat backup SQLite di `storage/app/backups`.
- Scheduler menjalankan backup otomatis setiap hari pukul 02:00. Jalankan scheduler dengan cron atau task runner: `php artisan schedule:work`.
- Backup disimpan maksimal tujuh file. Salin backup ke media terpisah secara berkala.
- SQLite memakai foreign keys, WAL, synchronous `NORMAL`, dan busy timeout 5 detik melalui konfigurasi `.env`.
- Deploy berikutnya: `php artisan migrate --force`, `npm run build`, lalu `php artisan optimize`.

## Pemeriksaan sebelum go-live

```powershell
php artisan test
vendor\bin\pint --test
php artisan route:list
php artisan backup:database
```

Pastikan login admin, input stok, penjualan, pembatalan transaksi, opname, laporan, dan pemulihan backup berhasil diuji pada lingkungan produksi sebelum toko dibuka.
