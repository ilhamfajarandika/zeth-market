# DATABASE_DESIGN — Rancangan Database

> - Sumber kebenaran skema = migration di `database/migrations/`. Dokumen ini harus diperbarui setiap kali ada migration baru.
> - Prinsip [FINAL]: sesederhana mungkin untuk timeline 1 bulan. **Tidak ada tabel baru yang diusulkan** di luar yang sudah ada.
> - Engine MySQL 8, charset `utf8mb4_unicode_ci`. Uang disimpan sebagai `DECIMAL(15,2)` dalam Rupiah.

## 1. Daftar Entitas

| Tabel           | Fungsi                                        | Migration (1)            |
| --------------- | --------------------------------------------- | ------------------------ |
| `users`         | Admin dan pelanggan                           | `0001…000000` + `000100` |
| `categories`    | Pengelompokan game (Mobile, PC, Voucher, ...) | `000200`                 |
| `games`         | Game yang dijual + aturan input User/Zone ID  | `000300`                 |
| `products`      | Item top up yang dijual + harga               | `000400`                 |
| `melo_products` | Katalog Melo hasil sync, dipilih admin (3)    | `2026_11_22_120006`      |
| `orders`        | Transaksi pembelian + data payment & supplier | `000500`                 |
| `game_accounts` | Akun game yang dijual (1 baris = 1 akun), tanpa kredensial | `2026_11_22_204917` (menggantikan `000600`) |
| `api_logs`      | Log request/callback payment & supplier       | `000700`                 |
| `settings`      | Pengaturan key-value (nama situs, dll.)       | `000800`                 |

