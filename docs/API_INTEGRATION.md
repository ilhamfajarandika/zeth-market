# API_INTEGRATION — Integrasi Supplier & Payment Gateway

> **Jangan pernah menulis API key, private key, atau secret asli di dokumen/kode.** Semua nilai ada di `.env`.
> Detail endpoint di bawah berasal dari implementasi di kode dan dokumentasi provider. **Wajib dicocokkan ulang dengan dokumentasi resmi** saat akun sandbox tersedia. [ASUMSI]

## 1. Konfigurasi

`config/services.php` membaca dari `.env`:

```env
# Payment gateway (Duitku)
DUITKU_MERCHANT_CODE=
DUITKU_API_KEY=
DUITKU_SANDBOX=true

# Supplier (Melo)
MELO_BASE_URL=https://api.melostore.id/api/v1/h2h
MELO_API_KEY=
MELO_SECRET_KEY=
MELO_WEBHOOK_SECRET=
MELO_SANDBOX=true
MELO_LOW_BALANCE_THRESHOLD=500000
```

| Key config             | Dipakai di                                   |
| ---------------------- | -------------------------------------------- |
| `services.duitku.*`    | `app/Services/Payment/DuitkuService.php`     |
| `services.melo.*`      | `app/Services/Supplier/MeloService.php`      |

Kredensial sandbox/production didaftarkan oleh: [TBD] (developer atau klien). Status akses: **belum tersedia** (risiko, lihat `IMPLEMENTATION_PLAN.md`).

## 2. Melo H2H API [FINAL sebagai supplier, menggantikan Digiflazz]

Dokumentasi resmi: https://h2h.melostore.id/id/docs (OpenAPI dan koleksi Postman ada di halaman itu). Base URL: `MELO_BASE_URL` (`https://api.melostore.id/api/v1/h2h`). Request dan respons JSON.

Kredensial (API Key, Secret Key, Webhook Secret) dibuat lewat **Rotate Keys** di menu API Credentials dashboard Melo. Ketiganya tampil sekali, dan Rotate Keys berikutnya langsung mengganti ketiganya, jadi `.env` server harus ikut diperbarui.

### 2.1 Autentikasi
Header `X-API-Key` dan `X-Secret-Key` pada setiap request, plus `Accept: application/json`. Tidak ada signature request. IP server bisa didaftarkan di dashboard (opsional). Respons 403 bisa berarti akses H2H belum aktif atau IP belum diizinkan.

Semua endpoint di bawah `MELO_BASE_URL`. Sumber: `melostore-h2h.json` (OpenAPI resmi). Semua method di `MeloService` mengembalikan `Response` mentah.

| Operasi | Endpoint | Metode di kode | Dipakai alur order? |
|---|---|---|---|
| Cek akses akun | `GET /profile` | `profile()` | — |
| Saldo dan kurs | `GET /profile/balance` | `balance()` | alert saldo (rencana) |
| Katalog reguler | `GET /pricelists` | `priceList()` | sync item per brand (`MeloCatalogService::syncProducts()`) |
| Katalog reguler per brand | `GET /pricelists/{category_slug}` | `priceListByBrand()` | — |
| Brand reguler | `GET /pricelists/categories` | `brands()` | pilihan brand di form Game (`MeloCatalogService::brands()`) |
| Katalog Smart | `GET /smart-pricelists` | `smartPriceList()` | — |
| Katalog Smart per brand | `GET /smart-pricelists/{category_slug}` | `smartPriceListByBrand()` | — |
| Brand Smart | `GET /smart-pricelists/categories` | `smartBrands()` | — |
| Buat order reguler | `POST /transaction` | `topup($order)` / `createTransaction()` | ✅ |
| Buat order Smart | `POST /smart-transaction` | `createSmartTransaction()` | — |
| Cek status transaksi | `GET /transaction/{id}` (ID Melo atau `buyer_trx_id`) | `status()` | ✅ |
| Kirim kode 2FA | `POST /transactions/2fa` | `submitTwoFactor()` | — |
| Buat bulk order | `POST /bulk-orders` | `createBulkOrder()` | — |
| Riwayat bulk | `GET /bulk-orders` | `bulkOrders()` | — |
| Progres bulk | `GET /bulk-orders/{id}` | `bulkOrder()` | — |
| Transaksi anak bulk | `GET /bulk-orders/{id}/transactions` | `bulkTransactions()` | — |
| Invoice PDF bulk | `GET /bulk-orders/{id}/invoice` | `bulkInvoice()` | — |
| Game yang bisa dicek (publik) | `GET /check-nickname/games` | `nicknameGames()` via `MeloCatalogService::nicknameGameOptions()` | ✅ dropdown kode game di form Game (2026-11-27, cache 1 jam; persetujuan PO tertulis belum ada) |
| Kuota/tarif cek akun (publik) | `GET /check-nickname/plan` | `nicknamePlan()` | ✅ widget `MeloNicknamePlanOverview` (dashboard admin, 2026-11-27, persetujuan PO menunggu) |
| Cek nickname | `POST /check-nickname` | `checkNickname()`, dibungkus `cachedNickname()` | ✅ endpoint pelanggan `POST /game/{game}/nickname` (2026-11-28, dialog Beli Sekarang, throttle 10/menit; test `NicknameCheckTest`). Konfirmasi PO §9 menyusul |
| Batas pembelian Mobile Legends | `POST /mobile-legends/purchase-limit` | `checkMobileLegendsPurchaseLimit()` | — |

