# PROJECT_SPEC — Spesifikasi Produk (Single Source of Truth)

> Dokumen ini adalah acuan utama kebutuhan produk (apa yang **harus** dibuat). Fakta implementasi (apa yang **sudah ada**) mengacu ke kode. Jika ada konflik, hentikan bagian yang terdampak dan laporkan ke PO (lihat [`AGENTS.md`](AGENTS.md) §3).
>
> Label:
> - **[FINAL]** = sudah disepakati
> - **[REKOMENDASI]** = usulan teknis, boleh diubah
> - **[ASUMSI]** = perlu dikonfirmasi
> - **[TBD]** = belum diputuskan

## 1. Deskripsi Proyek

Website untuk membeli **top up game** (diamond, UC, VP, dll.) dan **akun game** milik toko. Top up diproses otomatis lewat supplier Melo. Akun game dijual dari stok yang dikelola admin. Pembayaran dilakukan lewat satu payment gateway.

## 2. Tujuan & Ruang Lingkup

- Pembeli bisa memilih game dan produk, membayar, lalu menerima top up atau akun secara otomatis. [FINAL]
- Admin bisa mengelola katalog, harga, stok akun, pesanan, pelanggan, dan transaksi dari satu panel. [FINAL]
- Selesai dan siap deploy dalam **1 bulan** (rincian di `IMPLEMENTATION_PLAN.md`). [FINAL]

## 3. Pengguna

| Pengguna  | Hak utama                                     | Status  |
| --------- | --------------------------------------------- | ------- |
| Guest     | Melihat katalog & stok, **boleh membeli** (1) | [FINAL] |
| Pelanggan | Registrasi, login, membeli, riwayat transaksi | [FINAL] |
| Admin     | Mengelola seluruh data lewat `/admin`         | [FINAL] |

Catatan:
1. Login opsional (keputusan PO 2026-11-12). Guest membeli dengan mengisi email/WhatsApp.
- Guest dapat melihat game, produk, harga, stok akun, dan informasi website.
- Admin mengelola game, produk, harga, stok akun, pesanan, pelanggan, dan transaksi.

## 4. Jenis Produk

| Jenis       | Nilai `products.type` | Sumber pemenuhan                        | Status  |
| ----------- | --------------------- | --------------------------------------- | ------- |
| Top up game | `topup`               | Dikirim otomatis ke Melo (SKU)          | [FINAL] |
| Akun game   | `account`             | Stok `game_accounts`, tampil di halaman | [FINAL] |

## 5. Fitur Pelanggan / Guest

| #   | Fitur                                      | Status        |
| --- | ------------------------------------------ | ------------- |
| P1  | Beranda: daftar game per kategori          | [FINAL]       |
| P2  | Halaman game: produk, harga, stok akun (1) | [FINAL]       |
| P3  | Form order: User ID (+ Zone ID) (2)        | [FINAL]       |
| P4  | Pilih metode bayar & checkout              | [FINAL]       |
| P5  | Halaman invoice (3)                        | [FINAL]       |
| P6  | Cek status order via nomor invoice         | [REKOMENDASI] |
| P7  | Registrasi & login pelanggan (opsional)    | [FINAL]       |
| P8  | Riwayat transaksi pelanggan login          | [FINAL]       |
| P9  | Notifikasi hasil order (email / WA)        | [TBD]         |
| P10 | Checkbox "ID sudah benar"                  | [REKOMENDASI] |

Catatan:
1. Produk akun dengan stok habis tidak bisa dibeli.
2. Zone/Server ID hanya jika game membutuhkan. Validasi **format** saja, tanpa cek nickname.
3. Menampilkan status order, instruksi bayar, dan hasil (SN top up / detail akun).
- P9: kanal notifikasi dan apakah masuk scope belum diputuskan.

## 6. Fitur Admin

| #   | Fitur                                | Status               |
| --- | ------------------------------------ | -------------------- |
| A1  | Login admin (hanya `role = admin`)   | [FINAL]              |
| A2  | Kelola kategori & game (1)           | [FINAL]              |
| A3  | Kelola produk & harga (2)            | [FINAL]              |
| A4  | Kelola stok akun (3)                 | [FINAL]              |
| A5  | Import stok akun massal (CSV)        | [REKOMENDASI]        |
| A6  | Kelola pesanan (5)                   | [FINAL] · aksi [TBD] |
| A7  | Kelola pelanggan                     | [FINAL] · aksi [TBD] |
| A8  | Dashboard ringkas (4)                | [FINAL] · sudah ada  |
| A9  | Sinkron harga modal dari Melo        | [REKOMENDASI]        |
| A10 | Pengaturan markup harga              | [TBD]                |

Catatan:
1. Termasuk label dan regex untuk User ID & Zone ID.
2. Harga modal, harga jual, harga coret, aktif/nonaktif.
3. Tambah, nonaktifkan, lihat status reserved/sold.
4. Omzet hari ini, order perlu diproses, stok akun.
5. Lihat detail, status, dan log payment/supplier. Status hanya diubah lewat aksi khusus yang disetujui, bukan edit bebas (lihat `SECURITY_RULES.md` §9).
- A7: cakupan aksi belum diputuskan (mis. lihat saja atau bisa blokir).

## 7. Kebutuhan Fungsional Inti

1. Harga & total dihitung di server. Order menyimpan snapshot nama produk dan harga. [FINAL]
2. Satu akun game tidak boleh terjual ke dua order. Akun ditahan (reserved) saat checkout dan dilepas jika tidak dibayar. [FINAL]
3. Lama penahanan akun & masa berlaku invoice: **15 menit**. [REKOMENDASI]
4. Pembayaran sukses → top up dikirim otomatis ke Melo / akun langsung ditandai terjual. [FINAL]
5. Semua callback payment & supplier diverifikasi signature dan diproses sekali saja (idempotent). [FINAL]
6. Order yang gagal di supplier setelah dibayar → penanganan [TBD]: kirim ulang manual oleh admin, refund manual di luar sistem, atau refund lewat payment gateway (lihat `BUSINESS_FLOW.md` §9). Saldo/deposit pelanggan bukan opsi karena di luar scope (§10).

