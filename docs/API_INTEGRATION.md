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
| Katalog reguler | `GET /pricelists` | `priceList()` | sync harga (rencana) |
| Katalog reguler per brand | `GET /pricelists/{category_slug}` | `priceListByBrand()` | — |
| Brand reguler | `GET /pricelists/categories` | `brands()` | — |
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
| Game yang bisa dicek (publik) | `GET /check-nickname/games` | `nicknameGames()` | — |
| Kuota/tarif cek akun (publik) | `GET /check-nickname/plan` | `nicknamePlan()` | — |
| Cek nickname | `POST /check-nickname` | `checkNickname()` | — |
| Batas pembelian Mobile Legends | `POST /mobile-legends/purchase-limit` | `checkMobileLegendsPurchaseLimit()` | — |

Method bertanda "—" sudah tersedia di service tetapi **belum dipakai** oleh alur apa pun. Spec proyek (`PROJECT_SPEC.md`) menyatakan **tanpa cek nickname** dan tanpa Smart/bulk/2FA, jadi memanggil method itu dari fitur baru memerlukan persetujuan PO. Cek nickname memakai kuota atau biaya meski hasilnya "tidak ditemukan".

Rate limit: katalog (reguler, Smart, kategori) berbagi 20 request/menit. Jika 429, tunggu sesuai header `Retry-After`.

### 2.2 Daftar produk & harga
- `priceList()` mengambil katalog reguler (default `limit=500`, paginasi `cursor`). Item berisi `sku_code`, `name`, `price`, `status` (`active` / `out_of_stock`, hanya `active` yang bisa dipesan).
- Rencana: command/aksi admin untuk memperbarui `products.base_price` berdasarkan `supplier_sku`. Harga jual (`price`) **tidak** diubah otomatis; aturan markup = [TBD].

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
- Pemrosesan status: ➕ belum (TODO di controller), lihat `BUSINESS_FLOW.md` §7.

## 3. Payment Gateway — Duitku [FINAL provider, dipilih PO 2026-11-14]

Memakai `Http` Laravel langsung (`DuitkuService`). Library `duitkupg/duitku-php` **tidak dipakai**: hanya `dev-master` tanpa rilis, curl dengan `CURLOPT_SSL_VERIFYPEER = false`, log ke file di `vendor/`, dan callback membaca `$_POST`.

Base URL: sandbox `https://sandbox.duitku.com/webapi/api/merchant`, production `https://passport.duitku.com/webapi/api/merchant` (`DUITKU_SANDBOX`, `DuitkuService::baseUrl()`). Tidak ada header auth; keamanan lewat field `signature` di setiap request. Semua signature = **HMAC-SHA256(stringToSign, apiKey)**, ditulis langsung dengan `hash_hmac('sha256', …)` di `DuitkuService` (tidak ada helper `sign()`). MD5 dan SHA256 biasa sudah usang menurut dokumentasi resmi (docs.duitku.com, changelog v2.0). Koleksi Postman lama yang memakai SHA256/MD5 sudah dihapus dari repo; jangan memakai contoh signature dari sumber lama.

Ringkasan signature (`apiKey` = `DUITKU_API_KEY`, `merchantCode` = `DUITKU_MERCHANT_CODE`):

| Operasi                  | Endpoint                               | Metode di kode               | stringToSign                                | Format body | Status        |
| ------------------------ | -------------------------------------- | ---------------------------- | ------------------------------------------- | ----------- | ------------- |
| Daftar metode bayar      | `POST /paymentmethod/getpaymentmethod` | `paymentMethods($amount)`    | `merchantCode + amount + datetime`          | JSON        | ✅ ada + dites |
| Buat transaksi (inquiry) | `POST /v2/inquiry`                     | `createTransaction($order, $method)` | `merchantCode + merchantOrderId + paymentAmount` | JSON | ✅ ada + dites |
| Cek status               | `POST /transactionStatus`              | `transactionStatus($invoice)` | `merchantCode + merchantOrderId`           | JSON (1)    | ✅ ada + dites |
| Callback masuk           | `POST /webhook/duitku` (dari Duitku)   | `isValidCallbackSignature()` | `merchantCode + amount + merchantOrderId`   | form-urlencoded | ✅ ada + dites |

