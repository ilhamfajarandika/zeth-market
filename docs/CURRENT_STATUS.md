# CURRENT_STATUS — Status Proyek

> **Terakhir diperbarui:** 2026-11-12, oleh AI agent (supplier diganti dari Digiflazz ke Melo atas keputusan PO).
> Klaim di bawah diverifikasi dari source code dan hasil test pada tanggal tersebut.

## 1. Status Proyek

| Item | Nilai |
|---|---|
| Fase | Skeleton selesai, dokumentasi `docs/` dalam review PO |
| Tahap rencana | Tahap 1 (Setup), lihat [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md). Tanggal H1 = [TBD] |
| Test | Terakhir di MySQL `topup_game_test`: 22 lulus (sebelum penggantian supplier). Setelah penggantian: 29 dari 30 lulus **di SQLite in-memory** karena MySQL tidak aktif; 1 gagal = `ExampleTest` (Vite manifest belum di-build). **Perlu dijalankan ulang di MySQL.** |
| Versi | Laravel 13.x, Filament 5.x, PHP 8.4, MySQL 8.0.30 (Laragon), Tailwind 4, Vite 8 |
| Git | **Belum diinisialisasi** |

## 2. Sudah Ada (terverifikasi di kode)

| Area | Detail | Bukti |
|---|---|---|
| Skema DB | 8 tabel aplikasi + bawaan Laravel | `database/migrations/2026_11_11_*` |
| Model & enum | User, Category, Game, Product, GameAccount, Order, ApiLog, Setting; ProductType, OrderStatus, AccountStatus, UserRole | `app/Models`, `app/Enums` |
| Factory & seeder | Admin contoh, 3 game, 10 produk, 7 stok akun | `database/seeders/DatabaseSeeder.php` |
| Stok akun | reserve / markSold / release / releaseExpired dengan transaksi + lock | `app/Services/AccountStockService.php`, `AccountStockServiceTest` |
| Scheduler | `accounts:release-expired` tiap menit | `routes/console.php` |
| Enkripsi kredensial | cast `encrypted` + hidden | `GameAccount`, test `test_credentials_are_encrypted_at_rest` |
| Admin panel | Resource Kategori, Game, Produk (kolom stok), Stok Akun, Pesanan (tanpa create); widget dashboard; akses hanya admin | `app/Filament`, `AdminPanelAccessTest` |
| Storefront dasar | Home, halaman game (stok akun + input ID statis), invoice (kredensial hanya jika sukses) | `StorefrontController`, `resources/views/storefront`, `StorefrontTest` |
| Webhook | Verifikasi signature Tripay & Melo, log ke `api_logs`, 403 (Tripay) / 401 (Melo) jika salah, CSRF dikecualikan, throttle | `app/Http/Controllers/Webhook`, `WebhookSignatureTest` |
| Service integrasi | Tripay: channel, createTransaction, signature. Melo: profil, saldo, katalog, topup, cek status, signature webhook | `app/Services/Payment`, `app/Services/Supplier` (**belum diuji ke API asli**) |
| Konfigurasi | MySQL, timezone Asia/Jakarta, locale `id`, key `.env` Tripay/Melo (kosong) | `.env.example`, `config/services.php` |

## 3. Belum Dikerjakan

- Checkout Livewire (input ID tervalidasi, pilih produk, metode bayar, pembuatan order).
- Registrasi/login pelanggan dan riwayat transaksi.
- Resource admin Pelanggan.
- Pemrosesan callback payment & supplier (masih TODO di controller); queue jobs.
- Pemanggilan `createTransaction` dari alur order; tampilan instruksi bayar.
- Cek status supplier; penandaan order `pending` kedaluwarsa menjadi `expired`.
- Sinkron harga Melo, import CSV stok akun.
- Notifikasi.
- Desain storefront final dan branding admin.
- Deployment.

## 4. Masalah Ditemukan

