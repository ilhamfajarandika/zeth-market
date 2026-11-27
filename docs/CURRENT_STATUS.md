# CURRENT_STATUS — Status Proyek

> **Terakhir diperbarui:** 2026-11-28, oleh AI agent (cek nickname pelanggan di dialog Beli Sekarang, endpoint `POST /game/{game}/nickname`, purge cache nickname). Sebelumnya: 2026-11-27 (idempotensi callback sesuai SECURITY_RULES §7; format rupiah harga akun game; keputusan cek nickname pelanggan dan `cachedNickname()`; susunan kartu dashboard admin; kuota cek nickname di dashboard; gambar stok akun, maks 5 WebP; kode game Melo untuk cek nickname). Sebelumnya: 2026-11-26 (jenis dan QR code metode bayar), 2026-11-26 (tampilan detail pesanan di panel admin), 2026-11-26 (warna & favicon panel admin disesuaikan landing page), 2026-11-22 (katalog Melo dipilih dulu + hapus kolom reseller/tidak terpakai). Sebelumnya: 2026-11-21 (pilih metode bayar + checkout), 2026-11-21 (upload thumbnail game), 2026-11-17 (pemrosesan callback Duitku & Melo), 2026-11-16, oleh AI agent (implementasi master data admin). Sebelumnya: 2026-11-16 (sinkronisasi Duitku), 2026-11-14 (Tripay diganti Duitku).
> Klaim di bawah diverifikasi dari source code dan hasil test pada tanggal tersebut.

## 1. Status Proyek

| Item          | Nilai                                                                          |
| ------------- | ------------------------------------------------------------------------------ |
| Fase          | Skeleton selesai, supplier Melo terpasang, dokumentasi `docs/` dalam review PO |
| Tahap rencana | Tahap 1 (Setup), lihat `IMPLEMENTATION_PLAN.md`                                |
| Tanggal H1    | [TBD]                                                                          |
| Test          | **196 lulus, 7 gagal** (1)                                                      |
| Versi         | Laravel 13, Filament 5, PHP 8.4, MySQL 8.0.30 (2)                              |
| Git           | Remote `origin` (GitHub). Branch `main` dan `branch-ilham` di `da563a7` (3)    |

1. `INERTIA_SSR_ENABLED=false php artisan test --compact`, 203 test, SQLite in-memory, 2026-11-28 (setelah cek nickname pelanggan; sebelumnya 192 test, 185 lulus, 7 gagal, setelah dropdown kode game Melo; sebelumnya 189 test, 182 lulus, 7 gagal, setelah idempotensi callback; sebelumnya 183 test, 175 lulus, 8 gagal). Sebelum perubahan gambar stok akun 2026-11-27: 164 test, 156 lulus, 8 gagal. Sebelum perubahan warna/favicon 2026-11-26: 149 lulus, 9 gagal. Perlu `npm run build` dulu agar `ExampleTest` lulus.
2. Ditambah Tailwind 4 dan Vite 8, Vue 3.5 Inertia 3 (`inertiajs/inertia-laravel`, `@inertiajs/vue3`), MySQL dari Laragon.
3. Merge PR #1 (`init-melo`): Melo H2H menggantikan Digiflazz.

## 2. Sudah Ada (terverifikasi di kode)

| Area                | Ringkasan                                     | Test                      |
| ------------------- | --------------------------------------------- | ------------------------- |
| Skema DB            | 9 tabel aplikasi (+ `melo_products`) + bawaan Laravel; kolom uang integer; `games.melo_brand_id`, `games.melo_game_code` | — |
| Model & enum        | 9 model (+ `MeloProduct`), 4 enum              | —                         |
| Factory & seeder    | Admin contoh, 3 kategori, 17 game (+ ikon), produk top up, 7 akun game | — |
| Akun game           | `game_accounts` (tabel terpisah, tanpa reservasi, tanpa kredensial, gambar maks 5 WebP) | `AdminCatalogResourceTest`, `StorefrontTest` |
| Scheduler           | `melo:check-balance` tiap 10 menit            | `MeloBalanceDashboardTest` |
| Kredensial          | tidak disimpan di sistem (dihapus 2026-11-22) | `test_game_accounts_table_has_no_credentials_column` |
| Admin panel         | 6 resource + widget, hanya admin              | `AdminPanelAccessTest`, `AdminCatalogResourceTest`, `AdminCustomerResourceTest` |
| Dashboard saldo Melo | Widget saldo + pembayaran, peringatan saldo menipis lewat lonceng admin (**belum ke API asli**) | `MeloBalanceDashboardTest` |
| Sync katalog Melo   | Tautan game↔brand, sync ke `melo_products`, pilih item jadi produk (tab "Item Melo" atau Select SKU di form Produk), atur harga massal (**belum ke API asli**) | `MeloCatalogServiceTest`, `AdminCatalogResourceTest` |
| Storefront dasar    | Home, halaman game, invoice                   | `StorefrontTest`          |
| Webhook             | Verifikasi signature Duitku & Melo            | `WebhookSignatureTest`    |
| Pemrosesan callback | Job `HandlePaymentCallback`, `SendTopupToMelo`, `HandleSupplierCallback` (test lulus 2026-11-27, lihat §6; konkurensi lock di MySQL belum diuji) | `PaymentCallbackTest`, `SupplierCallbackTest` |
| Service integrasi   | Duitku & Melo (**belum ke API asli**)         | `DuitkuServiceTest`, `MeloServiceTest` |
| Cek nickname pelanggan | Tombol Beli Sekarang membuka dialog yang cek ID ke Melo dulu (`POST /game/{game}/nickname`, throttle 10/menit, cache 1 hari, purge tiap jam). Sandbox tidak memanggil Melo. **Belum ke API asli; build frontend gagal, lihat M19** | `NicknameCheckTest`, `PruneNicknameCacheTest` |
| Konfigurasi         | MySQL, Asia/Jakarta, locale `id`, key `.env`  | —                         |

