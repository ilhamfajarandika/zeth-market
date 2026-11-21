# To-Do Frontend

> Aturan: **1 fitur = 1 branch** dari `origin/main` terbaru.

## 1. Bereskan Dulu
- [ ] Commit & push `README.md` (jika perlu), lalu merge PR `landing-page` → `main` di GitHub.

## 2. Kerjakan Sekarang (Tanpa Tunggu Backend)
- [x] **`fe/ui-components`** — Buat komponen `ui/*` & migrasikan `AppLayout.vue`, `Game.vue`, `Invoice.vue` ke tema Cyberpunk Dark Gaming (hilangkan warna ungu lama).
- [ ] **`fe/brand-logo`** — Satukan logo navbar/footer ke satu komponen `BrandLogo.vue`.
- [ ] **`fe/filament-branding`** — Sesuaikan warna (`Color::Violet` → brand), logo, dan font di panel admin Filament.
- [ ] **`fe/error-states`** — Buat tampilan halaman error (404) & empty state yang seragam.

## 3. Lanjut Setelah Kontrak Backend Siap
- [ ] **`fe/checkout-form`** — Sambungkan form checkout `Game.vue` (`useForm` ke `checkout.store`).
- [ ] **`fe/auth-pages`** — Buat halaman login & register pelanggan.
- [ ] **`fe/order-history`** — Buat halaman riwayat transaksi pelanggan.
- [ ] **`fe/invoice-payment`** — Tampilkan instruksi bayar (QR/VA/e-wallet) & countdown di `Invoice.vue`.
- [ ] **`fe/invoice-polling`** — Tambah polling status otomatis di `Invoice.vue`.
- [ ] **`fe/account-delivery`** — Tampilkan detail akun (salin per field) & status gagal/expired.

---

### Cara Mulai Branch Baru
```bash
git fetch origin
git switch -c fe/<nama-fitur> origin/main
```
