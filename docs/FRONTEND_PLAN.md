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
- **Tailwind 4** CSS-first (`@theme` di `resources/css/app.css`), tema gelap matte bertema gaming (Dark Tactical / Deep Charcoal dengan aksen Cyber Gold/Amber, **bukan ungu/violet**), font Instrument Sans / Sans-serif modern.
- Tanpa Pinia; state lokal `shallowRef`/`computed`.
- Skill & panduan acuan: `vue-best-practices`, `tailwindcss-development`, `design-taste-frontend`, `antislop-*` (khususnya `antislop-ui` & `antislop-copywriting`).

## 5. Baseline Frontend (`branch-ilham`)

- Ada: `Home.vue`, `Game.vue`, `Invoice.vue`, `AppLayout.vue`, `GameCover.vue`, `StatusBadge.vue`, `CopyButton.vue`, `app.js`, `app.blade.php`, `app.css`.
- `Game.vue` masih pratinjau (tombol Beli nonaktif, metode bayar contoh).
- Belum ada: checkout, auth, riwayat, halaman error, countdown, polling status.

## 6. Strategi Tanpa Aset Klien

- Brand placeholder: memakai tema gaming taktis (Deep Charcoal/Carbon base + aksen Cyber Amber/Gold, **meninggalkan warna ungu/fuchsia**) + wordmark teks tegas.
- Komponen `BrandLogo` terpusat agar logo asli tinggal ganti satu tempat tanpa merombak layout.
- Gambar game: fallback kartu inisial game solid dengan border tajam dan tipografi tegas (`GameCover`), menghindari gradien pelangi/blur.
- Tidak ada dependensi gambar/ikon eksternal baru; ikon memakai SVG inline yang bersih dan relevan.
- Semua slot aset ditandai `[PLACEHOLDER]` agar mudah ditukar saat aset resmi dari klien/developer tiba.

## 7. Ketentuan Desain Storefront (Anti-Slop, Tanpa Ungu & Khusus Top Up Game)

> **Prinsip Utama:** Website top up game adalah platform **e-commerce utilitarian berkecepatan tinggi**, bukan landing page portofolio atau demo AI generic. Pengguna datang dengan tujuan spesifik: memilih nominal, membayar, dan menerima voucher/diamond dalam waktu kurang dari 1 menit. Setiap elemen visual harus mendukung alur belanja yang cepat, jelas, dan dapat dipercaya.
>
> **Catatan revisi 2026-11-19:** §7.1–§7.5 adalah aturan anti-slop yang tetap berlaku. Untuk **landing page (Home)**, arah visual yang dipakai adalah §7.6 (Acid Arcade), bukan palet amber di §7.2. Game/Invoice menyusul setelah arah ini disetujui.

### 7.1 Larangan Keras Elemen "AI-Slop" Generic
- 🚫 **Dilarang Skema Warna Ungu/Violet/Fuchsia:** Hindari palet default model AI (`violet-500`, `fuchsia-500`, `purple glow`) yang sering menjadi tanda instan template AI buatan mesin tanpa identitas brand.
- 🚫 **Dilarang Gradien Berlebihan (Gradient Soup):** Tidak ada background mesh 3-warna, tidak ada bola cahaya buram di belakang layar (`radial-gradient` orbs / `blur-3xl`), dan tidak ada teks bergradien (`bg-clip-text text-transparent`) pada setiap heading.
- 🚫 **Dilarang Glassmorphism Ekstrem:** Hindari `backdrop-blur` tebal di semua kartu sekaligus yang membuat hierarki visual rata dan membebani performa browser mobile.
- 🚫 **Dilarang Emoji sebagai Dekorasi UI:** Hapus emoji hiasan yang disematkan di judul, badge, atau tombol (mis. 🚀, 🔥, ⚡, 🔒, ✅, ℹ️). Makna antarmuka harus disampaikan lewat tipografi yang jelas dan ikon SVG fungsional.
- 🚫 **Dilarang Indikator Status Palsu (Pulsing Dot Slop):** Tidak boleh ada titik hijau/merah yang berkedip dan berdenyut tanpa henti (`animate-pulse`) pada label statis yang tidak menandakan status live/recording riil.
- 🚫 **Dilarang Bento Grid atau Card Raksasa yang Kosong:** Struktur harus mengikuti kebutuhan data riil produk top up, bukan grid mosaik hiasan.