Lokasi & detail:
- **Skema DB:** `database/migrations/2026_11_11_*`.
- **Model:** User, Category, Game, Product, GameAccount, Order, ApiLog, Setting (`app/Models`).
- **Enum:** OrderStatus, AccountStatus, UserRole (`app/Enums`).
- **Seeder:** `database/seeders/DatabaseSeeder.php`.
- **Akun game:** tidak ada service stok (`AccountStockService` dihapus 2026-11-22). Dijual manual.
- **Scheduler:** `routes/console.php`.
- **Kredensial:** tidak disimpan di sistem (keputusan PO 2026-11-22).
- **Admin panel** (`app/Filament`): resource Kategori, Game, Produk (harga jual bisa diedit inline, aksi massal "Atur harga jual"), Akun Game (status diatur manual oleh admin; `sold_at` terisi otomatis saat Terjual; tanpa kolom kredensial), Pesanan (tanpa halaman create), dan Pelanggan (hanya lihat, tanpa admin), plus widget dashboard. Form Kategori/Game/Produk punya slug otomatis + unik, validasi regex, zone wajib bila `requires_zone_id`, supplier + SKU wajib (semua produk top up).
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
- Nama metode bayar (nama Duitku, mis. `SHOPEEPAY QRIS`) belum disimpan di order. Invoice dan detail admin kini menampilkan kode + jenis (lihat riwayat 2026-11-26); tampilan nomor VA di invoice.
- Cek status supplier terjadwal / reconcile `transactionStatus()`; penandaan order `pending` kedaluwarsa menjadi `expired`.
- Sinkron harga Melo, import CSV akun game.
- Notifikasi.
- Desain storefront final.
- Deployment.

## 4. Masalah Ditemukan

| #   | Masalah                                              | Dampak                         | Status        |
| --- | ---------------------------------------------------- | ------------------------------ | ------------- |
| M1  | Invoice bisa dibuka siapa saja yang tahu nomornya    | Kebocoran data pesanan (kredensial sudah tidak disimpan sejak 2026-11-22) | Terbuka (berkurang) |
| M2  | ~~`releaseExpired()` tidak cek status order~~ | — | Selesai: fitur reservasi dihapus 2026-11-22 |
| M3  | Form edit pesanan admin bisa edit semua kolom        | Manipulasi data transaksi      | Terbuka       |
| M4  | Kolom/role reseller masih ada (out of scope)         | Kebingungan scope              | ✅ 2026-11-22 |
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
| M16 | `php artisan test` memakai DB dev jika config di-cache | Data `topup_game` terhapus | Terbuka |
| M17 | `CustomerAuthTest::test_customer_can_login_and_logout` gagal, di luar M15 (`MeloServiceTest::test_topup_...` sudah diperbaiki 2026-11-27) | Login pelanggan tidak terverifikasi | Terbuka |
| M18 | Format rupiah (titik ribuan) belum ada di form harga Produk (`base_price`, `price`, `strike_price`) dan Order (`price`) | Admin input harga tanpa format, rawan salah baca nominal | Terbuka |
| M19 | `resources/js/pages/Game.vue`: string literal di template (`' · '` dan `'Belum pilih item'`) terpecah baris | `npm run build` gagal, bundle baru tidak bisa dibuat | Terbuka |

Detail:
- **M1:** `StorefrontController::invoice`. Kredensial akun tidak ada lagi di sistem (2026-11-22). Yang masih terbuka: nomor SN dan data pesanan.
- **M2:** ~~`AccountStockService::releaseExpired`~~. Dihapus 2026-11-22 bersama fitur reservasi.
- **M3:** `app/Filament/Resources/Orders/Schemas/OrderForm.php`. Kolom total, status, dan payment ikut bisa diedit. Mengubah `price` atau target pesanan yang sudah `paid` dapat membuat body retry ke Melo berbeda (`max_price`, `customer_target`), lalu Melo membalas 409 untuk `buyer_trx_id` yang sama.
- **M4:** Selesai 2026-11-22. Migration `2026_11_22_120007` menghapus `users.balance` dan `products.reseller_price` (user `reseller` jadi `member`); `UserRole::Reseller`, `Product::priceFor()`, `UserFactory::reseller()`, dan kolom/field reseller di Filament dihapus.
- **M5:** Selesai. `CLAUDE.md` root meng-import `@docs/AGENTS.md`, dan status file itu menjadi "berlaku".
- **M6:** `boost.json` (`"cloud": true`) dan blok Boost di `CLAUDE.md`/`AGENTS.md` root. Bagian proyek di `CLAUDE.md` sudah menegaskan deploy = VPS.
- **M7:** Selesai. `TECH_STACK.md` diperbarui menjadi ringkasan yang merujuk ke `docs/` (1 bulan, tanpa reseller, ada login pelanggan, Melo).
- **M8:** `database/database.sqlite`, `resources/views/welcome.blade.php`.
- **M9:** `MeloService::topup` mengirim `customer_target` dan `customer_target_zone` terpisah. Format per SKU game belum dicek dengan SKU nyata.
- **M10:** `checkNickname()`, `nicknameGames()`, `nicknamePlan()`, `checkMobileLegendsPurchaseLimit()`, Smart, bulk, dan 2FA. `nicknamePlan()` kini dipakai widget dashboard admin `MeloNicknamePlanOverview` (permintaan user 2026-11-27, persetujuan PO menunggu, lihat §5 no. 14). `nicknameGames()` kini dipakai dropdown kode game di form Game (permintaan user 2026-11-27, lihat riwayat). Method lain belum dipakai alur mana pun; memakainya butuh persetujuan PO (cek nickname di luar scope, dan memakai kuota/biaya). `checkNickname()` kini dipakai lewat `cachedNickname()` oleh endpoint cek nickname pelanggan (2026-11-28, persetujuan PO 2026-11-27).
- **M11:** Default factory kini `melo`. Data `digiflazz` di database lama perlu diubah manual.
- **M13:** (selesai 2026-11-16, commit `5f7e2f3`) inquiry, callback, dan `transactionStatus()` kini HMAC-SHA256 dengan body JSON, sesuai test dan `API_INTEGRATION.md` §3. Dokumentasi resmi Duitku (docs.duitku.com) juga menyatakan MD5/SHA256 biasa usang. `php artisan test --compact --filter='Duitku|WebhookSignature'`: 15 lulus. Masih perlu verifikasi ke sandbox Duitku asli. Riwayat temuan: [`CORE_SYSTEM_REVIEW.md`](CORE_SYSTEM_REVIEW.md) §2.
- **M12:** `config/services.php`: `'sandbox' => env('MELO_SANDBOX')` tanpa default. Jika variabel tidak ada di `.env`, `MeloService::withSandboxMode()` mengirim `sandbox_mode = false` sehingga order menjadi transaksi nyata. Usulan: default `true`. Dicatat saja, belum diperbaiki.
- **M14:** Ditemukan saat uji manual di browser (2026-11-21). `GameForm` mengisi `games.thumbnail` dengan URL CDN Melo (`https://cdn.melostore.id/...`), lalu `StorefrontController::gameSummary()` (`thumbnailUrl`) membungkusnya dengan `Storage::disk('public')->url()` sehingga menjadi `/storage/https://...`. Gambar rusak di beranda dan halaman game. Selesai 2026-11-21: field thumbnail diganti upload file (lihat Riwayat), isi otomatis thumbnail dari Melo dihapus. Game yang sudah tersimpan dengan URL CDN tetap rusak sampai admin meng-upload ulang thumbnail-nya.
- **M15:** Ditemukan 2026-11-21, gagal juga tanpa perubahan hari itu (130 test, 113 lulus). (a) 11 test storefront/`ExampleTest` error 500 `StrayRequestException` ke `http://[::1]:5173/__inertia_ssr`: saat `composer run dev` jalan, `public/hot` ada sehingga Inertia mencoba SSR ke Vite. Terkonfirmasi 2026-11-21: dengan `INERTIA_SSR_ENABLED=false php artisan test --compact` semua test storefront lulus kecuali (e). (b) Halaman admin (`AdminCatalogResourceTest`, `AdminCustomerResourceTest`, `AdminPanelAccessTest`, `MeloBalanceDashboardTest`) mengembalikan 404, bukan 200/403; penyebab belum diselidiki. (c) ~~`test_admin_marking_account_sold_...` gagal karena `credentials` wajib di form edit~~ (hilang 2026-11-22, kolom dihapus). (d) `MeloBalanceDashboardTest::test_command_notifies_admins_once_per_level_and_escalates` flaky (2 dari 3 run lulus): notifikasi `menipis` dan `hampir habis` kemungkinan dibuat di detik yang sama sehingga urutan `notifications` tidak pasti. (e) ~~`StorefrontTest::test_invoice_shows_own_qris_image_only_while_pending` mencari data URI di HTML~~ diperbaiki 2026-11-21 (QR QRIS di invoice).
- **M16:** Ditemukan 2026-11-21. Jika `bootstrap/cache/config.php` ada (`php artisan optimize`/`config:cache`), env dari `phpunit.xml` diabaikan sehingga test berjalan di `topup_game` dan `RefreshDatabase` mengosongkannya (terjadi 2026-11-21, data dev hilang). Jalankan `php artisan optimize:clear` sebelum test. Usulan pengaman (belum dibuat): cek nama database di `TestCase::setUp()`.
- **M17:** Ditemukan 2026-11-27 saat suite penuh. Belum diselidiki dan tidak diubah di task gambar stok akun. Jumlah kegagalan sama dengan 2026-11-26, jadi kemungkinan sudah ada sebelumnya.
- **M18:** `app/Filament/Resources/Products/Schemas/ProductForm.php` dan `app/Filament/Resources/Orders/Schemas/OrderForm.php` memakai `->integer()->prefix('Rp')` tanpa mask. Pola yang sudah dipakai ada di `GameAccountForm.php` (`->mask(RawJs ... $money($input, ',', '.', 0))` dan `->stripCharacters('.')`). Belum diubah karena di luar permintaan 2026-11-27.
- **M19:** Ditemukan 2026-11-28 saat menambah dialog cek nickname. Baris pembuka teks di template `Game.vue` (sekitar baris 356–358, sebelum "Belum pilih item") berisi string yang terpecah baris, jadi Vite gagal dengan `Unterminated string constant`. Teks ini ikut berubah di file saat sesi (reformat di luar agent), dan tidak diperbaiki oleh agent karena file itu diubah di luar sesi. Perbaikan: gabungkan kembali kedua string itu. Lalu `npm run build` dan cek halaman game di browser.