## 8. Kebutuhan Nonfungsional

| Aspek             | Target                                     | Status        |
| ----------------- | ------------------------------------------ | ------------- |
| Responsif         | Nyaman di HP (mayoritas pembeli mobile)    | [FINAL]       |
| Keamanan          | Lihat `SECURITY_RULES.md`                  | [FINAL]       |
| Performa          | Katalog cepat, tanpa N+1 query             | [REKOMENDASI] |
| Keandalan         | Callback idempotent, retry supplier aman (2) | [FINAL]     |
| Zona waktu        | `Asia/Jakarta` (sudah di `config/app.php`) | [FINAL]       |
| Bahasa UI         | Indonesia                                  | [ASUMSI]      |
| Desain storefront | Bebas sesuai brand klien (1)               | [REKOMENDASI] |

Catatan:
1. Storefront memakai Tailwind dan desainnya bebas. Admin Filament hanya menyesuaikan warna, logo, dan font. Batasan ini perlu dicantumkan di kontrak.
2. Syaratnya [FINAL]. Mekanisme queue job = [REKOMENDASI] (`API_INTEGRATION.md` §4).

## 9. Batasan Proyek

- Waktu 1 bulan. Nilai kontrak tidak dicantumkan di dokumen (lihat kontrak). [FINAL]
- Hanya **satu** payment gateway. [FINAL]
- Bergantung pada akses API Melo & payment gateway yang harus didaftarkan klien/developer. Akses belum tersedia. [FINAL, risiko]
- Stack tidak boleh diganti tanpa persetujuan. [FINAL]

## 10. Di Luar Scope

| Item                                      | Status                   |
| ----------------------------------------- | ------------------------ |
| Cek nickname / cek ID (mis. cek ID FF)    | [FINAL] permintaan klien |
| MLBB calc (1)                             | [FINAL] permintaan klien |
| Reseller (harga khusus, saldo/deposit)    | [FINAL] keputusan PO (2) |
| Lebih dari satu payment gateway           | [FINAL]                  |
| Aplikasi mobile / API publik pihak ketiga | [ASUMSI]                 |
| Microservices / SPA terpisah              | [FINAL]                  |
| Multi-bahasa / multi-currency             | [ASUMSI]                 |
| Refund otomatis                           | [TBD]                    |

Catatan:
1. Kalkulator win rate / magic wheel / zodiac.
2. Diputuskan 2026-11-12. Kolom terkait reseller di kode perlu ditinjau.

## 11. Teknologi

Bagian ini satu-satunya sumber resmi stack dan versinya. Dokumen lain cukup merujuk ke sini.

| Layer         | Teknologi                                         | Status              |
| ------------- | ------------------------------------------------- | ------------------- |
| Bahasa        | PHP 8.4                                           | [FINAL]             |
| Framework     | Laravel 13                                        | [FINAL]             |
| Admin panel   | Filament 5                                        | [FINAL]             |
| Frontend      | Blade + Livewire + Alpine.js                      | [FINAL]             |
| Styling       | Tailwind CSS 4                                    | [FINAL]             |
| Build         | Vite 8                                            | [FINAL]             |
| Database      | MySQL 8                                           | [FINAL]             |
| Cache & queue | Redis (production), database driver (dev)         | [FINAL]             |
| Supplier      | Melo H2H API                                      | [FINAL]             |
| Payment       | 1 provider, kandidat **Tripay** (1)               | [TBD] provider      |
| Testing       | PHPUnit + Postman CLI                             | [FINAL]             |
| Deploy        | VPS + Coolify **atau** Laravel Forge + Cloudflare | [TBD] Coolify/Forge |
| Notifikasi    | Email (mailer Laravel) (2)                        | [TBD]               |

Catatan:
1. Alternatif: Duitku / Midtrans / Xendit.
2. WhatsApp gateway (Fonnte/Wablas) = teknologi baru, butuh persetujuan.

## 12. Kriteria Keberhasilan

1. Guest dan pelanggan bisa membeli top up sampai sukses terkirim (diuji di sandbox Melo & payment).
2. Pembelian akun menampilkan detail akun hanya setelah pembayaran sukses, tanpa akun ganda.
3. Order expired otomatis dan stok akun kembali.
4. Callback palsu (signature salah) ditolak; callback ganda tidak memproses order dua kali.
5. Admin bisa mengelola katalog, harga, stok, pesanan, dan pelanggan tanpa menyentuh database langsung.
6. Seluruh feature test lulus, aplikasi ter-deploy di VPS dengan HTTPS.
7. Selesai dalam 1 bulan.

## 13. Log Keputusan

| Tanggal    | Keputusan                                       | Sumber |
| ---------- | ----------------------------------------------- | ------ |
| 2026-11-11 | Tanpa MLBB calc / cek ID; ada display stok akun | Klien  |
| 2026-11-11 | Tetap Laravel 13; database MySQL                | PO     |
| 2026-11-12 | Timeline 21 hari                                | PO     |
| 2026-11-12 | Checkout login opsional (guest boleh membeli)   | PO     |
| 2026-11-12 | Reseller di luar scope                          | PO     |
| 2026-11-12 | Supplier diganti dari Digiflazz ke Melo H2H     | PO     |
| 2026-11-12 | Timeline diubah menjadi 1 bulan; nilai kontrak tidak dicantumkan di dokumen | PO |
