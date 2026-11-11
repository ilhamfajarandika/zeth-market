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

# Supplier (Melo)
MELO_BASE_URL=https://api.melostore.id/api/v1/h2h
MELO_API_KEY=
MELO_SECRET_KEY=
MELO_WEBHOOK_SECRET=
MELO_SANDBOX=true
```

| Key config | Dipakai di |
|---|---|
| `services.tripay.*` | `app/Services/Payment/TripayService.php` |
| `services.melo.*` | `app/Services/Supplier/MeloService.php` |

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
| `sandbox_mode` | `MELO_SANDBOX` (true di development: tanpa debit dan tanpa fulfilment nyata) |

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
| Timeout HTTP: Tripay 15 detik, Melo 30 detik | ✅ di service |
| Retry Tripay: 2x jeda 500 ms (`retry(2, 500, throw: false)`) | ✅ |
| Retry Melo **tidak** otomatis di HTTP client. Retry dilakukan job queue dengan `buyer_trx_id` sama (aman, tidak debit ganda); setelah respons 500 cek `status()` dulu | [REKOMENDASI], job belum ada |
| Callback diproses di queue job; controller hanya verifikasi, log, dispatch, lalu balas cepat | [REKOMENDASI], belum |
| Callback untuk order berstatus final diabaikan (`OrderStatus::isFinal()`) | [FINAL], belum diterapkan |
| Satu invoice = satu `ref_id` = satu transaksi supplier | [FINAL] |

## 5. Logging

- Semua request keluar dan callback masuk dicatat di `api_logs` (`provider`, `direction`, `reference`, `request`, `response`, `signature_valid`, `http_status`). ✅
- Header `X-API-Key` / `X-Secret-Key` tidak disimpan di `api_logs` (hanya payload dan respons). ✅
- Jangan mencatat API key, private key, atau `credentials` akun. [FINAL]

## 6. Pengujian Integrasi

- Feature test memakai `Http::fake()`. Tidak boleh ada request ke API asli dari test.
- Uji manual memakai Postman CLI (sudah terpasang) ke sandbox: buat transaksi, kirim callback dengan signature benar/salah, kirim callback dua kali.
- Sebelum go-live: transaksi nyata dengan nominal kecil untuk setiap metode bayar yang diaktifkan.
