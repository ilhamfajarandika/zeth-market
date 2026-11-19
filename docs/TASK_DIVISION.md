# TASK_DIVISION — Pembagian Tugas Frontend & Backend

> Dokumen ini menjawab **siapa** mengerjakan apa. **Kapan** dikerjakan ada di [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md), dan **apa** yang dibuat ada di [`PROJECT_SPEC.md`](PROJECT_SPEC.md). Jika isi dokumen ini berbeda dengan keduanya, laporkan ke PO (lihat [`AGENTS.md`](AGENTS.md) §3).
> Label: **[FINAL] / [REKOMENDASI] / [ASUMSI] / [TBD]** (lihat `PROJECT_SPEC.md`). Status: ✅ sudah ada · ⏳ belum.

## 1. Tim

| Peran    | PIC                          | Tanggung jawab utama                                                                          |
| -------- | ---------------------------- | --------------------------------------------------------------------------------------------- |
| Frontend | Ilham Fajar Andika           | Halaman & komponen Vue (Inertia), Tailwind, branding Filament, tampilan email                 |
| Backend  | Mochammad Lury Choirul Rizky | Skema DB, model, service, job, controller & props Inertia, resource Filament, integrasi, deploy |
| PO       | [TBD]                        | Keputusan [TBD], review dokumen, persetujuan scope & dependency                               |

## 2. Batas Wilayah File

Pemilik = yang mengerjakan dan me-review perubahan di area itu. Pihak lain boleh mengusulkan perubahan, tetapi lewat PR yang direview pemiliknya.

| Area                                                                                     | Pemilik  | Catatan                                                          |
| ---------------------------------------------------------------------------------------- | -------- | ---------------------------------------------------------------- |
| `resources/js/pages/**`, `resources/js/layouts/**`, `resources/js/components/**`         | Frontend | Halaman dan komponen Vue 3 (JavaScript), memakai kontrak §3      |
| `resources/js/app.js`, `resources/views/app.blade.php`                                   | Frontend | Setup Inertia dan root view; perubahan setup dikabarkan ke Backend |
| `resources/css/**`, aset gambar di `public/`                                             | Frontend | Tailwind 4, ikon/logo                                            |
| Theme Filament (`php artisan make:filament-theme`) dan bagian branding `AdminPanelProvider` (warna, font, logo) | Frontend | Konfigurasi panel lain (auth, path, resource) milik Backend |
| `resources/views/mail/**` atau view notifikasi                                           | Frontend | Hanya jika notifikasi disetujui [TBD]                            |
| `app/Http/Controllers/**` (termasuk props Inertia), `app/Http/Middleware/HandleInertiaRequests.php` | Backend  | Menentukan isi props; wajib array eksplisit (§3.1)               |
| `app/**` lainnya (Models, Services, Jobs, Http/Requests, Enums, Filament Resources, Console) | Backend  | Termasuk `Schemas/` dan `Tables/` resource Filament              |
| `database/**`, `routes/**`, `config/**`, `bootstrap/app.php`                             | Backend  | Migration mengikuti `AGENTS.md` §5                               |
| `tests/**`                                                                               | Backend  | Frontend memperbarui test yang terdampak perubahan props (§5)    |
| `package.json`, `vite.config.js`                                                         | Frontend | Dependency baru tetap butuh persetujuan PO (`AGENTS.md` §8)      |
| `docs/**`                                                                                | Keduanya | Yang mengubah perilaku wajib memperbarui dokumen terkait         |

## 3. Kontrak Frontend ↔ Backend

### 3.1 Aturan kontrak [FINAL]

1. Frontend **tidak menghitung** harga, fee, atau total. Semua angka (sudah diformat Rupiah) berasal dari Backend (`SECURITY_RULES.md` §9).
2. Backend mengirim props sebagai **array eksplisit**, bukan model utuh. Props Inertia tercetak sebagai JSON di sumber halaman, jadi kolom internal tidak boleh ikut (`SECURITY_RULES.md` §11).
3. Jika Frontend butuh data baru, minta ke Backend untuk ditambahkan ke props, lalu dicatat di §3.3.
4. Frontend hanya menampilkan `credentials` lewat prop yang disiapkan Backend; Backend hanya mengisinya pada kondisi yang diizinkan.
5. URL berasal dari Backend (prop `url` atau shared prop `links`), tidak ditulis langsung di Vue. Proyek tidak memakai Ziggy.
6. Perubahan kontrak (nama props, field, route) diperbarui **di dokumen ini lebih dulu** oleh Backend, lalu diberitahukan ke Frontend.

### 3.2 Route

| Nama route     | Parameter               | Komponen Inertia | Status |
| -------------- | ----------------------- | ---------------- | ------ |
| `home`         | —                       | `Home`           | ✅     |
| `game.show`    | `$game` (by `slug`)     | `Game`           | ✅     |
| `invoice.show` | `$order` (by `invoice`) | `Invoice`        | ✅     |

