# API_INTEGRATION — Integrasi Supplier & Payment Gateway

> **Jangan pernah menulis API key, private key, atau secret asli di dokumen/kode.** Semua nilai ada di `.env`.
> Detail endpoint di bawah berasal dari implementasi di kode dan dokumentasi provider. **Wajib dicocokkan ulang dengan dokumentasi resmi** saat akun sandbox tersedia. [ASUMSI]

## 1. Konfigurasi

`config/services.php` membaca dari `.env`:

```env
# Payment gateway (kandidat: Tripay)
TRIPAY_API_KEY=
TRIPAY_PRIVATE_KEY=
TRIPAY_MERCHANT_CODE=
TRIPAY_SANDBOX=true

# Supplier
DIGIFLAZZ_USERNAME=
DIGIFLAZZ_API_KEY=
DIGIFLAZZ_WEBHOOK_SECRET=
DIGIFLAZZ_TESTING=true
```

| Key config | Dipakai di |
|---|---|
| `services.tripay.*` | `app/Services/Payment/TripayService.php` |
| `services.digiflazz.*` | `app/Services/Supplier/DigiflazzService.php` |

Kredensial sandbox/production didaftarkan oleh: [TBD] (developer atau klien). Status akses: **belum tersedia** (risiko, lihat `IMPLEMENTATION_PLAN.md`).

## 2. Digiflazz API v1 [FINAL sebagai supplier]

Base URL: `https://api.digiflazz.com/v1`. Semua request `POST` JSON.

### 2.1 Signature request
`sign = md5(username + api_key + suffix)` → `DigiflazzService::sign()`.

| Operasi | Endpoint | Suffix sign | Metode di kode | Status kode |
|---|---|---|---|---|
| Cek saldo | `/cek-saldo` (`cmd: deposit`) | `depo` | `balance()` | ✅ ada |
| Daftar harga | `/price-list` (`cmd: prepaid`) | `pricelist` | `priceList()` | ✅ ada |
| Transaksi | `/transaction` | `ref_id` | `topup($order)` | ✅ ada |
| Cek status | `/transaction` dengan `ref_id` yang sama | `ref_id` | — | ➕ belum |

### 2.2 Daftar produk & harga
- `priceList()` mengembalikan daftar produk prepaid (SKU, harga modal, status). [ASUMSI] struktur respons, cocokkan dengan dokumentasi.
- Rencana: command/aksi admin untuk memperbarui `products.base_price` berdasarkan `supplier_sku`. Harga jual (`price`) **tidak** diubah otomatis; aturan markup = [TBD].

### 2.3 Pengiriman transaksi
Payload (`DigiflazzService::topup`):

| Field | Nilai |
|---|---|
| `username` | config |
| `buyer_sku_code` | `products.supplier_sku` |
| `customer_no` | `target_user_id` + `target_zone_id` digabung tanpa pemisah [ASUMSI] format per game, cocokkan dengan dokumentasi tiap produk |
| `ref_id` | `orders.invoice` (unik) |
| `sign` | `md5(username + api_key + ref_id)` |
| `testing` | `DIGIFLAZZ_TESTING` (true di development) |

Respons `data.status`: `Pending` / `Sukses` / `Gagal`, beserta `sn` dan `message`. Simpan ke `supplier_status`, `serial_number`, `supplier_message`.

### 2.4 Cek status transaksi
Kirim ulang request `/transaction` dengan `ref_id` yang sama. Digiflazz mengembalikan status transaksi yang sudah ada, bukan membuat transaksi baru. [ASUMSI] cocokkan dengan dokumentasi. Dipakai jika webhook tidak datang ([TBD] interval).

### 2.5 Webhook supplier
- Endpoint: `POST /webhook/digiflazz` → `DigiflazzCallbackController`.
- Signature: header `X-Hub-Signature` = `"sha1=" + HMAC-SHA1(raw body, DIGIFLAZZ_WEBHOOK_SECRET)` → `isValidWebhookSignature()`. ✅ ada + dites.
- Referensi order: `data.ref_id`.
- Pemrosesan status: ➕ belum (TODO di controller), lihat `BUSINESS_FLOW.md` §7.

