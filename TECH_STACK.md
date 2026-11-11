# Tech Stack — Website Top Up Game

Target pengerjaan: **1 bulan (4 minggu)**.
Permintaan khusus client:

1. **Tanpa** MLBB calc / cek nickname (cek ID FF, dll.).
2. **Ada** display stok akun (jualan akun game dengan stok terlihat di halaman produk).

Prinsip pemilihan stack: **satu monolith + admin panel instan**, supaya tidak perlu membuat API dan SPA terpisah. Ini paling realistis untuk deadline 1 bulan.

---

## 1. Ringkasan Stack

| Layer | Teknologi | Versi terpasang | Alasan |
|---|---|---|---|
| Bahasa | PHP | 8.4 | Didukung Laravel terbaru, tersedia di Laragon dan hampir semua VPS/hosting. |
| Framework | **Laravel** | 13.x | Auth, queue, scheduler, validasi, enkripsi, dan HTTP client sudah tersedia. |
| Admin panel | **Filament** | 5.x | CRUD game, produk, stok akun, dan order langsung jadi. Hemat ±1 minggu. |
| Frontend user | **Blade + Livewire + Alpine.js** | (bawaan Filament) | Halaman interaktif (pilih nominal, total live, status order) tanpa SPA. |
| Styling | **Tailwind CSS** | 4.x | Desain storefront bebas dan cepat dibuat. |
| Build tool | Vite | 8.x | Bawaan Laravel. |
| Database | **MySQL 8** (dev & production) | 8.0 | Transaksi + row locking untuk stok akun. |
| Cache & Queue | **Redis** (production), database driver (dev) | — | Callback payment, order ke supplier, dan retry diproses di background. |
| Supplier top up | **Digiflazz** | API v1 | Satu API untuk ratusan produk game, ada webhook dan price list. |
| Payment gateway | **Tripay** (alternatif: Duitku / Midtrans / Xendit) | — | QRIS, VA, dan e-wallet dengan fee murah. |
| Notifikasi | Email (SMTP/Resend) + WhatsApp gateway (Fonnte/Wablas), opsional | — | Kirim invoice dan detail akun. |
| Testing | PHPUnit + Postman CLI | — | Feature test + uji callback API. |
| Code style | Laravel Pint | — | `vendor/bin/pint` |
| Deploy | VPS + **Coolify** / Laravel Forge, ditambah **Cloudflare** | — | SSL, CDN, dan proteksi DDoS. |

---

## 2. Penyesuaian dari Permintaan Client

### Tanpa MLBB calc / cek ID
- Form order hanya meminta **User ID** (dan **Zone ID** bila `games.requires_zone_id = true`, misalnya MLBB).
- Validasi cukup **format** lewat regex per game (`games.user_id_regex`, `games.zone_id_regex`). Tidak ada API cek nickname.
- Rencana UI: checkbox konfirmasi *"ID yang saya masukkan sudah benar"* untuk mengurangi komplain salah ID.

### Display stok akun
- Produk punya `type`: `topup` (dikirim ke supplier) atau `account` (dari stok internal).
- Tabel `game_accounts`: 1 baris = 1 akun. `credentials` **dienkripsi** (cast `encrypted`), sedangkan `public_info` boleh tampil (rank, skin).
- Stok yang tampil = jumlah akun `status = available` (scope `Product::withStock()` → `available_accounts_count`).
- Alur status akun (`App\Services\AccountStockService`):

```
available ──reserve()──▶ reserved ──markSold()──▶ sold
    ▲                       │
    └──release() / releaseExpired() (scheduler tiap menit)
```

- `reserve()` memakai `DB::transaction` + `lockForUpdate()` sehingga satu akun **tidak bisa terjual ke dua pembeli**.
- Reservasi berlaku 15 menit (`AccountStockService::RESERVATION_MINUTES`). Samakan dengan masa berlaku invoice payment.
- Detail login hanya tampil di halaman invoice setelah status order **Sukses**.

---

## 3. Struktur Project

```
app/
├── Enums/                     ProductType, OrderStatus, AccountStatus, UserRole (label + warna Filament)
├── Models/                    Category, Game, Product, GameAccount, Order, ApiLog, Setting, User
├── Services/
│   ├── AccountStockService.php      reserve / markSold / release / releaseExpired
│   ├── Payment/TripayService.php    create transaction + verifikasi signature callback
│   └── Supplier/DigiflazzService.php saldo, price list, topup + verifikasi signature webhook
├── Http/Controllers/
│   ├── StorefrontController.php     home, halaman game, invoice
│   └── Webhook/                     TripayCallbackController, DigiflazzCallbackController
├── Console/Commands/ReleaseExpiredAccounts.php   accounts:release-expired
├── Exceptions/OutOfStockException.php
└── Filament/
    ├── Resources/             Orders, Categories, Games, Products (kolom stok), GameAccounts (Stok Akun)
    └── Widgets/StoreOverview.php    omzet hari ini, order perlu diproses, stok akun
resources/views/
├── components/layouts/app.blade.php
└── storefront/                home, game, invoice
tests/Feature/                 AccountStockService, WebhookSignature, Storefront, AdminPanelAccess
```

