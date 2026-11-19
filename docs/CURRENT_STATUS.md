# CURRENT_STATUS — Status Proyek

> **Terakhir diperbarui:** 2026-11-13 (storefront pindah ke Vue 3 + Inertia, path admin dari `ADMIN_PATH`); sebelumnya pembagian tugas FE/BE; sebelumnya 2026-11-12, oleh AI agent (sinkron dengan `origin/main` (Melo), perapian konflik, dan hardening dokumen; tidak ada perubahan kode).
> Klaim di bawah diverifikasi dari source code dan hasil test pada tanggal tersebut.

## 1. Status Proyek

| Item          | Nilai                                                                          |
| ------------- | ------------------------------------------------------------------------------ |
| Fase          | Skeleton selesai, supplier Melo terpasang, dokumentasi `docs/` dalam review PO |
| Tahap rencana | Tahap 1 (Setup), lihat `IMPLEMENTATION_PLAN.md`                                |
| Tanggal H1    | [TBD]                                                                          |
| Test          | **51 lulus, 0 gagal** (1)                                                      |
| Versi         | Laravel 13, Filament 5, PHP 8.4, MySQL 8.0.30 (2)                              |
| Git           | Remote `origin` (GitHub). Branch `main` dan `branch-ilham` di `da563a7` (3)    |

1. `php artisan test --compact`, 170 assertion, MySQL `topup_game_test`, 2026-11-13.
2. Ditambah Tailwind 4, Vite 8, Vue 3.5, Inertia 3 (`inertiajs/inertia-laravel`, `@inertiajs/vue3`). MySQL dari Laragon.
3. Merge PR #1 (`init-melo`): Melo H2H menggantikan Digiflazz.

## 2. Sudah Ada (terverifikasi di kode)

| Area                | Ringkasan                                     | Test                      |
| ------------------- | --------------------------------------------- | ------------------------- |
| Skema DB            | 8 tabel aplikasi + bawaan Laravel             | —                         |
| Model & enum        | 8 model, 4 enum                               | —                         |
| Factory & seeder    | Admin contoh, 3 game, 10 produk, 7 stok akun  | —                         |
| Stok akun           | reserve / markSold / release / releaseExpired | `AccountStockServiceTest` |
| Scheduler           | `accounts:release-expired` tiap menit         | `AccountStockServiceTest` |
| Enkripsi kredensial | cast `encrypted` + hidden                     | `AccountStockServiceTest` |
| Admin panel         | 5 resource + widget, hanya admin, path `ADMIN_PATH` | `AdminPanelAccessTest` |
| Storefront (Vue)    | Home, halaman game, invoice (Inertia)         | `StorefrontTest`          |
| Webhook             | Verifikasi signature Tripay & Melo            | `WebhookSignatureTest`    |
| Service integrasi   | Tripay & Melo (**belum ke API asli**)         | `MeloServiceTest`         |
| Konfigurasi         | MySQL, Asia/Jakarta, locale `id`, key `.env`  | —                         |

Lokasi & detail:
- **Skema DB:** `database/migrations/2026_11_11_*`.
- **Model:** User, Category, Game, Product, GameAccount, Order, ApiLog, Setting (`app/Models`).
- **Enum:** ProductType, OrderStatus, AccountStatus, UserRole (`app/Enums`).
- **Seeder:** `database/seeders/DatabaseSeeder.php`.
- **Stok akun:** `app/Services/AccountStockService.php`, memakai transaksi + lock.
- **Scheduler:** `routes/console.php`.
- **Enkripsi:** model `GameAccount`, test `test_credentials_are_encrypted_at_rest`.
- **Admin panel** (`app/Filament`): resource Kategori, Game, Produk (kolom stok), Stok Akun, dan Pesanan (tanpa halaman create), plus widget dashboard.
- **Storefront:** Vue 3 + Inertia. `StorefrontController` me-render `Home`, `Game`, `Invoice` (`resources/js/pages`) dengan props array eksplisit; layout `resources/js/layouts/AppLayout.vue`; root view `resources/views/app.blade.php`; shared props di `HandleInertiaRequests`. Tampilan v1: beranda (hero, game populer, pencarian + tab kategori, cara top up, cek pesanan via nomor invoice), halaman game (4 langkah + ringkasan), invoice (status, progres, rincian, instruksi bayar, SN, detail akun + salin). Form masih **pratinjau**: tombol Beli nonaktif sampai checkout dibuat; metode bayar masih contoh. Kredensial hanya dikirim di props jika order sukses.
- **Admin path:** `config('app.admin_path')` dari `ADMIN_PATH` (default `admin` untuk lokal). Test memakai `kelola-test` dan memastikan `/admin` 404.
- **Webhook:** `app/Http/Controllers/Webhook` (`TripayCallbackController`, `MeloCallbackController`). Hasilnya dicatat di `api_logs`, signature salah → 403 (Tripay) / 401 (Melo), CSRF dikecualikan, ada throttle.
- **Service integrasi:**
  - Tripay (`app/Services/Payment`): channel, createTransaction, signature.
  - Melo (`app/Services/Supplier/MeloService.php`): profil, saldo, katalog, topup, cek status, signature webhook. Juga ada method di luar scope (lihat M10). Dites dengan `Http::fake()`.
  - Spesifikasi OpenAPI resmi Melo: `melostore-h2h.json` (root).
