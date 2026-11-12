# SYSTEM_ARCHITECTURE — Arsitektur Sistem

> Prinsip [FINAL]: **satu aplikasi Laravel monolith**. Tidak ada microservices, tidak ada SPA/API terpisah.

## 1. Gambaran Umum

```mermaid
flowchart TB
    subgraph Browser
        U[Pembeli: Blade + Livewire + Alpine]
        A[Admin: Filament /admin]
    end
    subgraph Laravel["Aplikasi Laravel 13"]
        R[Routes + Controllers / Livewire]
        F[Filament Resources]
        SV[Services<br/>AccountStockService<br/>TripayService<br/>MeloService]
        J[Queue Jobs]
        SC[Scheduler]
        M[Eloquent Models]
    end
    DB[(MySQL 8)]
    RD[(Redis — production<br/>cache + queue)]
    PG[Payment Gateway<br/>kandidat: Tripay]
    DF[Melo H2H API]

    U --> R
    A --> F
    R --> SV
    F --> M
    SV --> M
    M --> DB
    J --> SV
    SC --> SV
    R -. dispatch .-> J
    J -. queue .- RD
    SV -->|HTTPS| PG
    SV -->|HTTPS| DF
    PG -->|callback /webhook/tripay| R
    DF -->|webhook /webhook/melo| R
```

## 2. Peran Komponen

| Komponen       | Peran                                                    | Status |
| -------------- | -------------------------------------------------------- | ------ |
| Laravel        | Routing, auth, validasi, Eloquent, queue, HTTP, enkripsi | ✅     |
| Filament 5     | Seluruh panel admin di `/admin` (1)                      | ✅     |
| Blade          | Layout dan halaman storefront (2)                        | ✅     |
| Livewire       | Bagian interaktif storefront, terutama checkout (3)      | ➕     |
| Alpine.js      | Interaksi kecil: toggle, copy kode bayar, modal (4)      | ➕     |
| Tailwind CSS 4 | Styling storefront (`resources/css/app.css`) (5)         | ✅     |
| Services       | Logika bisnis & integrasi eksternal (6)                  | ✅     |
| Queue Jobs     | Callback payment, kirim ke Melo, notifikasi         | ➕     |
| Scheduler      | `accounts:release-expired` tiap menit                    | ✅     |

✅ ada di kode · ➕ direkomendasikan, belum dibuat.

1. CRUD katalog, stok akun, pesanan, dashboard. Akses dibatasi `User::canAccessPanel()` (role admin).
2. `resources/views/storefront/*` dan `resources/views/components/layouts/app.blade.php`.
3. Form checkout: pilih produk, input ID, metode bayar, total.
4. Ikut terpasang bersama Livewire.
5. Admin memakai styling bawaan Filament.
6. Folder `app/Services`. Controller, Livewire, dan Job memanggil service, bukan memanggil API langsung.
- Scheduler didaftarkan di `routes/console.php`.

## 3. Komunikasi Frontend ↔ Backend

- Storefront dirender di server (Blade). Interaksi checkout lewat Livewire, tanpa REST API publik. [FINAL]
- Admin sepenuhnya lewat Filament (Livewire). [FINAL]
- Endpoint non-HTML hanya webhook: `POST /webhook/tripay`, `POST /webhook/melo`. Keduanya dikecualikan dari CSRF (`bootstrap/app.php`) dan diberi throttle. [FINAL]

## 4. Database, Cache, Queue

| Kebutuhan | Development           | Production               |
| --------- | --------------------- | ------------------------ |
| Database  | MySQL 8 (Laragon) (1) | MySQL 8 di VPS           |
| Cache     | `database`            | Redis [FINAL]            |
| Queue     | `database`            | Redis [FINAL] (2)        |
| Session   | `database`            | `database` / Redis [TBD] |
| Mail      | `log`                 | SMTP / Resend [TBD]      |

1. Database `topup_game`; test memakai `topup_game_test`.
2. Worker: `php artisan queue:work --tries=3`. Horizon = [TBD] (dependency baru).

## 5. Integrasi Eksternal

- **Payment gateway:** `app/Services/Payment/TripayService.php` (kandidat). Jika provider berubah, buat service baru dengan metode yang setara (buat transaksi, verifikasi callback). Controller dan job tidak boleh bergantung pada detail Tripay. [REKOMENDASI]
- **Melo:** `app/Services/Supplier/MeloService.php`.
- Konfigurasi di `config/services.php` (`services.tripay.*`, `services.melo.*`), nilai dari `.env`.
- Detail ada di [`API_INTEGRATION.md`](API_INTEGRATION.md).

