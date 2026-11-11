# AI_WORKFLOW — Cara Kerja AI Coding Agent

> Berlaku untuk Claude Code dan agent lain. Aturan inti ada di [`AGENTS.md`](AGENTS.md); dokumen ini menjelaskan langkah kerjanya.

## 1. Aturan Wajib

1. Baca `docs/AGENTS.md` sebelum memulai pekerjaan.
2. Baca dokumen teknis di `docs/` yang berkaitan dengan tugas (tabel di `AGENTS.md` §2).
3. Audit kode yang sudah ada sebelum mengubahnya.
4. Buat rencana sebelum melakukan perubahan besar (lebih dari satu fitur, migration, atau lebih dari ±5 file).
5. Jangan mengubah arsitektur tanpa persetujuan.
6. Jangan membuat fitur di luar scope (`PROJECT_SPEC.md` §10).
7. Jangan mengubah file yang tidak berkaitan.
8. Jangan membuat duplikasi controller, model, service, atau migration.
9. Ikuti konvensi Laravel dan panduan Laravel Boost (`AGENTS.md`/`CLAUDE.md` root).
10. Jalankan testing setelah implementasi.
11. Laporkan file yang dibuat, diubah, atau dihapus.
12. Perbarui `docs/CURRENT_STATUS.md` setelah pekerjaan selesai.
13. Jangan menganggap pekerjaan selesai jika testing belum dilakukan.

## 2. Alur Kerja per Tugas

```mermaid
flowchart TD
    A[Terima tugas] --> B[Baca AGENTS.md + CURRENT_STATUS.md]
    B --> C[Baca dokumen teknis terkait]
    C --> D{Masuk scope & tidak konflik?}
    D -- Tidak/ragu --> E[Laporkan & minta konfirmasi PO]
    D -- Ya --> F[Audit kode terkait]
    F --> G{Perubahan besar?}
    G -- Ya --> H[Susun rencana → tunggu persetujuan]
    G -- Tidak --> I[Implementasi]
    H --> I
    I --> J[Tulis/ubah test]
    J --> K[php artisan test + pint]
    K -- Gagal --> I
    K -- Lulus --> L[Update docs terkait + CURRENT_STATUS]
    L --> M[Laporan akhir]
```

## 3. Langkah Rinci

1. **Pahami tugas.** Jika ambigu atau menyentuh keputusan [TBD], tanyakan dulu. Jangan mengarang aturan bisnis.
2. **Cek scope.** Fitur yang tidak ada di `PROJECT_SPEC.md` = minta persetujuan.
3. **Audit.** Cari kode yang bisa dipakai ulang:
   ```bash
   php artisan route:list --except-vendor
   php artisan model:show Order
   ```
   Lalu baca service dan enum terkait.
4. **Rencana** (untuk perubahan besar): file yang akan dibuat/diubah, migration, test, dan risiko.
5. **Implementasi.**
   - `php artisan make:* --no-interaction`.
   - Tidak menambah dependency tanpa izin.
   - Migration baru, bukan mengedit migration lama.
6. **Test.**
   ```bash
   php artisan test --compact --filter=NamaTest
   php artisan test --compact
   vendor/bin/pint --format agent
   ```
   HTTP eksternal di-fake. MySQL harus berjalan (DB test: `topup_game_test`).
7. **Dokumentasi.**
   - Perbarui `DATABASE_DESIGN.md` jika skema berubah.
   - Perbarui `API_INTEGRATION.md` jika integrasi berubah.
   - Selalu perbarui `CURRENT_STATUS.md`.
8. **Laporan.** Gunakan format di §4.

## 4. Format Laporan Akhir

```markdown
## Ringkasan
<apa yang dikerjakan, 1–3 kalimat>

## Perubahan File
- Dibuat: path/…
- Diubah: path/…
- Dihapus: path/… (dengan alasan)

## Migration
- <nama migration> / tidak ada

## Test
- Perintah: php artisan test --compact
- Hasil: N lulus, M gagal

## Belum Selesai / Risiko / Konflik
- …

## Dokumentasi
- CURRENT_STATUS.md diperbarui: ya
```

## 5. Checklist Sebelum Menyatakan Selesai

- [ ] Hanya file terkait tugas yang berubah.
- [ ] Tidak ada fitur di luar scope.
- [ ] Tidak ada secret di kode/dokumen/log.
- [ ] Test baru untuk perilaku baru; seluruh test lulus.
- [ ] `vendor/bin/pint --format agent` dijalankan.
- [ ] Dokumen `docs/` terkait diperbarui.
- [ ] `docs/CURRENT_STATUS.md` diperbarui (tanggal, selesai, masalah, berikutnya).
- [ ] Tidak ada `git commit/push` kecuali diminta.

## 6. Saat Menemukan Masalah

| Situasi | Tindakan |
|---|---|
| Dokumen bertentangan dengan kode | Laporkan. Jangan memilih sendiri (hierarki di `AGENTS.md` §3). |
| Keputusan bisnis belum ada | Tandai [TBD], tanyakan PO, kerjakan bagian lain. |
| Test lama gagal karena perubahan | Perbaiki kode, bukan menghapus/melemahkan test, kecuali disetujui. |
| Akses API eksternal belum ada | Kerjakan dengan `Http::fake()`, tandai "integrasi nyata tertunda". |
| Bug di luar tugas | Catat di `CURRENT_STATUS.md` → Masalah, jangan langsung diperbaiki. |
