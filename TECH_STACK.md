# Tech Stack — Website Top Up Game

> Ringkasan teknis untuk orientasi cepat. Acuan resmi ada di `docs/`: scope di [`docs/PROJECT_SPEC.md`](docs/PROJECT_SPEC.md), jadwal di [`docs/IMPLEMENTATION_PLAN.md`](docs/IMPLEMENTATION_PLAN.md), progres di [`docs/CURRENT_STATUS.md`](docs/CURRENT_STATUS.md). Jika ada perbedaan, `docs/` yang berlaku.
> Diperbarui 2026-11-12 (supplier Melo, timeline 1 bulan).

- **Target pengerjaan:** 1 bulan (H1–H30, rincian di `docs/IMPLEMENTATION_PLAN.md`).
- **Prinsip:** monolith + admin panel instan (tanpa API/SPA terpisah) agar realistis untuk deadline.

## Permintaan Khusus Client & Keputusan PO

1. **Tanpa** fitur MLBB calc / cek nickname (termasuk cek ID FF, dll.).
2. **Ada** tampilan stok akun (jualan akun game dengan stok terlihat di halaman produk).
3. Login pelanggan **opsional**: guest boleh membeli, pelanggan yang login punya riwayat transaksi.
4. Reseller (harga khusus, saldo/deposit) **di luar scope**.

---

## 1. Ringkasan Stack

Sumber resmi versi stack: [`docs/PROJECT_SPEC.md`](docs/PROJECT_SPEC.md) §11.

| Layer           | Teknologi                                         | Versi  | Status / Catatan                                                |
| --------------- | ------------------------------------------------- | ------ | --------------------------------------------------------------- |
| Bahasa          | PHP                                               | 8.4    | Tersedia di Laragon dan mayoritas VPS.                          |
| Framework       | Laravel                                           | 13.x   | Auth, queue, scheduler, validasi, enkripsi, HTTP client bawaan. |
| Admin panel     | Filament                                          | 5.x    | CRUD katalog, stok akun, pesanan.                               |
| Frontend user   | Blade + Livewire + Alpine.js                      | bawaan | Interaktif tanpa SPA.                                           |
| Styling         | Tailwind CSS                                      | 4.x    | Storefront.                                                     |
| Build tool      | Vite                                              | 8.x    | Bawaan Laravel.                                                 |
| Database        | MySQL                                             | 8.0    | Dev & production. Transaksi + row locking untuk stok akun.      |
| Cache & Queue   | Redis (production), database driver (dev)         | —      | Callback payment, order ke supplier, retry.                     |
| Supplier top up | **Melo H2H API**                                  | v1     | Menggantikan Digiflazz (2026-11-12).                            |
| Payment gateway | 1 provider, kandidat **Tripay**                   | —      | [TBD] provider final. Alternatif: Duitku / Midtrans / Xendit.   |
| Notifikasi      | Email (mailer Laravel)                            | —      | [TBD]. WhatsApp gateway (Fonnte/Wablas) butuh persetujuan PO.   |
| Testing         | PHPUnit + Postman CLI                             | —      | Feature test + uji callback API.                                |
| Code style      | Laravel Pint                                      | —      | `vendor/bin/pint --dirty --format agent`.                       |
| Deploy          | VPS + Coolify **atau** Laravel Forge + Cloudflare | —      | [TBD] Coolify vs Forge. Bukan Laravel Cloud.                    |

---

## 2. Penyesuaian dari Permintaan Client

### 2.1 Tanpa MLBB calc / cek ID

- Form order hanya meminta **User ID** (dan **Zone ID** bila `games.requires_zone_id = true`, mis. MLBB).
- Validasi **format** via regex per game (`games.user_id_regex`, `games.zone_id_regex`), tanpa API cek nickname.
- `MeloService` punya method `checkNickname()`, tetapi **tidak boleh dipakai** tanpa persetujuan PO.
- Rencana UI: checkbox konfirmasi _"ID yang saya masukkan sudah benar"_ [REKOMENDASI].

### 2.2 Tampilan stok akun

