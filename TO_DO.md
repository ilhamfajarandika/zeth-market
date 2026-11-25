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
- [ ] **`fe/auth-pages`** — Buat halaman login & register pelanggan.
- [ ] **`fe/order-history`** — Buat halaman riwayat transaksi pelanggan.
- [ ] **`fe/invoice-payment`** — Tampilkan QRIS (pakai `chillerlan/php-qrcode` dari Backend), kode VA/e-wallet, & countdown `expiresAt` di `Invoice.vue`.
- [ ] **`fe/invoice-polling`** — Tambah polling status otomatis di `Invoice.vue` (callback Duitku & Melo sudah punya job di Backend).
- [ ] **`fe/account-delivery`** — Tampilkan detail akun (salin per field) & status gagal/expired.

---

### Cara Mulai Branch Baru
```bash
git fetch origin
git switch -c fe/<nama-fitur> origin/main
```
