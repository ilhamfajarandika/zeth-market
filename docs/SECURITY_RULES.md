# SECURITY_RULES — Aturan Keamanan

> Setiap aturan diberi status: ✅ sudah diterapkan di kode · ⏳ wajib saat fitur terkait dibuat · ⚠️ masalah terbuka.

## 1. Password & Autentikasi

| Aturan                                            | Status             |
| ------------------------------------------------- | ------------------ |
| Password di-hash otomatis (cast `hashed`, bcrypt) | ✅                 |
| Login admin lewat Filament `/{ADMIN_PATH}/login`  | ✅                 |
| Login admin dibatasi 5 percobaan/menit (Filament) | ✅                 |
| Login/registrasi pelanggan, terpisah dari admin   | ⏳                 |
| Rate limit login & registrasi pelanggan           | ⏳                 |
| Session di-regenerate setelah login               | ⏳ (1)             |
| Akun admin contoh hanya untuk lokal               | ⚠️ saat deploy (2) |

1. Perilaku bawaan Laravel auth.
2. `admin@example.com / password` wajib diganti/dihapus di production.
- Cast `hashed` ada di `app/Models/User.php`.

## 2. Otorisasi & Role

| Role                 | Akses                                   | Status       |
| -------------------- | --------------------------------------- | ------------ |
| `admin`              | Panel admin (1)                         | ✅ dites (2) |
| `member` (pelanggan) | Storefront + riwayat order **miliknya** | ⏳           |
| guest                | Storefront, checkout, invoice           | ✅ / ⏳      |
| `reseller`           | **Out of scope**, jangan dipakai (3)    | ⚠️ ditinjau  |

1. `User::canAccessPanel()` → `role === Admin`.
2. `AdminPanelAccessTest`.
3. Nilainya masih ada di enum `UserRole`.

- Path panel admin diambil dari `ADMIN_PATH` (`config('app.admin_path')`). Jika diganti, `/admin` mengembalikan 404. ✅ dites (`test_default_admin_path_is_not_reachable_when_a_custom_path_is_configured`)
- Production **wajib** memakai `ADMIN_PATH` acak (bukan `admin`) dan tidak menuliskannya di dokumen, README, atau commit. Path rahasia hanya mengurangi bot/scanner; pengaman utama tetap role admin, rate limit, dan password kuat. ⚠️ saat deploy
- Riwayat transaksi wajib difilter `orders.user_id = auth()->id()`. ⏳
- Kolom `role` tidak boleh diisi dari input registrasi (mass assignment). Default `member`. ⏳

## 3. Validasi Input

- Semua input storefront divalidasi di server (Form Request). Validasi di Vue hanya untuk kenyamanan, bukan pengaman. ✅ checkout (`CheckoutRequest`)
- User ID / Zone ID divalidasi dengan `games.user_id_regex` / `zone_id_regex` jika diisi, plus batas panjang. ✅
- Produk yang dipilih harus `is_active` dan milik game yang sama. Semua produk adalah top up (keputusan 2026-11-22). ✅
- Email/WhatsApp divalidasi formatnya. ✅
- Metode bayar harus ada di daftar Duitku untuk nominal itu; harga, fee, dan total tidak diterima dari input. ✅
- Upload gambar produk hanya tipe gambar (`FileUpload::image()` di `ProductForm`). ✅
- Thumbnail game (`games.thumbnail`) di-upload dengan `FileUpload::image()` (WebP/JPEG/PNG, maks 2 MB) di `GameForm`. ✅
- Gambar stok akun (`game_accounts.images`) di-upload dengan `FileUpload::image()` (WebP/JPEG/PNG, maks 2 MB, maks 5 file) di `GameAccountForm`, lalu disimpan sebagai WebP. ✅
- Banner game (`games.banner`) masih berupa input teks path, belum upload. Jika diubah menjadi upload, wajib `FileUpload::image()`. ⚠️ ditinjau

## 4. Proteksi Endpoint