Catatan:
1. Nama lengkap file: `database/migrations/2026_11_11_<nomor>_*.php`. Kolom `role` dan `whatsapp` di `users` ditambahkan oleh `2026_11_11_000100_add_role_to_users_table.php` (`balance` juga, lalu dihapus oleh `2026_11_22_120007`).
- Tabel bawaan Laravel (`0001_01_01_*`): `sessions`, `password_reset_tokens`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`.
2. Tabel `notifications` (migration `2026_11_16_202857_create_notifications_table.php`, standar Laravel via `make:notifications-table`): menyimpan notifikasi lonceng panel admin (Filament database notifications), saat ini untuk peringatan saldo Melo. Bukan tabel domain.
3. Ditambahkan atas persetujuan PO 2026-11-22 (pengecualian prinsip "tidak ada tabel baru").
4. Sejak 2026-11-22 semua produk adalah top up. Akun game dipisah ke `game_accounts` (tabel baru di migration `2026_11_22_204917`, menggantikan `000600`). Kolom `products.type` dan `orders.product_type` dihapus. Data akun lama dibuang atas keputusan PO. Lihat §3 `game_accounts`.

## 2. ERD

```mermaid
erDiagram
    users ||--o{ orders : "membuat (opsional)"
    categories ||--o{ games : memiliki
    games ||--o{ products : memiliki
    games ||--o{ melo_products : "katalog Melo"
    games ||--o{ orders : "dirujuk"
    products ||--o{ orders : "dibeli di"
    games ||--o{ game_accounts : "akun dijual"

    users { bigint id PK
        string email UK
        string role
        string whatsapp }
    categories { bigint id PK
        string slug UK }
    games { bigint id PK
        bigint category_id FK
        string slug UK
        int melo_brand_id UK
        bool requires_zone_id }
    products { bigint id PK
        bigint game_id FK
        string supplier_sku
        bigint price }
    melo_products { bigint id PK
        bigint game_id FK
        string sku_code UK
        bigint price }
    orders { bigint id PK
        string invoice UK
        bigint user_id FK
        bigint game_id FK
        bigint product_id FK
        string status
        bigint total }
    game_accounts { bigint id PK
        bigint game_id FK
        string name
        bigint price
        string status }
```

## 3. Rancangan Tabel

Singkatan: **PK** primary key · **FK** foreign key · **UQ** unique · **IDX** index · **N** nullable.

### `users`

| Kolom               | Tipe            | Keterangan                            |
| ------------------- | --------------- | ------------------------------------- |
| `id`                | BIGINT PK       |                                       |
| `name`              | VARCHAR         |                                       |
| `email`             | VARCHAR UQ      |                                       |
| `whatsapp`          | VARCHAR(20) N   |                                       |
| `email_verified_at` | TIMESTAMP N     | Verifikasi email pelanggan = [TBD]    |
| `password`          | VARCHAR         | Hash (cast `hashed`)                  |
| `role`              | VARCHAR(20) IDX | Default `member` (1)                  |
| `remember_token`    | VARCHAR N       |                                       |
| timestamps          |                 |                                       |

1. Nilai: `admin` / `member` (pelanggan). Role `reseller` dan kolom `balance` dihapus 2026-11-22 (`2026_11_22_120007`, user `reseller` lama diubah jadi `member`).

### `categories`

| Kolom       | Tipe         | Keterangan    |
| ----------- | ------------ | ------------- |
| `id`        | BIGINT PK    |               |
| `name`      | VARCHAR      |               |
| `slug`      | VARCHAR UQ   |               |
| `sort`      | INT UNSIGNED | Urutan tampil |
| `is_active` | BOOLEAN      | Default true  |

### `games`

| Kolom                            | Tipe         | Keterangan                        |
| -------------------------------- | ------------ | --------------------------------- |
| `id`                             | BIGINT PK    |                                   |
| `category_id`                    | FK N         | → `categories.id`, set null (1)   |
| `name`, `publisher`              | VARCHAR      |                                   |
| `slug`                           | VARCHAR UQ   | Dipakai di URL `/game/{slug}`     |
| `melo_brand_id`                  | INT UNSIGNED UQ N | Tautan opsional ke brand Melo untuk sync item (3). Migration `2026_11_16_200655` |
| `thumbnail`, `banner`            | VARCHAR N    | Path file di storage              |
| `description`, `instruction`     | TEXT N       | `instruction` = cara cari User ID |
| `requires_zone_id`               | BOOLEAN      | Mis. MLBB = true                  |
| `user_id_label`                  | VARCHAR      | Default `User ID`                 |
| `zone_id_label`                  | VARCHAR N    |                                   |
| `user_id_regex`, `zone_id_regex` | VARCHAR N    | Validasi format (2)               |
| `is_active`, `is_popular`        | BOOLEAN      |                                   |
| `sort`                           | INT UNSIGNED |                                   |

1. `ON DELETE SET NULL`.
2. Pengganti cek nickname.
3. NULL = game dibuat manual tanpa sync. Diisi lewat Select "Brand Melo" di form Game (`MeloCatalogService::brands()`).

### `products`

| Kolom            | Tipe            | Keterangan                             |
| ---------------- | --------------- | -------------------------------------- |
| `id`             | BIGINT PK       |                                        |
| `game_id`        | FK              | → `games.id`, cascade (1)              |
| `name`           | VARCHAR         |                                        |
| `group`          | VARCHAR N       | Pengelompokan tampilan (Diamond, dll.) |
| `supplier`       | VARCHAR N       | `melo`                                 |
| `supplier_sku`   | VARCHAR N IDX   | `sku_code` Melo                        |
| `base_price`     | BIGINT UNSIGNED | Harga modal, Rupiah penuh (2)          |
| `price`          | BIGINT UNSIGNED | Harga jual, Rupiah penuh. `0` = belum diatur (2) |
| `strike_price`   | BIGINT UNSIGNED N | Harga coret, Rupiah penuh            |
| `is_active`      | BOOLEAN         |                                        |

1. `ON DELETE CASCADE`.
2. Sejak 2026-11-16 kolom uang `products` dan `orders` berupa integer (Rupiah penuh, tanpa sen); lihat `CURRENT_STATUS.md` §6.
3. Kolom `reseller_price`, `sort`, `image`, `description` dihapus 2026-11-22 (`2026_11_22_120007`, keputusan PO). Kolom `type` dihapus 2026-11-22 (`2026_11_22_204917`): semua produk top up. Urutan tampil storefront = `price`.
- Index tambahan: UQ(`supplier`, `supplier_sku`) untuk mencegah SKU ganda. Dipakai untuk mencocokkan produk dengan `melo_products.sku_code`.

### `melo_products`

Katalog Melo per game hasil sync (`MeloCatalogService::syncProducts()`). Admin memilih item di sini untuk dijadikan `products` (`addProducts()`). Migration `2026_11_22_120006`.

| Kolom       | Tipe            | Keterangan                                          |
| ----------- | --------------- | --------------------------------------------------- |
| `id`        | BIGINT PK       |                                                     |
| `game_id`   | FK              | → `games.id`, cascade                               |
| `sku_code`  | VARCHAR UQ      | `sku_code` Melo; produk terpilih = `products.supplier_sku` (`supplier = melo`) |
| `name`      | VARCHAR         |                                                     |
| `type_name` | VARCHAR N       | Menjadi `products.group` saat dipilih               |
| `price`     | BIGINT UNSIGNED | Harga modal Melo, `ceil`, Rupiah penuh              |
| `status`    | VARCHAR(20)     | Status Melo (`active` / `out_of_stock`)             |
| timestamps  |                 |                                                     |

- Baris game yang hilang dari katalog Melo dihapus saat sync.

### `orders`

| Kolom                              | Tipe            | Keterangan                           |
| ---------------------------------- | --------------- | ------------------------------------ |
| `id`                               | BIGINT PK       |                                      |
| `invoice`                          | VARCHAR UQ      | Nomor order (1)                      |
| `user_id`                          | FK N            | → `users.id`, set null. NULL = guest |
| `game_id`, `product_id`            | FK              | Restrict (2)                         |
| `product_name`                     | VARCHAR         | **Snapshot** saat order              |
| `target_user_id`, `target_zone_id` | VARCHAR N       | ID tujuan top up                     |
| `email`, `whatsapp`                | VARCHAR N       | Kontak pembeli                       |
| `price`, `fee`, `total`            | BIGINT UNSIGNED | **Snapshot**, Rupiah penuh (3)       |
| `status`                           | VARCHAR(20) IDX | Default `pending` (4)                |
| `payment_method`                   | VARCHAR N       |                                      |
| `payment_reference`                | VARCHAR N IDX   |                                      |
| `payment_url`, `payment_code`      | TEXT N          | URL checkout / nomor VA / string QR  |
| `paid_at`, `expired_at`            | TIMESTAMP N     |                                      |
| `supplier_reference`               | VARCHAR N IDX   |                                      |
| `supplier_status`                  | VARCHAR N       |                                      |
| `supplier_message`                 | TEXT N          |                                      |
| `serial_number`                    | TEXT N          | SN = bukti top up                    |
| `completed_at`                     | TIMESTAMP N     |                                      |

1. Format `INV{yymmdd}{8 karakter acak}`. Dipakai juga sebagai `merchant_ref` (payment) dan `buyer_trx_id` (Melo).
2. `ON DELETE RESTRICT`: produk/game yang pernah dibeli tidak bisa dihapus.
3. `total = price + fee` [ASUMSI].
4. Daftar status ada di `BUSINESS_FLOW.md` §1.
- Login opsional [FINAL], jadi `user_id` boleh kosong.
- Riwayat transaksi pelanggan = `orders` dengan `user_id` miliknya. Tidak perlu tabel terpisah. [FINAL]

### `game_accounts`

Akun game yang dijual, terpisah dari `products` (keputusan PO 2026-11-22, migration `2026_11_22_204917`). Penjualan dilakukan manual di luar sistem, jadi tabel ini **tidak** terhubung ke `orders` dan **tidak** menyimpan kredensial.

| Kolom         | Tipe            | Keterangan                                   |
| ------------- | --------------- | -------------------------------------------- |
| `id`          | BIGINT PK       |                                              |
| `game_id`     | FK              | → `games.id`, cascade                        |
| `name`        | VARCHAR         | Nama akun, contoh "Akun Mythic Glory"        |
| `price`       | BIGINT UNSIGNED | Harga jual, Rupiah penuh, diisi admin        |
| `public_info` | TEXT N          | Boleh tampil publik (rank, skin)             |
| `status`      | VARCHAR(20)     | Default `available`. `available` / `sold` / `disabled` (1) |
| `sold_at`     | TIMESTAMP N     | Diisi otomatis saat status jadi `sold` (2)   |
| timestamps    |                 |                                              |

1. Diubah manual oleh admin. Tidak ada status `reserved` lagi.
2. Dikosongkan otomatis jika status berubah dari `sold`.
- Index: (`game_id`, `status`) untuk daftar akun tersedia per game.
- Tidak ada kolom kredensial. Data login akun tidak disimpan di sistem.

### `api_logs`

| Kolom                 | Tipe          | Keterangan              |
| --------------------- | ------------- | ----------------------- |
| `provider`            | VARCHAR(30)   | `duitku` / `melo`       |
| `direction`           | VARCHAR(10)   | `outgoing` / `callback` |
| `reference`           | VARCHAR N IDX | Invoice                 |
| `endpoint`            | VARCHAR N     |                         |
| `request`, `response` | JSON N        | **Tanpa** secret/sign   |
| `signature_valid`     | BOOLEAN N     |                         |
| `http_status`         | SMALLINT N    |                         |

- Masa simpan log: [TBD] (rekomendasi: hapus setelah 90 hari).

### `settings`

| Kolom   | Tipe       | Keterangan |
| ------- | ---------- | ---------- |
| `key`   | VARCHAR UQ |            |
| `value` | TEXT N     |            |

- Diakses lewat `Setting::get()` dengan cache.
- Isi yang dibutuhkan (nama situs, kontak CS, dll.): [TBD].

## 4. Aturan Penyimpanan Data [FINAL]

1. Harga & nama produk di `orders` adalah snapshot. Perubahan harga produk tidak memengaruhi order lama.
2. Data login akun game tidak disimpan di sistem (keputusan PO 2026-11-22). Jangan menambah kolom kredensial tanpa persetujuan.
3. `APP_KEY` production wajib dibackup (dipakai untuk enkripsi data lain).
4. Status disimpan sebagai string dan di-cast ke enum PHP (`app/Enums`). Nilai baru harus ditambahkan di enum terlebih dahulu.
5. Produk yang sudah pernah dibeli tidak dihapus. Nonaktifkan dengan `is_active = false` (FK `RESTRICT`).

## 5. Aturan Transaksi Database [FINAL]

- Perubahan yang melibatkan lebih dari satu tabel (order + akun, order + status payment) wajib di dalam `DB::transaction`.
- Update status order dari callback: ambil order dengan `lockForUpdate()`, cek `status->isFinal()`, baru ubah. [REKOMENDASI] untuk job yang akan dibuat.
- Panggilan HTTP ke pihak luar **tidak** boleh berada di dalam transaksi DB yang memegang lock.

## 6. Perlindungan Stok (dihapus 2026-11-22)

`AccountStockService`, scheduler `accounts:release-expired`, dan `OutOfStockException` dihapus atas keputusan PO. Akun dijual manual, jadi tidak ada reservasi dan tidak ada anti jual ganda otomatis. Stok = jumlah baris `game_accounts` dengan status `available`.

## 7. Item Ditinjau / TBD

| Item                        | Status                              |
| --------------------------- | ----------------------------------- |
| Kolom & role reseller (1)   | Dihapus 2026-11-22 (`2026_11_22_120007`) |
| Verifikasi email pelanggan  | [TBD]                               |
| Masa simpan `api_logs`      | [TBD]                               |
| Kolom tambahan untuk refund | [TBD], tunggu alur refund           |

1. `users.balance`, `products.reseller_price`, role `reseller`, `Product::priceFor()`.
