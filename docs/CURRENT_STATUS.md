# CURRENT_STATUS — Status Proyek

> **Terakhir diperbarui:** 2026-11-21, oleh AI agent (pilih metode bayar + checkout). Sebelumnya: 2026-11-21 (upload thumbnail game), 2026-11-17 (pemrosesan callback Duitku & Melo), 2026-11-16, oleh AI agent (implementasi master data admin). Sebelumnya: 2026-11-16 (sinkronisasi Duitku), 2026-11-14 (Tripay diganti Duitku).
> Klaim di bawah diverifikasi dari source code dan hasil test pada tanggal tersebut.

## 1. Status Proyek

| Item          | Nilai                                                                          |
| ------------- | ------------------------------------------------------------------------------ |
| Fase          | Skeleton selesai, supplier Melo terpasang, dokumentasi `docs/` dalam review PO |
| Tahap rencana | Tahap 1 (Setup), lihat `IMPLEMENTATION_PLAN.md`                                |
| Tanggal H1    | [TBD]                                                                          |
| Test          | **104 lulus, 0 gagal** (1)                                                      |
| Versi         | Laravel 13, Filament 5, PHP 8.4, MySQL 8.0.30 (2)                              |
| Git           | Remote `origin` (GitHub). Branch `main` dan `branch-ilham` di `da563a7` (3)    |

1. `php artisan test --compact`, 328 assertion, MySQL `topup_game_test`, 2026-11-16. Perlu `npm run build` dulu agar `ExampleTest` lulus.
2. Ditambah Tailwind 4 dan Vite 8, Vue 3.5 Inertia 3 (`inertiajs/inertia-laravel`, `@inertiajs/vue3`), MySQL dari Laragon.
3. Merge PR #1 (`init-melo`): Melo H2H menggantikan Digiflazz.

## 2. Sudah Ada (terverifikasi di kode)

| Area                | Ringkasan                                     | Test                      |
| ------------------- | --------------------------------------------- | ------------------------- |
| Skema DB            | 8 tabel aplikasi + bawaan Laravel; kolom uang integer; `games.melo_brand_id` | — |
| Model & enum        | 8 model, 4 enum                               | —                         |
| Factory & seeder    | Admin contoh, 3 kategori, 17 game (+ ikon), produk, 12 stok akun | — |
| Stok akun           | reserve / markSold / release / releaseExpired | `AccountStockServiceTest` |
| Scheduler           | `accounts:release-expired` tiap menit         | `AccountStockServiceTest` |
| Enkripsi kredensial | cast `encrypted` + hidden                     | `AccountStockServiceTest` |
| Admin panel         | 6 resource + widget, hanya admin              | `AdminPanelAccessTest`, `AdminCatalogResourceTest`, `AdminCustomerResourceTest` |
| Dashboard saldo Melo | Widget saldo + pembayaran, peringatan saldo menipis lewat lonceng admin (**belum ke API asli**) | `MeloBalanceDashboardTest` |
| Sync katalog Melo   | Tautan game↔brand, sync item, atur harga massal (**belum ke API asli**) | `MeloCatalogServiceTest`, `AdminCatalogResourceTest` |
| Storefront dasar    | Home, halaman game, invoice                   | `StorefrontTest`          |
| Webhook             | Verifikasi signature Duitku & Melo            | `WebhookSignatureTest`    |
| Pemrosesan callback | Job `HandlePaymentCallback`, `SendTopupToMelo`, `HandleSupplierCallback` (**test belum dijalankan, lihat §6 2026-11-17**) | `PaymentCallbackTest`, `SupplierCallbackTest` |
| Service integrasi   | Duitku & Melo (**belum ke API asli**)         | `DuitkuServiceTest`, `MeloServiceTest` |
| Konfigurasi         | MySQL, Asia/Jakarta, locale `id`, key `.env`  | —                         |