- `products.type`: `topup` (ke supplier Melo) atau `account` (stok internal).
- Tabel `game_accounts`: 1 baris = 1 akun.
    - `credentials`: **dienkripsi** (cast `encrypted`).
    - `public_info`: aman ditampilkan (mis. rank, skin).
- Stok tampil = jumlah akun dengan `status = available` (scope `Product::withStock()` → `available_accounts_count`).
- Alur status akun (`App\Services\AccountStockService`):

```text
available ── reserve() ──▶ reserved ── markSold() ──▶ sold
    ▲                          │
    └──── release() / releaseExpired() (scheduler tiap menit)
```

- `reserve()` memakai `DB::transaction` + `lockForUpdate()` agar satu akun tidak terjual ganda.
- Masa reservasi: **15 menit** (`AccountStockService::RESERVATION_MINUTES`) [REKOMENDASI, menunggu keputusan PO]. Samakan dengan masa berlaku invoice payment.
- Detail login hanya tampil di halaman invoice setelah order berstatus **Sukses**.

---

## 3. Struktur Project

```text
app/
├── Enums/                     ProductType, OrderStatus, AccountStatus, UserRole
├── Models/                    Category, Game, Product, GameAccount, Order, ApiLog, Setting, User
├── Services/
│   ├── AccountStockService.php      reserve / markSold / release / releaseExpired
│   ├── Payment/TripayService.php    channel, create transaction, verifikasi signature callback
│   └── Supplier/MeloService.php     profil, saldo, katalog, topup, cek status, verifikasi signature webhook
├── Http/Controllers/
│   ├── StorefrontController.php     home, halaman game, invoice
│   └── Webhook/                     TripayCallbackController, MeloCallbackController
├── Console/Commands/ReleaseExpiredAccounts.php   (accounts:release-expired)
├── Exceptions/OutOfStockException.php
└── Filament/
    ├── Resources/             Orders, Categories, Games, Products, GameAccounts
    └── Widgets/StoreOverview.php

resources/views/
├── components/layouts/app.blade.php
└── storefront/                home, game, invoice

tests/Feature/                 AccountStockService, WebhookSignature, Storefront, AdminPanelAccess, MeloService
melostore-h2h.json             OpenAPI resmi Melo
```

Struktur lengkap (termasuk folder yang direncanakan): [`docs/SYSTEM_ARCHITECTURE.md`](docs/SYSTEM_ARCHITECTURE.md) §6.

### 3.1 Skema Database Inti

| Tabel           | Isi penting                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------- |
| `users`         | `role` (`admin` / `member`), `whatsapp` (1)                                                       |
| `categories`    | Kategori (Mobile Game, PC Game, Voucher, dll.)                                                    |
| `games`         | `slug`, label & regex User ID/Zone ID, `requires_zone_id`, `is_popular`                           |
| `products`      | `type`, `supplier` (`melo`), `supplier_sku`, `base_price`, `price`, `strike_price` (1)            |
| `game_accounts` | `credentials` (terenkripsi), `public_info`, `status`, `order_id`, `reserved_until`, `sold_at`     |
| `orders`        | `invoice`, snapshot produk, ID tujuan, kontak, harga/fee/total, `status`, data payment & supplier |
| `api_logs`      | Log request/callback payment & supplier, `signature_valid`                                        |
| `settings`      | Key-value (nama situs, dll.), di-cache                                                            |

1. Kolom `users.balance`, `products.reseller_price`, dan role `reseller` masih ada di kode tetapi **di luar scope** (lihat `docs/CURRENT_STATUS.md` M4).

Detail kolom: [`docs/DATABASE_DESIGN.md`](docs/DATABASE_DESIGN.md).

### 3.2 Route

| Method | URL                | Nama               | Keterangan                                     |
| ------ | ------------------ | ------------------ | ---------------------------------------------- |
| GET    | `/`                | `home`             | Home: daftar game per kategori                 |
| GET    | `/game/{game}`     | `game.show`        | Daftar produk + stok akun (binding `slug`)     |
| GET    | `/invoice/{order}` | `invoice.show`     | Status order + detail akun (binding `invoice`) |
| POST   | `/webhook/tripay`  | `webhook.tripay`   | Callback Tripay (tanpa CSRF, signature wajib)  |
| POST   | `/webhook/melo`    | `webhook.melo`     | Webhook Melo (tanpa CSRF, signature wajib)     |
| —      | `/admin`           | `filament.admin.*` | Filament admin panel (hanya `role = admin`)    |