Route pelanggan (login, register, riwayat), checkout, dan cek status order: ⏳ ditambahkan Backend di Tahap 3, lalu dicatat di tabel ini.

### 3.3 Props yang sudah ada

**Shared props** (`HandleInertiaRequests`, tersedia di semua halaman lewat `usePage().props`):

| Prop                | Isi                                      |
| ------------------- | ---------------------------------------- |
| `appName`           | Nama aplikasi (`config('app.name')`)     |
| `links.home`        | URL beranda                              |
| `links.invoiceBase` | URL dasar invoice, ditambah `/{invoice}` |

**Props per halaman** (`StorefrontController`):

| Komponen  | Prop            | Field                                                                                                         |
| --------- | --------------- | ------------------------------------------------------------------------------------------------------------- |
| `Home`    | `categories[]`  | `slug`, `name`, `games[]` → `slug`, `name`, `publisher`, `url`, `thumbnailUrl`, `isPopular`                    |
| `Game`    | `game`          | field game di atas + `bannerUrl`, `instruction`, `requiresZoneId`, `userIdLabel`, `zoneIdLabel`               |
| `Game`    | `productGroups[]` | `label`, `products[]` → `id`, `name`, `description`, `price`, `strikePrice`, `isAccount`, `stock`           |
| `Invoice` | `order`         | `invoice`, `status{value,label,color}`, `gameName`, `productName`, `target`, `paymentMethod`, `createdAt`, `expiresAt`, `price`, `fee`, `total`, `payment{code,url}` (hanya `pending`), `serialNumber` & `credentials` (hanya `success`) |

### 3.4 Checkout [REKOMENDASI, ⏳ Tahap 3]

Usulan kontrak awal. Backend memfinalkannya sebelum H11, lalu menandai [FINAL] di sini.

- Frontend memakai `useForm()` dari `@inertiajs/vue3` di `Game.vue` dan mengirim `POST` ke route `checkout.store` (URL dikirim Backend sebagai prop).
- Field form: `product_id`, `target_user_id`, `target_zone_id` (hanya jika `requiresZoneId`), `email`, `whatsapp` (aturan wajibnya [TBD]), `payment_method`, `id_confirmed`.
- Backend memvalidasi lewat Form Request, menghitung total, membuat order, `reserve()` untuk akun, lalu redirect ke `invoice.show`.
- Error validasi kembali ke `form.errors.<nama_field>`. Stok habis dikembalikan sebagai error pada `product_id`.
- Prop tambahan untuk `Game`: `paymentChannels[]` (`code`, `name`, `group`, `iconUrl`) dan `checkoutUrl`.

## 4. Tugas per Tahap

Hari mengikuti [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md) (H1–H30).

### Tahap 1 — H1–4: Setup & Konfigurasi Dasar

| Frontend (Ilham)                                         | Backend (Lury)                                                                 |
| -------------------------------------------------------- | ------------------------------------------------------------------------------ |
| ✅ Layout dasar + halaman home, game, invoice (statis)   | ✅ Migration, model, enum, factory, seeder                                     |
| ⏳ Minta dan kumpulkan aset klien (E5): logo, warna, daftar game | ✅ Service stok akun, signature webhook, scheduler, integrasi Melo     |
| ⏳ Review dokumen `docs/`                                | ⏳ Eksekusi keputusan kolom reseller (M4) jika diputuskan hapus                |
|                                                          | ⏳ Review dokumen `docs/`                                                       |

### Tahap 2 — H5–10: Admin Panel & Manajemen Game/Produk

| Frontend (Ilham)                                          | Backend (Lury)                                                        |
| --------------------------------------------------------- | --------------------------------------------------------------------- |
| ⏳ Branding panel Filament: logo, warna, font (dari E5)   | ⏳ Resource Pelanggan (A7)                                            |
| ⏳ Design system storefront: warna, tipografi, kartu produk, badge stok | ⏳ Batasi form edit Pesanan: read-only + aksi khusus (M3) |
|                                                           | ⏳ Mask kredensial di admin [TBD]                                     |
|                                                           | ⏳ Import stok akun CSV [REKOMENDASI]                                  |
|                                                           | ⏳ Sinkron harga modal Melo [REKOMENDASI, butuh E2]                    |

### Tahap 3 — H11–16: Frontend & Sistem Order