## 5. Keputusan yang Menunggu (PO/Klien)

Semua diputuskan oleh PO. Kolom "Dibutuhkan sebelum" diturunkan dari `IMPLEMENTATION_PLAN.md`; jika lewat, tahap terkait tertahan.

| #   | Keputusan                                                       | Dibutuhkan sebelum   |
| --- | --------------------------------------------------------------- | -------------------- |
| 1   | ~~Provider payment final~~ → Duitku (2026-11-14)                | ✅ selesai           |
| 2   | Penanganan supplier gagal setelah dibayar & apakah refund masuk | H23 (Tahap 5)        |
| 3   | Aturan akses invoice (M1)                       | H23 (Tahap 5)        |
| 4   | Aksi admin yang diizinkan pada pesanan (M3)                     | H5 (Tahap 2)         |
| 5   | ~~Nasib kolom reseller (M4)~~ → dihapus lewat migration (2026-11-22) | ✅ selesai |
| 6   | Notifikasi: email saja, atau + WhatsApp gateway                 | H23 (Tahap 5)        |
| 7   | ~~Kontak wajib untuk guest~~ → WhatsApp wajib, email opsional (2026-11-21) | ✅ selesai |
| 8   | ~~Penanggung biaya payment (fee)~~ → pembeli (2026-11-21); aturan markup harga masih terbuka | H11 (Tahap 3)        |
| 9   | Masa berlaku invoice/reservasi (rekomendasi 15 menit)           | H11 (Tahap 3)        |
| 10  | Verifikasi email pelanggan, cakupan "kelola pelanggan"          | H5 (Tahap 2)         |
| 11  | Coolify vs Laravel Forge; domain & VPS                          | H27 (Tahap 6, E6)    |
| 12  | Konfirmasi 1 bulan = 30 hari (H1–H30)                           | H1                   |
| 13  | Tanggal mulai H1                                                | Sebelum mulai        |
| 14  | Widget kuota cek nickname di dashboard (`nicknamePlan()`, M10, PROJECT_SPEC §10 [FINAL]). Dibuat atas permintaan user 2026-11-27, disetujui PO untuk pelanggan 2026-11-27, bagian dari cek nickname (`docs/PERSETUJUAN_CEK_NICKNAME.md`) | ✅ selesai |
| 15  | Pemetaan game ke `game_code` Melo: **opsi A dipakai** (kolom `games.melo_game_code`, dipilih dari dropdown `GET /check-nickname/games` di form Game, atas permintaan user 2026-11-27). Persetujuan PO untuk kolom baru belum tercatat. Opsi B (`games.slug`) tidak dipakai: spesifikasi menyebut game_code terpisah dari slug brand. `PERSETUJUAN_CEK_NICKNAME.md` §9 no. 1 | Endpoint cek nickname pelanggan |
| 16  | Batas pemakaian cek nickname per IP (usulan 10/menit), §9 no. 2. Diterapkan 2026-11-28 sesuai usulan (`throttle:10,1`) | Konfirmasi PO (endpoint sudah dibuat) |
| 17  | Cek nickname opsional atau wajib sebelum checkout (usulan: opsional), §9 no. 3. Diterapkan 2026-11-28 sesuai usulan: hasil cek hanya ditampilkan, "Lanjut bayar" tetap ada | Konfirmasi PO (endpoint sudah dibuat) |
| 18  | Konfirmasi "tidak ditemukan" tidak di-cache dengan premis yang dikoreksi (§9 no. 4); gerbang sandbox (§9 no. 5). Diterapkan: 404 tidak di-cache (jawaban PO), gerbang sandbox aktif (usulan) | Konfirmasi PO (endpoint sudah dibuat) |

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

