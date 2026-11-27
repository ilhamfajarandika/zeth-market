# Permintaan Keputusan: Cek Nickname (Username) Melo

> **Tanggal:** 2026-11-27 · **Status:** disetujui PO untuk pelanggan (2026-11-27); endpoint dan UI pelanggan belum dibuat (lihat §9) · **Kode:** `MeloService::cachedNickname()` sudah ada

## 1. Permintaan

Dev meminta fitur cek username pemain game lewat Melo (`POST /check-nickname`). Hasil pengecekan disimpan di cache selama 1 hari, supaya pengecekan berulang untuk pemain yang sama tidak memanggil API Melo lagi.

## 2. Kenapa perlu keputusan

Cek nickname sudah ditandai di luar scope proyek:

| Sumber | Isi |
| --- | --- |
| `docs/PROJECT_SPEC.md` §10 | "Cek nickname / cek ID (mis. cek ID FF)": **[FINAL] permintaan klien** |
| `CLAUDE.md` | "There is no nickname/ID lookup by design (only regex format validation per game)." |
| `docs/AGENTS.md` §4 | Fitur cek nickname, termasuk `MeloService::checkNickname()`, tidak boleh dibuat tanpa persetujuan |
| `docs/API_INTEGRATION.md` | Cek nickname memakai kuota atau biaya, meski hasilnya "tidak ditemukan" |

Karena itu implementasi menunggu persetujuan PO. Jika disetujui, dokumen spesifikasi juga perlu diubah.

## 3. Fakta dari Melo

- **Billing mengikuti mode akun H2H, bukan `MELO_SANDBOX`.** Tidak ada sandbox per request. Cek ini bisa memakai saldo atau kuota nyata walaupun aplikasi berjalan dalam mode sandbox.
- **Kuota saat ini** (dibaca dari `GET /check-nickname/plan` di dashboard admin): 40 cek gratis per hari, maks 5 per menit. Setelah kuota gratis habis, **1 MC per cek**, maks 20 per menit.
- **"Tidak ditemukan" tetap memakai jatah.** Deskripsi `POST /check-nickname` di spesifikasi menulis bahwa hasil ditemukan maupun tidak ditemukan memakai kuota atau biaya. Contoh respons 404 menampilkan `charged_mc: 0` dan `free_remaining_today: 39`, sama seperti contoh 200. Contoh itu lebih cocok dengan satu jatah gratis yang terpakai, bukan nol. Belum dikonfirmasi ke Melo.
- **Respons 200** berisi `username`, `region`, dan `billing.free_remaining_today`.
- **Kode gagal:** 402 (saldo tidak cukup), 429 (batas tercapai), 502 (gangguan Melo).

## 4. Usulan teknis (jika disetujui)

1. Hasil disimpan di cache dengan TTL 1 hari (86.400 detik), key `game_code + customer_target + zone`. Cache proyek memakai driver `database`, jadi tidak perlu tabel baru.
2. Yang di-cache: respons 200, dan 404 (lihat pertanyaan 3). Respons 402, 429, dan 502 tidak di-cache.
3. Pengecekan dengan key yang sama dalam 24 jam tidak memanggil Melo lagi.

## 5. Yang sudah ada di kode

- `MeloService::checkNickname()`, `nicknameGames()`, dan `nicknamePlan()` sudah ada, tetapi belum dipakai alur mana pun.
- Widget "Cek nickname gratis / berbayar" di dashboard admin sudah dibuat. Widget ini hanya membaca kuota (`nicknamePlan()`) dan ikut menunggu keputusan ini. Jika opsi A dipilih, widget ini perlu dicabut.

## 6. Keputusan yang diminta

### Pertanyaan 1: cakupan

| Opsi | Arti | Yang perlu diubah |
| --- | --- | --- |
| **A. Tolak** | Cek nickname tetap di luar scope. | Widget kuota dicabut. Dokumen tidak berubah. |
| **B. Setujui untuk admin saja** | Admin bisa cek username dari panel admin, misalnya untuk verifikasi manual. Storefront tetap tanpa cek. | `PROJECT_SPEC.md` §10 dan `CLAUDE.md` perlu catatan pengecualian. |
| **C. Setujui untuk pelanggan** | Pelanggan bisa cek username di halaman game sebelum checkout. | Spesifikasi alur order, UI, batas pemakaian per pelanggan/IP, dan kebijakan privasi. Ini perubahan terbesar. |

### Pertanyaan 2: penyimpanan 24 jam

Username dan region pemain disimpan selama 24 jam lalu kedaluwarsa. Setuju? Belum ada aturan retensi data pelanggan di `docs/SECURITY_RULES.md`.

### Pertanyaan 3: hasil tidak ditemukan (404)

Di-cache juga (menghemat kuota), atau tidak (selalu cek ulang ke Melo)?

### Pertanyaan 4: batas biaya

Berapa batas pengecekan per hari atau per bulan yang diizinkan? Setiap cek di luar kuota gratis memakai 1 MC.