| Aturan                                        | Status         |
| --------------------------------------------- | -------------- |
| CSRF aktif, **kecuali** `webhook/*` (1)       | ✅             |
| Webhook di-throttle 120 request/menit         | ✅             |
| Rate limit form checkout                      | ⏳             |
| Anti-bot checkout (mis. Cloudflare Turnstile) | [TBD] (2)      |
| Whitelist IP callback provider                | [TBD] (3)      |
| `APP_DEBUG=false` di production               | ⚠️ saat deploy |

1. Diatur di `bootstrap/app.php`.
2. Termasuk dependency/layanan baru, butuh persetujuan.
3. Opsional, sebagai tambahan dari verifikasi signature.

## 5. Secret & Konfigurasi

- Semua secret di `.env` (tidak masuk git). `.env.example` hanya berisi nama variabel tanpa nilai. ✅
- Kode membaca secret lewat `config('services.*')`, bukan `env()` langsung di luar folder `config/`. ✅
- AI agent tidak membaca atau menampilkan isi `.env` (`AGENTS.md` §9). [FINAL]
- `MELO_SANDBOX` wajib `true` di semua lingkungan selain production. Di `config/services.php` variabel ini tidak punya default, jadi jika lupa diisi, sandbox **mati** dan order memotong saldo sungguhan. ⚠️ (M12)
- `APP_KEY` production dibuat sekali, dibackup aman, dan tidak pernah diganti (kunci enkripsi data sensitif). ⚠️ saat deploy
- Jangan menulis secret di dokumen, test, log, atau screenshot. [FINAL]

## 6. Verifikasi Callback

| Provider  | Header                 | Algoritma                             | Status   |
| --------- | ---------------------- | ------------------------------------- | -------- |
| Duitku    | field `signature`      | HMAC-SHA256(merchantCode + amount + merchantOrderId, API key) | ✅ dites |
| Melo      | `X-H2H-Signature`      | hex HMAC-SHA256(raw body, secret)     | ✅ dites |

- Signature dibandingkan dengan `hash_equals`. ✅
- Signature salah/kosong → Duitku 403, Melo 401; keduanya dicatat di `api_logs`. Melo juga ditolak jika webhook secret belum diisi. ✅
- Test: `WebhookSignatureTest` (Duitku valid/salah/tanpa signature/nominal diubah/merchant lain/API key kosong; Melo valid/salah/tanpa signature/secret kosong) dan `MeloServiceTest::test_webhook_signature_is_case_insensitive_hex_hmac_sha256`.
- Nominal callback dicocokkan dengan `orders.total`. ⏳

## 7. Idempotency

- Callback yang sama bisa datang lebih dari sekali. Order berstatus final (`OrderStatus::isFinal()`) tidak diproses ulang. ✅ Dites: paid ganda dari Duitku (order `pending` dan `expired`), dan success, failed, serta refunded ganda dari Melo. Status `Refunded` belum dipakai karena keputusan refund masih terbuka. ⏳
- Alert refund dari Melo hanya sekali per order. Status order tidak berubah. ✅ dites (`test_duplicate_refunded_webhook_alerts_admin_once_per_order`)
- ~~`markSold()` sudah idempotent.~~ Dihapus 2026-11-22 bersama fitur akun stok.
- Satu invoice hanya menghasilkan satu transaksi supplier (`buyer_trx_id = invoice`). Retry memakai `buyer_trx_id` dan body yang sama. ✅ dites di sisi kode (`test_retry_after_unknown_result_sends_identical_request`). Perilaku Melo di sandbox (200 untuk `buyer_trx_id` yang sudah ada, 409 untuk body berbeda) belum diverifikasi. ⏳
- Job pemrosesan callback unik per invoice lewat row lock: `lockForUpdate` dan cek status di dalam transaksi. ✅ `ShouldBeUnique` tidak dipakai untuk job callback, karena kunci per invoice bisa membuang callback yang datang sesudahnya (misalnya `pending` lalu `success`) saat callback sebelumnya masih di antrean. `SendTopupToMelo` memakai `ShouldBeUnique` per order. ✅ Lock belum teruji konkurensi, karena test memakai SQLite yang mengabaikan `lockForUpdate`. ⏳
- Alert berulang: paid ganda untuk order `expired` atau `failed`, dan callback dengan nominal tidak cocok, memicu alert admin lagi setiap kali. Status order tidak berubah. ⏳ Dibiarkan sebagai noise; bisa dideduplikasi bila diminta.

