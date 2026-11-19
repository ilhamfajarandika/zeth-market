# FRONTEND_PLAN — Persiapan & Rencana Kerja Storefront

> Pemilik: Frontend (Ilham). Kontrak: [`TASK_DIVISION.md`](TASK_DIVISION.md) §3. Kebutuhan: [`PROJECT_SPEC.md`](PROJECT_SPEC.md). Status: dokumen kerja, bukan pengganti `docs/`.
> Dibuat: 2026-11-19. Tanpa aset klien (memakai placeholder). Perapian design system bersifat struktural.

## 1. Tujuan

Menyiapkan dan membangun storefront Vue 3 + Inertia (Home, Game, Invoice, Checkout, Auth, Riwayat) sesuai kontrak props backend, tanpa menghitung harga di klien, dan tanpa dependency baru tanpa persetujuan PO.

## 2. Prasyarat Lingkungan

- [ ] PHP 8.4, Composer, Node + npm, MySQL 8 (Laragon).
- [ ] `composer install`, `npm install`.
- [ ] `.env` dari `.env.example`; DB `topup_game` + `topup_game_test`; `php artisan key:generate`.
- [ ] `php artisan migrate --seed` (3 game, 10 produk, 7 stok akun).
- [ ] `npm run build` (manifest Vite wajib untuk `php artisan test`).
- [ ] `npm run dev` / `composer run dev` saat mengerjakan UI.
- [ ] Baseline: `php artisan test --compact` (harus 51 lulus).

## 3. Dokumen & Aturan Wajib

- [ ] [`AGENTS.md`](AGENTS.md) (aturan kerja, larangan, batas file).
- [ ] [`PROJECT_SPEC.md`](PROJECT_SPEC.md) (scope; di luar scope: cek nickname, MLBB calc, reseller).
- [ ] [`TASK_DIVISION.md`](TASK_DIVISION.md) §2–§3 (batas file + kontrak FE↔BE).
- [ ] [`BUSINESS_FLOW.md`](BUSINESS_FLOW.md) (status order & alur bayar).
- [ ] [`CURRENT_STATUS.md`](CURRENT_STATUS.md) (yang ada + M1–M12).
- [ ] [`SECURITY_RULES.md`](SECURITY_RULES.md) §9 & §11 (harga dari server; props eksplisit).

## 4. Stack & Konvensi

- Vue **3.5 JavaScript** (bukan TS), Composition API `<script setup>`.
- **Inertia 3** — tanpa Vue Router, tanpa Ziggy; URL dari props (`links`, `url`, `checkoutUrl`).
- **Tailwind 4** CSS-first (`@theme` di `resources/css/app.css`), tema gelap violet/fuchsia, font Instrument Sans.
- Tanpa Pinia; state lokal `shallowRef`/`computed`.
- Skill: `vue-best-practices`, `tailwindcss-development`, `design-taste-frontend`, `antislop-*`.

## 5. Baseline Frontend (`branch-ilham`)

- Ada: `Home.vue`, `Game.vue`, `Invoice.vue`, `AppLayout.vue`, `GameCover.vue`, `StatusBadge.vue`, `CopyButton.vue`, `app.js`, `app.blade.php`, `app.css`.
- `Game.vue` masih pratinjau (tombol Beli nonaktif, metode bayar contoh).
- Belum ada: checkout, auth, riwayat, halaman error, countdown, polling status.

## 6. Strategi Tanpa Aset Klien

- Brand placeholder: tetap tema violet/fuchsia + wordmark teks (huruf awal nama) seperti sekarang.
- Komponen `BrandLogo` terpusat agar logo asli tinggal ganti satu tempat.
- Gambar game: fallback gradien inisial (`GameCover` sudah mendukung).
- Tidak ada dependensi gambar/ikon eksternal baru; ikon memakai SVG inline.
- Semua slot aset ditandai `[PLACEHOLDER]` agar mudah ditukar saat aset datang.

## 7. Perapian Design System (yang dirapikan & diubah)

Perapian bersifat **struktural**, bukan desain ulang — tampilan akhir dibuat setara dengan v1. Semua di `resources/js/components/**` dan `resources/css/**` (wilayah Frontend).