### 2026-11-21 — Trust proxy (ngrok/Cloudflare)

- `bootstrap/app.php`: `$middleware->trustProxies(at: '*')`. Tanpa ini, di balik proxy HTTPS (ngrok, nanti Cloudflare) URL asset/route dibuat `http://` dan diblokir browser (mixed content), halaman kosong. Catatan production: `'*'` membuat `X-Forwarded-For` bisa dipalsukan jika server bisa diakses langsung tanpa Cloudflare; sebaiknya batasi ke IP Cloudflare saat deploy.
- Suite penuh: 149 test, 141 lulus, 8 gagal; hasil sama tanpa perubahan ini (semua M15 b/c/e).

### 2026-11-21 — Test pakai SQLite in-memory

- Keputusan PO: `phpunit.xml` kini `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` (sebelumnya MySQL `topup_game_test`). Docs yang menyebut `topup_game_test` sebagai aturan ikut diperbarui (`CLAUDE.md`, `AGENTS.md` §7, `AI_WORKFLOW.md`, `SYSTEM_ARCHITECTURE.md`, `TECH_STACK.md`); petunjuk setup lama yang masih membuat `topup_game_test` dibiarkan.
- M15(d) diperbaiki: di SQLite test notifikasi Melo selalu gagal (dua notifikasi di detik yang sama, urutan tidak pasti). `MeloBalanceDashboardTest` kini `travel(1)->seconds()` sebelum level kedua.
- Catatan: SQLite tidak menguji perilaku khusus MySQL (`lockForUpdate`, tipe kolom, `->after()`).
- Suite penuh: 149 test, 141 lulus, 8 gagal (semua M15 b/c/e, sama seperti di MySQL).

### 2026-11-21 — QR QRIS di invoice

- `StorefrontController::invoice` menambah prop `order.payment.qrImage`: SVG data URI dari `payment_code` (`qrString` Duitku) lewat `chillerlan/php-qrcode`, hanya jika `DuitkuService::channelGroup(payment_method) === 'QRIS'` dan order `pending`. `Invoice.vue` menampilkan gambar QR (latar putih) menggantikan teks kode; metode lain (VA) tetap menampilkan kode + tombol salin.
- M15(e) diperbaiki: `test_invoice_shows_own_qris_image_only_while_pending` kini memakai `assertInertia`. Test baru `test_invoice_has_no_qr_image_for_virtual_account`.
- Suite penuh: 150 test, 143 lulus, 7 gagal (semua M15 b/c).

### 2026-11-22 — Katalog Melo dipilih dulu + hapus kolom reseller/tidak terpakai

- Keputusan PO: katalog Melo disimpan di tabel baru `melo_products` (pengecualian prinsip "tidak ada tabel baru"); admin memilih item sebelum jadi produk. Reseller dihapus seluruhnya (M4). Kolom `products.sort`, `image`, `description` dihapus (produk akun nanti dipisah).
- Migration baru: `2026_11_22_120006_create_melo_products_table.php`, `2026_11_22_120007_drop_reseller_and_unused_columns.php`.
- `MeloCatalogService::syncProducts()` kini mengisi `melo_products` dan hanya memperbarui produk yang sudah dipilih (return `catalog/updated/deactivated`). Method baru `addProducts()`. Relation manager `MeloProductsRelationManager` (tab "Item Melo" di Edit Game) dengan aksi massal "Jadikan produk" (produk nonaktif, harga 0).
- Storefront: urutan produk = `price`; deskripsi produk akun tidak tampil lagi (`productCard` tanpa `description`).
- Produk lama hasil sync sebelumnya (nonaktif, harga 0) tetap di `products`; hapus manual bila tidak dipakai.
- Suite penuh (`INERTIA_SSR_ENABLED=false`): 153 test, 146 lulus, 7 gagal (sama dengan sebelum perubahan, semua M15 b/c).

### 2026-11-22 — Status invoice realtime (polling)

- `Invoice.vue` memakai `usePoll` Inertia (tanpa websocket/paket baru): reload prop `order` tiap 5 detik, berhenti saat status final. Prop baru `order.status.isFinal` (dari `OrderStatus::isFinal()`). Test `test_invoice_marks_final_status_so_page_stops_polling`.
- Status hanya berubah jika callback Duitku/Melo sampai ke server (lokal butuh ngrok). Order `pending` yang lewat `expired_at` tidak otomatis jadi `expired`, jadi tab invoice itu terus polling selama dibuka.

### 2026-11-22 — Pilih SKU Melo di form Produk

- Instruksi PO: admin tetap mengisi produk lewat form, tetapi SKU dipilih dari item Melo. `melo_products` tetap jadi penampung katalog (diisi "Sync item dari Melo"); tab "Item Melo" + "Jadikan produk" tetap ada.
- `ProductForm`: `supplier_sku` jadi Select dari `melo_products` milik game terpilih (label nama · grup · modal · status [SKU]). Memilih SKU mengisi `base_price` dan `group`, serta `name` jika masih kosong. Ganti game mengosongkan SKU. SKU di luar item game itu ditolak (`in`); SKU produk yang sedang diedit selalu ikut di pilihan, jadi produk yang SKU-nya hilang dari katalog tetap bisa disimpan. Game tanpa brand/belum di-sync tidak punya pilihan SKU (produk topup tidak bisa dibuat sampai di-sync). Tidak ada migration.
- Label "★ Harga terbaik" di Select SKU: SKU `active` termurah di antara SKU dengan nama sama (tanpa beda huruf besar/spasi ujung), hanya jika nama itu punya >1 SKU; harga sama = semua ditandai. Tidak memakai katalog Smart Melo (`smart-pricelists`).
- Test baru di `AdminCatalogResourceTest`: prefill dari SKU, label harga terbaik, SKU game lain ditolak, edit produk dengan SKU yang sudah hilang. Test create/unique disesuaikan (membuat `MeloProduct` dulu).
- Suite penuh (`INERTIA_SSR_ENABLED=false`): 158 test, 150 lulus, 8 gagal: 7 M15 b/c, ditambah `MeloServiceTest::test_topup_sends_authenticated_request_...` yang gagal karena perubahan `max_price` di `MeloService::topup()` (belum di-commit, di luar tugas ini; body request di test belum memuat `max_price`).

### 2026-11-22 — Akun game dipisah dari produk