Method bertanda "—" sudah tersedia di service tetapi **belum dipakai** oleh alur apa pun, kecuali `nicknameGames()` (dropdown kode game di form Game, lihat tabel). `nicknamePlan()` dipakai hanya untuk menampilkan kuota di dashboard admin (read-only, di-cache 10 menit, tanpa kredensial); `checkNickname()` hanya dipakai lewat `cachedNickname()`, oleh endpoint pelanggan `POST /game/{game}/nickname`. Cek nickname untuk pelanggan disetujui PO 2026-11-27 (`PROJECT_SPEC.md` §5 P11). Smart, bulk, dan 2FA tetap di luar scope. Cek nickname memakai jatah atau biaya meski hasilnya "tidak ditemukan" (deskripsi spesifikasi Melo).

**Cache cek nickname** (`MeloService::cachedNickname()`, keputusan PO 2026-11-27):
- Key: SHA-256 dari `game_code|customer_target|zone` dengan prefix `melo.nickname.`. TTL 86.400 detik (1 hari). Driver cache `database`, tanpa tabel baru.
- Hanya respons 200 yang di-cache. 404, 402, 429, dan 502 tidak di-cache.
- Saat `MELO_SANDBOX=true`, tidak ada panggilan ke Melo dan hasilnya `null`.
- `api_logs` tidak menyimpan `data.username` dan `data.region` untuk `/check-nickname` (`logOutgoing(..., redact:)`).
- Baris cache kedaluwarsa dihapus terjadwal tiap jam lewat `melo:prune-nickname-cache`. Lihat `SECURITY_RULES.md` §13.
- Endpoint pelanggan `POST /game/{game}/nickname`: body `target_user_id` (+ `target_zone_id` bila game punya zona). Respons 200 `{username, region}`. Keduanya `null` jika tidak ditemukan, Melo galat, atau sandbox aktif. Game tanpa `melo_game_code` → 404 tanpa panggilan Melo.

Rate limit: katalog (reguler, Smart, kategori) berbagi 20 request/menit. Jika 429, tunggu sesuai header `Retry-After`.

