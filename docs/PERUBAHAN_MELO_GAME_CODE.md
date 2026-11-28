# Perubahan: kode game Melo untuk cek nickname

> Tanggal: 2026-11-27 · Status: belum di-commit · Keputusan terkait: opsi A (CURRENT_STATUS §5 no. 15)

## 1. Ringkasan

Admin bisa memilih kode game Melo (`game_code`) untuk setiap game dari daftar `GET /check-nickname/games`. Kode disimpan di kolom baru `games.melo_game_code`. Endpoint cek nickname pelanggan belum dibuat. Kolom ini disiapkan untuk itu.

## 2. Keputusan dan alasannya

| Keputusan | Alasan |
| --- | --- |
| Kolom baru `games.melo_game_code` (opsi A) | Spesifikasi menyebut `game_code` berbeda dari slug brand. Karena itu `games.slug` (opsi B) tidak dipakai. |
| Kode tidak diambil dari dropdown brand Melo | Dropdown brand berisi `brand_id`, `name`, dan `slug`. Daftar game cek nickname tidak berisi `brand_id`. Mencocokkan lewat slug atau nama berarti menebak. |
| Dropdown diisi dari `GET /check-nickname/games` (`nicknameGames()`) | Kode diambil langsung dari sumbernya. Pemakaian method ini belum punya persetujuan PO tertulis (lihat bagian 6). |
| Kolom tidak unik | Aturan kode per game belum ada di spesifikasi. |
| Tidak ada `maxLength` di form | Spesifikasi memberi `maxLength: 100` untuk `game_code` di body `CheckNickname`. Batas itu belum diterapkan. Nilai dipilih dari daftar Melo, jadi risikonya kecil. |
| Kode tersimpan tetap ada di pilihan saat Melo tidak bisa dihubungi | Tanpa ini, membuka dan menyimpan form edit saat Melo mati akan mengosongkan kode. |
| Cache 1 jam; galat tidak di-cache | Mengurangi panggilan ke Melo saat form dibuka. Galat tidak di-cache supaya daftar langsung terisi setelah Melo pulih. |

## 3. Berkas yang berubah

| Berkas | Perubahan |
| --- | --- |
| `database/migrations/2026_11_27_212731_add_melo_game_code_to_games_table.php` | Baru. Menambah kolom `melo_game_code` (string, nullable) setelah `melo_brand_id`. `down()` menghapus kolom. |
| `app/Models/Game.php` | `melo_game_code` ditambahkan ke `#[Fillable]`. |
| `app/Services/Supplier/MeloCatalogService.php` | Konstanta `NICKNAME_GAMES_CACHE_KEY` dan `NICKNAME_GAMES_CACHE_SECONDS` (3600). Method baru `nicknameGameOptions()`. |
| `app/Filament/Resources/Games/Schemas/GameForm.php` | Field `melo_game_code` berupa `Select` yang bisa dicari. Method privat `nicknameGameOptions(?Game $record)` menambahkan kode tersimpan ke pilihan jika tidak ada di daftar. |
| `tests/Feature/AdminCatalogResourceTest.php` | Dua test baru. `setUp()` juga membersihkan cache daftar game. |
| `tests/Feature/MeloCatalogServiceTest.php` | Satu test baru. `setUp()` juga membersihkan cache daftar game. |
| `docs/CURRENT_STATUS.md` | Riwayat, status keputusan no. 15, daftar M10, dan jumlah test. |
| `docs/DATABASE_DESIGN.md` | Baris `melo_game_code` di tabel `games`, catatan 4, dan ERD. |
| `docs/API_INTEGRATION.md` | Baris `nicknameGames()` dan catatan method yang sudah dipakai. |

Tidak ada perubahan pada `MeloService`, job, atau webhook dalam pekerjaan ini.

## 4. Alur data

1. Admin membuka form Game (create atau edit).
2. `GameForm` memanggil `nicknameGameOptions()` dengan record game (atau `null` saat create).
3. `MeloCatalogService::nicknameGameOptions()` membaca cache `melo.catalog.nickname_games`. Jika belum ada, memanggil `MeloService::nicknameGames()`, yaitu `GET /check-nickname/games`. Endpoint ini publik, tanpa kredensial, dan tidak dibatasi `MELO_SANDBOX`.
4. Daftar diurutkan berdasarkan nama dan menjadi pilihan `game_code => name`.
5. Jika game sudah punya `melo_game_code` yang tidak ada di daftar, kode itu ditambahkan ke pilihan dengan label kodenya sendiri.
6. Saat disimpan, nilai `melo_game_code` ikut tersimpan lewat `Game::$fillable`.

## 5. Pengujian

Hasil dijalankan dengan `INERTIA_SSR_ENABLED=false` setelah `php artisan optimize:clear`.

- Test baru, terfilter: `--filter='nickname_game_options|melo_game_code|saved_game_code'` → 3 lulus.
- Suite penuh: 192 test, 185 lulus, 7 gagal.
- Tujuh kegagalan itu sudah ada sebelumnya: 6 halaman admin yang mengembalikan 404 (M15 b) dan `CustomerAuthTest` (M17). Daftar nama test yang gagal sama dengan sebelum perubahan ini.

Test baru:
- `AdminCatalogResourceTest::test_game_melo_game_code_is_saved_from_melo_nickname_games`
- `AdminCatalogResourceTest::test_saved_game_code_is_kept_when_melo_nickname_games_is_down`
- `MeloCatalogServiceTest::test_nickname_game_options_are_sorted_by_name_and_failures_are_not_cached`

Belum diuji: tampilan di browser, dan respons dari API Melo yang asli. Semua HTTP di test di-fake.

## 6. Belum selesai dan butuh keputusan

- **Persetujuan PO tertulis** untuk dua hal: kolom baru (§5 no. 15) dan pemakaian `nicknameGames()` di form (M10).
- **Endpoint dan UI cek nickname pelanggan** belum dibuat. Menunggu keputusan §5 no. 16–18 (batas per IP, opsional atau wajib, dan gerbang sandbox).
- **Penghapusan terjadwal** baris cache kedaluwarsa (`SECURITY_RULES.md` §13). Harus ada sebelum endpoint pelanggan dibuka.
- **Seeder** belum mengisi `melo_game_code` untuk game contoh.
- **Migration** belum dijalankan di DB lokal (`php artisan migrate`).
- **Format respons Melo** yang asli belum diverifikasi.
- **`maxLength: 100`** dari spesifikasi belum diterapkan.

## 7. Risiko

- Form Game memanggil Melo saat dirender. Jika Melo lambat, form ikut lambat sampai cache terisi.
- Kode yang sudah tersimpan tidak divalidasi ulang terhadap daftar Melo. Jika Melo menghapus game itu, kode lama tetap ada sampai admin memilih ulang.
- Nilai ini belum dipakai di alur mana pun. Dampaknya baru terasa saat endpoint cek nickname dibuat.

## 8. Cara meninjau

- [ ] Migration hanya menambah kolom, dan `down()` menghapusnya.
- [ ] `Game` punya `melo_game_code` di `#[Fillable]`.
- [ ] `GameForm` memakai `Select`, bukan input teks.
- [ ] Kode tersimpan tetap ada saat daftar Melo kosong (test `saved_game_code_is_kept...`).
- [ ] `nicknameGameOptions()` tidak meng-cache galat (test `nickname_game_options...`).
- [ ] Dokumen di `docs/` sesuai dengan perubahan di atas.