## 7. Setelah ada keputusan

- Catat keputusan di `docs/PROJECT_SPEC.md` §10 (jika disetujui) dan di `docs/CURRENT_STATUS.md` §5.
- Jika disetujui: tambahkan cache di sekitar `MeloService::checkNickname()`, buat test dengan `Http::fake`, perbarui `docs/API_INTEGRATION.md`, dan `docs/SECURITY_RULES.md` untuk retensi data.

## 8. Keputusan PO (2026-11-27)

| # | Pertanyaan | Jawaban PO | Yang dicatat |
| --- | --- | --- | --- |
| 1 | Cakupan | **C. Setujui untuk pelanggan** | Pelanggan bisa cek nickname di halaman game. Endpoint dan UI belum dibuat (§9). |
| 2 | Penyimpanan | **1 hari** | Cache 86.400 detik. Catatan retensi di §10. |
| 3 | "Tidak ditemukan" | Tidak di-cache, dengan alasan "kalau tidak mengurangi saldo, tidak apa-apa" | Premis itu tidak sesuai §3: "tidak ditemukan" memakai jatah. 404 tetap tidak di-cache mengikuti jawaban PO. **Perlu konfirmasi ulang** (§9 no. 4). |
| 4 | Batas biaya | Tidak ada batas. "Coba rekap saja" | Sistem tidak membatasi biaya per hari atau per bulan. Rekap biaya di bawah. |

**Rekap biaya** (dari spesifikasi Melo, belum diuji ke akun nyata):
- 40 cek gratis per hari, reset 00:00 UTC (`free_daily_limit`), maks 5 per menit.
- Setelah 40 habis: 1 MC per cek, maks 20 per menit.
- Pemeriksaan ulang pemain yang sama dalam 24 jam tidak memanggil Melo, karena hasil 200 di-cache. Hasil "tidak ditemukan" selalu memanggil Melo.
- Setiap panggilan tercatat di `api_logs` (provider `melo`, endpoint `/check-nickname`), termasuk `billing.charged_mc`. Rekap pemakaian bisa dibuat dari sana tanpa kode tambahan.

## 9. Keputusan yang masih dibutuhkan sebelum endpoint pelanggan

1. **Pemetaan game ke `game_code` Melo.** Tabel `games` hanya punya `melo_brand_id` (ID brand katalog), bukan kode yang dipakai `check-nickname`. Opsi A: kolom baru `games.melo_game_code` (nullable), diisi di form admin (rekomendasi). Kolom baru perlu persetujuan (AGENTS §5). Opsi B: pakai `games.slug` (seeder memakai `mobile-legends`, `pubg-mobile`, yang cocok dengan contoh spesifikasi). Tanpa kolom, tetapi harus dicek dulu terhadap daftar `GET /check-nickname/games`.
   - **Diterapkan opsi A (2026-11-27, atas permintaan user):** migration `2026_11_27_212731`, dipilih dari dropdown berisi `nicknameGames()` (`MeloCatalogService::nicknameGameOptions()`, cache 1 jam). Persetujuan PO untuk kolom baru dan pemakaian `nicknameGames()` (M10) belum tercatat.
2. **Batas pemakaian per IP.** Usulan: 10 cek per menit per IP, sama dengan checkout (`throttle:10,1`). Tanpa batas ini, satu orang bisa menghabiskan saldo H2H yang juga dipakai untuk top up pesanan.
3. **Cek nickname opsional atau wajib sebelum checkout?** Usulan: opsional, supaya kegagalan Melo (402, 429, 502) tidak menghentikan checkout.
4. **Konfirmasi "tidak ditemukan" tidak di-cache** dengan premis yang benar (§3). Jika tetap tidak di-cache, setiap pemeriksaan ulang nama yang tidak ditemukan memakai jatah atau 1 MC.
5. **Gerbang sandbox.** `cachedNickname()` tidak memanggil Melo saat `MELO_SANDBOX=true`, jadi fitur ini tidak jalan di lokal. Usulan: setuju.

## 10. Yang sudah dikerjakan dan retensi

- `MeloService::cachedNickname()`: cache 1 hari, key SHA-256 dari `game_code|customer_target|zone`, hanya respons 200 yang di-cache, gerbang sandbox. Test: `MeloServiceTest` (+7 kasus).
- `api_logs` tidak lagi menyimpan `data.username` dan `data.region` untuk `/check-nickname`.
- **Belum:** baris cache kedaluwarsa hanya dihapus saat dibaca ulang (`DatabaseStore::get`). Penghapusan terjadwal belum ada, jadi retensi 24 jam belum benar secara fisik. Dicatat di `SECURITY_RULES.md` §13 dan harus selesai sebelum endpoint dibuka.
- `customer_target` (ID pemain) tetap tercatat di `api_logs` tanpa masa berlaku, seperti ID pada pesanan. Ini di luar keputusan 24 jam, dan retensi `api_logs` belum diputuskan.
