# SECURITY_RULES — Aturan Keamanan

> Setiap aturan diberi status: ✅ sudah diterapkan di kode · ⏳ wajib saat fitur terkait dibuat · ⚠️ masalah terbuka.

## 1. Password & Autentikasi

| Aturan | Status |
|---|---|
| Password di-hash otomatis (cast `hashed` di `app/Models/User.php`, bcrypt) | ✅ |
| Login admin lewat Filament `/admin/login` | ✅ |
| Login/registrasi pelanggan di storefront, terpisah dari panel admin | ⏳ |
| Rate limit untuk login & registrasi pelanggan | ⏳ |
| Session di-regenerate setelah login | ⏳ (perilaku bawaan Laravel auth) |
| Akun admin contoh `admin@example.com / password` **hanya untuk lokal**. Wajib diganti/dihapus di production. | ⚠️ saat deploy |

## 2. Otorisasi & Role

| Role | Akses | Status |
|---|---|---|
| `admin` | Panel `/admin` (`User::canAccessPanel()` → `role === Admin`) | ✅ dites (`AdminPanelAccessTest`) |
| `member` (pelanggan) | Storefront + riwayat order **miliknya sendiri** | ⏳ |
| guest | Storefront, checkout, invoice | ✅ / ⏳ |
| `reseller` | **Out of scope**. Enum masih ada, jangan dipakai. | ⚠️ ditinjau |

- Riwayat transaksi wajib difilter `orders.user_id = auth()->id()`. ⏳
- Kolom `role` tidak boleh diisi dari input registrasi (mass assignment). Default `member`. ⏳

## 3. Validasi Input

- Semua input storefront divalidasi di server (Form Request atau aturan validasi Livewire). ⏳
- User ID / Zone ID divalidasi dengan `games.user_id_regex` / `zone_id_regex` jika diisi, plus batas panjang. ⏳
- Produk yang dipilih harus `is_active`, milik game yang sama, dan (untuk akun) stoknya tersedia. ⏳
- Email/WhatsApp divalidasi formatnya. ⏳
- Upload gambar admin hanya tipe gambar (`FileUpload::image()`). ✅

## 4. Proteksi Endpoint

| Aturan | Status |
|---|---|
| CSRF aktif untuk semua route web, **kecuali** `webhook/*` (`bootstrap/app.php`) | ✅ |
| Webhook di-throttle `120 request/menit` | ✅ |
| Rate limit form checkout | ⏳ |
| Anti-bot checkout (mis. Cloudflare Turnstile) | [TBD] dependency/layanan baru |
| Whitelist IP callback provider | [TBD] opsional, tambahan dari signature |
| `APP_DEBUG=false` di production | ⚠️ saat deploy |

## 5. Secret & Konfigurasi

- Semua secret di `.env` (tidak masuk git). `.env.example` hanya berisi nama variabel tanpa nilai. ✅
- Kode membaca secret lewat `config('services.*')`, bukan `env()` langsung di luar folder `config/`. ✅
- `APP_KEY` production dibuat sekali, dibackup aman, dan tidak pernah diganti (kunci enkripsi kredensial akun). ⚠️ saat deploy
- Jangan menulis secret di dokumen, test, log, atau screenshot. [FINAL]

## 6. Verifikasi Callback

| Provider | Mekanisme | Status |
|---|---|---|
| Tripay | `X-Callback-Signature` = HMAC-SHA256(raw body, private key), dibandingkan dengan `hash_equals` | ✅ dites |
| Melo | `X-H2H-Signature` = hex HMAC-SHA256(raw body, webhook secret), dibandingkan dengan `hash_equals`; ditolak juga jika secret belum diisi | ✅ dites |
| Signature salah/kosong → Tripay 403, Melo 401, keduanya dicatat di `api_logs` | | ✅ |
| Cocokkan nominal callback dengan `orders.total` | | ⏳ |

## 7. Idempotency

- Callback yang sama bisa datang lebih dari sekali. Order berstatus final (`OrderStatus::isFinal()`) tidak boleh diproses ulang. ⏳
- `markSold()` sudah idempotent. ✅
- Satu invoice hanya menghasilkan satu transaksi supplier (`buyer_trx_id = invoice`). Retry memakai `buyer_trx_id` yang sama. ⏳
- Job pemrosesan callback sebaiknya unik per invoice (`ShouldBeUnique` atau lock). [REKOMENDASI] ⏳

## 8. Transaksi Database

- Stok akun: transaksi + `lockForUpdate()` (`AccountStockService`). ✅
- Perubahan status order dari callback: transaksi + lock order. ⏳
- Tidak boleh ada panggilan HTTP eksternal di dalam transaksi yang memegang lock. [FINAL]

## 9. Proteksi Manipulasi Harga

- Harga, fee, dan total dihitung di server dari data produk. Nilai harga dari request diabaikan. ⏳
- Snapshot harga disimpan di order saat dibuat. ✅ (skema)
- Nominal yang dikirim ke payment gateway = `orders.total`. ✅ (`TripayService`)
- ⚠️ **Form edit order di admin** (`app/Filament/Resources/Orders/Schemas/OrderForm.php`) mengizinkan semua kolom diedit, termasuk `total`, `status`, dan data payment. Perlu dibatasi (read-only + aksi khusus). Keputusan aksi admin yang diizinkan = [TBD].

## 10. Perlindungan Informasi Akun Game

- `game_accounts.credentials` terenkripsi (cast `encrypted`) dan disembunyikan dari serialisasi. ✅ dites
- Hanya `public_info` yang tampil di halaman produk. ✅
- Kredensial hanya tampil di invoice jika order `success`. ✅ dites
- ⚠️ **Invoice dapat dibuka siapa saja yang tahu nomor invoice** (`/invoice/{invoice}`), termasuk kredensial setelah sukses. Perlu aturan akses [TBD]: mis. URL bertanda tangan (signed URL), token rahasia di link email, atau wajib login untuk order milik pelanggan.
- Admin melihat kredensial di form edit Stok Akun. Apakah perlu disembunyikan atau di-mask = [TBD].

## 11. Pencegahan Kebocoran Data

- Jangan tampilkan email/WA pembeli lain di halaman publik. ✅
- Log (`api_logs`, `storage/logs`) tidak boleh berisi secret atau `credentials`. ✅ untuk kode saat ini
- Respons error production generik (tanpa stack trace) → `APP_DEBUG=false`. ⚠️ saat deploy
- Riwayat order pelanggan hanya menampilkan order miliknya. ⏳

## 12. Logging & Error Handling

- Request/callback eksternal → `api_logs`. ✅
- Error integrasi (timeout, respons tidak valid) dicatat dengan konteks `invoice`, tanpa secret. ⏳
- Job yang gagal permanen masuk `failed_jobs`. Admin perlu tahu order yang tertahan. [REKOMENDASI] ⏳
- `OutOfStockException` ditangani menjadi pesan ramah "stok habis". ⏳