### 7.2 Palet Warna: Dark Tactical Gaming (Solid, High-Contrast & Utilitarian)
Karakter warna mengambil inspirasi dari storefront gaming profesional (seperti platform esports, Codashop, Steam, atau dashboard perlengkapan gaming) yang tegas dan kontras tinggi:

| Peran Warna | Token Semantik | Nilai Acuan / Karakter | Penggunaan |
|---|---|---|---|
| **Base Ground** | `--color-ground` | `#090b10` / `zinc-950` solid | Latar belakang utama aplikasi (matte murni, tanpa blur orb). |
| **Surface Flat** | `--color-surface` | `#11141c` / `zinc-900` solid | Kartu konten, kontainer langkah pembelian, panel rincian. |
| **Surface Raised** | `--color-surface-raised` | `#181d28` / `zinc-850` | Input form, item yang sedang di-hover, bilah dropdown. |
| **Border Normal** | `--color-border` | `rgba(255, 255, 255, 0.08)` | Garis batas tipis 1px yang tajam dan tegas antar komponen. |
| **Border Active** | `--color-border-active` | `rgba(245, 158, 11, 0.6)` / Amber solid | Menandai kartu denom/metode bayar yang sedang dipilih pembeli. |
| **Primary Brand** | `--color-brand` | `#f59e0b` (Amber-500) / `#fbbf24` (Amber-400) | Aksen utama: Tombol aksi transaksi (CTA), badge promo diskon, harga terpilih. Melambangkan koin emas, kredit game, dan energi taktis. |
| **Brand Hover/Active** | `--color-brand-hover` | `#d97706` (Amber-600) | State aktif/hover pada tombol utama. |
| **Text Primary** | `--color-text` | `#f8fafc` / `slate-50` solid | Judul game, nominal harga, nilai invoice (kontras tertinggi). |
| **Text Muted** | `--color-muted` | `#94a3b8` / `slate-400` | Petunjuk, label ID game, deskripsi sekunder. |
| **Status Sukses** | `--color-success` | `#10b981` (Emerald-500) | Order lunas, SN pengiriman berhasil, stok tersedia banyak. |
| **Status Warning** | `--color-warning` | `#f59e0b` (Amber-500) | Menunggu pembayaran, stok menipis (<= 3). |
| **Status Bahaya** | `--color-danger` | `#ef4444` (Rose-500) | Pembayaran kedaluwarsa/gagal, stok akun habis. |

> **Aturan Single Accent:** Warna aksen emas/amber hanya boleh hidup di 1–2 titik fokus per layar (mis. tombol beli utama atau kartu yang sedang aktif dipilih). Jangan mewarnai seluruh border atau background dengan warna aksen agar tombol aksi tidak kehilangan kekuatan visualnya.

### 7.3 Estetika Permukaan & Geometri: Matte, Crisp & Taktil
1. **Permukaan Matte Bersih:** Menggunakan permukaan solid bertingkat dengan kontras 1px border halus (`border-white/10`). Hindari transparansi berlebihan.
2. **Radius Proporsional:**
   - Kartu game & container section: `rounded-xl` (12px–14px).
   - Tombol, input, dan kartu nominal item: `rounded-lg` (8px–10px).
   - Hindari bentuk pil raksasa (`rounded-full`) untuk kartu atau input; bentuk pil hanya diizinkan untuk badge status kecil atau tag kategori.
3. **Elevasi & Shadow:**
   - Gunakan bayangan flat/terarah yang sangat halus (`shadow-sm` atau `shadow-md` gelap). Hindari bayangan melayang berukuran besar yang membuat antarmuka terasa mengapung tanpa gravitasi.

### 7.4 Komponen & Tata Letak Khusus Top Up Game