### Skema database inti

| Tabel | Isi penting |
|---|---|
| `users` | + `role` (admin/member/reseller), `whatsapp`, `balance` |
| `categories` | Mobile Game, PC Game, Voucher, ... |
| `games` | slug, label & regex User ID / Zone ID, `requires_zone_id`, `is_popular` |
| `products` | `type`, `supplier_sku`, `base_price` (modal), `price`, `reseller_price`, `strike_price` |
| `game_accounts` | `credentials` (terenkripsi), `public_info`, `status`, `order_id`, `reserved_until`, `sold_at` |
| `orders` | `invoice`, snapshot produk, ID tujuan, kontak, harga/fee/total, `status`, data payment & supplier |
| `api_logs` | log request/callback payment & supplier, termasuk `signature_valid` |
| `settings` | key-value (nama situs, markup, dll.), di-cache |

### Route

| Method | URL | Keterangan |
|---|---|---|
| GET | `/` | Home: daftar game per kategori |
| GET | `/game/{slug}` | Daftar produk + stok akun |
| GET | `/invoice/{invoice}` | Status order + detail akun (setelah sukses) |
| POST | `/webhook/tripay` | Callback Tripay (tanpa CSRF, signature wajib) |
| POST | `/webhook/digiflazz` | Webhook Digiflazz (tanpa CSRF, signature wajib) |
| — | `/admin` | Filament admin panel (hanya `role = admin`) |

---

## 4. Setup Lokal (Laragon)

Nyalakan MySQL di Laragon (**Start All**), lalu buat dua database:

```sql
CREATE DATABASE topup_game      CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; -- aplikasi
CREATE DATABASE topup_game_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; -- khusus test (dikosongkan tiap test)
```

```bash
composer install
npm install
cp .env.example .env        # sudah berisi DB_CONNECTION=mysql, DB_DATABASE=topup_game
php artisan key:generate
php artisan migrate --seed  # admin: admin@example.com / password
npm run build               # atau: composer run dev
php artisan serve
```

Konfigurasi database ada di `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=topup_game
DB_USERNAME=root
DB_PASSWORD=
```

Test (`phpunit.xml`) memakai database terpisah `topup_game_test`, sehingga data di `topup_game` tidak tersentuh saat test dijalankan.

**Scheduler** (wajib di production, untuk melepas reservasi akun yang kedaluwarsa):

```bash
php artisan schedule:work      # lokal
# production (cron): * * * * * php /path/artisan schedule:run >> /dev/null 2>&1
```

**Queue worker** (production): `php artisan queue:work --tries=3` (atau Horizon bila memakai Redis).

**Test**: `php artisan test --compact`

---

## 5. Timeline 4 Minggu

| Minggu | Target | Status |
|---|---|---|
| **1** | Setup project, skema DB, admin Filament (game, produk, stok akun), service stok akun, signature webhook, sync harga Digiflazz, desain dasar UI | ✅ skeleton selesai, ⏳ sync harga dan desain UI |
| **2** | Checkout Livewire (input ID + validasi regex, pilih produk, pilih metode bayar), `TripayService::createTransaction`, job `HandlePaymentCallback`, halaman invoice | ⏳ |
| **3** | Job kirim order ke Digiflazz, job `HandleSupplierCallback`, alur stok akun di checkout (reserve → sold / release), notifikasi email/WA, cek status order | ⏳ |
| **4** | Testing end-to-end (Postman + transaksi nominal kecil), hardening keamanan, SEO dasar, deploy, revisi client | ⏳ |

---

## 6. Checklist Keamanan & Keandalan

- [x] Verifikasi signature callback Tripay (`X-Callback-Signature`, HMAC-SHA256).
- [x] Verifikasi signature webhook Digiflazz (`X-Hub-Signature`, HMAC-SHA1).
- [x] Kredensial akun dienkripsi di database.
- [x] Row lock saat reserve stok akun.
- [x] Hanya `role = admin` yang bisa masuk `/admin`.
- [x] Rate limit endpoint webhook (`throttle:120,1`).
- [ ] **Idempotency** callback: abaikan jika `OrderStatus::isFinal()` (dikerjakan di job minggu 2–3).
- [ ] Rate limit + Cloudflare Turnstile di form checkout.
- [ ] Alert saldo Digiflazz menipis (cek via `DigiflazzService::balance()` terjadwal).
- [ ] Whitelist IP callback di sisi Tripay/Digiflazz (opsional, tambahan dari signature).
- [ ] Backup database harian di VPS.

---

## 7. Catatan Kustomisasi Desain

- **Storefront** (halaman pembeli): Blade + Tailwind, desain 100% bebas sesuai permintaan client.
- **Admin panel** (Filament): menyesuaikan brand lewat `app/Providers/Filament/AdminPanelProvider.php` (warna, font, logo, layout), dan bisa ditambah custom theme dengan `php artisan make:filament-theme`. Struktur dasar panel tetap. Sebaiknya hal ini dicantumkan di scope kontrak.