| Frontend (Ilham)                                                      | Backend (Lury)                                                                |
| --------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| ⏳ Desain storefront final sesuai brand, responsif HP (v1 Vue ✅ 2026-11-13, menunggu revisi & aset E5) | ⏳ Finalkan kontrak checkout (§3.4) paling lambat H11                          |
| ⏳ Form checkout di `Game.vue` (`useForm`): input ID, pilih produk, kontak, metode bayar, error validasi | ⏳ `CheckoutController` + Form Request: validasi regex, total server, buat order, `reserve()` |
| ⏳ Halaman login, register, riwayat transaksi                         | ⏳ Auth pelanggan + Form Request + rate limit                                  |
| ⏳ Halaman cek status order [REKOMENDASI]                             | ⏳ Query riwayat (hanya milik sendiri) + route cek status [REKOMENDASI]        |
| ⏳ Pesan error ramah (stok habis, ID tidak valid)                     | ⏳ Test: validasi input, harga tidak bisa dimanipulasi, stok habis ditolak     |

### Tahap 4 — H17–22: Payment Gateway & Melo

| Frontend (Ilham)                                                   | Backend (Lury)                                                          |
| ------------------------------------------------------------------ | ----------------------------------------------------------------------- |
| ⏳ Instruksi bayar di invoice: QR, nomor VA, tombol bayar           | ⏳ `createTransaction` + simpan `payment_url`, `payment_code`, `expired_at` |
| ⏳ Hitung mundur `expired_at` di invoice (salin kode bayar ✅)       | ⏳ Job `HandlePaymentCallback`, kirim ke Melo, `HandleSupplierCallback` |
| ⏳ Label & warna status order; pembaruan status otomatis (polling Inertia) [REKOMENDASI] | ⏳ Cek status supplier; tandai order `pending` lewat batas menjadi `expired` |
|                                                                    | ⏳ Test semua cabang callback dengan `Http::fake()`                     |

### Tahap 5 — H23–26: Stok Akun, Transaksi & Notifikasi

| Frontend (Ilham)                                             | Backend (Lury)                                                          |
| ------------------------------------------------------------ | ----------------------------------------------------------------------- |
| ⏳ Tampilan detail akun di invoice (salin per field)         | ⏳ Hubungkan `reserve`/`markSold`/`release` ke callback                  |
| ⏳ Tampilan order gagal/expired dan langkah berikutnya       | ⏳ Perbaiki `releaseExpired()` (M2); aturan akses invoice (M1) [TBD]     |
| ⏳ Template email notifikasi [TBD]                           | ⏳ Penanganan supplier gagal [TBD]; notifikasi email [TBD]               |

### Tahap 6 — H27–30: Testing, Bug Fixing & Deployment

| Frontend (Ilham)                                         | Backend (Lury)                                                                     |
| -------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| ⏳ Uji manual alur pembeli di HP dan desktop              | ⏳ Setup VPS, Redis, queue worker, cron, HTTPS, `APP_DEBUG=false`, backup DB        |
| ⏳ Perbaikan bug tampilan                                 | ⏳ Pastikan `MELO_SANDBOX` benar per lingkungan (M12); ganti akun admin contoh      |
| ⏳ Bersama: transaksi nyata nominal kecil, serah terima   | ⏳ Daftarkan URL webhook production; bersama: transaksi nyata, serah terima          |

## 5. Alur Kerja Bersama

- **Branch:** satu branch per fitur dari `main`, dengan awalan `fe/` atau `be/` (mis. `fe/checkout-view`, `be/checkout-class`). PR ke `main` direview anggota lain. [REKOMENDASI]
- **Urutan fitur bersama:** Backend menetapkan kontrak (§3) → Frontend membangun tampilan → Backend menyambungkan logika. Frontend boleh mulai lebih dulu dengan data contoh dari factory/seeder.
- **Butuh data baru di view:** Frontend minta ke Backend, Backend menambahkannya ke controller/komponen dan mencatatnya di §3.3 atau §3.4.
- **Test:** Backend menulis feature test untuk logika. Jika perubahan tampilan mengubah perilaku yang dites (mis. `StorefrontTest`), pembuat perubahan memperbarui test-nya. Jalankan `npm run build` sebelum test supaya manifest Vite tersedia.
- **Dokumentasi:** setiap selesai tugas, pembuatnya memperbarui `CURRENT_STATUS.md` dan tanda ✅/⏳ di dokumen ini.

## 6. Titik Ketergantungan

| Yang dibutuhkan                                   | Dari → Untuk        | Paling lambat |
| ------------------------------------------------- | ------------------- | ------------- |
| Aset klien (logo, warna, daftar game) (E5)        | Klien → Frontend    | H5            |
| Kontrak checkout final (§3.4)                     | Backend → Frontend  | H11           |
| Route & data auth pelanggan + riwayat             | Backend → Frontend  | H13           |
| Field payment terisi (`payment_url`, `payment_code`, `expired_at`) | Backend → Frontend | H19 |
| Aturan tampil kredensial di invoice (M1)          | PO → Backend → Frontend | H23       |
| Tampilan semua halaman selesai untuk uji akhir    | Frontend → Bersama  | H27           |

Tanggal di kolom terakhir adalah [ASUMSI] turunan dari `IMPLEMENTATION_PLAN.md`; sesuaikan jika jadwal tahap berubah.