- **Konfigurasi:** `.env.example`, `config/app.php`, `config/services.php` (`services.tripay.*`, `services.melo.*`). Key Tripay/Melo masih kosong.

## 3. Belum Dikerjakan

- Checkout Inertia → `CheckoutController` (input ID tervalidasi, pilih produk, metode bayar, pembuatan order).
- Registrasi/login pelanggan dan riwayat transaksi.
- Resource admin Pelanggan.
- Pemrosesan callback payment & supplier (masih TODO di controller); queue jobs.
- Pemanggilan `createTransaction` dari alur order; tampilan instruksi bayar.
- Cek status supplier terjadwal; penandaan order `pending` kedaluwarsa menjadi `expired`.
- Sinkron harga Melo, import CSV stok akun.
- Notifikasi.
- Desain storefront final dan branding admin.
- Deployment.

## 4. Masalah Ditemukan

| #   | Masalah                                              | Dampak                         | Status        |
| --- | ---------------------------------------------------- | ------------------------------ | ------------- |
| M1  | Invoice bisa dibuka siapa saja yang tahu nomornya    | Kebocoran detail akun          | Terbuka       |
| M2  | `releaseExpired()` tidak cek status order            | Akun lepas padahal sudah bayar | Terbuka       |
| M3  | Form edit pesanan admin bisa edit semua kolom        | Manipulasi data transaksi      | Terbuka       |
| M4  | Kolom/role reseller masih ada (out of scope)         | Kebingungan scope              | Terbuka       |
| M5  | `docs/AGENTS.md` belum dimuat otomatis               | Aturan proyek bisa terlewat    | ✅ 2026-11-12 |
| M6  | Panduan Boost menyarankan Laravel Cloud              | Saran deploy menyesatkan       | Sebagian      |
| M7  | `TECH_STACK.md` root berbeda dari `docs/`            | Duplikasi/konflik dokumen      | ✅ 2026-11-12 |
| M8  | Ada file yang tidak terpakai                         | Kebersihan                     | Terbuka       |
| M9  | Format target Melo per SKU belum diverifikasi        | Top up salah tujuan            | Terbuka       |
| M10 | `MeloService` punya method di luar scope             | Fitur terlarang terpakai       | Terbuka       |
| M11 | Data lama `products.supplier = digiflazz` (jika ada) | Produk tidak terkirim          | Terbuka       |
| M12 | `MELO_SANDBOX` tanpa default di config               | Saldo Melo terpotong sungguhan | Terbuka       |