#### A. Katalog Game & Beranda (Home)
- **Banner Promo Terarah (Bukan Hero AI):** Hero section langsung berfungsi sebagai papan promo singkat/banner penawaran game populer, bukan teks puisi generic ("Membuka Pengalaman Bermain").
- **Grid Poster Game Rasio 1:1 atau 3:4:** Kartu game dirancang padat dengan gambar cover yang jelas, judul game tebal (maksimal 2 baris), dan nama publisher. Rasio visual tinggi memudahkan scanning mata dalam hitungan detik.
- **Filter & Quick Search:** Input pencarian cepat di bagian atas katalog dengan pemfilteran kategori tab instan (Semua, Mobile, PC, Voucher) tanpa jeda reload.
- **Cek Pesanan Fungsional:** Widget pelacak invoice ringkas dan mudah diakses di bagian atas atau bawah katalog.

#### B. Alur Pembelian Halaman Game (`Game.vue`)
Mengadopsi alur terstruktur standar industri gaming Indonesia yang terbukti memiliki konversi tertinggi:
1. **Langkah 1 — Input Akun:**
   - Kolom User ID dan Zone ID berdampingan rapi dengan label yang jelas sesuai game.
   - Tombol/link panduan *"Di mana menemukan User ID?"* membuka modal ilustrasi atau panduan ringkas, tanpa merusak kerapian form.
2. **Langkah 2 — Kartu Denominasi Produk (Tactile Denom Cards):**
   - Disusun dalam grid responsif (2 kolom di mobile, 3–4 kolom di desktop).
   - Tampilan per kartu: Nama item jelas, harga Rupiah tebal dan kontras, harga coret (bila diskon), dan indikator stok riil untuk produk tipe akun.
   - **Active State:** Kartu yang diklik mendapatkan border aksen solid (`border-amber-400`), latar sedikit terangkat, dan ikon centang/indikator aktif yang tegas di sudut kartu.
3. **Langkah 3 — Pemilihan Metode Pembayaran:**
   - Dikelompokkan rapi berdasarkan kategori channel: **QRIS** (paling populer), **E-Wallet** (GoPay, OVO, DANA, ShopeePay), dan **Virtual Account** (BCA, Mandiri, BRI, BNI).
   - Setiap channel menampilkan nama jelas, estimasi waktu verifikasi otomatis, serta logo/ikon channel.
4. **Langkah 4 — Konfirmasi Kontak & Eksekusi:**
   - Input nomor WhatsApp / Email untuk pengiriman bukti invoice.
   - Checkbox konfirmasi kebenaran data ID: *"Saya menyatakan User ID & Server yang dimasukkan sudah benar."*
   - Tombol utama **"Beli Sekarang"** yang solid, mencantumkan total harga secara transparan, memenuhi lebar penuh di layar mobile, dan memberikan feedback taktil saat ditekan.

#### C. Halaman Invoice & Struk Digital (`Invoice.vue`)
- **Format Struk Digital Bersih:** Format receipt/struk transaksi yang jelas: nomor invoice berukuran besar dengan tombol salin instan 1-klik, status transaksi dengan kontras tinggi, dan rincian harga (Harga Produk + Biaya Admin = Total Bayar).
- **Instruksi Bayar Terisolasi:**
  - Untuk QRIS: Kotak kode QR dengan kontras tinggi yang siap dipindai langsung atau diunduh tangkapan layarnya.
  - Untuk Virtual Account / Kode Bayar: Kode pembayaran monospaced besar dengan tombol salin (copy) cepat dan petunjuk cara transfer ringkas.
  - Countdown timer batas waktu pembayaran yang presisi.
- **Penyerahan Kredensial Akun (Setelah Order Sukses):**
  - Kartu aman khusus hasil pembelian akun (username, password, dan catatan login) yang hanya terbuka setelah status `success`, dilengkapi tombol salin per baris untuk kemudahan pengguna.
- **Serial Number (Top Up Sukses):** Kotak bukti serial number (SN) dari supplier yang rapi dan mudah dicatat untuk klaim ke customer service game jika diperlukan.

### 7.5 Mikro-Interaksi & Copywriting Storefront