- Keputusan PO: semua produk adalah top up. Akun game dipindah ke tabel `game_accounts` yang berdiri sendiri (`game_id`, `name`, `price`, `public_info`, `status`, `sold_at`), tanpa kredensial dan tanpa relasi ke order. Atas persetujuan PO, `AccountStatus::Reserved`, stok/reservasi, dan kredensial dihapus.
- Migration baru: `2026_11_22_204917_separate_game_accounts_from_products.php`. Men-drop `game_accounts` lama, menghapus baris produk dan order bertipe `account`, lalu men-drop `products.type` dan `orders.product_type`. Data lama dibuang atas persetujuan PO. `down()` best effort (tidak mengembalikan data).
- Dihapus: `ProductType`, `AccountStockService`, `OutOfStockException`, command dan jadwal `accounts:release-expired`, `AccountStockServiceTest`, kartu kredensial di invoice, filter/kolom tipe di admin, dan logika akun di checkout dan callback.
- Storefront: halaman game memuat `products` (datar) dan `accounts` (hanya `available`, field `name`/`price`/`publicInfo`). Akun tampil sebagai daftar baca-saja dengan teks "hubungi admin". Tidak ada tombol beli.
- Seeder: 7 akun contoh (5 MLBB, 2 FF) langsung di `game_accounts`.
- Test: `AccountStockServiceTest` dihapus. Test checkout/callback akun dihapus. Test halaman game, form akun, dan "kolom kredensial tidak ada" ditulis ulang. Suite penuh (`INERTIA_SSR_ENABLED=false`): 149 test, 142 lulus, 7 gagal (M15 b/c dan `MeloServiceTest` `max_price`, lihat entri sebelumnya).
- Dokumen direvisi: `CLAUDE.md`, `PROJECT_SPEC.md` (§4, §13), `DATABASE_DESIGN.md`, `BUSINESS_FLOW.md`, `API_INTEGRATION.md` §3.4, `SYSTEM_ARCHITECTURE.md`, `SECURITY_RULES.md` §10, `CORE_SYSTEM_REVIEW.md`.
- **Belum direvisi:** `README.md`, `TECH_STACK.md`, `TASK_DIVISION.md`, `IMPLEMENTATION_PLAN.md`, `FRONTEND_PLAN.md`, `TO_DO.md`, dan `docs/AGENTS.md`. Masih menyebut stok akun atau produk akun. `AGENTS.md` butuh persetujuan PO untuk diubah.
- **Belum diverifikasi:** migration belum dijalankan di MySQL (server dev di port 8889 tidak berjalan). Perlu `php artisan migrate` di DB lokal dan pengecekan halaman game serta invoice di browser (`npm run build` sudah lulus).

### 2026-11-22 — Brand Zeth Market dan logo transparan

- Nama toko diambil dari `APP_NAME` di `.env` (diubah PO ke "Zeth Market"). Judul halaman dan footer ikut berubah. `VITE_APP_NAME` juga harus sama bila diisi, lalu `npm run build`.
- Logo tanpa latar: `public/images/logo.png` (512 px, PNG alpha). Dibuat dari `logo.webp` lewat Vision foreground subject lifting (macOS). File sumber di root repo tidak di-track.
- `HandleInertiaRequests` membagikan `logoUrl = asset('images/logo.png')` (sebelumnya `storage/app/public/logo/logo.webp`, yang di-ignore git). `BrandLogo.vue` menampilkan logo sebagai gambar dengan tinggi tetap; teks nama disembunyikan saat logo tampil, subtitle tetap.
- Test: `StorefrontTest::test_storefront_shares_the_brand_logo`. Suite penuh: 152 test, 145 lulus, 7 gagal (sama dengan sebelumnya: M15 dan `MeloServiceTest` `max_price`).
- Belum: favicon masih dari logo versi dengan latar gelap (belum diganti versi transparan). Tampilan header belum dicek di browser.

### 2026-11-23 — Mempercepat "Jadikan produk" dengan harga otomatis

- `MeloCatalogService::addProducts()`: saat aksi massal "Jadikan produk" di tab Item Melo, produk kini dibuat dengan `price = base_price` (harga modal dari Melo), bukan `price = 0`. Admin tinggal toggle `is_active` di table Produk (via bulk action) tanpa perlu edit satu-satu untuk isi harga.
- Alasan: mempercepat alur master data — produk siap dengan harga, tidak perlu edit manual lagi.
- File berubah: `app/Services/Supplier/MeloCatalogService.php` (1 baris).
- Test berubah: 2 test yang assert `price = 0` diubah assert `price = base_price` (`AdminCatalogResourceTest`, `MeloCatalogServiceTest`).
- Suite penuh (`INERTIA_SSR_ENABLED=false`): 152 test, 145 lulus, 7 gagal (M15 b/c dan `MeloServiceTest` `max_price`, sama dengan sebelumnya).

### 2026-11-26 — Warna & favicon panel admin mengikuti landing page

- Permintaan PO: warna panel admin disamakan dengan landing page, dan logo di header admin memakai favicon.
- `AdminPanelProvider`: `primary` (brand `#e8174a` di 600, highlight `#ff2bd6` di 400 dan 500) dan `gray` (text `#f4f6fb` di 50, muted `#9aa3b8` di 400, surface-raised `#21103a` di 800, surface `#140a22` di 900, ground `#09050f` di 950) diambil dari `@theme` di `resources/css/app.css`. Shade lain dihitung manual. Success, warning, dan danger tetap bawaan Filament. Tema default gelap (`ThemeMode::Dark`), admin tetap bisa mengganti ke terang.
- Favicon panel: `favicon(fn () => asset('favicon-32x32.png'))`. Dibungkus closure agar URL dibuat saat render, bukan saat boot (`asset()` men-cache skema URL dan bisa mengunci `http://` sebelum trusted proxy aktif). Sebelumnya menunjuk `storage/app/public/logo/logo.webp` yang tidak ada (logo sudah pindah ke `public/images/logo.png` pada 2026-11-22), sehingga tab tanpa ikon dan header memakai kotak "Z" merah `#e50914`.
- `resources/views/filament/brand-logo.blade.php`: gambar logo kini `favicon-32x32.png`; kotak fallback "Z" dihapus.
- Test: `AdminPanelAccessTest::test_admin_login_page_uses_the_logo_favicon` (kini memeriksa link favicon dan `src` logo) dan `test_admin_login_page_uses_the_landing_colors_in_dark_mode` (baru). Suite penuh: 159 test, 151 lulus, 8 gagal (sebelumnya 158 / 149 / 9; yang diperbaiki hanya test favicon, sisanya sama).
- Belum dicek di browser. Verifikasi baru lewat HTML yang dirender dan test. Favicon 32 px di header bisa terlihat sedikit kabur di layar retina; alternatifnya `apple-touch-icon.png` (180 px, berisi tulisan "ZETH MARKET").

