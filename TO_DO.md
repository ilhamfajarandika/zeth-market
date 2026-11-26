# To-Do Frontend

> Aturan: **1 fitur = 1 branch** dari `origin/main` terbaru.

## 1. Bereskan Dulu
- [x] Merge PR `landing-page`, `ui-components`, `brand-logo`, dan `admin-panel` ke `main`.
- [x] Jalankan `composer install && php artisan migrate` di lokal (PR #8 menambah `chillerlan/php-qrcode`, `laravel-debugbar`, & 2 migration baru).
- [x] Pulihkan seeder 17 game + ikon WebP + `BannerSeeder` (tertimpa saat merge PR #8 di `DatabaseSeeder.php`).

## 2. Kerjakan Sekarang (Tanpa Tunggu Backend)
- [x] **`fe/ui-components`** — Buat komponen `ui/*` & migrasikan `AppLayout.vue`, `Game.vue`, `Invoice.vue` ke tema Cyberpunk Dark Gaming (hilangkan warna ungu lama).
- [x] **`fe/brand-logo`** — Satukan logo navbar/footer ke satu komponen `BrandLogo.vue`.
- [x] **`fe/filament-branding`** — Sesuaikan warna (`Color::Violet` → brand), logo, dan font di panel admin Filament.
- [x] **`fe/error-states`** — Buat tampilan halaman error (404) & empty state yang seragam.

## 3. Lanjut Setelah Kontrak Backend Siap
- [ ] **`fe/checkout-form`** — Sambungkan form checkout `Game.vue` (`useForm` ke `checkout.store` + channel Duitku).
- [x] **`fe/auth-pages`** — Buat halaman login & register pelanggan.
- [x] **`fe/order-history`** — Buat halaman riwayat transaksi pelanggan.
- [ ] **`fe/invoice-payment`** — Tampilkan QRIS (pakai `chillerlan/php-qrcode` dari Backend), kode VA/e-wallet, & countdown `expiresAt` di `Invoice.vue`.
- [ ] **`fe/invoice-polling`** — Tambah polling status otomatis di `Invoice.vue` (callback Duitku & Melo sudah punya job di Backend).
- [ ] **`fe/account-delivery`** — Tampilkan detail akun (salin per field) & status gagal/expired.

## 4. Checklist Testing (`filament-branding` → `order-history`)

> Prasyarat: nyalakan **MySQL (Laragon Start All)**, jalankan `npm run build` dan `php artisan serve --host=127.0.0.1 --port=8000`.

- [ ] **Automated Test:** jalankan `php artisan test --compact tests/Feature/CustomerAuthTest.php tests/Feature/OrderHistoryTest.php` (semua harus lulus).
- [ ] **`filament-branding`:** buka `/admin/login`, login (`admin@example.com` / `password`) → cek logo Zeth Market, warna merah brand, tema gelap Zinc, dan font rapi.
- [ ] **`error-states`:** buka `/halaman-tidak-ada` → tampil halaman **404** Cyberpunk; di `/` cari kata acak di katalog → tampil `EmptyState` + tombol **Reset filter**.
- [ ] **`auth-pages`:** buka `/register` → daftar akun baru → cek navbar berubah jadi **Riwayat** & **Keluar**; klik **Keluar**, lalu uji `/login` (password salah → muncul error; password benar → masuk).
- [ ] **`order-history`:** saat logout buka `/orders` → redirect ke `/login`; setelah login buka `/orders` → cek tampilan kosong/daftar transaksi, filter status, pencarian, dan klik **Lihat invoice**.

---

### Cara Mulai Branch Baru
```bash
git fetch origin
git switch -c fe/<nama-fitur> origin/main
```
