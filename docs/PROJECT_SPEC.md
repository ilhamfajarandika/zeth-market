# PROJECT_SPEC — Spesifikasi Produk (Single Source of Truth)

> Dokumen ini adalah acuan utama kebutuhan produk. Jika dokumen lain bertentangan dengan dokumen ini, ikuti dokumen ini lalu laporkan konfliknya (lihat [`AGENTS.md`](AGENTS.md) §3).
>
> Label: **[FINAL]** = sudah disepakati · **[REKOMENDASI]** = usulan teknis, boleh diubah · **[ASUMSI]** = perlu dikonfirmasi · **[TBD]** = belum diputuskan.

## 1. Deskripsi Proyek

Website untuk membeli **top up game** (diamond, UC, VP, dll.) dan **akun game** milik toko. Top up diproses otomatis lewat supplier Melo. Akun game dijual dari stok yang dikelola admin. Pembayaran dilakukan lewat satu payment gateway.

## 2. Tujuan & Ruang Lingkup

- Pembeli bisa memilih game dan produk, membayar, lalu menerima top up atau akun secara otomatis. [FINAL]
- Admin bisa mengelola katalog, harga, stok akun, pesanan, pelanggan, dan transaksi dari satu panel. [FINAL]
- Selesai dan siap deploy dalam **21 hari** dengan budget **Rp4.500.000**. [FINAL]

## 3. Pengguna

| Pengguna | Hak | Status |
|---|---|---|
| **Guest** | Melihat game, produk, harga, stok akun, informasi website. **Boleh membeli** dengan mengisi email/WhatsApp. | [FINAL] login opsional (keputusan PO 2026-11-12) |
| **Pelanggan** | Registrasi, login, membeli, membayar, melihat riwayat transaksi miliknya. | [FINAL] |
| **Admin** | Mengelola game, produk, harga, stok akun, pesanan, pelanggan, transaksi. Masuk lewat `/admin`. | [FINAL] |

## 4. Jenis Produk

| Jenis | Sumber pemenuhan | Status |
|---|---|---|
| **Top up game** (`products.type = topup`) | Dikirim otomatis ke Melo berdasarkan `supplier_sku` | [FINAL] |
| **Akun game** (`products.type = account`) | Diambil dari stok `game_accounts`. Stok tampil di halaman produk. | [FINAL] |

## 5. Fitur Pelanggan / Guest

| # | Fitur | Status |
|---|---|---|
| P1 | Halaman beranda: daftar game per kategori | [FINAL] |
| P2 | Halaman game: daftar produk, harga, dan **stok akun** (habis = tidak bisa dibeli) | [FINAL] |
| P3 | Form order: User ID (+ Zone/Server ID jika game membutuhkan), validasi **format** saja | [FINAL] |
| P4 | Pilih metode pembayaran dan checkout | [FINAL] |
| P5 | Halaman invoice: status order, instruksi bayar, hasil (SN top up / detail akun) | [FINAL] |
| P6 | Cek status order berdasarkan nomor invoice | [REKOMENDASI] |
| P7 | Registrasi & login pelanggan (opsional untuk membeli) | [FINAL] |
| P8 | Riwayat transaksi pelanggan yang login | [FINAL] |
| P9 | Notifikasi hasil order (email / WhatsApp) | [TBD] kanal & apakah masuk scope |
| P10 | Checkbox konfirmasi "ID sudah benar" | [REKOMENDASI] |

## 6. Fitur Admin

| # | Fitur | Status |
|---|---|---|
| A1 | Login admin (hanya `role = admin`) | [FINAL] |
| A2 | Kelola kategori & game (termasuk label/regex User ID & Zone ID) | [FINAL] |
| A3 | Kelola produk & harga (harga modal, harga jual, harga coret, aktif/nonaktif) | [FINAL] |
| A4 | Kelola stok akun (tambah, nonaktifkan, lihat status reserved/sold) | [FINAL] |
| A5 | Import stok akun massal (CSV) | [REKOMENDASI] |
| A6 | Kelola pesanan (lihat detail, status, log supplier/payment) | [FINAL] |
| A7 | Kelola pelanggan | [FINAL] (cakupan aksi: [TBD], mis. lihat saja / blokir) |
| A8 | Dashboard ringkas: omzet hari ini, order perlu diproses, stok akun | [FINAL] (sudah ada di skeleton) |
| A9 | Sinkron harga modal dari Melo | [REKOMENDASI] |
| A10 | Pengaturan markup harga | [TBD] |

## 7. Kebutuhan Fungsional Inti