### 2026-11-26 — Tampilan detail pesanan di panel admin

- Permintaan PO: halaman detail pesanan (`ViewOrder`) dibuat lebih rapi. Hanya presentasi: `OrderInfolist` (read-only) kini dikelompokkan ke bagian Ringkasan, Rincian harga, Produk & tujuan, Pembeli, Pembayaran, dan Supplier (Melo), masing-masing dengan ikon dan grid responsif.
- Total tampil tebal dan berwarna primary. ID, invoice, referensi, kode, dan URL pembayaran bisa disalin (`copyable`) dan memakai font mono. Label yang masih bahasa Inggris dialihkan ke Indonesia. Field yang kosong menampilkan `-`.
- Tidak ada perubahan data, route, atau aksi. Kolom "Nama produk saat pesan" (snapshot) dan "Produk saat ini" (relasi) tetap ditampilkan keduanya.
- Test baru: `AdminOrderViewTest` (2 test: bagian dan nilai tampil, placeholder untuk field kosong). Suite penuh: 161 test, 153 lulus, 8 gagal (sama dengan sebelumnya, semua M15 b/c dan `MeloServiceTest` `max_price`).
- Belum dicek di browser; verifikasi lewat render Livewire dan test. Tombol salin dan lebar URL pembayaran yang panjang belum diuji secara visual.

### 2026-11-26 — Jenis dan QR code metode bayar

- Permintaan PO: metode bayar tampil bersama jenisnya (QRIS, Virtual Account, E-Wallet, atau Lainnya). Untuk order QRIS, kode pembayaran di detail admin tampil sebagai QR code.
- Jenis diambil dari `DuitkuService::channelGroup()` (kode, bukan nama Duitku), tanpa kolom baru dan tanpa migration. Data yang tersimpan (`orders.payment_method`, `orders.payment_code`) tidak diubah, dan request ke Duitku tidak berubah.
- Invoice: prop `order.paymentMethod` kini `SP (QRIS)` (kode + jenis). `DuitkuService::qrisImage()` dipindah dari `StorefrontController` agar invoice dan detail admin memakai satu fungsi.
- Detail admin (`OrderInfolist`): entri baru "Jenis" (badge). Untuk order QRIS, "Kode pembayaran" diganti "QR code"; metode lain tetap teks kode.
- Dari log API (`api_logs`, respons `getpaymentmethod` sandbox 2026-11-26): daftar Duitku memuat SP (SHOPEEPAY QRIS) dan NQ (NOBU QRIS) di grup QRIS, serta FT, VC, DN, IR, dan lainnya yang masuk "Lainnya". Order uji yang ada semuanya SP.
- Belum: nama Duitku per metode belum disimpan di order (butuh kolom baru, perlu persetujuan PO). Pengelompokan FT (Retail), VC (Kartu kredit), DN (PayLater), IR (Indomaret) masih "Lainnya", dan perlu keputusan PO.
- Test: `AdminOrderViewTest` (+2: QRIS tampil QR tanpa kode mentah; non-QRIS tampil teks kode dan jenis), `StorefrontTest::test_invoice_shows_payment_method_code_with_its_type` (+1). Suite penuh: 164 test, 156 lulus, 8 gagal (sama dengan sebelumnya).

### 2026-11-27 — Gambar stok akun (maks 5, WebP)

- Permintaan user: form Stok Akun (`GameAccountForm`) punya upload gambar, maksimal 5 file per akun.
- Migration baru `2026_11_27_053142_add_images_to_game_accounts_table.php`: kolom `game_accounts.images` (JSON, nullable) berisi path di disk `public` (`game-accounts/images/*.webp`). Tanpa tabel baru.
- Setiap file diubah ke WebP kualitas 80 lewat GD (`imagewebp`, tanpa package baru) di `GameAccountForm::storeAsWebp()`. Batas 2 MB per file, sama dengan thumbnail game. Batas ini mengikuti `upload_max_filesize` PHP (2M di CLI lokal). Naikkan batasnya hanya jika `upload_max_filesize` di server juga dinaikkan.
- Model `GameAccount`: `images` di fillable dan cast `array`.
- Belum: gambar belum tampil di storefront (halaman game hanya nama, harga, dan info publik). File yang dilepas dari form atau akun yang dihapus tetap ada di disk. Resize dan koreksi rotasi EXIF tidak dikerjakan (lihat komentar `shortcut` di `GameAccountForm`).
- Bug yang ditemukan lewat test sebelum rilis: PNG berbentuk palette (mode indeks, umum pada screenshot terkompresi) membuat `imagewebp()` gagal dan request error 500. Diperbaiki dengan `imagepalettetotruecolor()` dan penolakan hasil encode kosong.
- Test: `AdminCatalogResourceTest` (+3: gambar tersimpan sebagai WebP di disk `public`; maks 5 gambar; PNG palette tersimpan sebagai WebP). Suite penuh: 167 test, 159 lulus, 8 gagal. Enam kegagalan adalah 404 halaman admin (M15 b), dua lainnya tercatat sebagai M17.

### 2026-11-27 — Kuota cek nickname di dashboard

- Permintaan user: tampilkan kuota cek nickname (`GET /check-nickname/plan`) di dashboard admin. Respons berisi batas gratis per hari (`free_daily_limit`), batas gratis per menit (`free_minute_limit`), tarif berbayar (`paid_price_mc`), batas berbayar per menit (`paid_minute_limit`), dan jam reset (`resets_at`).
- Widget baru `MeloNicknamePlanOverview` (`app/Filament/Widgets/`) memakai `MeloService::nicknamePlan()` yang sudah ada. Tanpa migration, tanpa tabel, tanpa service baru. Cache 10 menit (`melo.nickname_plan`), gagal tidak di-cache, polling dimatikan. Bila Melo gagal atau respons tidak lengkap, widget menampilkan "Tidak dapat dimuat".
- **Keputusan terbuka:** fitur cek nickname di luar scope (PROJECT_SPEC §10 [FINAL]). Persetujuan PO belum tertulis, lihat §5 no. 14 dan `docs/PERSETUJUAN_CEK_NICKNAME.md`. Widget hanya membaca kuota, tidak memanggil `checkNickname()` dan tidak ada pencarian nickname untuk pelanggan.
- **Belum:** respons plan hanya berisi batas, bukan jumlah yang sudah dipakai hari ini. Sisa kuota per hari hanya ada di header `X-Nickname-RateLimit-Remaining` pada respons `POST /check-nickname` (belum dipakai). Satuan "MC" dan arti `*_minute_limit` belum dikonfirmasi di spesifikasi; widget menampilkannya sebagai "per menit" dan "MC".
- Respons divalidasi dengan `Validator` (4 batas harus integer, `resets_at` string). Nilai `null` atau bukan angka ditolak dan widget menampilkan "Tidak dapat dimuat", tidak error.
- Test: `MeloNicknamePlanOverviewTest` (+4: tampil dan di-cache; gagal dari Melo; respons tidak lengkap; batas bernilai null). Suite penuh: 171 test, 163 lulus, 8 gagal (8 kegagalan sama dengan sebelumnya: M15 b dan M17).