1. Dokumentasi Duitku tidak konsisten soal format body (lihat §3.3). Dipakai JSON, konfirmasi di sandbox.

Semua request keluar memakai timeout 15 detik dan retry 2x (jeda 500 ms), dan dicatat di `api_logs` tanpa field `signature` (lihat §4 dan §5). Kode belum pernah dijalankan ke API Duitku asli.

Alur singkat pembayaran (detail status order di [`BUSINESS_FLOW.md`](BUSINESS_FLOW.md) §4–5):

```
Checkout (belum ada) → createTransaction() → Duitku → simpan reference/payment_url/payment_code
Pembeli bayar → Duitku POST /webhook/duitku → verifikasi signature → api_logs → (job HandlePaymentCallback: belum ada)
Tidak ada callback sampai expired_at → tandai expired lewat scheduler / transactionStatus() (belum ada)
```

### 3.1 Metode pembayaran
- `POST /paymentmethod/getpaymentmethod` → `paymentMethods($amount)`. ✅ ada. Body: `merchantcode`, `amount`, `datetime` (`Y-m-d H:i:s`), `signature` = `HMAC-SHA256(merchantCode + amount + datetime, apiKey)`. Respons: `paymentFee[].paymentMethod`.
- Metode yang ditampilkan ke pembeli: [TBD] (rekomendasi: QRIS + beberapa VA + e-wallet populer).
- Cache daftar metode agar tidak memanggil API setiap halaman dibuka. [REKOMENDASI]

### 3.2 Pembuatan invoice / transaksi
`POST /v2/inquiry` → `createTransaction($order, $method)`. ✅ ada + dites.

| Field                         | Nilai                                  |
| ----------------------------- | -------------------------------------- |
| `merchantCode`                | `DUITKU_MERCHANT_CODE`                 |
| `paymentMethod`               | kode metode (mis. `BK`)                |
| `merchantOrderId`             | `orders.invoice`                       |
| `paymentAmount`               | `orders.total` (integer Rupiah)        |
| `productDetails`, `itemDetails` | `product_name`, 1 item, harga = total |
| `email`, `phoneNumber`        | dari order (1)                         |
| `customerVaName`              | `Customer` (tetap)                     |
| `callbackUrl`                 | `route('webhook.duitku')`              |
| `returnUrl`                   | `route('invoice.show', $order)`        |
| `expiryPeriod`                | menit sampai `orders.expired_at` (min 1, default 15) |
| `signature`                   | `HMAC-SHA256(merchantCode + merchantOrderId + paymentAmount, apiKey)` |

1. Fallback email jika guest tidak mengisi email: [TBD]. Sementara kode memakai `noreply@example.com`.

Catatan perilaku `createTransaction()`:
- `paymentAmount` selalu dari `orders.total` (dicast `(int)`), tidak dari input user.
- `expiryPeriod` dihitung dari `orders.expired_at` (dibulatkan ke atas, minimal 1 menit; 15 jika `expired_at` kosong). Harus sama dengan masa reservasi/kedaluwarsa order.
- Respons dikembalikan mentah (`Response`); pemanggil yang memeriksa `statusCode`/`statusMessage` dan menyimpan hasilnya.

Simpan dari respons: `reference` → `payment_reference`, `paymentUrl` → `payment_url`, `vaNumber` / `qrString` → `payment_code`. [ASUMSI] nama field respons. Mapping ini belum diimplementasikan (alur order belum ada).

### 3.3 Cek status transaksi
`POST /transactionStatus` (JSON) → `transactionStatus($invoice)`. ✅ ada + dites. Signature `HMAC-SHA256(merchantCode + merchantOrderId, apiKey)`. Dokumentasi Duitku tidak konsisten soal format body (tabel: form-urlencoded, contoh kode: JSON); dipakai JSON, konfirmasi di sandbox. `statusCode`: `00` sukses, `01` pending, `02` gagal/kedaluwarsa. [ASUMSI] arti `02`.