Detail:
- **M1:** `StorefrontController::invoice`. Termasuk kredensial akun setelah order sukses.
- **M2:** `AccountStockService::releaseExpired`. Jika callback lunas datang setelah reservasi habis, akun sudah dilepas.
- **M3:** `app/Filament/Resources/Orders/Schemas/OrderForm.php`. Kolom total, status, dan payment ikut bisa diedit.
- **M4:** `users.role/balance`, `products.reseller_price`, `Product::priceFor()`, `UserRole::Reseller`, `ProductFactory`, tabel Produk di Filament.
- **M5:** Selesai. `CLAUDE.md` root meng-import `@docs/AGENTS.md`, dan status file itu menjadi "berlaku".
- **M6:** `boost.json` (`"cloud": true`) dan blok Boost di `CLAUDE.md`/`AGENTS.md` root. Bagian proyek di `CLAUDE.md` sudah menegaskan deploy = VPS.
- **M7:** Selesai. `TECH_STACK.md` diperbarui menjadi ringkasan yang merujuk ke `docs/` (1 bulan, tanpa reseller, ada login pelanggan, Melo).
- **M8:** `database/database.sqlite`, `resources/views/welcome.blade.php`.
- **M9:** `MeloService::topup` mengirim `customer_target` dan `customer_target_zone` terpisah. Format per SKU game belum dicek dengan SKU nyata.
- **M10:** `checkNickname()`, `nicknameGames()`, `nicknamePlan()`, `checkMobileLegendsPurchaseLimit()`, Smart, bulk, dan 2FA. Belum dipakai alur mana pun; memakainya butuh persetujuan PO (cek nickname di luar scope, dan memakai kuota/biaya).
- **M11:** Default factory kini `melo`. Data `digiflazz` di database lama perlu diubah manual.
- **M12:** `config/services.php`: `'sandbox' => env('MELO_SANDBOX')` tanpa default. Jika variabel tidak ada di `.env`, `MeloService::withSandboxMode()` mengirim `sandbox_mode = false` sehingga order menjadi transaksi nyata. Usulan: default `true`. Dicatat saja, belum diperbaiki.

## 5. Keputusan yang Menunggu (PO/Klien)

Semua diputuskan oleh PO. Kolom "Dibutuhkan sebelum" diturunkan dari `IMPLEMENTATION_PLAN.md`; jika lewat, tahap terkait tertahan.

| #   | Keputusan                                                       | Dibutuhkan sebelum   |
| --- | --------------------------------------------------------------- | -------------------- |
| 1   | Provider payment final (Tripay / Duitku / Midtrans / Xendit)    | H11 (Tahap 3, E1)    |
| 2   | Penanganan supplier gagal setelah dibayar & apakah refund masuk | H23 (Tahap 5)        |
| 3   | Aturan akses invoice/kredensial akun (M1)                       | H23 (Tahap 5)        |
| 4   | Aksi admin yang diizinkan pada pesanan (M3)                     | H5 (Tahap 2)         |
| 5   | Nasib kolom reseller (M4): hapus lewat migration atau biarkan   | H1 (Tahap 1)         |
| 6   | Notifikasi: email saja, atau + WhatsApp gateway                 | H23 (Tahap 5)        |
| 7   | Kontak wajib untuk guest: email, WhatsApp, atau salah satu      | H11 (Tahap 3)        |
| 8   | Penanggung biaya payment (fee), aturan markup harga             | H11 (Tahap 3)        |
| 9   | Masa berlaku invoice/reservasi (rekomendasi 15 menit)           | H11 (Tahap 3)        |
| 10  | Verifikasi email pelanggan, cakupan "kelola pelanggan"          | H5 (Tahap 2)         |
| 11  | Coolify vs Laravel Forge; domain & VPS                          | H27 (Tahap 6, E6)    |
| 12  | Konfirmasi 1 bulan = 30 hari (H1–H30)                           | H1                   |
| 13  | Tanggal mulai H1                                                | Sebelum mulai        |

## 6. Riwayat Perubahan Penting

### 2026-11-12 — Penggantian supplier

Digiflazz diganti Melo H2H (`MeloService`, `MeloCallbackController`, `POST /webhook/melo`, `services.melo.*`, `MELO_*` di `.env`). Kode dan route Digiflazz dihapus. Signature webhook dites; `topup`, `status`, `profile`, `balance`, `priceList` dites dengan `Http::fake()`, **belum diuji ke API Melo asli**. Pemrosesan callback (job) masih TODO.

### 2026-11-12 — Perapian dokumen