### 2026-11-27 — Susunan kartu dashboard admin

- Kartu sapaan bawaan Filament (`AccountWidget`, "Selamat Datang" + Keluar) tampil setengah lebar dan terjepit di antara kartu lebar penuh. Sort-nya sama dengan kartu saldo Melo, jadi posisinya tidak jelas.
- `App\Filament\Widgets\WelcomeWidget` (subclass `AccountWidget`): lebar penuh, `$sort = -4`, dan lazy (`$isLazyLoaded = true`) seperti widget lain. `AccountWidget` bawaan eager (`$isLazy = false`), sehingga di browser kartu ini tampil paling bawah sendirian meski urutan di HTML awal sudah benar. Registrasi manual `->widgets([AccountWidget::class])` di `AdminPanelProvider` dihapus agar tidak dobel dengan auto-discovery.
- Urutan akhir: sapaan (penuh), saldo Melo (penuh), kuota cek nickname (penuh), lalu Pembayaran dan Toko berdampingan.
- Test: `WelcomeWidgetTest` (+3: lebar penuh; sapaan tampil untuk admin; urutan widget di dashboard). Suite penuh: 174 test, 166 lulus, 8 gagal (sama dengan sebelumnya).

### 2026-11-27 — Cek nickname pelanggan: keputusan PO dan cache

- Keputusan PO (`docs/PERSETUJUAN_CEK_NICKNAME.md` §8): disetujui untuk pelanggan; hasil 200 di-cache 1 hari; hasil 404 tidak di-cache; tidak ada batas biaya (rekap biaya di dokumen yang sama).
- Koreksi premis PO: "tidak ditemukan" tidak gratis. Spesifikasi Melo menulis hasil tidak ditemukan memakai jatah, lalu 1 MC per cek setelah jatah harian 40 habis (`PERSETUJUAN_CEK_NICKNAME.md` §3). Keputusan 404 perlu dikonfirmasi ulang PO (§9 no. 4).
- Kode: `MeloService::cachedNickname()` (key `melo.nickname.*`, TTL 86.400 detik, hanya 200, tanpa panggilan Melo saat `MELO_SANDBOX=true`). `MeloService::logOutgoing()` menerima `redact:`, sehingga `data.username` dan `data.region` tidak masuk `api_logs` untuk `/check-nickname`.
- Tidak ada migration, tabel, atau endpoint baru. Endpoint dan UI pelanggan belum dibuat, menunggu §5 no. 15–18.
- Dokumen yang diperbarui: `PROJECT_SPEC.md` (§5 P11, catatan 2 dan 4, §10), `CLAUDE.md`, `docs/AGENTS.md` §4, `TECH_STACK.md`, `BUSINESS_FLOW.md`, `FRONTEND_PLAN.md`, `DATABASE_DESIGN.md`, `API_INTEGRATION.md`, `SECURITY_RULES.md` §13.
- Test: `MeloServiceTest` (+7: cache 1 hari; 404, 402, 429, 502 tidak di-cache; sandbox tanpa panggilan; username tidak masuk `api_logs`). Suite penuh: 181 test, 173 lulus, 8 gagal. Delapan kegagalan sama dengan sebelumnya (admin 404 M15 b, `CustomerAuthTest` dan `MeloServiceTest::test_topup_...` M17).
- **Belum:** baris cache kedaluwarsa belum dihapus terjadwal (`SECURITY_RULES.md` §13). Sebelum endpoint dibuka, retensi 24 jam belum benar secara fisik.

### 2026-11-27 — Format rupiah harga akun game

- Form akun game (`GameAccountForm`, dipakai create dan edit) memakai mask `$money($input, ',', '.', 0)` pada field `price`, plus `stripCharacters('.')`. Harga tampil `350.000` saat diketik dan tersimpan sebagai `350000`.
- Validasi `integer` tetap berjalan pada nilai yang sudah dibersihkan titik. Input integer biasa (`350000`) juga tetap diterima.
- Test: `AdminCatalogResourceTest` (+2: input `350.000` tersimpan sebagai 350000; ekspresi mask ter-render di halaman create). Suite penuh: 183 test, 175 lulus, 8 gagal (sama dengan sebelumnya).
- **Belum diuji di browser.** Mask berjalan di sisi klien dengan `$money` dari Livewire (`formatMoney(input, delimiter, thousands, precision)`). Test hanya memeriksa sisi server dan ekspresi yang ter-render.
- Form Produk dan Order belum diubah (M18).

### 2026-11-27 — Idempotensi callback (SECURITY_RULES §7)

- Refund Melo ganda: alert hanya sekali per order. Pengecekan `supplier_status` dilakukan sebelum `fill()` di `HandleSupplierCallback`. Status order tetap, karena keputusan refund masih terbuka (§5 no. 2).
- Tidak ditambah `ShouldBeUnique` ke job callback. Row lock di transaksi sudah menjaga satu invoice diproses satu per satu. `ShouldBeUnique` per invoice bisa membuang callback yang datang sesudahnya saat callback sebelumnya masih di antrean (misalnya `pending` lalu `success`). `SendTopupToMelo` tetap memakai `ShouldBeUnique` per order.
- Test baru: `SupplierCallbackTest` (+5: success ganda menyimpan serial pertama; failed ganda hanya satu alert; refunded ganda satu alert per order; job tidak mengirim ulang untuk order `Processing`; retry dengan body identik). `PaymentCallbackTest` (+1: paid ganda untuk order expired tetap expired).
- `MeloServiceTest::test_topup_...` diperbaiki: ekspektasi body kini memuat `max_price`, sesuai kode `MeloService::topup()` yang sudah berubah tetapi belum di-commit. **Perilaku `max_price` belum diputuskan** (saldo ditahan penuh per order; `CORE_SYSTEM_REVIEW.md` O4). Test ini sekarang mengunci perilaku itu, jadi perlu ditinjau bila keputusan berubah.
- Suite penuh (`INERTIA_SSR_ENABLED=false`): 189 test, 182 lulus, 7 gagal. Tujuh kegagalan adalah M15 b dan `CustomerAuthTest` (M17).
- **Belum:** konkurensi lock di MySQL (SQLite mengabaikan `lockForUpdate`), perilaku Melo di sandbox, dan alert berulang untuk callback pada order yang sudah ditutup (noise, SECURITY_RULES §7).