## 6. Struktur Direktori

Status: ✅ ada di kode · ➕ direkomendasikan untuk dibuat (lokasi yang harus dipakai).

```
app/
├── Console/Commands/ReleaseExpiredAccounts.php      ✅ accounts:release-expired
├── Enums/                                          ✅ ProductType, OrderStatus, AccountStatus, UserRole
├── Exceptions/OutOfStockException.php               ✅
├── Filament/
│   ├── Resources/                                  ✅ Categories, Games, Products, GameAccounts, Orders
│   │   └── Users/ (Pelanggan)                      ➕
│   └── Widgets/StoreOverview.php                   ✅
├── Http/
│   ├── Controllers/StorefrontController.php        ✅ home, game, invoice
│   ├── Controllers/Webhook/                        ✅ TripayCallbackController, MeloCallbackController
│   ├── Controllers/Auth/ (login/register pelanggan) ➕
│   └── Requests/                                   ➕ Form Request validasi
├── Jobs/                                           ➕ HandlePaymentCallback, SendTopupToSupplier, HandleSupplierCallback
├── Livewire/Checkout.php                           ➕
├── Models/                                         ✅ User, Category, Game, Product, GameAccount, Order, ApiLog, Setting
├── Notifications/ atau Mail/                       ➕ jika notifikasi disetujui
├── Providers/Filament/AdminPanelProvider.php       ✅
└── Services/
    ├── AccountStockService.php                     ✅
    ├── Payment/TripayService.php                   ✅
    └── Supplier/MeloService.php                    ✅
database/
├── migrations/2026_11_11_*                         ✅ lihat DATABASE_DESIGN.md
├── factories/                                      ✅ satu per model
└── seeders/DatabaseSeeder.php                      ✅ admin + data contoh
resources/views/
├── components/layouts/app.blade.php                ✅
├── storefront/{home,game,invoice}.blade.php        ✅
└── livewire/                                       ➕
routes/
├── web.php                                         ✅ storefront + webhook
└── console.php                                     ✅ scheduler
tests/Feature/                                      ✅ AccountStockService, WebhookSignature, Storefront, AdminPanelAccess, MeloService
```

Aturan: jangan membuat folder dasar baru di luar daftar ini tanpa persetujuan.

## 7. Route Aktual

| Method | URL                  | Nama                | Handler                        |
| ------ | -------------------- | ------------------- | ------------------------------ |
| GET    | `/`                  | `home`              | `StorefrontController@home`    |
| GET    | `/game/{game}`       | `game.show`         | `StorefrontController@game`    |
| GET    | `/invoice/{order}`   | `invoice.show`      | `StorefrontController@invoice` |
| POST   | `/webhook/tripay`    | `webhook.tripay`    | `TripayCallbackController`     |
| POST   | `/webhook/melo` | `webhook.melo` | `MeloCallbackController`  |
| —      | `/admin/*`           | `filament.admin.*`  | Filament                       |

- `{game}` dicari berdasarkan `slug`; `{order}` dicari berdasarkan `invoice`.

Cek terbaru: `php artisan route:list --except-vendor`.

## 8. Menjalankan Lokal (Laragon)

Nyalakan MySQL di Laragon (**Start All**). Database `topup_game` dan `topup_game_test` dibuat dengan `utf8mb4_unicode_ci`.

```bash
composer install
npm install
cp .env.example .env          # isi kredensial sandbox (jangan commit)
php artisan key:generate
php artisan migrate --seed    # admin contoh: admin@example.com / password (khusus lokal)
npm run build                 # atau: composer run dev
php artisan serve
php artisan schedule:work     # terminal terpisah, untuk release stok akun
php artisan queue:work        # terminal terpisah, setelah job dibuat
php artisan test --compact
```

## 9. Deploy [TBD detail]

- VPS + Coolify **atau** Laravel Forge, dengan Cloudflare di depan (DNS, SSL, proteksi). Pilihan Coolify vs Forge = [TBD].
- Wajib di server: cron `schedule:run` tiap menit, queue worker sebagai service, Redis, backup database harian [REKOMENDASI].
- URL webhook production harus didaftarkan di dashboard payment gateway dan Melo.