### 3.4 Callback pembayaran
- Endpoint: `POST /webhook/duitku` → `DuitkuCallbackController` (form-urlencoded).
- Signature: field `signature` = `HMAC-SHA256(merchantCode + amount + merchantOrderId, apiKey)` → `isValidCallbackSignature()`. ✅ ada + dites. Ditolak (403) jika signature kosong/salah, `merchantCode` bukan milik kita, atau API key belum diisi. Field `signature` tidak disimpan di `api_logs`.
- Referensi order: `merchantOrderId`.
- Urutan field callback (`merchantCode + amount + merchantOrderId`) berbeda dari inquiry (`merchantCode + merchantOrderId + paymentAmount`). Wajib diverifikasi dengan callback nyata dari sandbox sebelum go-live.

| Callback Duitku             | Status order                                          | Aksi stok akun |
| --------------------------- | ----------------------------------------------------- | -------------- |
| `resultCode` = `00`         | `paid`, lalu `processing` (top up) / `success` (akun) | `markSold()`   |
| `resultCode` = `01`         | `failed`                                              | `release()`    |
| kedaluwarsa (tanpa callback) | `expired`                                            | `release()`    |

Duitku tidak mengirim callback kedaluwarsa: tandai dari `orders.expired_at` dan/atau `transactionStatus()`. Refund: [TBD].

Pemrosesan status: ➕ belum (TODO di controller).

Validasi tambahan [REKOMENDASI]: cocokkan `amount` callback dengan `orders.total` sebelum menandai lunas.

### 3.5 Jika provider diganti
Buat service setara di `app/Services/Payment/` dengan kemampuan: daftar metode, buat transaksi, verifikasi callback, mapping status. Mapping status order di §3.4 tetap sama.

## 4. Timeout, Retry, Idempotency

| Aturan                                             | Status               |
| -------------------------------------------------- | -------------------- |
| Timeout HTTP: Duitku 15 detik, Melo 30 detik     | ✅ di service        |
| Retry Duitku 2x, jeda 500 ms (1)                   | ✅                   |
| Retry Melo lewat job queue, `buyer_trx_id` sama (2) | [REKOMENDASI], belum |
| Callback diproses di queue job (3)                 | [REKOMENDASI], belum |
| Callback untuk order final diabaikan (4)           | [FINAL], belum       |
| 1 invoice = 1 `buyer_trx_id` = 1 transaksi supplier | [FINAL]              |

1. `retry(2, 500, throw: false)`.
2. HTTP client Melo **tidak** retry otomatis. Retry aman karena `buyer_trx_id` sama (tanpa debit ganda); setelah respons 500, cek `status()` dulu. Job-nya belum ada.
3. Controller hanya verifikasi, log, dispatch, lalu langsung membalas. Lock order, cek `isFinal()`, dan perubahan status dilakukan di job. Syarat idempotent = [FINAL]; mekanisme queue = [REKOMENDASI].
4. Memakai `OrderStatus::isFinal()`. Belum diterapkan.

## 5. Logging

- Semua request keluar dan callback masuk dicatat di `api_logs` (`provider`, `direction`, `reference`, `request`, `response`, `signature_valid`, `http_status`). ✅
- Header `X-API-Key` / `X-Secret-Key` tidak disimpan di `api_logs` (hanya payload dan respons). ✅
- Jangan mencatat API key, private key, atau `credentials` akun. [FINAL]

## 6. Pengujian Integrasi

- Feature test memakai `Http::fake()`. Tidak boleh ada request ke API asli dari test.
- Uji manual memakai Postman CLI (sudah terpasang) ke sandbox: buat transaksi, kirim callback dengan signature benar/salah, kirim callback dua kali.
- Sebelum go-live: transaksi nyata dengan nominal kecil untuk setiap metode bayar yang diaktifkan.