### 2026-11-27 — Kode game Melo untuk cek nickname

- Permintaan user: tambahkan `game_code` supaya akun bisa dicek. Dipakai opsi A dari `PERSETUJUAN_CEK_NICKNAME.md` §9 no. 1. Spesifikasi (`GET /check-nickname/games`, `games[].game_code`) menyebut kode ini terpisah dari slug brand, jadi `games.slug` tidak dipakai.
- Migration baru `2026_11_27_212731_add_melo_game_code_to_games_table.php`: kolom `games.melo_game_code` (VARCHAR, nullable, tidak unik). Dipilih dari dropdown (`Select` di `GameForm`) berisi `games[].game_code` dan nama dari `GET /check-nickname/games` lewat `MeloCatalogService::nicknameGameOptions()` (cache 1 jam, galat tidak di-cache). Kode yang sudah tersimpan tetap ada di pilihan walau Melo tidak terjangkau, supaya tidak terhapus saat simpan. Kosong = game tidak dicek nickname. `Game` ikut `#[Fillable]`.
- `nicknameGames()` kini dipakai dropdown ini (M10). Endpointnya publik dan tanpa biaya, tetapi persetujuan PO tertulis untuk pemakaian ini belum ada.
- Tidak ada endpoint atau UI cek nickname. Keputusan §5 no. 16–18 belum ada.
- Test: `AdminCatalogResourceTest` (+2: kode dipilih dari daftar Melo tersimpan; kode tersimpan tetap ada saat Melo mati), `MeloCatalogServiceTest` (+1: urutan nama, galat tidak di-cache). Suite penuh: 192 test, 185 lulus, 7 gagal (sama dengan sebelumnya: M15 b ×6 dan M17).
- Belum: kode untuk game yang sudah ada belum diisi (seeder tidak diubah). Jalankan `php artisan migrate` di DB lokal.

### 2026-11-28 — Cek nickname pelanggan di dialog Beli Sekarang

- Permintaan user: sebelum bayar, username pembeli dicek dulu. Setelah klik **Beli Sekarang**, dialog "Konfirmasi pesanan" muncul, mengecek ID ke Melo, lalu menampilkan nickname dan region bila ditemukan. Dialog juga memuat item, metode, dan total.
- Endpoint baru `POST /game/{game}/nickname` (`NicknameCheckController`, `NicknameCheckRequest`, route `nickname.check`) dengan throttle `10,1`. Memanggil `MeloService::cachedNickname()` (tanpa method baru). Respons hanya `username` dan `region`; data billing Melo tidak dikirim. Game tanpa `melo_game_code` → 404 tanpa panggilan Melo. Hasil tidak ditemukan, galat Melo, dan mode sandbox menghasilkan `username: null` (pesan "tidak dapat dipastikan"), bukan error.
- Keputusan yang diterapkan dari usulan di `PERSETUJUAN_CEK_NICKNAME.md` §9, belum dikonfirmasi PO (§5 no. 16–18): throttle 10/menit per IP; cek bersifat informasi, "Lanjut bayar" tetap ada walau cek gagal; hasil 404 tidak di-cache (sesuai jawaban PO).
- Retensi: command `melo:prune-nickname-cache` (jadwal tiap jam) menghapus baris cache `melo.nickname.*` yang kedaluwarsa. Prasyarat di `SECURITY_RULES.md` §13 sudah terpenuhi.
- `Game.vue`: `<dialog>` native dengan `showModal()` dan `closedby="any"`. Pemanggilan memakai `useHttp` Inertia (CSRF otomatis). Hasil cek sebelumnya dibatalkan (`cancel()`) bila pembeli mengecek ulang dengan ID lain. Tombol Batal memakai handler JS, bukan atribut `command`, karena Safari sebelum 26.2 tidak mendukungnya.
- Trait `ValidatesGameIdPattern` dipakai bersama `CheckoutRequest` dan `NicknameCheckRequest`. Logika regex tidak berubah.
- `StorefrontController::game()` menambah prop `nicknameUrl` (null bila game tidak punya kode cek nickname).
- Tanpa migration.
- Test: `NicknameCheckTest` (9), `PruneNicknameCacheTest` (2). Suite penuh (`INERTIA_SSR_ENABLED=false`): 203 test, 196 lulus, 7 gagal (M15 b ×6 dan M17, sama seperti sebelumnya).
- **Belum diverifikasi:** dialog belum dicek di browser. `npm run build` gagal karena M19, jadi bundle baru belum bisa dibuat. Sandbox Melo asli belum dites (di sandbox lokal hasil selalu "tidak dapat dipastikan"). Konfirmasi PO §5 no. 16–18 belum ada.

## 7. Pekerjaan Berikutnya

0. Jawab pertanyaan di [`CORE_SYSTEM_REVIEW.md`](CORE_SYSTEM_REVIEW.md) §5 (review core system 2026-11-16). Dokumen itu juga mencatat keputusan baru yang belum masuk `PROJECT_SPEC.md` §13: akun dijual manual via WhatsApp (K1), kolom uang jadi integer dengan pengecualian edit migration (K2), dan pengujian pembayaran tanpa tampilan (K3).
1. PO me-review dan merevisi dokumen `docs/`.
2. Putuskan keputusan §5 yang dibutuhkan sebelum Tahap 1–2 (no. 4, 5, 10, 12, 13).
2a. Cek nickname pelanggan: endpoint, dialog, dan purge sudah dibuat 2026-11-28 (lihat riwayat). Sisa: perbaiki M19 agar `npm run build` lulus, cek dialog di browser, uji sandbox Melo asli, dan konfirmasi PO §5 no. 16–18 (`PERSETUJUAN_CEK_NICKNAME.md` §9).
3. Daftar akun sandbox Duitku & Melo (risiko E1/E2), lalu verifikasi signature callback dan format body `transactionStatus` (lihat `API_INTEGRATION.md` §3.3–3.4). Di sandbox Melo, uji juga idempotensi: `buyer_trx_id` yang sama membalas 200 tanpa debit ganda, dan body berbeda membalas 409 (`API_INTEGRATION.md` §4).
4. Uji `MeloService` ke sandbox Melo, termasuk format target per SKU (M9).
5. Lanjutkan Tahap 2: batasi form pesanan (M3), import CSV akun game; lalu core system sesuai `CORE_SYSTEM_REVIEW.md` §4.
6. Semua developer: `php artisan migrate:fresh --seed` di DB lokal setelah menarik perubahan ini.
7. Jalankan `php artisan migrate` di DB lokal untuk migration 2026-11-22 (menghapus kolom reseller/`sort`/`image`/`description`).