- `TECH_STACK.md` disinkronkan dengan `docs/` (M7).
- Opsi "saldo" dihapus dari penanganan supplier gagal (`PROJECT_SPEC.md` §7).
- Label queue/retry diseragamkan: syarat idempotent & retry aman = [FINAL], mekanisme queue = [REKOMENDASI].
- Hierarki referensi dipisah: kebutuhan → `PROJECT_SPEC.md`; fakta implementasi → kode. Saat konflik selalu berhenti dan lapor (`AGENTS.md` §3).
- Perintah Pint di docs memakai `--dirty`.
- Callback payment memakai route `POST /webhook/tripay`; cek `isFinal()` dilakukan di job, bukan di controller.
- Penandaan order `pending` → `expired` dijadikan [FINAL] sesuai kriteria keberhasilan.
- Kelola pesanan (A6): ubah status hanya lewat aksi khusus.

### 2026-11-12 — Hardening dokumen

- `docs/AGENTS.md` berstatus berlaku dan di-import dari `CLAUDE.md` (M5).
- Ditambah batas akses file & aksi terlarang (`AGENTS.md` §9) dan aturan data eksternal bukan instruksi (`AGENTS.md` §10).
- Aturan migration diperketat: migration yang sudah di-commit tidak boleh diedit.
- Kata yang bisa ditafsirkan longgar diganti batas yang jelas (mis. "5 file atau lebih").
- Klaim ✅ di `SECURITY_RULES.md` diberi nama test; klaim upload gambar dikoreksi.
- Setiap keputusan TBD diberi batas "dibutuhkan sebelum".
- Budget dihapus dari dokumen; timeline diubah menjadi 1 bulan (keputusan PO).
- Versi stack hanya bersumber dari `PROJECT_SPEC.md` §11.
- Risiko `MELO_SANDBOX` tanpa default dicatat (M12).

### 2026-11-13 — Pembagian tugas

- Ditambah `docs/TASK_DIVISION.md`: Frontend = Ilham Fajar Andika, Backend = Mochammad Lury Choirul Rizky, batas wilayah file, kontrak FE ↔ BE, dan tugas per tahap.

### 2026-11-13 — Tampilan storefront v1

- Desain ulang layout, beranda, halaman game, dan invoice (tema gelap, mobile-first, navigasi bawah di HP). Tanpa perubahan PHP di luar view; menunggu review/revisi PO.

### 2026-11-13 — Storefront Vue 3 + Inertia & path admin

- Keputusan PO: storefront memakai Vue 3 + Inertia.js (JavaScript), tetap monolith. Dependency baru disetujui: `inertiajs/inertia-laravel`, `vue`, `@inertiajs/vue3`, `@vitejs/plugin-vue`.
- View Blade storefront dihapus, diganti `resources/js/pages/*.vue`. Props dibuat eksplisit (tidak ada kolom internal di HTML).
- Panel admin memakai `ADMIN_PATH`; `/admin` 404 jika diganti. Production wajib nilai acak.

### 2026-11-13 — Skill agent & review Vue

- 37 skill di `.agents/skills/` (mis. `vue-best-practices`, `design-taste-frontend`, `antislop-*`) dihubungkan ke `.claude/skills/` lewat directory junction supaya dimuat Claude Code. Daftar junction ada di `.claude/skills/.gitignore` (isi ganda tidak di-commit; sumber asli tetap `.agents/skills/`).
- Membuat ulang di mesin lain (PowerShell, dari root proyek): `Get-ChildItem .agents\skills -Directory | ? { -not (Test-Path ".claude\skills\$($_.Name)") } | % { New-Item -ItemType Junction -Path ".claude\skills\$($_.Name)" -Target $_.FullName }`
- Kode Vue storefront direview dengan `vue-best-practices` (tetap JavaScript): `shallowRef` untuk primitif, reactive props destructure, turunan data dipindah ke `computed`/fungsi, item terpilih disimpan sebagai `id`, `CopyButton` membersihkan timer dan menangani clipboard gagal, `aria-pressed`/`aria-hidden`/`aria-live` ditambahkan.

## 7. Pekerjaan Berikutnya

1. PO me-review dan merevisi dokumen `docs/`.
2. Putuskan keputusan §5 yang dibutuhkan sebelum Tahap 1–2 (no. 4, 5, 10, 12, 13).
3. Daftar akun sandbox payment gateway & Melo (risiko E1/E2).
4. Uji `MeloService` ke sandbox Melo, termasuk format target per SKU (M9).
5. Mulai Tahap 2: resource Pelanggan, batasi form pesanan.