- **Copywriting Lugas & Ramah Gamer:** Gunakan istilah yang natural bagi komunitas gaming lokal: *"Masukkan User ID"*, *"Pilih Nominal"*, *"Metode Pembayaran"*, *"Total Bayar"*, *"Salin Kode"*, *"Proses Otomatis"*. Hindari bahasa korporat kaku atau terjemahan mesin yang janggal.
- **Interaksi Taktil (Hover & Press Feedback):**
  - Tombol aksi merespons tekanan (`active:scale-[0.98]` atau step down 1px) saat ditekan di mobile.
  - Kartu produk terangkat halus 1–2px saat di-hover pada desktop.
  - Feedback salin (copy) yang responsif: ikon berubah centang hijau selama 1.5 detik dengan teks *"Tersalin!"*.
- **Motion Restraint:** Dilarang menggunakan animasi berlebihan (no continuous bounce, no parallax scrolling pada teks). Semua transisi state (opacity, background) berdurasi 150ms–200ms dengan timing curve yang responsif.

### 7.6 Arah Desain Landing v2 — Acid Arcade (2026-11-19)

Landing v1 dinilai terlalu mainstream. Arah v2 mengganti palet amber dan bentuk bulat dengan sistem yang lebih tegas, tetap tanpa ungu.

**Palet & token (`resources/css/app.css`)**

| Token | Nilai | Peran |
|---|---|---|
| `--color-ink` | `#0b0b0c` | Latar utama, header, footer |
| `--color-surface` | `#141416` | Panel, kartu, form |
| `--color-paper` | `#f2f0e9` | Teks utama, tombol hover |
| `--color-acid` | `#d7ff3f` | Satu aksen utama: CTA, label aktif, marquee |
| `--color-electric` | `#4de1ff` | Aksen sekunder terbatas (data/label) |
| `--color-signal` | `#ff5c35` | Penanda peringatan/status |
| `--color-line` | `rgba(242,240,233,0.14)` | Border 1px |

**Tipografi:** Space Grotesk untuk display/judul, Instrument Sans untuk body, monospace untuk label dan meta. Dimuat lewat `bunny()` di `vite.config.js`.

**Geometri & gerak:** sudut tajam (tanpa `rounded-full` pada kartu/input), border 1px, offset shadow keras (`shadow-[10px_10px_0_0_#d7ff3f]`), marquee tipis satu baris, hero masuk sekali (`animate-enter`), kartu naik 2px + cover zoom 1.03 saat hover, hormati `prefers-reduced-motion`.

**Struktur Home:** hero satu layar (katalog indeks 2x2 dari data asli), marquee, katalog + pencarian + tab kategori, cara top up, band fakta acid, FAQ, cek pesanan (pesan inline, tanpa redirect), CTA akhir. Layout khusus `resources/js/layouts/LandingLayout.vue`; `Game.vue`/`Invoice.vue` tetap memakai `AppLayout` sampai arah ini disetujui.

**Larangan yang tetap berlaku:** tanpa violet/lavender, tanpa blur orb, tanpa teks bergradien, tanpa emoji dekoratif, tanpa pulsing dot palsu, tanpa klaim yang tidak ada di `docs/`.

---

## 8. Perapian Design System (yang dirapikan & diubah)

Perapian bersifat **struktural**, menggantikan utility hardcoded lama menjadi token semantik bertema dark gaming taktis di `resources/js/components/**` dan `resources/css/**` (wilayah Frontend).