Lokasi & detail:
- **Skema DB:** `database/migrations/2026_11_11_*`.
- **Model:** User, Category, Game, Product, GameAccount, Order, ApiLog, Setting (`app/Models`).
- **Enum:** ProductType, OrderStatus, AccountStatus, UserRole (`app/Enums`).
- **Seeder:** `database/seeders/DatabaseSeeder.php`.
- **Stok akun:** `app/Services/AccountStockService.php`, memakai transaksi + lock.
- **Scheduler:** `routes/console.php`.
- **Enkripsi:** model `GameAccount`, test `test_credentials_are_encrypted_at_rest`.
- **Admin panel** (`app/Filament`): resource Kategori, Game, Produk (kolom stok, harga jual bisa diedit inline, aksi massal "Atur harga jual"), Stok Akun (semua status diatur manual oleh admin; `sold_at` terisi otomatis saat Terjual, K1), Pesanan (tanpa halaman create), dan Pelanggan (hanya lihat, tanpa admin), plus widget dashboard. Form Kategori/Game/Produk punya slug otomatis + unik, validasi regex, zone wajib bila `requires_zone_id`, supplier+SKU wajib untuk top up.
- **Dashboard & peringatan saldo:** widget `MeloBalanceOverview` dan `PaymentOverview` (`app/Filament/Widgets`), `MeloBalanceService`, command `melo:check-balance` (dijadwalkan tiap 10 menit; butuh `php artisan schedule:work`). Ambang: `MELO_LOW_BALANCE_THRESHOLD`. Detail di `API_INTEGRATION.md` §2.1a.
- **Sync katalog Melo:** `app/Services/Supplier/MeloCatalogService.php` + `app/Filament/Resources/Games/Actions/SyncMeloProductsAction.php`. Detail di `API_INTEGRATION.md` §2.2.
- **Storefront:** `StorefrontController`, `resources/views/storefront`. Input ID di halaman game masih statis. Kredensial di invoice hanya tampil jika order sukses.
- **Webhook:** `app/Http/Controllers/Webhook` (`DuitkuCallbackController`, `MeloCallbackController`). Hasilnya dicatat di `api_logs`, signature salah → 403 (Duitku) / 401 (Melo), CSRF dikecualikan, ada throttle.
- **Service integrasi:**
  - Duitku (`app/Services/Payment/DuitkuService.php`): paymentMethods, createTransaction, transactionStatus, verifikasi signature callback. Tanpa SDK. Dites dengan `Http::fake()`.
  - Melo (`app/Services/Supplier/MeloService.php`): profil, saldo, katalog, topup, cek status, signature webhook. Juga ada method di luar scope (lihat M10). Dites dengan `Http::fake()`.
  - Spesifikasi OpenAPI resmi Melo: `melostore-h2h.json` (root).
- **Konfigurasi:** `.env.example`, `config/app.php`, `config/services.php` (`services.duitku.*`, `services.melo.*`). Key Duitku/Melo masih kosong.

## 3. Belum Dikerjakan