1. Harga & total dihitung di server. Order menyimpan snapshot nama produk dan harga. [FINAL]
2. Satu akun game tidak boleh terjual ke dua order. Akun ditahan (reserved) saat checkout dan dilepas jika tidak dibayar. [FINAL]
3. Lama penahanan akun & masa berlaku invoice: **15 menit**. [REKOMENDASI]
4. Pembayaran sukses → top up dikirim otomatis ke Melo / akun langsung ditandai terjual. [FINAL]
5. Semua callback payment & supplier diverifikasi signature dan diproses sekali saja (idempotent). [FINAL]
6. Order yang gagal di supplier setelah dibayar → penanganan [TBD] (refund otomatis / manual / saldo).

## 8. Kebutuhan Nonfungsional

| Aspek | Target | Status |
|---|---|---|
| Responsif | Nyaman di HP (mayoritas pembeli mobile) | [FINAL] |
| Keamanan | Lihat [`SECURITY_RULES.md`](SECURITY_RULES.md) | [FINAL] |
| Performa | Halaman katalog cepat, tanpa N+1 query | [REKOMENDASI] |
| Keandalan | Proses callback & supplier di queue dengan retry | [FINAL] |
| Zona waktu | `Asia/Jakarta` | [FINAL] (sudah di `config/app.php`) |
| Bahasa UI | Indonesia | [ASUMSI] |
| Desain storefront | Bebas sesuai brand klien (Tailwind). Admin Filament hanya menyesuaikan warna/logo/font. | [REKOMENDASI] perlu dicantumkan di kontrak |

## 9. Batasan Proyek

- Waktu 21 hari, budget Rp4.500.000. [FINAL]
- Hanya **satu** payment gateway. [FINAL]
- Bergantung pada akses API Melo & payment gateway yang harus didaftarkan klien/developer. Akses belum tersedia. [FINAL, risiko]
- Stack tidak boleh diganti tanpa persetujuan. [FINAL]

## 10. Di Luar Scope

| Item | Status |
|---|---|
| Cek nickname / cek ID (mis. cek ID FF) | [FINAL] permintaan klien |
| MLBB calc (kalkulator win rate / magic wheel / zodiac) | [FINAL] permintaan klien |
| Reseller (harga khusus, saldo/deposit reseller) | [FINAL] keputusan PO 2026-11-12. Kolom terkait di kode perlu ditinjau. |
| Lebih dari satu payment gateway | [FINAL] |
| Aplikasi mobile / API publik untuk pihak ketiga | [ASUMSI] |
| Microservices / SPA terpisah | [FINAL] |
| Multi-bahasa / multi-currency | [ASUMSI] |
| Refund otomatis | [TBD] |

## 11. Teknologi

| Layer | Teknologi | Status |
|---|---|---|
| Bahasa | PHP 8.4 | [FINAL] |
| Framework | Laravel 13 | [FINAL] |
| Admin panel | Filament 5 | [FINAL] |
| Frontend | Blade + Livewire + Alpine.js | [FINAL] |
| Styling | Tailwind CSS 4 | [FINAL] |
| Build | Vite 8 | [FINAL] |
| Database | MySQL 8 | [FINAL] |
| Cache & queue | Redis (production), database driver (development) | [FINAL] |
| Supplier | Melo H2H API | [FINAL] |
| Payment | 1 provider. Kandidat awal **Tripay**, alternatif Duitku / Midtrans / Xendit. | [TBD] provider final |
| Testing | PHPUnit + Postman CLI | [FINAL] |
| Deploy | VPS + Coolify **atau** Laravel Forge, ditambah Cloudflare | [TBD] Coolify vs Forge |
| Notifikasi | Email (mailer Laravel). WhatsApp gateway (Fonnte/Wablas) = teknologi baru. | [TBD] |

## 12. Kriteria Keberhasilan

1. Guest dan pelanggan bisa membeli top up sampai sukses terkirim (diuji di sandbox/testing Melo & payment).
2. Pembelian akun menampilkan detail akun hanya setelah pembayaran sukses, tanpa akun ganda.
3. Order expired otomatis dan stok akun kembali.
4. Callback palsu (signature salah) ditolak; callback ganda tidak memproses order dua kali.
5. Admin bisa mengelola katalog, harga, stok, pesanan, dan pelanggan tanpa menyentuh database langsung.
6. Seluruh feature test lulus, aplikasi ter-deploy di VPS dengan HTTPS.
7. Selesai dalam 21 hari.

## 13. Log Keputusan

| Tanggal | Keputusan | Sumber |
|---|---|---|
| 2026-11-11 | Tanpa MLBB calc / cek ID; ada display stok akun | Klien |
| 2026-11-11 | Tetap Laravel 13 (tidak downgrade ke 12); database MySQL | PO |
| 2026-11-12 | Timeline 21 hari, budget Rp4.500.000 | PO |
| 2026-11-12 | Checkout login opsional (guest boleh membeli) | PO |
| 2026-11-12 | Reseller di luar scope | PO |
