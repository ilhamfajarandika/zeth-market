# IMPLEMENTATION_PLAN — Rencana 1 Bulan

> Tanggal mulai hari ke-1 (H1): **[TBD]**. Skeleton sudah dibuat pada 2026-11-11 (lihat [`CURRENT_STATUS.md`](CURRENT_STATUS.md)), sehingga sebagian pekerjaan tahap 1–2 sudah ada (ditandai ✅).
> Durasi **1 bulan** (keputusan PO 2026-11-12, menggantikan 21 hari). [ASUMSI] 1 bulan = 30 hari (H1–H30); konfirmasi ke PO.
> Ini timeline resmi proyek. `TECH_STACK.md` (root) hanya merujuk ke dokumen ini.

## Ketergantungan Eksternal (Risiko Utama)

| #   | Kebutuhan                               | Paling lambat | Status       |
| --- | --------------------------------------- | ------------- | ------------ |
| E1  | Akun **sandbox** payment gateway (1)    | H11           | ❌ belum ada |
| E2  | Akun Melo (sandbox) + key + IP (5)      | H17           | ❌ belum ada |
| E3  | Akun **production** payment gateway (2) | H24           | ❌ belum ada |
| E4  | Saldo deposit Melo untuk uji nyata      | H27           | ❌ belum ada |
| E5  | Aset klien (3)                          | H5            | ❌ belum ada |
| E6  | VPS + domain + akses Cloudflare         | H27           | ❌ belum ada |
| E7  | Keputusan TBD di `PROJECT_SPEC.md` (4)  | sesuai tahap  | ❌           |

1. Tripay atau pengganti, sekaligus keputusan provider final.
2. Verifikasi merchant bisa memakan beberapa hari.
3. Logo, warna brand, daftar game & produk, harga jual/markup.
4. Refund, notifikasi, akses invoice, dll.
5. API Key, Secret Key, Webhook Secret (dibuat lewat Rotate Keys di dashboard Melo); whitelist IP server opsional.

Jika E1/E2 belum ada saat tahap terkait, kerjakan dengan `Http::fake()` dan tandai integrasi nyata sebagai **tertunda**, bukan selesai.

---

## Tahap 1 — Hari 1–4: Setup & Konfigurasi Dasar