| # | Masalah | Lokasi | Dampak |
|---|---|---|---|
| M1 | Invoice dapat dibuka siapa saja yang tahu nomor invoice, termasuk kredensial akun setelah sukses | `StorefrontController::invoice` | Kebocoran akun |
| M2 | `releaseExpired()` hanya melihat waktu, tidak mengecek status order | `AccountStockService::releaseExpired` | Akun bisa dilepas padahal pembayaran sukses datang terlambat |
| M3 | Form edit pesanan admin mengizinkan edit semua kolom (total, status, payment) | `app/Filament/Resources/Orders/Schemas/OrderForm.php` | Manipulasi data transaksi |
| M4 | Kolom/role reseller masih ada padahal out of scope | `users.role/balance`, `products.reseller_price`, `Product::priceFor()`, `UserRole::Reseller`, `ProductFactory`, Filament Produk | Kebingungan scope |
| M5 | `docs/AGENTS.md` belum dibaca otomatis; Claude Code hanya membaca `CLAUDE.md` root | root | Aturan proyek bisa terlewat |
| M6 | Panduan Boost menyarankan Laravel Cloud (`boost.json` `"cloud": true`), padahal deploy = VPS | `CLAUDE.md`, `AGENTS.md` root | Saran deploy menyesatkan |
| M7 | Dokumen root `TECH_STACK.md` berbeda dari `docs/`: timeline 4 minggu (vs 21 hari), menyebut harga reseller, tanpa pelanggan login | root | Duplikasi/konflik dokumen |
| M8 | File tidak terpakai: `database/database.sqlite`, `resources/views/welcome.blade.php` | — | Kebersihan |
| M9 | Melo menerima `customer_target` dan `customer_target_zone` terpisah. Apakah tiap SKU game memakai format itu (dan nilai zone-nya) belum diverifikasi dengan SKU nyata | `MeloService::topup` | Top up salah tujuan |

## 5. Keputusan yang Menunggu (PO/Klien)

1. Provider payment final (Tripay / Duitku / Midtrans / Xendit).
2. Penanganan supplier gagal setelah dibayar & apakah refund masuk scope.
3. Aturan akses invoice/kredensial akun (M1).
4. Aksi admin yang diizinkan pada pesanan (M3).
5. Nasib kolom reseller (M4): hapus lewat migration atau biarkan.
6. Notifikasi: email saja, atau + WhatsApp gateway (teknologi baru).
7. Kontak wajib untuk guest: email, WhatsApp, atau salah satu.
8. Penanggung biaya payment (fee), aturan markup harga.
9. Masa berlaku invoice/reservasi (rekomendasi 15 menit).
10. Verifikasi email pelanggan, cakupan "kelola pelanggan".
11. Coolify vs Laravel Forge; domain & VPS.
12. Cara mengaktifkan `docs/AGENTS.md` dan nasib `TECH_STACK.md` root (M5, M7).
13. Tanggal mulai H1.

## 5a. Perubahan Supplier (2026-11-12)

Digiflazz diganti Melo H2H (`MeloService`, `MeloCallbackController`, `POST /webhook/melo`, `services.melo.*`, `MELO_*` di `.env`). Kode dan route Digiflazz dihapus. Kolom `products.supplier` default factory kini `melo`; data lama bernilai `digiflazz` (jika ada) perlu diubah manual. Signature webhook dites; `topup`, `status`, `profile`, `balance`, `priceList` dites dengan `Http::fake()`, **belum diuji ke API Melo asli**. Pemrosesan callback (job) masih TODO.

## 6. Pekerjaan Berikutnya

1. PO me-review dan merevisi dokumen `docs/`.
2. Putuskan item 5.1, 5.3, 5.5, 5.12 (berpengaruh ke tahap awal).
3. `git init` + commit awal (atas izin PO).
4. Daftar akun sandbox payment gateway & Melo (risiko E1/E2).
5. Mulai Tahap 2: resource Pelanggan, batasi form pesanan.
