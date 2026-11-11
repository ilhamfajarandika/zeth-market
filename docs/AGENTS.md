# AGENTS.md — Instruksi Utama untuk AI Coding Agent

> **Status file ini:** draf di `docs/AGENTS.md` (dibuat 2026-11-12) untuk dibandingkan dengan `AGENTS.md` / `CLAUDE.md` di root.
> Saat ini **belum aktif otomatis**: Claude Code hanya membaca `CLAUDE.md` di root. Cara mengaktifkannya [TBD]: pindah ke root, atau tambahkan `@docs/AGENTS.md` di `CLAUDE.md`.
> Sampai diputuskan, minta agent membaca file ini secara manual di awal sesi.

## 1. Identitas Proyek

| Item | Nilai |
|---|---|
| Nama | Website Top Up Game (`topup-game`) |
| Klien | Freelance, 1 klien |
| Budget | Rp4.500.000 |
| Waktu | 21 hari |
| Arsitektur | Monolith Laravel (bukan microservices) |
| Stack | Laravel 13 · PHP 8.4 · Filament 5 · Blade + Livewire + Alpine.js · Tailwind CSS 4 · Vite 8 · MySQL 8 |
| Integrasi | Digiflazz API v1 (supplier) · 1 payment gateway (kandidat: Tripay) |
| Deploy | VPS + Coolify atau Laravel Forge + Cloudflare |

Detail lengkap ada di [`docs/PROJECT_SPEC.md`](PROJECT_SPEC.md).

## 2. Wajib Dibaca Sebelum Bekerja

1. File ini (`docs/AGENTS.md`).
2. [`docs/PROJECT_SPEC.md`](PROJECT_SPEC.md): scope, fitur, dan apa yang **tidak** dikerjakan.
3. [`docs/CURRENT_STATUS.md`](CURRENT_STATUS.md): progres terakhir dan masalah terbuka.
4. [`docs/AI_WORKFLOW.md`](AI_WORKFLOW.md): cara kerja langkah demi langkah.
5. Dokumen teknis yang terkait dengan tugas:

| Tugas menyentuh… | Baca |
|---|---|
| Alur order, status, pembayaran | [`BUSINESS_FLOW.md`](BUSINESS_FLOW.md) |
| Struktur folder, lokasi file baru, route | [`SYSTEM_ARCHITECTURE.md`](SYSTEM_ARCHITECTURE.md) |
| Migration, model, query | [`DATABASE_DESIGN.md`](DATABASE_DESIGN.md) |
| Tripay / Digiflazz / webhook | [`API_INTEGRATION.md`](API_INTEGRATION.md) |
| Auth, validasi, data sensitif | [`SECURITY_RULES.md`](SECURITY_RULES.md) |
| Penjadwalan pekerjaan | [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md) |

Panduan Laravel Boost di `AGENTS.md`/`CLAUDE.md` root (blok `<laravel-boost-guidelines>`) **tetap berlaku** untuk konvensi Laravel, Pint, dan PHPUnit.

## 3. Hierarki Referensi

Jika informasi berbeda, ikuti urutan ini:

1. Instruksi terbaru dari Product Owner (PO).
2. `docs/PROJECT_SPEC.md`.
3. `docs/AGENTS.md` (file ini).
4. Dokumen teknis lain di `docs/`.
5. Source code aktual.

**Jika ada konflik antar sumber, jangan memutuskan sendiri.** Hentikan pekerjaan yang terdampak, laporkan konfliknya (sumber A vs sumber B), lalu minta konfirmasi PO.

Catatan: panduan Boost menyarankan Laravel Cloud untuk deploy. Proyek ini **tidak** memakai Laravel Cloud. Deploy = VPS (lihat §1).

## 4. Aturan Perubahan Kode

- Kerjakan **hanya** yang diminta dan yang ada di scope `PROJECT_SPEC.md`. Fitur di luar scope (mis. cek nickname, MLBB calc, reseller) tidak boleh dibuat tanpa persetujuan.
- Audit kode yang ada sebelum menulis kode baru. Pakai ulang service/model/enum yang sudah ada:
  - stok akun → `app/Services/AccountStockService.php`
  - payment → `app/Services/Payment/TripayService.php`
  - supplier → `app/Services/Supplier/DigiflazzService.php`
  - status → `app/Enums/*`
- Jangan membuat duplikat controller, model, service, enum, atau migration.
- Jangan mengubah arsitektur (folder dasar, pola service, monolith) tanpa persetujuan.
- Jangan mengubah file yang tidak berkaitan dengan tugas, termasuk merapikan, rename, atau reformat file lain.
- Buat file lewat `php artisan make:* --no-interaction`.
- Jalankan `vendor/bin/pint --format agent` setelah mengubah PHP.

## 5. Aturan Database

- Skema acuan: [`DATABASE_DESIGN.md`](DATABASE_DESIGN.md). Tabel atau kolom baru harus disetujui dan dicatat di dokumen itu.
- Perubahan skema selalu lewat **migration baru**. Migration yang sudah dijalankan di server bersama/production tidak boleh diedit.
- Operasi stok akun dan perubahan status order wajib di dalam `DB::transaction` dengan row lock (`lockForUpdate`).
- Order menyimpan **snapshot** nama produk dan harga. Harga tidak boleh dibaca ulang dari tabel produk setelah order dibuat.
- Jangan menjalankan `migrate:fresh` di database selain lokal/test.

## 6. Aturan Keamanan (ringkas, detail di `SECURITY_RULES.md`)

- Tidak ada secret di kode, dokumen, log, atau commit. Semua secret ada di `.env`.
- Setiap callback (payment & supplier) wajib diverifikasi signature-nya dan diproses idempotent.
- Harga dan total selalu dihitung di server, tidak pernah dari input user.
- Kolom `game_accounts.credentials` terenkripsi dan hanya ditampilkan ke pemilik order yang sudah sukses.

## 7. Aturan Testing

- Setiap perilaku baru atau perubahan perilaku harus punya feature test (PHPUnit), di `tests/Feature/`.
- Test memakai database `topup_game_test` (lihat `phpunit.xml`). Jangan arahkan test ke `topup_game`.
- HTTP ke Tripay/Digiflazz di test harus di-fake (`Http::fake()`). Jangan memanggil API asli dari test.
- Jalankan test tersempit dulu, lalu `php artisan test --compact` sebelum melapor selesai.
- **Pekerjaan belum dianggap selesai jika test belum dijalankan dan lulus.**

## 8. Aturan Dependency

- Jangan menambah package Composer/NPM atau layanan baru (mis. WA gateway, Horizon, Sentry) tanpa persetujuan PO.
- Jangan upgrade/downgrade versi framework atau package tanpa persetujuan.

## 9. Larangan

- Mengubah file tanpa alasan yang terkait tugas.
- Menghapus file atau data tanpa izin.
- `git commit` / `git push` tanpa diminta.
- Menulis API key, private key, atau password asli di mana pun selain `.env` lokal.
- Mengklaim fitur selesai tanpa verifikasi dari kode dan test.
- Mengarang aturan bisnis. Jika belum jelas, tandai **TBD** dan tanyakan.

## 10. Kewajiban Laporan

Setiap selesai satu tugas, laporkan:

1. Ringkasan apa yang dikerjakan.
2. File **dibuat / diubah / dihapus** (path lengkap).
3. Migration baru (jika ada).
4. Hasil test (perintah + jumlah lulus/gagal).
5. Hal yang belum selesai, risiko, atau konflik yang ditemukan.
6. Konfirmasi bahwa `docs/CURRENT_STATUS.md` sudah diperbarui.