### 2.1a Saldo H2H & peringatan saldo menipis
- `MeloBalanceService` memanggil `balance()` (`GET /profile/balance`, field `h2h_balance`, `is_sandbox_mode`), di-cache 60 detik. Ditampilkan di widget dashboard admin (`MeloBalanceOverview`), polling 60 detik.
- Tingkat: `ok` > `MELO_LOW_BALANCE_THRESHOLD` (default Rp 500.000) · `low` ≤ batas · `critical` ≤ setengah batas.
- Command terjadwal `melo:check-balance` (tiap 10 menit, `routes/console.php`) mengirim notifikasi lonceng ke semua admin sekali per tingkat (ulang tiap 6 jam; `low` → `critical` langsung dikirim; kembali `ok` mereset). Tidak mengirim saat `is_sandbox_mode = true` atau saat Melo tidak bisa dihubungi (command gagal, error di log). Belum ada kanal email/WhatsApp (keputusan #6 masih terbuka). Belum diuji ke API asli.

### 2.2 Daftar produk & harga
- `priceList()` mengambil katalog reguler (default `limit=500`, paginasi `cursor`). Item berisi `sku_code`, `name`, `price`, `status` (`active` / `out_of_stock`, hanya `active` yang bisa dipesan).
- **Sync katalog (sudah ada, belum diuji ke API asli):** `App\Services\Supplier\MeloCatalogService`. Admin menautkan game ke brand Melo (`games.melo_brand_id`, Select dari `brands()`), lalu aksi "Sync item dari Melo" memanggil `priceList(['brand_id' => …, 'format' => 'compact'])` mengikuti `next_cursor`. Sejak 2026-11-22 sync **tidak membuat produk**: semua item disimpan ke `melo_products` (upsert kunci `sku_code`: `name`, `type_name`, `price` = `ceil(price)`, `status`; baris game itu yang hilang dari katalog dihapus). Admin memilih item di tab "Item Melo" (Edit Game) lalu aksi massal "Jadikan produk" (`addProducts()`) membuat `products` nonaktif dengan `price = 0` (SKU yang sudah jadi produk dilewati). Produk terpilih milik game itu (`supplier = melo`, `supplier_sku = sku_code`) tetap diperbarui `name`, `group` (= `type_name`), `base_price` saat sync; `price`, `strike_price`, dan status aktif **tidak** ditimpa; item `out_of_stock` atau hilang dari katalog dinonaktifkan (tidak diaktifkan kembali otomatis). Penulisan DB satu transaksi; gagal HTTP/429 berarti tidak ada perubahan. Label/zone game diisi dari `meta.inquiry_forms` (`customer_target_zone`). Thumbnail game diambil dari `meta.brands[].thumbnail` lalu diunduh ke disk `public` (`games/thumbnails/`) oleh `storeThumbnail()`: hanya https, maks 2 MB, isi harus WebP/JPEG/PNG; gagal berarti field dibiarkan kosong. Mapping field berasal dari `melostore-h2h.json`, bukan respons nyata.
- Harga jual **tidak** diubah otomatis. Admin mengatur lewat edit inline atau aksi massal "Atur harga jual" (markup % / Rupiah tetap + pembulatan); aturan markup tersimpan = [TBD] (A10).

### 2.3 Pengiriman transaksi
Payload (`MeloService::topup`):

| Field | Nilai |
|---|---|
| `sku_code` | `products.supplier_sku` |
| `customer_target` | `orders.target_user_id` |
| `customer_target_zone` | `orders.target_zone_id` (tidak dikirim jika kosong) |
| `buyer_trx_id` | `orders.invoice` (unik) |
| `sandbox_mode` | `MELO_SANDBOX` (true di development: tanpa debit dan tanpa fulfilment nyata). ⚠️ Tidak ada default di `config/services.php`: jika variabel kosong/tidak ada, nilainya `false` dan order menjadi transaksi nyata (M12). |

Respons:
- **201**: diterima dan diantrekan, **bukan selesai**. Baca `data.status` (`pending`, `processing`, `success`, `failed`, `refunded`), lalu tunggu webhook.
- **200**: `buyer_trx_id` sudah pernah dipakai, mengembalikan transaksi yang ada tanpa debit ganda. Aman untuk retry.
- **409**: `buyer_trx_id` dipakai ulang dengan isi berbeda.
- **422**: validasi gagal.
- **500**: hasil tidak diketahui. Panggil `status()` dengan `buyer_trx_id` yang sama sebelum mengulang.

`data` berisi `id`, `buyer_trx_id`, `status`, `serial_number`, `price`, `is_sandbox`. Simpan ke `supplier_reference`, `supplier_status`, `serial_number`. `success` dan `failed` bersifat final; saldo yang ditahan dikembalikan jika `failed`.

### 2.4 Cek status transaksi
`status($reference)` memanggil `GET /transaction/{id}`; `id` boleh `buyer_trx_id` (invoice). Dipakai jika webhook tidak datang atau setelah respons 500 ([TBD] interval).

### 2.5 Webhook supplier
- Endpoint: `POST /webhook/melo` → `MeloCallbackController`. Daftarkan URL ini di dashboard Melo.
- Signature: header `X-H2H-Signature` = hex `HMAC-SHA256(raw body, MELO_WEBHOOK_SECRET)` (tanpa prefix) → `isValidWebhookSignature()`. ✅ ada + dites. Jika signature salah atau secret kosong, jawab 401.
- Referensi order: `buyer_trx_id`.
- Callback final tidak punya field `event`; `status` = `success` atau `failed` (gagal menambah `error_code` dan `message`).
- Event `transaction.action_change` (tindakan pelanggan/2FA) tidak boleh menyelesaikan order, mengubah saldo, atau me-refund.
- Callback yang sama bisa diterima lebih dari sekali: simpan event dulu, jawab 2xx, dan dedup (`event_id` + `action.version`). Melo mencoba maksimal 3 kali (jeda 30 dan 120 detik) dan menunggu respons 2xx dalam 10 detik, jadi simpan event dulu dan jawab cepat.
- Pemrosesan status: ✅ job `App\Jobs\HandleSupplierCallback` (dispatch dari controller), lihat `BUSINESS_FLOW.md` §7. Event (`transaction.action_change`) diabaikan setelah tersimpan di `api_logs` (tanpa dedup `event_id`, karena tidak memicu aksi). Order final tidak diubah. `success` → `success` + `serial_number` + `completed_at`; `failed` → `failed` + `supplier_message` + lonceng admin; `refunded` → status tetap + lonceng admin (O6 belum diputuskan); `pending`/`processing` → hanya `supplier_status`.
- Pengiriman: job `App\Jobs\SendTopupToMelo` (unik per order, 3 percobaan, backoff 30/120 detik). Hanya order `paid`. 200/201 → `processing` lalu `data` diteruskan ke `HandleSupplierCallback`. 5xx/timeout → `status(invoice)` dulu; jika belum ada → retry. 4xx → order tetap `paid`, `supplier_message` diisi, lonceng admin. Retry habis → lonceng admin.

## 3. Payment Gateway — Duitku [FINAL provider, dipilih PO 2026-11-14]

Memakai `Http` Laravel langsung (`DuitkuService`). Library `duitkupg/duitku-php` **tidak dipakai**: hanya `dev-master` tanpa rilis, curl dengan `CURLOPT_SSL_VERIFYPEER = false`, log ke file di `vendor/`, dan callback membaca `$_POST`.

Base URL: sandbox `https://sandbox.duitku.com/webapi/api/merchant`, production `https://passport.duitku.com/webapi/api/merchant` (`DUITKU_SANDBOX`, `DuitkuService::baseUrl()`), dipakai semua endpoint. Tidak ada header auth; keamanan lewat field `signature` di setiap request. (API Duitku POP `createInvoice` di `api-sandbox.duitku.com` memakai header signature, tetapi **tidak dipakai**: responsnya tidak membawa `qrString`/`vaNumber`, jadi QR sendiri tidak bisa dibuat. Dicoba di sandbox 2026-11-21.) Semua signature = **HMAC-SHA256(stringToSign, apiKey)**, ditulis langsung dengan `hash_hmac('sha256', …)` di `DuitkuService` (tidak ada helper `sign()`). MD5 dan SHA256 biasa sudah usang menurut dokumentasi resmi (docs.duitku.com, changelog v2.0). Koleksi Postman `duitku-collection.json` (root) hanya dipakai sebagai acuan **nama field** (mis. payload callback); script signature-nya (MD5 / SHA256 biasa) usang dan tidak dipakai. Signature tetap HMAC-SHA256 (dikonfirmasi PO 2026-11-17).

Ringkasan signature (`apiKey` = `DUITKU_API_KEY`, `merchantCode` = `DUITKU_MERCHANT_CODE`):

| Operasi                  | Endpoint                               | Metode di kode               | stringToSign                                | Format body | Status        |
| ------------------------ | -------------------------------------- | ---------------------------- | ------------------------------------------- | ----------- | ------------- |
| Daftar metode bayar      | `POST /paymentmethod/getpaymentmethod` | `paymentMethods($amount)`    | `merchantCode + amount + datetime`          | JSON        | ✅ ada + dites |
| Buat transaksi (inquiry) | `POST /v2/inquiry`                     | `createTransaction($order, $method)` | `merchantCode + merchantOrderId + paymentAmount` | JSON | ✅ ada + dites + sandbox |
| Cek status               | `POST /transactionStatus`              | `transactionStatus($invoice)` | `merchantCode + merchantOrderId`           | JSON (1)    | ✅ ada + dites |
| Callback masuk           | `POST /webhook/duitku` (dari Duitku)   | `isValidCallbackSignature()` | `merchantCode + amount + merchantOrderId`   | form-urlencoded | ✅ ada + dites |

1. Dokumentasi Duitku tidak konsisten soal format body (lihat §3.3). Dipakai JSON, konfirmasi di sandbox.

Semua request keluar memakai timeout 15 detik dan retry 2x (jeda 500 ms), dan dicatat di `api_logs` tanpa field `signature` (lihat §4 dan §5). Sudah dicoba ke sandbox asli 2026-11-21: `getpaymentmethod` (200, 23 metode), `/v2/inquiry` (200, `qrString` keluar untuk `SP`), callback (signature HMAC cocok). `transactionStatus()` belum dicoba.

Kode error HTTP (dokumentasi Duitku): 400 Bad Request (termasuk "Amount is different please try again later" bila order ID sama dikirim ulang dengan nominal beda), 401 Unauthorized, 404 Not Found, 409 "The transaction is still in progress" (request ulang order ID sama saat request pertama masih diproses), 500 Internal Server Error. Order ID sama dalam 5 menit mengembalikan respons yang sama. Untuk checkout: satu invoice = satu nominal; jangan kirim ulang invoice yang sama dengan nominal berbeda.

Alur singkat pembayaran (detail status order di [`BUSINESS_FLOW.md`](BUSINESS_FLOW.md) §4–5):

```
CheckoutController → createTransaction() → storePaymentDetails() → invoice menampilkan QR dari payment_code
Pembeli bayar → Duitku POST /webhook/duitku → verifikasi signature → api_logs → job HandlePaymentCallback
Tidak ada callback sampai expired_at → tandai expired lewat scheduler / transactionStatus() (belum ada)
```

### 3.1 Metode pembayaran
`POST {baseUrl}/paymentmethod/getpaymentmethod` → `paymentMethods($amount)`. Opsional (dokumentasi Duitku): memberi daftar metode aktif proyek. Body JSON: `merchantcode`, `amount`, `datetime` (`Y-m-d H:i:s`), `signature` = `HMAC-SHA256(merchantCode + amount + datetime, apiKey)`. Respons: `paymentFee[]` berisi `paymentMethod` (kode), `paymentName`, `paymentImage`, `totalFee`, serta `responseCode` `00` dan `responseMessage`. `totalFee` adalah fee per metode untuk nominal itu, dasar pengisian `orders.fee`. Kode QRIS: `SP`, `NQ`, `SQ`; daftar lengkap ada di dokumentasi Duitku.
- Metode yang ditampilkan ke pembeli: **semua** metode aktif yang dikembalikan Duitku (keputusan PO 2026-11-21). `DuitkuService::paymentChannels($amount)` menormalkan ke `code`, `name`, `group`, `iconUrl`, `fee` (`ceil(totalFee)`); `group` dari peta kode di `channelGroup()` (QRIS / Virtual Account / E-Wallet / Lainnya; peta kode belum dicocokkan dengan dokumentasi resmi Duitku, kode baru otomatis masuk "Lainnya").
- Di-cache 10 menit per nominal (`duitku.channels.{amount}`); respons gagal tidak di-cache dan menghasilkan daftar kosong. Dicoba ke sandbox 2026-11-21: 23 metode, semua `totalFee` 0 (fee asli baru terlihat di production).
- Checkout memakai daftar yang sama untuk menghitung `orders.fee` dan menolak kode metode yang tidak ada di daftar.

### 3.2 Pembuatan transaksi (inquiry)
`POST /v2/inquiry` (JSON) → `createTransaction($order, $method)`. ✅ ada + dites + dicoba di sandbox (2026-11-21, `SP`, Rp9.000: HTTP 200, `statusCode` `00`, `qrString` diterima).

| Field                         | Nilai                                  |
| ----------------------------- | -------------------------------------- |
| `merchantCode`                | `DUITKU_MERCHANT_CODE`                 |
| `paymentMethod`               | kode metode (wajib, mis. `SP`)         |
| `merchantOrderId`             | `orders.invoice`                       |
| `paymentAmount`               | `orders.total` (integer Rupiah)        |
| `productDetails`, `itemDetails` | `product_name`, 1 item, harga = total |
| `email`, `phoneNumber`        | dari order (1)                         |
| `customerVaName`              | `Customer` (tetap)                     |
| `callbackUrl`                 | `route('webhook.duitku')` (dari `APP_URL`) |
| `returnUrl`                   | `route('invoice.show', $order)`        |
| `expiryPeriod`                | menit sampai `orders.expired_at` (min 1, default 15) (2) |
| `signature`                   | `HMAC-SHA256(merchantCode + merchantOrderId + paymentAmount, apiKey)` |

1. Email opsional di checkout (WhatsApp wajib, keputusan PO 2026-11-21). Jika kosong, kode memakai fallback `noreply@example.com`; alamat fallback final masih [TBD].
2. Contoh di dokumentasi Duitku menyebut 5, 10, atau 60 menit. Apakah 15 menit diterima belum diverifikasi [TBD].

Respons sukses: `reference`, `paymentUrl`, `qrString` (metode QR) atau `vaNumber` (VA), opsional `appUrl` (deeplink e-wallet), `amount`, `statusCode` `00`, `statusMessage`. `DuitkuService::storePaymentDetails($order, $response)` menyimpan `reference` → `payment_reference`, `paymentUrl` → `payment_url`, `qrString` ?? `vaNumber` → `payment_code`; tidak mengubah apa pun jika `statusCode` ≠ `00`. `payment_method` diisi pemanggil.

Tampilan: halaman invoice (`storefront/invoice.blade.php`) merender `payment_code` menjadi gambar QR (library `chillerlan/php-qrcode`, SVG data URI) selama order `pending` dan metode QRIS (`SP`, `NQ`, `SQ`). Metode lain: tautan ke `payment_url`. Tampilan nomor VA: belum.

Redirect `returnUrl`: Duitku menambahkan `merchantOrderId`, `reference`, `resultCode` (`00` sukses, `01` proses, `02` batal/gagal) di query. Hanya informasi; status order **tidak** diubah dari redirect (bisa dimanipulasi pembeli). Status hanya dari callback.

### 3.3 Cek status transaksi
`POST /transactionStatus` (JSON) → `transactionStatus($invoice)`. ✅ ada + dites. Signature `HMAC-SHA256(merchantCode + merchantOrderId, apiKey)`. Dokumentasi Duitku tidak konsisten soal format body (tabel: form-urlencoded, contoh kode: JSON); dipakai JSON, konfirmasi di sandbox. `statusCode`: `00` sukses, `01` pending, `02` gagal/kedaluwarsa. [ASUMSI] arti `02`.

### 3.4 Callback pembayaran
- Endpoint: `POST /webhook/duitku` → `DuitkuCallbackController` (form-urlencoded).
- Signature: field `signature` = `HMAC-SHA256(merchantCode + amount + merchantOrderId, apiKey)` → `isValidCallbackSignature()`. ✅ ada + dites. Ditolak (403) jika signature kosong/salah, `merchantCode` bukan milik kita, atau API key belum diisi. Field `signature` tidak disimpan di `api_logs`.
- Referensi order: `merchantOrderId`.
- Urutan field callback (`merchantCode + amount + merchantOrderId`) berbeda dari inquiry (`merchantCode + merchantOrderId + paymentAmount`). Sudah dicocokkan dengan callback nyata dari sandbox (2026-11-21).
- IP outgoing Duitku (sandbox `182.23.85.11`, `182.23.85.12`, `103.177.101.187`, `103.177.101.188`; production ada di dokumentasi Duitku) **tidak** di-whitelist; verifikasi cukup lewat signature (keputusan 2026-11-21).

| Callback Duitku             | Status order                                          |
| --------------------------- | ----------------------------------------------------- |
| `resultCode` = `00`         | `paid`, lalu `processing` (top up)                    |
| `resultCode` = `02`         | `failed`                                              |
| kedaluwarsa (tanpa callback) | `expired`                                            |

Sejak 2026-11-22 tidak ada aksi stok akun di callback (akun game dijual manual, `game_accounts` tidak terhubung ke order).

Duitku tidak mengirim callback kedaluwarsa: tandai dari `orders.expired_at` dan/atau `transactionStatus()`. Refund: [TBD].

Kode callback (dokumentasi Duitku): `00` sukses, `02` gagal. `01` hanya ada di redirect; callback dengan kode lain tidak mengubah status (log warning).

Pemrosesan status: ✅ job `App\Jobs\HandlePaymentCallback`. Field callback: `merchantCode`, `amount`, `merchantOrderId`, `productDetail`, `additionalParam`, `paymentCode`, `resultCode`, `merchantUserId`, `reference`, `signature`, `publisherOrderId`, `spUserHash`, `settlementDate`, `issuerCode`, `customerName` (dan field kartu kredit). Yang dipakai: `merchantOrderId`, `amount`, `resultCode`, `reference`. Semua order adalah top up, jadi order `pending` yang diproses. `00` + nominal = `total` → `paid`, `paid_at`, `payment_reference`, dispatch `SendTopupToMelo`; `02` → `failed`. Nominal beda, atau lunas untuk order `expired`/`failed` → status tidak diubah + lonceng admin. Callback ulang tidak mengirim top up dua kali.

Validasi tambahan [REKOMENDASI]: cocokkan `amount` callback dengan `orders.total` sebelum menandai lunas.

### 3.5 Jika provider diganti
Buat service setara di `app/Services/Payment/` dengan kemampuan: daftar metode, buat transaksi, verifikasi callback, mapping status. Mapping status order di §3.4 tetap sama.

## 4. Timeout, Retry, Idempotency

| Aturan                                             | Status               |
| -------------------------------------------------- | -------------------- |
| Timeout HTTP: Duitku 15 detik, Melo 30 detik     | ✅ di service        |
| Retry Duitku 2x, jeda 500 ms (1)                   | ✅                   |
| Retry Melo lewat job queue, `buyer_trx_id` sama (2) | ✅ `SendTopupToMelo` |
| Callback diproses di queue job (3)                 | ✅                   |
| Callback untuk order final diabaikan (4)           | ✅ dites             |
| Refund Melo hanya dialert sekali per order (5)     | ✅ dites             |
| 1 invoice = 1 `buyer_trx_id` = 1 transaksi supplier | [FINAL] · kode dan test ✅, perilaku Melo di sandbox ⏳ |

1. `retry(2, 500, throw: false)`.
2. HTTP client Melo **tidak** retry otomatis. Retry aman karena `buyer_trx_id` sama (tanpa debit ganda); setelah respons 500, cek `status()` dulu. Diterapkan di `SendTopupToMelo`.
3. Controller hanya verifikasi, log, dispatch, lalu langsung membalas. Lock order, cek `isFinal()`, dan perubahan status dilakukan di job. Syarat idempotent = [FINAL]; mekanisme queue = [REKOMENDASI].
4. Memakai `OrderStatus::isFinal()` (Melo) dan hanya-dari-`pending` (Duitku).
5. `refunded` belum mengubah status order (menunggu keputusan refund). Alert hanya dikirim jika `supplier_status` belum `refunded`, dibaca sebelum `fill()`.

Mekanisme per invoice: job callback tidak memakai `ShouldBeUnique`. Row lock (`lockForUpdate`) di dalam transaksi menjaga urutan proses per invoice. `ShouldBeUnique` per invoice akan membuang callback yang datang saat callback sebelumnya masih di antrean, sehingga status akhir bisa terlewat. Respons 409 dari Melo (body berbeda untuk `buyer_trx_id` yang sama) berarti body retry harus identik. Perubahan `price` atau target pada order yang sudah `paid` dapat membuatnya berbeda (lihat `CURRENT_STATUS.md` M3).

## 5. Logging

- Semua request keluar dan callback masuk dicatat di `api_logs` (`provider`, `direction`, `reference`, `request`, `response`, `signature_valid`, `http_status`). ✅
- Header `X-API-Key` / `X-Secret-Key` tidak disimpan di `api_logs` (hanya payload dan respons). ✅
- Jangan mencatat API key, private key, atau `credentials` akun. [FINAL]

## 6. Pengujian Integrasi

- Feature test memakai `Http::fake()`. Tidak boleh ada request ke API asli dari test.
- Uji manual memakai Postman CLI (sudah terpasang) ke sandbox: buat transaksi, kirim callback dengan signature benar/salah, kirim callback dua kali.
- Sebelum go-live: transaksi nyata dengan nominal kecil untuk setiap metode bayar yang diaktifkan.