| Area | Kondisi sekarang (v1) | Perubahan (Sesuai Ketentuan Baru) | Alasan |
|---|---|---|---|
| Token warna | Hardcoded `violet-500`, `fuchsia-600`, `slate-950`, gradien ungu tersebar | Tambah token semantik gaming di `@theme` (`--color-brand: #f59e0b`, `--color-surface: #11141c`, `--color-ground: #090b10`, `--color-border`, `--color-text`, `--color-success/warning/danger`) | Menghilangkan slop ungu, mudah disesuaikan bila klien membawa aset warna baru |
| Background & Hero | Gradien ungu/fuchsia 3-layer + bola blur `blur-3xl` | Background matte bersih gelap (`#090b10`), hero banner game fungsional tanpa blur orb | Tampilan profesional, bersih, tidak boros render GPU di HP |
| Kartu | String `rounded-2xl border border-white/10 bg-white/5` berulang | `ui/BaseCard.vue` dengan varian matte surface (`default`, `raised`, `active`) | Konsisten, border tajam, bebas gradien berlebih |
| Tombol | Class inline violet berbeda-beda per halaman | `ui/BaseButton.vue` (varian `primary` beraksen amber solid, `secondary`, `ghost`, ukuran, feedback press) | CTA belanja tegas, kontras tinggi, mudah di-tap |
| Input | `inputClass` lokal di `Game.vue` dengan ring ungu | `ui/BaseInput.vue`, `ui/BaseSelect.vue`, `ui/BaseCheckbox.vue` dengan fokus amber | Validasi seragam, kontras teks tinggi |
| Badge | `StatusBadge.vue` khusus order | `ui/BaseBadge.vue` generik + `StatusBadge` (varian `success`, `warning`, `danger`, `neutral` solid tanpa glow liar) | Status order dan stok akun terbaca jelas |
| Alert | Markup ad-hoc (mis. pesan error invoice) | `ui/BaseAlert.vue` (pesan kesalahan/instruksi terstruktur) | Komunikasi error/instruksi pembayaran seragam |
| Loading/empty | Teks manual / skeleton ungu | `ui/BaseSkeleton.vue`, `ui/EmptyState.vue` berpalet netral | State loading dan pencarian kosong rapi |
| Logo | Huruf awal di `AppLayout` | `BrandLogo.vue` berbasis wordmark teks gaming tegas | Titik tukar logo resmi klien |
| Halaman | Home/Game/Invoice memakai class inline ungu & emoji hiasan | Migrasi ke komponen `ui/*`, hapus emoji dekoratif, terapkan alur taktis gaming | Bebas AI-slop, UX e-commerce top up standar industri |

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

- [ ] Terapkan token warna/typografi & panduan anti-slop (§7 & §8).
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

### 11.1 Aturan Branch (satu fitur = satu branch)

- Setiap mau menambahkan sesuatu (fitur baru, perbaikan bug, atau perapian), **buat branch baru dari `main` terbaru** sebelum menulis kode.
- **Satu branch hanya untuk satu fitur/perubahan.** Jangan mencampur beberapa fitur dalam satu branch atau satu PR.
- Format nama: `fe/<slug-fitur>` (kebab-case, huruf kecil), mis. `fe/checkout-form`.
- Alur: `git switch main` → `git pull` → `git switch -c fe/<slug-fitur>` → kerjakan → commit → PR ke `main` → review Backend/PO → merge → hapus branch.
- Perubahan kecil yang berbeda topik tetap dibuat branch sendiri; jangan menumpang di branch fitur lain.
- Jika branch sudah ter-merge dan mulai tugas baru, ulangi dari `main` terbaru (jangan lanjut di branch lama).

### 11.2 Branch yang Direncanakan

| Pekerjaan (§10)                          | Branch yang disarankan     |
| ---------------------------------------- | -------------------------- |
| Token warna/typografi                    | `fe/design-tokens`         |
| Komponen `ui/*` + migrasi halaman        | `fe/ui-components`         |
| `BrandLogo`                              | `fe/brand-logo`            |
| Branding panel Filament                  | `fe/filament-branding`     |
| Form checkout (`useForm`)                | `fe/checkout-form`         |
| Halaman login/register                   | `fe/auth-pages`            |
| Riwayat transaksi                        | `fe/order-history`         |
| Instruksi bayar + countdown di invoice   | `fe/invoice-payment`       |
| Polling status invoice                   | `fe/invoice-polling`       |
| Detail akun & order gagal/expired        | `fe/account-delivery`      |
| Halaman error / empty state              | `fe/error-states`          |
| Perbaikan bug hasil QA                   | `fe/qa-fixes`              |

### 11.3 Batas File

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