## 3. Payment Gateway — Kandidat Tripay [TBD provider final]

Base URL: sandbox `https://tripay.co.id/api-sandbox`, production `https://tripay.co.id/api` (`TRIPAY_SANDBOX`). Header `Authorization: Bearer {api_key}`.

### 3.1 Metode pembayaran
- `GET /merchant/payment-channel` → `paymentChannels()`. ✅ ada.
- Channel yang ditampilkan ke pembeli: [TBD] (rekomendasi: QRIS + beberapa VA + e-wallet populer).
- Cache daftar channel agar tidak memanggil API setiap halaman dibuka. [REKOMENDASI]

### 3.2 Pembuatan invoice / transaksi
`POST /transaction/create` → `createTransaction($order, $method)`. ✅ ada.

| Field | Nilai |
|---|---|
| `method` | kode channel |
| `merchant_ref` | `orders.invoice` |
| `amount` | `orders.total` (integer Rupiah) |
| `customer_name/email/phone` | dari order (fallback email jika guest tanpa email: [TBD]) |
| `order_items` | 1 item = `product_name` |
| `return_url` | `route('invoice.show', $order)` |
| `expired_time` | `orders.expired_at` (timestamp) |
| `signature` | `HMAC-SHA256(merchant_code + merchant_ref + amount, private_key)` |

Simpan dari respons: `reference` → `payment_reference`, `checkout_url` → `payment_url`, `pay_code` / `qr_string` → `payment_code`. [ASUMSI] nama field respons. Mapping ini belum diimplementasikan.

### 3.3 Callback pembayaran
- Endpoint: `POST /webhook/tripay` → `TripayCallbackController`.
- Signature: header `X-Callback-Signature` = `HMAC-SHA256(raw body, private_key)` → `isValidCallbackSignature()`. ✅ ada + dites.
- Referensi order: `merchant_ref`.

| Status Tripay | Status order | Aksi stok akun |
|---|---|---|
| `PAID` | `paid`, lalu `processing` (top up) / `success` (akun) | `markSold()` |
| `EXPIRED` | `expired` | `release()` |
| `FAILED` | `failed` | `release()` |
| `REFUND` | [TBD] | [TBD] |

Pemrosesan status: ➕ belum (TODO di controller).

Validasi tambahan [REKOMENDASI]: cocokkan `total_amount` callback dengan `orders.total` sebelum menandai lunas.

### 3.4 Jika provider diganti
Buat service setara di `app/Services/Payment/` dengan kemampuan: daftar channel, buat transaksi, verifikasi callback, mapping status. Mapping status order di §3.3 tetap sama.

## 4. Timeout, Retry, Idempotency

| Aturan | Status |
|---|---|
| Timeout HTTP: Tripay 15 detik, Digiflazz 30 detik | ✅ di service |
| Retry Tripay: 2x jeda 500 ms (`retry(2, 500, throw: false)`) | ✅ |
| Retry Digiflazz **tidak** otomatis di HTTP client. Retry dilakukan job queue dengan `ref_id` sama | [REKOMENDASI], job belum ada |
| Callback diproses di queue job; controller hanya verifikasi, log, dispatch, lalu balas cepat | [REKOMENDASI], belum |
| Callback untuk order berstatus final diabaikan (`OrderStatus::isFinal()`) | [FINAL], belum diterapkan |
| Satu invoice = satu `ref_id` = satu transaksi supplier | [FINAL] |

## 5. Logging

- Semua request keluar dan callback masuk dicatat di `api_logs` (`provider`, `direction`, `reference`, `request`, `response`, `signature_valid`, `http_status`). ✅
- Field `sign` tidak disimpan di log Digiflazz (`except('sign')`). ✅
- Jangan mencatat API key, private key, atau `credentials` akun. [FINAL]

## 6. Pengujian Integrasi

- Feature test memakai `Http::fake()`. Tidak boleh ada request ke API asli dari test.
- Uji manual memakai Postman CLI (sudah terpasang) ke sandbox: buat transaksi, kirim callback dengan signature benar/salah, kirim callback dua kali.
- Sebelum go-live: transaksi nyata dengan nominal kecil untuk setiap metode bayar yang diaktifkan.