## 8. Transaksi Database

- Stok akun: tidak ada lagi (dihapus 2026-11-22). Akun game dijual manual, tanpa reservasi.
- Perubahan status order dari callback: transaksi + lock order. ⏳
- Tidak boleh ada panggilan HTTP eksternal di dalam transaksi yang memegang lock. [FINAL]

## 9. Proteksi Manipulasi Harga

- Harga, fee, dan total dihitung di server dari data produk. Nilai harga dari request diabaikan. ⏳
- Snapshot harga disimpan di order saat dibuat. ✅ (skema)
- Nominal yang dikirim ke payment gateway = `orders.total`. ✅ (`DuitkuService`)
- ⚠️ **Form edit order di admin** (`app/Filament/Resources/Orders/Schemas/OrderForm.php`) mengizinkan semua kolom diedit, termasuk `total`, `status`, dan data payment. Perlu dibatasi (read-only + aksi khusus). Keputusan aksi admin yang diizinkan = [TBD].

## 10. Perlindungan Informasi Akun Game

- Kredensial akun game tidak disimpan di sistem (keputusan PO 2026-11-22). Kolom `credentials` dihapus. ✅ dites (`test_game_accounts_table_has_no_credentials_column`)
- Halaman game hanya memuat akun dengan status `available`, dengan field `name`, `price`, dan `publicInfo` (array eksplisit). ✅ dites (`test_game_page_lists_only_available_accounts`)
- `public_info` ditulis admin dan tampil publik. Jangan isi data login di sana.

## 11. Pencegahan Kebocoran Data

- Jangan tampilkan email/WA pembeli lain di halaman publik. ✅
- Props Inertia tercetak sebagai JSON di sumber halaman. Controller wajib mengirim array eksplisit, **bukan model utuh**, supaya `base_price`, `reseller_price`, `supplier_sku` tidak bocor. ✅ dites (`test_game_page_props_never_expose_internal_product_columns`)
- Log (`api_logs`, `storage/logs`) tidak boleh berisi secret atau `credentials`. ✅ untuk kode saat ini
- Respons error production generik (tanpa stack trace) → `APP_DEBUG=false`. ⚠️ saat deploy
- Riwayat order pelanggan hanya menampilkan order miliknya. ⏳

## 12. Logging & Error Handling

- Request/callback eksternal → `api_logs`. ✅
- Error integrasi (timeout, respons tidak valid) dicatat dengan konteks `invoice`, tanpa secret. ⏳
- Job yang gagal permanen masuk `failed_jobs`. Admin perlu tahu order yang tertahan. [REKOMENDASI] ⏳

## 13. Data Cek Nickname Pelanggan (disetujui PO 2026-11-27)

- Username dan region pemain hanya disimpan di cache (tabel `cache`, driver `database`) selama **1 hari**. Setelah itu `Cache::get` tidak mengembalikannya lagi. ✅ dites (`test_cached_nickname_reuses_found_result_for_one_day`)
- Baris cache kedaluwarsa hanya dihapus saat dibaca ulang. Belum ada penghapusan terjadwal, jadi baris yang tidak dibaca lagi tetap ada di tabel. Harus dibuat sebelum endpoint pelanggan dibuka. ⏳
- `api_logs` tidak menyimpan `data.username` dan `data.region` untuk `/check-nickname` (redaksi di `MeloService::logOutgoing`). ✅ dites (`test_nickname_username_and_region_are_not_stored_in_api_logs`)
- Kunci cache memakai hash SHA-256 dari `game_code|customer_target|zone`, sehingga ID pemain tidak terbaca dari nama key. ✅
- `customer_target` (ID pemain) tetap tercatat di `api_logs` (kolom `request`) tanpa masa berlaku, sama seperti ID pada pesanan. Retensi `api_logs` belum diputuskan. [TBD]
- Batas pemakaian per IP untuk endpoint cek nickname belum diputuskan (`PERSETUJUAN_CEK_NICKNAME.md` §9 no. 2). ⏳
