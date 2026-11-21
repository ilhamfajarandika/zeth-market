# Zeth Market — Website Top Up Game & Jual Akun Game

Aplikasi web monolith **Laravel 13** untuk penjualan **top up game** (diproses otomatis melalui **Melo H2H API**) dan **akun game** dari stok internal toko, dengan pembayaran melalui **Duitku Payment Gateway** dan panel admin berbasis **Filament 5**.

> Dokumentasi lengkap spesifikasi, arsitektur, alur bisnis, dan progres proyek tersedia di folder [`docs/`](docs/).

---

## Teknologi

| Layer | Teknologi |
| --- | --- |
| Bahasa & Framework | PHP 8.4, Laravel 13 |
| Admin Panel | Filament 5 (`/{ADMIN_PATH}`) |
| Storefront | Vue 3 + Inertia.js 3 (JavaScript) |
| Styling & Build | Tailwind CSS 4, Vite 8 |
| Database | MySQL 8 (`topup_game` & `topup_game_test`) |
| Payment Gateway | Duitku (`DuitkuService`) |
| Supplier Top Up | Melo H2H API (`MeloService`) |
| Testing & Formatter | PHPUnit 12, Laravel Pint |

---

## Fitur yang Sudah Tersedia

### Storefront (Pembeli — Vue 3 + Inertia.js)
- **Beranda (`/`):** Banner slideshow promo, katalog 17 game berikon WebP, filter kategori (`Mobile Game`, `PC Game`, `Voucher`), pencarian real-time, panduan cara top up, FAQ, dan tampilan cek pesanan.
- **Halaman Detail Game (`/game/{slug}`):** Input User ID (+ Zone/Server ID sesuai konfigurasi game), daftar produk top up & akun game, serta tampilan **stok akun real-time** (produk stok 0 otomatis nonaktif). *(Form checkout masih pratinjau; menunggu penyambungan ke endpoint checkout).*
- **Halaman Invoice (`/invoice/{invoice}`):** Nomor invoice dengan tombol salin, status & progres pesanan, rincian harga, instruksi pembayaran, serial number (SN), serta kredensial akun yang hanya tampil setelah order berstatus `success`.

### Admin Panel (`/{ADMIN_PATH}`, default `/admin` lokal)
- Akses dibatasi khusus pengguna dengan `role = admin`. Path panel diatur melalui variabel `ADMIN_PATH` di `.env` (di production wajib memakai path acak; `/admin` otomatis 404).
- **Dashboard (`StoreOverview`):** Ringkasan omzet hari ini, pesanan yang perlu diproses, dan total stok akun tersedia.
- **Resource CRUD:** Banner, Kategori, Game, Produk (termasuk kolom stok akun), Stok Akun, dan Pesanan.

### Backend, Stok Akun & Integrasi
- **Anti-Jual Ganda (`AccountStockService`):** Siklus stok `available → reserved → sold` (dan `release` / `releaseExpired`) menggunakan `DB::transaction` + `lockForUpdate()`, plus scheduler `accounts:release-expired` tiap menit.
- **Keamanan Data:** Kolom `game_accounts.credentials` dienkripsi di database (`encrypted` cast), dan props Inertia dikirim sebagai array eksplisit agar data internal tidak bocor.
- **Webhook & Integrasi:** Endpoint `POST /webhook/duitku` dan `POST /webhook/melo` dengan verifikasi signature, rate limit (`throttle:120,1`), pengecualian CSRF, dan pencatatan ke tabel `api_logs`.

---

## Route Utama

| Method | URL | Nama | Keterangan |
| --- | --- | --- | --- |
| `GET` | `/` | `home` | Beranda & katalog game |
| `GET` | `/game/{game}` | `game.show` | Halaman produk & stok akun (`slug`) |
| `GET` | `/invoice/{order}` | `invoice.show` | Halaman status & hasil pesanan (`invoice`) |
| `POST` | `/webhook/duitku` | `webhook.duitku` | Callback payment gateway Duitku |
| `POST` | `/webhook/melo` | `webhook.melo` | Webhook supplier Melo H2H |
| — | `/{ADMIN_PATH}/*` | `filament.admin.*` | Panel admin Filament |

---

## Instalasi & Menjalankan Lokal (Laragon / MySQL 8)

1. Pastikan MySQL 8 berjalan, lalu buat dua database (`utf8mb4_unicode_ci`):
   ```sql
   CREATE DATABASE topup_game CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE topup_game_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Install dependency dan siapkan environment:
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   php artisan storage:link
   ```
   Sesuaikan `DB_*`, `APP_URL`, serta kredensial sandbox `DUITKU_*` dan `MELO_*` di `.env` (jangan commit `.env`).

3. Jalankan migration, seeder, dan build aset frontend:
   ```bash
   php artisan migrate --seed
   npm run build
   ```
   > Akun admin contoh khusus lokal: `admin@example.com` / `password` (wajib diganti di production).

4. Jalankan server pengembangan:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```
   Untuk proses background di terminal terpisah:
   ```bash
   php artisan schedule:work   # menjalankan accounts:release-expired tiap menit
   php artisan queue:work      # menjalankan job antrean
   ```
   Jika sedang mengedit UI dengan HMR, jalankan `npm run dev` (hapus `public/hot` jika kembali memakai `npm run build`).

---

## Pengujian & Code Style

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

> Pengujian memakai database terpisah `topup_game_test` (`phpunit.xml`) dan seluruh panggilan HTTP eksternal di-fake (`Http::fake()`).

---

## Dokumentasi Proyek

- [`docs/PROJECT_SPEC.md`](docs/PROJECT_SPEC.md) — Spesifikasi produk & batasan scope (Single Source of Truth)
- [`docs/CURRENT_STATUS.md`](docs/CURRENT_STATUS.md) — Progres terakhir, masalah terbuka, dan keputusan menunggu
- [`docs/IMPLEMENTATION_PLAN.md`](docs/IMPLEMENTATION_PLAN.md) — Rencana pengerjaan 1 bulan
- [`docs/SYSTEM_ARCHITECTURE.md`](docs/SYSTEM_ARCHITECTURE.md) — Arsitektur & struktur direktori
- [`docs/DATABASE_DESIGN.md`](docs/DATABASE_DESIGN.md) — Skema database & ERD
- [`docs/BUSINESS_FLOW.md`](docs/BUSINESS_FLOW.md) — Alur transaksi, pembayaran, dan stok akun
- [`docs/API_INTEGRATION.md`](docs/API_INTEGRATION.md) — Panduan integrasi payment gateway & Melo H2H
- [`docs/SECURITY_RULES.md`](docs/SECURITY_RULES.md) — Checklist keamanan
- [`docs/TASK_DIVISION.md`](docs/TASK_DIVISION.md) — Pembagian tugas Frontend & Backend serta kontrak props