- **Tujuan:** fondasi proyek siap dan dapat dijalankan oleh siapa pun.
- **Pekerjaan:**
  - ✅ Project Laravel 13 + Filament 5, MySQL, Laravel Boost.
  - ✅ Migration, model, enum, factory, seeder.
  - ✅ Service stok akun, signature webhook, scheduler.
  - ✅ Repository git + remote `origin` (GitHub).
  - ✅ Supplier diganti dari Digiflazz ke Melo (PR #1).
  - ⏳ Keputusan atas kolom reseller (out of scope).
  - ⏳ Finalisasi `docs/`.
- **Dependensi:** —
- **Output:** repository berisi skeleton dan dokumentasi.
- **Kriteria selesai:** `php artisan migrate:fresh --seed` dan `php artisan test --compact` lulus di mesin baru; dokumen `docs/` disetujui PO.
- **Risiko:** keputusan TBD tertunda, sehingga tahap berikutnya ikut tertahan.

## Tahap 2 — Hari 5–10: Admin Panel & Manajemen Game/Produk

- **Tujuan:** admin bisa mengelola seluruh katalog tanpa menyentuh database.
- **Pekerjaan:**
  - ✅ Resource Kategori, Game, Produk (kolom stok), Stok Akun, Pesanan; widget dashboard.
  - ⏳ Resource Pelanggan (A7).
  - ⏳ Batasi form edit Pesanan (read-only + aksi yang disetujui).
  - ⏳ Sembunyikan/mask kredensial akun di admin [TBD].
  - ⏳ Import stok akun CSV [REKOMENDASI].
  - ⏳ Sinkron harga modal Melo [REKOMENDASI, butuh E2].
  - ⏳ Branding panel (logo, warna dari E5).
- **Dependensi:** E5 (aset), keputusan aksi admin pesanan.
- **Output:** panel admin lengkap.
- **Kriteria selesai:** feature test untuk setiap resource (akses admin, non-admin ditolak, aturan khusus); demo ke PO.
- **Risiko:** permintaan desain admin melebihi kemampuan Filament (lihat catatan desain di `PROJECT_SPEC.md` §8).

## Tahap 3 — Hari 11–16: Frontend & Sistem Order

- **Tujuan:** pembeli bisa memilih produk dan membuat order.
- **Pekerjaan:**
  - ✅ Halaman home, halaman game (stok akun), invoice dasar.
  - ⏳ Desain storefront sesuai brand.
  - ⏳ Komponen Livewire checkout: input ID + validasi regex, pilih produk, kontak, metode bayar, total dihitung server.
  - ⏳ Pembuatan order + `reserve()` untuk akun.
  - ⏳ Registrasi/login pelanggan (opsional saat checkout) + riwayat transaksi.
  - ⏳ Halaman cek status order [REKOMENDASI].
- **Dependensi:** E1 (daftar metode bayar; bisa di-fake dulu), E5.
- **Output:** checkout end-to-end sampai order `pending` tersimpan.
- **Kriteria selesai:** test validasi input, harga tidak bisa dimanipulasi, stok habis ditolak, riwayat hanya milik sendiri.
- **Risiko:** revisi desain berulang. Batasi jumlah revisi di kontrak.

## Tahap 4 — Hari 17–22: Payment Gateway & Melo

- **Tujuan:** order lunas diproses otomatis.
- **Pekerjaan:**
  - ⏳ Panggil `createTransaction`, simpan data payment, tampilkan instruksi bayar.
  - ⏳ Job `HandlePaymentCallback` (idempotent, cek nominal, lock order).
  - ⏳ Job kirim ke Melo (`buyer_trx_id = invoice`, retry aman).
  - ⏳ Job `HandleSupplierCallback` (Sukses/Gagal + SN).
  - ⏳ Cek status supplier jika webhook tidak datang.
  - ⏳ Penandaan order `pending` yang lewat `expired_at` menjadi `expired`.
- **Dependensi:** **E1, E2** (wajib untuk uji nyata).
- **Output:** alur bayar → top up sukses di sandbox.
- **Kriteria selesai:** test dengan `Http::fake()` untuk semua cabang (paid, expired, failed, callback ganda, signature salah); uji sandbox lewat Postman CLI.
- **Risiko:** akses API terlambat, IP whitelist, perbedaan format respons dari asumsi di `API_INTEGRATION.md`.

## Tahap 5 — Hari 23–26: Stok Akun, Transaksi & Notifikasi

- **Tujuan:** penjualan akun aman dan pembeli mendapat hasil.
- **Pekerjaan:**
  - ✅ `reserve` / `markSold` / `release` / `releaseExpired` + test.
  - ⏳ Hubungkan ke callback payment.
  - ⏳ Perbaiki `releaseExpired()` agar tidak melepas akun milik order yang sudah dibayar.
  - ⏳ Aturan akses invoice/kredensial [TBD].
  - ⏳ Penanganan supplier gagal setelah dibayar [TBD].
  - ⏳ Notifikasi email [TBD] (WA gateway butuh persetujuan).
- **Dependensi:** tahap 4, keputusan TBD terkait.
- **Output:** semua jenis transaksi selesai end-to-end.
- **Kriteria selesai:** test race/expiry, kredensial tidak bocor, notifikasi terkirim (mail `log` di lokal).
- **Risiko:** keputusan refund/akses invoice belum ada.

## Tahap 6 — Hari 27–30: Testing, Bug Fixing & Deployment

- **Tujuan:** aplikasi live dan stabil.
- **Pekerjaan:**
  - ⏳ Seluruh test lulus.
  - ⏳ Uji manual checklist (`SECURITY_RULES.md`).
  - ⏳ Setup VPS (Coolify/Forge [TBD]), Redis, queue worker, cron scheduler, HTTPS Cloudflare, `APP_DEBUG=false`, backup DB, ganti akun admin contoh.
  - ⏳ Daftarkan URL webhook production.
  - ⏳ Transaksi nyata nominal kecil; serah terima ke klien.
- **Dependensi:** E3, E4, E6.
- **Output:** website production + catatan serah terima.
- **Kriteria selesai:** kriteria keberhasilan di `PROJECT_SPEC.md` §12 terpenuhi.
- **Risiko:** verifikasi merchant production lama; buffer waktu sangat tipis. Siapkan rencana go-live dengan mode sandbox jika production belum aktif.