---

## 4. Setup Lokal (Laragon)

1. Nyalakan MySQL di Laragon (**Start All**), lalu buat database:

```sql
CREATE DATABASE topup_game      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; -- aplikasi
CREATE DATABASE topup_game_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; -- khusus test
```

2. Instalasi dan migrasi:

```bash
composer install
npm install
cp .env.example .env        # DB_DATABASE=topup_game; isi TRIPAY_* dan MELO_* sandbox (jangan commit)
php artisan key:generate
php artisan migrate --seed  # admin contoh: admin@example.com / password (khusus lokal)
npm run build               # atau: composer run dev
php artisan serve
```

3. Konfigurasi `.env` (database):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=topup_game
DB_USERNAME=root
DB_PASSWORD=
```

> `phpunit.xml` memakai `topup_game_test`, sehingga data `topup_game` tidak terpengaruh saat test.

4. Proses background:

```bash
# Scheduler (wajib, melepas reservasi kedaluwarsa)
php artisan schedule:work      # lokal
# production (cron): * * * * * php /path/artisan schedule:run >> /dev/null 2>&1

# Queue worker (setelah job dibuat)
php artisan queue:work --tries=3  # Horizon = [TBD], dependency baru butuh persetujuan
```

5. Test:

```bash
php artisan test --compact
```

---

## 5. Timeline (1 Bulan)

Rincian, dependensi, dan kriteria selesai ada di [`docs/IMPLEMENTATION_PLAN.md`](docs/IMPLEMENTATION_PLAN.md).

| Tahap | Hari  | Fokus                               |
| :---: | ----- | ----------------------------------- |
|   1   | 1–4   | Setup & konfigurasi dasar           |
|   2   | 5–10  | Admin panel & manajemen game/produk |
|   3   | 11–16 | Frontend & sistem order             |
|   4   | 17–22 | Payment gateway & Melo              |
|   5   | 23–26 | Stok akun, transaksi & notifikasi   |
|   6   | 27–30 | Testing, bug fixing & deployment    |

---

## 6. Checklist Keamanan & Keandalan

Daftar lengkap dan statusnya: [`docs/SECURITY_RULES.md`](docs/SECURITY_RULES.md).

### Selesai

- [x] Verifikasi signature callback Tripay (`X-Callback-Signature`, HMAC-SHA256).
- [x] Verifikasi signature webhook Melo (`X-H2H-Signature`, hex HMAC-SHA256).
- [x] Kredensial akun dienkripsi di database.
- [x] Row lock saat reserve stok akun.
- [x] Akses `/admin` terbatas `role = admin`.
- [x] Rate limit endpoint webhook (`throttle:120,1`).

### Belum

- [ ] Idempotency callback: abaikan jika `OrderStatus::isFinal()` (job tahap 4).
- [ ] Rate limit form checkout.
- [ ] Anti-bot checkout (mis. Cloudflare Turnstile) [TBD, butuh persetujuan].
- [ ] Alert saldo Melo menipis (cek terjadwal via `MeloService::balance()`) [REKOMENDASI].
- [ ] Whitelist IP callback [TBD, opsional].
- [ ] Aturan akses invoice/kredensial (M1) dan pembatasan form edit pesanan (M3).
- [ ] Backup database harian di VPS.

---

## 7. Catatan Kustomisasi Desain

- **Storefront (pembeli):** Blade + Tailwind, desain bebas sesuai brand klien.
- **Admin (Filament):** branding via `app/Providers/Filament/AdminPanelProvider.php` (warna, font, logo); custom theme via `php artisan make:filament-theme` bila perlu. Struktur dasar panel tetap. Batasan ini perlu dicantumkan dalam kontrak.