- Registrasi/login pelanggan dan riwayat transaksi.
- Aksi admin pada Pelanggan (blokir dll., keputusan #10) dan batasan form Pesanan (M3).
- Nama metode bayar di invoice (masih kode, mis. `SP`); tampilan nomor VA di invoice.
- Cek status supplier terjadwal / reconcile `transactionStatus()`; penandaan order `pending` kedaluwarsa menjadi `expired`.
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
| M13 | `DuitkuService` tidak sinkron dengan test/docs | Checkout crash, callback sah ditolak | Selesai (2026-11-16) |
| M14 | Thumbnail game dari Melo (URL absolut) jadi gambar rusak | Gambar game tidak tampil | Selesai (2026-11-21), data lama perlu upload ulang |
| M15 | 17 test gagal saat dev server (`public/hot`) aktif / admin 404 | Hasil test tidak bisa dipercaya | Terbuka |

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
- **M13:** (selesai 2026-11-16, commit `5f7e2f3`) inquiry, callback, dan `transactionStatus()` kini HMAC-SHA256 dengan body JSON, sesuai test dan `API_INTEGRATION.md` §3. Dokumentasi resmi Duitku (docs.duitku.com) juga menyatakan MD5/SHA256 biasa usang. `php artisan test --compact --filter='Duitku|WebhookSignature'`: 15 lulus. Masih perlu verifikasi ke sandbox Duitku asli. Riwayat temuan: [`CORE_SYSTEM_REVIEW.md`](CORE_SYSTEM_REVIEW.md) §2.
- **M12:** `config/services.php`: `'sandbox' => env('MELO_SANDBOX')` tanpa default. Jika variabel tidak ada di `.env`, `MeloService::withSandboxMode()` mengirim `sandbox_mode = false` sehingga order menjadi transaksi nyata. Usulan: default `true`. Dicatat saja, belum diperbaiki.
- **M14:** Ditemukan saat uji manual di browser (2026-11-21). `GameForm` mengisi `games.thumbnail` dengan URL CDN Melo (`https://cdn.melostore.id/...`), lalu `StorefrontController::gameSummary()` (`thumbnailUrl`) membungkusnya dengan `Storage::disk('public')->url()` sehingga menjadi `/storage/https://...`. Gambar rusak di beranda dan halaman game. Selesai 2026-11-21: field thumbnail diganti upload file (lihat Riwayat), isi otomatis thumbnail dari Melo dihapus. Game yang sudah tersimpan dengan URL CDN tetap rusak sampai admin meng-upload ulang thumbnail-nya.
- **M15:** Ditemukan 2026-11-21, gagal juga tanpa perubahan hari itu (130 test, 113 lulus). (a) 11 test storefront/`ExampleTest` error 500 `StrayRequestException` ke `http://[::1]:5173/__inertia_ssr`: saat `composer run dev` jalan, `public/hot` ada sehingga Inertia mencoba SSR ke Vite. Terkonfirmasi 2026-11-21: dengan `INERTIA_SSR_ENABLED=false php artisan test --compact` semua test storefront lulus kecuali (e). (b) Halaman admin (`AdminCatalogResourceTest`, `AdminCustomerResourceTest`, `AdminPanelAccessTest`, `MeloBalanceDashboardTest`) mengembalikan 404, bukan 200/403; penyebab belum diselidiki. (c) `test_admin_marking_account_sold_...` gagal karena `credentials` wajib di form edit. (d) `MeloBalanceDashboardTest::test_command_notifies_admins_once_per_level_and_escalates` flaky (2 dari 3 run lulus): notifikasi `menipis` dan `hampir habis` kemungkinan dibuat di detik yang sama sehingga urutan `notifications` tidak pasti. (e) `StorefrontTest::test_invoice_shows_own_qris_image_only_while_pending` mencari `data:image/svg+xml;base64,` di HTML, padahal invoice kini halaman Inertia (QR dirender di Vue); gagal juga tanpa perubahan. Dicatat saja, belum diperbaiki.

## 5. Keputusan yang Menunggu (PO/Klien)

Semua diputuskan oleh PO. Kolom "Dibutuhkan sebelum" diturunkan dari `IMPLEMENTATION_PLAN.md`; jika lewat, tahap terkait tertahan.

| #   | Keputusan                                                       | Dibutuhkan sebelum   |
| --- | --------------------------------------------------------------- | -------------------- |
| 1   | ~~Provider payment final~~ → Duitku (2026-11-14)                | ✅ selesai           |
| 2   | Penanganan supplier gagal setelah dibayar & apakah refund masuk | H23 (Tahap 5)        |
| 3   | Aturan akses invoice/kredensial akun (M1)                       | H23 (Tahap 5)        |
| 4   | Aksi admin yang diizinkan pada pesanan (M3)                     | H5 (Tahap 2)         |
| 5   | Nasib kolom reseller (M4): hapus lewat migration atau biarkan   | H1 (Tahap 1)         |
| 6   | Notifikasi: email saja, atau + WhatsApp gateway                 | H23 (Tahap 5)        |
| 7   | ~~Kontak wajib untuk guest~~ → WhatsApp wajib, email opsional (2026-11-21) | ✅ selesai |
| 8   | ~~Penanggung biaya payment (fee)~~ → pembeli (2026-11-21); aturan markup harga masih terbuka | H11 (Tahap 3)        |
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
- Callback payment memakai route `POST /webhook/duitku`; cek `isFinal()` dilakukan di job, bukan di controller.
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
### 2026-11-14 — Penggantian payment gateway

Tripay diganti Duitku (keputusan PO): `DuitkuService`, `DuitkuCallbackController`, `POST /webhook/duitku`, `services.duitku.*`, `DUITKU_*` di `.env`. `TripayService`, `TripayCallbackController`, route, config, dan test Tripay dihapus. Library `duitkupg/duitku-php` dievaluasi dan **tidak dipakai** (lihat `API_INTEGRATION.md` §3). Tidak ada migration baru; komentar di migration `api_logs` yang menyebut `tripay` tidak diedit (migration sudah di-commit). **Belum diuji ke API Duitku asli.** Pemrosesan callback (job) masih TODO.

### 2026-11-16 — Master data admin + kolom uang integer

- **Pengecualian aturan migration (atas persetujuan PO/user):** `2026_11_11_000400_create_products_table.php` (`base_price`, `price`, `strike_price`) dan `2026_11_11_000500_create_orders_table.php` (`price`, `fee`, `total`) diedit langsung dari `decimal(15,2)` menjadi `unsignedBigInteger` (Rupiah penuh). Berlaku hanya untuk perubahan ini. **Setiap developer (termasuk pemilik `branch-ilham`) wajib menjalankan `php artisan migrate:fresh --seed` di database LOKAL masing-masing**; jangan dijalankan di database bersama/staging. `reseller_price` dan `users.balance` tetap decimal (M4, out of scope).
- Migration baru `2026_11_16_200655_add_melo_brand_id_to_games_table.php` (`games.melo_brand_id`, nullable, unik).
- Resource Pelanggan baru (`/admin/users`), hanya List + View. Kategori/Game/Produk/Stok Akun diperkuat (lihat §2). Toggle `is_active`/`is_popular` tidak lagi `required()` (di Filament, toggle wajib berarti harus aktif, sehingga item tidak bisa disimpan nonaktif).
- Sync katalog Melo (A9 sebagian): hanya lewat `Http::fake()`, **belum diuji ke API Melo asli**. Format target per SKU masih M9.
- Dashboard saldo Melo + notifikasi lonceng admin ditambahkan. Migration baru `2026_11_16_202857_create_notifications_table.php` (tabel `notifications` standar Laravel). `tests/TestCase.php` kini memanggil `Http::preventStrayRequests()` agar test tak pernah menembak API asli.
- Tidak dikerjakan: aturan markup tersimpan (A10), import CSV akun (A5), pembatasan form Pesanan (M3), core system (checkout/callback/job).

### 2026-11-17 — Pemrosesan callback Duitku & Melo

- Job baru di `app/Jobs/`: `HandlePaymentCallback` (Duitku), `SendTopupToMelo` (kirim top up), `HandleSupplierCallback` (Melo). Controller webhook kini dispatch job (TODO dihapus). Detail perilaku di `API_INTEGRATION.md` §2.5, §3.4, §4.
- Keputusan user: hanya order top up yang diproses (K1); kasus tepi (lunas setelah `expired`/`failed`, nominal beda, Melo `refunded`, Melo menolak 4xx, retry habis) tidak mengubah status, hanya `Log::warning` + notifikasi lonceng admin.
- Signature Duitku tetap HMAC-SHA256 (dikonfirmasi PO); `duitku-collection.json` hanya acuan nama field. Menjawab pertanyaan 24 `CORE_SYSTEM_REVIEW.md`.
- **K1 diputuskan PO (2026-11-17):** akun game hanya display, dijual manual; admin mengubah status akun di admin panel. Dicatat di `PROJECT_SPEC.md` §4 + §13 dan `BUSINESS_FLOW.md` §3 (ditandai diganti). §7 no. 2/4 dan §12 no. 2–3 diberi catatan.
- Form Stok Akun (`GameAccountForm`) kini mengizinkan semua status; `GameAccount` mengisi `sold_at` saat status jadi Terjual dan mengosongkannya bila diubah lagi. Test lama penguncian status diganti (`AdminCatalogResourceTest`), belum dijalankan (MySQL mati).
- **Risiko:** M12 (`MELO_SANDBOX` tanpa default) kini berdampak langsung: `SendTopupToMelo` bisa memotong saldo nyata jika variabel kosong. Perbaiki sebelum queue worker dijalankan di luar test.
- Tidak dikerjakan: scheduler `pending → expired`, reconcile terjadwal (B3), snapshot `supplier_sku` (O3).
- Test: `PaymentCallbackTest` (5) dan `SupplierCallbackTest` (10) ditulis tetapi **belum lulus terverifikasi**: MySQL test (`127.0.0.1:8889`) tidak berjalan saat dikerjakan.

### 2026-11-21 — Upload thumbnail game

- `GameForm`: `thumbnail` kini `FileUpload::image()` (disk `public`, folder `games/thumbnails`, WebP/JPEG/PNG, maks 2 MB), otomatis dipotong 1:1 dan di-resize ke 512×512 px sesuai tampilan storefront (`aspect-square`). Kolom tetap menyimpan path (sesuai `DATABASE_DESIGN.md`), tanpa migration.
- Thumbnail dari brand Melo kini **diunduh ke storage** saat brand dipilih (keputusan PO 2026-11-21, bukan menyimpan link CDN): `MeloCatalogService::storeThumbnail()` hanya menerima URL https, maks 2 MB, dan isi file harus benar-benar WebP/JPEG/PNG (dicek dari isi, bukan header). Hasilnya mengisi field upload; admin tetap bisa menggantinya. Gambar Melo disimpan apa adanya (tidak di-resize 512×512). Jika admin batal menyimpan, file unduhan tertinggal di `games/thumbnails`.
- `GamesTable`: kolom thumbnail kini `ImageColumn` (pratinjau gambar).
- `banner` game masih input teks (tidak diminta).
- Hapus game yang sudah punya pesanan: FK `orders.game_id` tetap `RESTRICT` (keputusan PO 2026-11-21, sesuai `DATABASE_DESIGN.md`; opsi cascade ditolak karena menghapus riwayat transaksi). Tombol Delete di halaman edit kini nonaktif dengan tooltip "nonaktifkan lewat toggle Aktif" (sebelumnya error SQL 1451). Bulk delete sudah melewati game berpesanan; pesan gagalnya kini menjelaskan alasannya. Relasi baru `Game::orders()`. Test: 3 baru di `AdminCatalogResourceTest`, lulus. Suite penuh: 137 test, 120 lulus, 17 gagal (semua M15).

### 2026-11-21 — Pilih metode bayar + checkout

- Keputusan PO: fee ditanggung pembeli (#8 sebagian), semua metode aktif Duitku ditampilkan dan dikelompokkan, WhatsApp wajib dan email opsional (#7). Checkout hanya produk top up (K1).
- `DuitkuService::paymentChannels()` (cache 10 menit per nominal) + `channelGroup()`. `StorefrontController::game()` menambah prop `paymentChannels` (diisi lewat partial reload `?product=ID`) dan `checkoutUrl`.
- Baru: route `POST /game/{game}/checkout` (`checkout.store`, throttle 10/menit), `CheckoutRequest`, `CheckoutController`. Order `pending` dengan snapshot, `fee` dari Duitku, `total = price + fee`, `expired_at` 15 menit, lalu `createTransaction()` + `storePaymentDetails()`, kemudian redirect ke invoice. Duitku gagal: order `failed` + error di form.
- `Game.vue`: form `useForm`, kartu metode bayar per grup dengan biaya, ringkasan Harga/Biaya admin/Total, tombol Beli aktif. Checkbox konfirmasi ID dipindah ke kartu "Detail kontak" agar tampil juga di HP (sebelumnya hanya di sidebar desktop).
- Tanpa migration. Kontrak di `TASK_DIVISION.md` §3.3–3.4 ditandai FINAL.
- Diuji ke **sandbox** Duitku: 23 metode tampil (fee 0 di sandbox); checkout `SP` Rp9.000 → order `INV261121M3OXNIIV` `pending` dengan `payment_reference` dan QR string, redirect ke invoice.
- Test baru: `CheckoutTest` (7), `StorefrontTest` (+3), `DuitkuServiceTest` (+2), semua lulus. Suite penuh: 149 test; dengan dev server jalan 129 lulus/20 gagal (M15 + 3 test storefront baru yang kena SSR); dengan `INERTIA_SSR_ENABLED=false` 140 lulus/9 gagal (semua M15).
- Belum: nama metode di invoice masih kode; penanda `pending` → `expired`; fee asli production belum terverifikasi; peta kode grup belum dicocokkan dokumentasi resmi Duitku.
- Test baru: `AdminCatalogResourceTest` (upload tersimpan di disk `public`, file non-gambar ditolak, pilih brand Melo menyimpan thumbnail unduhan) dan `MeloCatalogServiceTest` (unduh sukses; tolak isi bukan gambar, 404, dan http). Semua lulus. Suite penuh: 134 test, 116 lulus, 18 gagal; semua kegagalan ada di M15 (17 sudah ada sebelum perubahan + 1 test flaky).

## 7. Pekerjaan Berikutnya

0. Jawab pertanyaan di [`CORE_SYSTEM_REVIEW.md`](CORE_SYSTEM_REVIEW.md) §5 (review core system 2026-11-16). Dokumen itu juga mencatat keputusan baru yang belum masuk `PROJECT_SPEC.md` §13: akun dijual manual via WhatsApp (K1), kolom uang jadi integer dengan pengecualian edit migration (K2), dan pengujian pembayaran tanpa tampilan (K3).
1. PO me-review dan merevisi dokumen `docs/`.
2. Putuskan keputusan §5 yang dibutuhkan sebelum Tahap 1–2 (no. 4, 5, 10, 12, 13).
3. Daftar akun sandbox Duitku & Melo (risiko E1/E2), lalu verifikasi signature callback dan format body `transactionStatus` (lihat `API_INTEGRATION.md` §3.3–3.4).
4. Uji `MeloService` ke sandbox Melo, termasuk format target per SKU (M9).
5. Lanjutkan Tahap 2: batasi form pesanan (M3), mask kredensial akun, import CSV akun; lalu core system sesuai `CORE_SYSTEM_REVIEW.md` §4.
6. Semua developer: `php artisan migrate:fresh --seed` di DB lokal setelah menarik perubahan ini.