| Area | Kondisi sekarang | Perubahan | Alasan |
|---|---|---|---|
| Token warna | Utility hardcoded (`violet-500`, `slate-950`, `white/10`) tersebar | Tambah token semantik di `@theme` (`--color-brand`, `--color-surface`, `--color-surface-raised`, `--color-border`, `--color-muted`, `--color-success/warning/danger/info`) dipetakan ke nilai yang sama | Satu sumber warna, ganti brand mudah |
| Kartu | String `rounded-2xl border border-white/10 bg-white/5` berulang | `ui/BaseCard.vue` | Konsisten, kurangi duplikasi |
| Tombol | Class inline berbeda-beda per halaman | `ui/BaseButton.vue` (varian primary/secondary/ghost, ukuran, disabled/loading) | Konsisten + state jelas |
| Input | `inputClass` lokal di `Game.vue` | `ui/BaseInput.vue`, `ui/BaseSelect.vue`, `ui/BaseCheckbox.vue` | Validasi/error/disabled seragam |
| Badge | `StatusBadge.vue` khusus order | `ui/BaseBadge.vue` generik + `StatusBadge` memakainya | Pakai ulang untuk stok/status |
| Alert | Markup ad-hoc (mis. invoice error) | `ui/BaseAlert.vue` (info/success/warning/danger) | Pesan error/sukses seragam |
| Loading/empty | Teks "Game tidak ditemukan" manual | `ui/BaseSkeleton.vue`, `ui/EmptyState.vue` | State konsisten |
| Logo | Huruf awal di `AppLayout` | `BrandLogo.vue` | Titik tukar aset |
| Halaman | Home/Game/Invoice memakai class inline | Migrasi ke komponen `ui/*` (markup & tampilan tetap) | Maintainability |

Catatan: file baru di `resources/js/components/ui/`. Tidak mengubah `app.js`, `app.blade.php`, atau dependency. Setelah eksekusi, tabel ini diubah menjadi changelog "sebelum → sesudah" dengan status ✅.

## 8. Keputusan TBD yang Memengaruhi UI (tunggu PO)

- [ ] Provider payment final → daftar metode bayar.
- [ ] Kontak guest wajib: email / WhatsApp / salah satu.
- [ ] Penanggung biaya admin (`fee`) & markup.
- [ ] Masa berlaku invoice/reservasi (rekomendasi 15 menit) → countdown.
- [ ] Aturan akses invoice/kredensial (M1).
- [ ] Kanal notifikasi (email/WA).
- [ ] Cakupan login/riwayat & verifikasi email.

## 9. Kontrak FE↔BE yang Perlu Difinalkan Backend

- [ ] `checkout.store`: `product_id`, `target_user_id`, `target_zone_id`, `email`, `whatsapp`, `payment_method`, `id_confirmed`; error per field; stok habis → error `product_id` ([`TASK_DIVISION.md`](TASK_DIVISION.md) §3.4).
- [ ] Prop `Game`: `paymentChannels[]` (`code`, `name`, `group`, `iconUrl`) + `checkoutUrl`.
- [ ] Route & props auth (login/register) serta riwayat transaksi.
- [ ] Kontrak polling status invoice (interval/endpoint) [REKOMENDASI].
- [ ] Semua angka Rupiah sudah diformat server; frontend tidak menghitung.

## 10. Rencana Kerja (selaras `TASK_DIVISION`)

### Tahap 2 — Design system & branding

- [ ] Terapkan token warna/typografi (§7).
- [ ] Buat komponen `ui/*` dan migrasikan Home/Game/Invoice.
- [ ] `BrandLogo` + branding panel Filament (logo/warna/font) — bagian branding saja.

### Tahap 3 — Checkout & Auth

- [ ] `Game.vue` → `useForm` ke `checkout.store`: ID/Zone (validasi format dari props), produk, kontak, metode bayar, `id_confirmed`.
- [ ] Error validasi per field + pesan stok habis.
- [ ] Halaman login/register, riwayat transaksi, cek status order, 404/error.

### Tahap 4 — Pembayaran

- [ ] Invoice: instruksi bayar (QR/VA/e-wallet), tombol bayar, countdown `expiresAt`.
- [ ] Polling status (Inertia reload) + label/warna status.

### Tahap 5 — Stok akun & hasil

- [ ] Detail akun (salin per field), tampilan gagal/expired + langkah lanjut.
- [ ] Template email notifikasi (jika disetujui).

### Tahap 6 — QA

- [ ] Uji manual HP & desktop, perbaikan bug tampilan.

## 11. Alur Kerja & Batas File

- Branch `fe/<nama>` dari `main`; PR direview Backend/PO.
- Wilayah Frontend: `resources/js/{pages,layouts,components}/**`, `app.js`, `app.blade.php`, `resources/css/**`, aset `public/`, branding Filament.
- Jangan ubah controller/props/routes/migrations/tests — minta Backend.
- Selesai tugas: perbarui [`CURRENT_STATUS.md`](CURRENT_STATUS.md) + tanda ✅ di [`TASK_DIVISION.md`](TASK_DIVISION.md).

## 12. Verifikasi Sebelum Melapor

- [ ] `npm run build` sukses.
- [ ] `php artisan test --compact` tetap lulus (termasuk `StorefrontTest`).
- [ ] Responsif 360/768/1280px, keyboard nav, kontras, `aria`.
- [ ] Tidak ada harga/total yang dihitung di Vue.

## 13. Risiko & Dependensi

- Aset brand & keputusan payment bisa menahan desain final (placeholder dipakai sementara).
- Kontrak checkout final dibutuhkan sebelum form dibuat (target H11).
- Perubahan props menyentuh `StorefrontTest`; koordinasi dengan Backend.
- Perapian design system menyentuh banyak file Frontend; lakukan bertahap dan jalankan `npm run build` tiap langkah.
