# CORE_SYSTEM_REVIEW — Review Core System & Daftar Pertanyaan ("Grill")

> **Tanggal review:** 2026-11-16. Review baca-saja atas `docs/`, controller, service, migration, route, dan test. Tidak ada kode aplikasi yang diubah oleh review ini.
> **Tujuan:** menemukan celah dan hal yang belum ada **sebelum** core system (checkout, order, callback payment, pengiriman ke Melo, expiry) dibangun, dan memaksa keputusan yang masih menggantung.
> **Cara pakai:** isi kolom "Jawaban PO" di §4. Keputusan yang sudah final dipindahkan ke `PROJECT_SPEC.md` §13 dan dokumen terkait.
>
> Tingkat: 🔴 blocker (harus beres sebelum bangun) · 🟠 harus diputuskan sebelum bangun · 🟡 boleh menyusul.

## 1. Keputusan yang sudah diambil saat review ini

| #   | Keputusan (2026-11-16)                                                                                                  | Dampak                                                                                                                                                                                                                                                                                                                                                                                                                                            |
| --- | ----------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| K1  | **Akun game hanya ditampilkan di web (display/katalog); penjualannya manual di luar sistem, lewat WhatsApp.** Tidak ada checkout, order, atau pembayaran untuk produk `account`. (Semula diputuskan sebagai tombol "Beli" ke WhatsApp; user mempertegas bahwa akun hanya display, sehingga tombol WA bersifat opsional, lihat pertanyaan 6.) | Core system otomatis (checkout, Duitku, callback, Melo) **hanya untuk top up**. **Bertentangan** dengan `PROJECT_SPEC.md` §4, §7 no. 2 dan 4, §12 no. 2, `BUSINESS_FLOW.md` §3, dan Tahap 5 `IMPLEMENTATION_PLAN.md`. Menurut `AGENTS.md` §3 dokumen-dokumen itu belum boleh dianggap berubah sampai PO mencatat keputusan ini di `PROJECT_SPEC.md` §13. Isu B2/M2, B4, O10/M1, O11 untuk akun gugur atau mengecil. |
| K2  | **Kolom uang jadi integer (Rupiah penuh)**: `orders.price/fee/total` dan `products.base_price/price/strike_price`.       | Dilakukan dengan **mengedit langsung** migration `2026_11_11_000500_create_orders_table.php` dan `2026_11_11_000400_create_products_table.php` (`decimal(15,2)` → `unsignedBigInteger`). Ini **pengecualian eksplisit** dari aturan `AGENTS.md` §5 / `CLAUDE.md` (migration yang sudah di-commit tidak boleh diedit), dengan alasan belum ada production. Syarat di bawah tabel. |
| K3  | **Pembayaran diuji dulu tanpa tampilan**, lewat feature test + `Http::fake()`.                                          | Lihat §6.                                                                                                                                                                                                                                                                                                                                                                                                                                         |

Syarat pengecualian K2:
1. Berlaku hanya untuk perubahan ini, bukan izin umum mengedit migration.
2. Semua developer (termasuk pemilik branch `branch-ilham`) wajib menjalankan `php artisan migrate:fresh --seed` di database **lokal** masing-masing setelah menarik perubahan (`AGENTS.md` §5: hanya lokal/test).
3. Jangan diterapkan jika ada database bersama atau staging yang sudah memakai migration lama.
4. `reseller_price` dan `users.balance` dibiarkan (out of scope, M4).
5. Ikut berubah: cast `decimal:2` di model `Order`/`Product` → `integer`, `ProductForm`/`OrderForm` (`->numeric()` → `->integer()`), factory, test, `DATABASE_DESIGN.md`. Cast `(int)` di `DuitkuService::createTransaction` jadi tidak diperlukan.
6. Dicatat di `CURRENT_STATUS.md` §6 saat dikerjakan.

## 2. Temuan paling mendesak: `DuitkuService` tidak sinkron dengan test dan docs 🔴

> **Update 2026-11-16 (setelah commit `5f7e2f3`): sudah diperbaiki.** Semua signature HMAC-SHA256 dan `transactionStatus()` mengirim JSON; 15 test Duitku/webhook lulus. Isi di bawah adalah kondisi saat review dibuat dan dipertahankan sebagai riwayat. Pertanyaan 24 (algoritma) terjawab oleh dokumentasi resmi Duitku: HMAC-SHA256; MD5 sudah usang.

Status verifikasi: kode dibaca, lalu test dijalankan pada working tree saat ini (`php artisan test --compact --filter='Duitku|WebhookSignature'`, 2026-11-16, MySQL lokal hidup): **15 test, 14 lulus, 1 gagal**, yaitu `DuitkuServiceTest::test_transaction_status_posts_json_with_hmac_signature` (request yang diharapkan tidak terekam, karena kode masih `md5` + form). Pada `HEAD` (tanpa perubahan working tree) hasilnya tidak dijalankan; kesimpulan untuk `HEAD` berasal dari baca kode. Klaim "58 test lulus" di `CURRENT_STATUS.md` (2026-11-14) belum diverifikasi ulang untuk seluruh suite.

Pada commit terakhir (`HEAD`, branch `lury/be/inisialisasi-duitku`) kode berbeda dari test dan `API_INTEGRATION.md`. Working tree saat ini punya perubahan **belum di-commit** di `app/Services/Payment/DuitkuService.php` yang memperbaiki sebagian:

| Bagian                                       | Kode di `HEAD`                                             | Working tree (belum commit)                                        | Test / docs mengharapkan                                         |
| -------------------------------------------- | ---------------------------------------------------------- | ------------------------------------------------------------------ | ---------------------------------------------------------------- |
| `transactionSignature()` (inquiry)           | `hash_hmac($data, $key)` tanpa algoritma → `ArgumentCountError` (diverifikasi: PHP menolak 2 argumen), jadi `createTransaction()` pasti crash | `hash_hmac('sha256', …)` ✅                                        | HMAC-SHA256                                                      |
| `isValidCallbackSignature()`                 | `md5(…)`                                                   | `hash_hmac('sha256', …)` ✅                                        | HMAC-SHA256 (`WebhookSignatureTest`, `API_INTEGRATION.md` §3.4)  |
| `transactionStatus()`                        | `md5(merchantCode + invoice + apiKey)`, kirim sebagai form | **masih `md5` + `asForm: true`** ❌ (baris 84 dan 87)              | HMAC-SHA256, body JSON (`DuitkuServiceTest`, `API_INTEGRATION.md` §3.3) |
| Helper `sign()` yang disebut di `API_INTEGRATION.md` §3 | tidak ada                                       | tidak ada                                                          | docs menyebut `DuitkuService::sign()`                            |

Yang perlu dilakukan (bukan bagian review ini): selesaikan `transactionStatus()` (HMAC-SHA256 + JSON), jalankan `php artisan test --compact` untuk seluruh suite, lalu commit. Jika PO ternyata ingin MD5 (dokumentasi Duitku lama), test dan docs yang harus diubah, bukan sebaliknya. Ini konflik kode vs test+docs, sesuai `AGENTS.md` §3 harus dikonfirmasi PO. Dicatat sebagai M13 di `CURRENT_STATUS.md`.

## 3. Yang belum ada (inventaris core system)

| #   | Komponen                                                                                           | Status                                                         |
| --- | -------------------------------------------------------------------------------------------------- | -------------------------------------------------------------- |
| 1   | Livewire checkout (input ID, regex, produk, kontak, metode bayar)                                  | belum; input ID di `game.blade.php` statis                     |
| 2   | `OrderService`/action pembuat order (snapshot harga+nama, `generateInvoice()`, `expired_at`)       | belum; hanya `Order::generateInvoice()`                        |
| 3   | Pemanggilan `createTransaction` + simpan `payment_reference/url/code`                              | belum; nama field respons Duitku masih [ASUMSI]                |
| 4   | Job `HandlePaymentCallback`                                                                        | belum; controller masih TODO                                   |
| 5   | Job kirim ke Melo (`topup`), retry, respons 500 → `status()` dulu                                  | belum                                                          |
| 6   | Job `HandleSupplierCallback` + dedup `event_id` + `action.version`                                 | belum; belum ada tempat simpan event (hanya `api_logs`)        |
| 7   | Scheduler penanda order `pending` lewat `expired_at` → `expired`                                   | belum; hanya `accounts:release-expired` yang ada               |
| 8   | Cek status supplier terjadwal bila webhook tidak datang                                            | belum; interval [TBD]                                          |
| 9   | State machine transisi status (siapa boleh dari mana ke mana)                                      | belum; hanya `OrderStatus::isFinal()`                          |
| 10  | Akses invoice aman (M1)                                                                            | belum; mengecil karena K1 (hanya SN top up yang tampil)        |
| 11  | Notifikasi hasil order                                                                             | belum; kanal [TBD]                                             |
| 12  | Halaman instruksi bayar (VA/QR) + refresh status                                                   | belum                                                          |
| 13  | Daftar order "dibayar tapi tertahan/gagal" untuk admin + alert                                     | belum                                                          |
| 14  | Auth pelanggan + riwayat transaksi                                                                 | belum                                                          |
| 15  | Queue worker + scheduler sebagai proses produksi                                                   | belum                                                          |
| 16  | Tampilan produk akun sebagai display murni (tanpa tombol beli) + tombol WA / aksi admin "tandai terjual" **jika** disetujui (K1, pertanyaan 6–7) | tampilan stok sudah ada; sisanya belum |
| 17  | Test untuk semua di atas                                                                           | belum; yang ada hanya stok, signature, service, storefront dasar |

## 4. Celah flow dan kode

### 🔴 Blocker

- **B1. Bayar setelah expired/failed = uang masuk, order tidak diproses.** `isFinal()` mencakup `expired` dan `failed`, dan callback pada order final "diabaikan". Jika pembeli membayar di menit ke-14:59 dan callback tiba setelah scheduler menandai `expired`, uang sudah masuk tetapi tidak ada tindakan dan tidak ada jejak yang menandainya. Butuh keputusan: penanda/antrean "dibayar setelah kedaluwarsa", atau order boleh dibuka lagi, atau refund manual.
- **B2. Kode Duitku tidak sinkron** (lihat §2).
- **B3. Dispatch gagal tapi controller membalas sukses.** Jika queue/Redis mati saat callback tiba, `ApiLog` tersimpan lalu controller membalas 200, sehingga provider tidak mengulang dan order macet di `pending`. Perlu: simpan event callback dulu sebelum respons, dan/atau reconcile terjadwal lewat `DuitkuService::transactionStatus()`.
- **B4. ~~M2 (`releaseExpired()` tidak cek status order)~~ dan ~~DoS stok oleh guest~~: gugur untuk akun** karena K1. `AccountStockService` tidak dipakai alur checkout. Sisa risiko untuk top up: spam order `pending` yang membebani Duitku dan tabel order. Cukup rate limit checkout (`SECURITY_RULES.md` §4), tanpa Turnstile.

### 🟠 Harus diputuskan sebelum membangun

- **O1. Atomicity checkout.** Urutan aman: transaksi DB `[buat order]` → commit → HTTP `createTransaction` **di luar** transaksi (`SECURITY_RULES.md` §8). Jika HTTP gagal: order dibatalkan, atau dibiarkan `pending` tanpa `payment_url` lalu dicoba ulang?
- **O2. Fee dan total.** `fee` bergantung pada metode bayar, sedangkan total harus final sebelum `createTransaction`. Tipe uang sudah diputuskan integer (K2). Yang terbuka: siapa menanggung fee, apakah ditampilkan, boleh harga jual di bawah modal Melo?
- **O3. Snapshot tidak lengkap.** Order menyimpan nama dan harga, tetapi `MeloService::topup` membaca `$order->product->supplier_sku` saat kirim (baris 123). Admin yang mengubah SKU atau menonaktifkan produk di antara order dan bayar mengubah apa yang dikirim. Snapshot SKU butuh kolom baru (butuh persetujuan, catat di `DATABASE_DESIGN.md`). Karena migration `orders` sedang diedit untuk K2, ini saat yang efisien untuk memutuskannya.
- **O4. Harga modal naik / SKU habis.** Melo bisa menaikkan harga atau menandai SKU `out_of_stock` setelah toko menjual. Pakai `max_price` di payload, tolak order jika SKU habis, atau toko menanggung selisih?
- **O5. Supplier gagal setelah dibayar.** Order jadi `failed` (final) padahal `paid_at` terisi, dan tidak ada jalur refund (keputusan #2). Tanpa penanda terpisah, admin tidak bisa membedakan "gagal bayar" dan "dibayar tapi gagal kirim".
- **O6. Pemetaan status Melo.** Data bisa `pending/processing/success/failed/refunded`. Status `refunded` dari Melo belum punya aturan pemetaan ke `OrderStatus`.
- **O7. Order macet di `paid`/`processing`.** Jika job ke Melo gagal permanen (`failed_jobs`), siapa yang tahu? Belum ada halaman admin, alert, atau batas waktu.
- **O8. Transisi status tidak terkunci.** `OrderForm` admin (M3) bisa mengubah status dan total secara bebas, melewati semua aturan idempotency.
- **O9. Idempotency di level job.** Callback ganda bisa memicu dua job bersamaan. Butuh `lockForUpdate()` pada order, job unik per invoice, dan pencocokan `amount` callback dengan `orders.total` (mismatch → ditandai apa?).
- **O10. Kontak guest.** Keputusan #7 belum diambil. `DuitkuService` memakai fallback hardcoded `noreply@example.com` bila email kosong.
- **O11. Top up salah target.** Hanya validasi format (keputusan final). Tidak ada refund bila pembeli salah ID, dan format target Melo per SKU belum diverifikasi (M9). Perlu syarat dan ketentuan + checkbox "ID sudah benar" (P10).
- **O12. Waktu.** `expired_at` order dan `expiryPeriod` Duitku dihitung di tempat berbeda dan di detik berbeda. Tetapkan satu acuan. Duitku memakai `datetime` zona Asia/Jakarta.
- **O13. M12.** `MELO_SANDBOX` tanpa default (`config/services.php`): jika kosong, order Melo menjadi transaksi nyata dan memotong saldo. Harus diperbaiki sebelum job Melo dibuat.
- **O14. M10.** `MeloService` masih punya method di luar scope (cek nickname, bulk, 2FA, Smart). Bersihkan atau tegaskan tidak dipakai.
- **O15. Invoice publik (M1), versi mengecil.** `/invoice/{invoice}` terbuka untuk siapa pun yang tahu nomor. Dengan K1 yang tampil hanya status dan SN top up, tetapi tetap perlu aturan (nomor mengandung tanggal + 8 karakter acak).

### 🟡 Boleh menyusul

- M3, M4, M8, M11; cache daftar metode bayar; sinkron harga Melo; import CSV stok; branding; notifikasi WA.
- Perbedaan perilaku queue `database` (dev) vs Redis (production).
- Scheduler `accounts:release-expired` dan `AccountStockService::reserve()` menjadi tidak terpakai oleh checkout karena K1. Jangan dihapus tanpa izin.

## 5. Pertanyaan untuk PO ("grill")

Kolom "Jawaban PO" dikosongkan sengaja.

### A. Uang

| #   | Pertanyaan                                                                                                                  | Jawaban PO |
| --- | --------------------------------------------------------------------------------------------------------------------------- | ---------- |
| 1   | Pembeli membayar tepat setelah invoice kedaluwarsa dan uang masuk. Apa yang terjadi, dan siapa yang mengurusnya? (B1)       |            |
| 2   | Supplier gagal setelah dibayar: retry otomatis berapa kali sebelum menyerah? Lalu refund manual atau lewat gateway? SLA ke pembeli? (O5) |   |
| 3   | Siapa menanggung fee Duitku, apakah tampil di invoice? Boleh harga jual di bawah modal Melo? Ada guard saat `base_price` naik? (O2, O4) |       |
| 4   | Jika `amount` callback ≠ `orders.total`, order ditandai apa dan siapa diberi tahu? (O9)                                     |            |
| 5   | Snapshot `supplier_sku` di order disetujui (kolom baru, sekalian di edit migration `orders` untuk K2)? (O3)                 |            |

### B. Akun manual via WhatsApp (K1)

| #   | Pertanyaan                                                                                                                         | Jawaban PO |
| --- | ---------------------------------------------------------------------------------------------------------------------------------- | ---------- |
| 6   | Apakah produk akun tetap punya tombol/tautan "Hubungi via WhatsApp"? Jika ya: nomor WA admin disimpan di tabel `settings` atau `.env`, dan pesan apa yang di-prefill (nama produk, harga)? Jika tidak, cukup tampilan murni. | |
| 7   | Admin masih perlu menandai akun `sold`/`disabled` secara manual di Filament (Stok Akun sudah punya resource-nya) supaya stok yang tampil akurat? Siapa yang mengirim kredensial ke pembeli (di luar sistem)? |            |
| 8   | Apakah penjualan akun dicatat di tabel `orders` (omzet, dashboard `StoreOverview`)? | **Tidak.** Akun hanya display, jadi tidak ada order untuk akun (user, 2026-11-16). Konfirmasi: dashboard `StoreOverview` jangan menghitung akun. |
| 9   | Stok akun tetap tampil ke publik (P2)? Apakah status `reserved` dipakai admin untuk "ditahan sementara untuk pembeli WA"?          | **Tampil: ya** (display). Pemakaian `reserved` oleh admin masih terbuka. |
| 10  | Halaman invoice dan tampilan kredensial: dihapus untuk akun, atau tetap ada hanya untuk top up? (M1 mengecil)                      | **Tidak ada alur akun di invoice**, karena akun tidak punya order. Invoice hanya untuk top up. Kode tampilan kredensial di `invoice.blade.php` dan `Order::gameAccount()` jadi tidak terpakai; hapus atau biarkan? |
| 11  | Siapa yang menyetujui perubahan `PROJECT_SPEC.md` §4/§7/§12, `BUSINESS_FLOW.md` §3, dan Tahap 5, dan kapan? Sampai itu, dokumen lama dan K1 saling bertentangan. |   |
| 12  | Garansi/komplain akun: bagaimana jika kredensial yang dijual tidak valid?                                                           |            |

### C. Akses dan identitas

| #   | Pertanyaan                                                                                                       | Jawaban PO |
| --- | ---------------------------------------------------------------------------------------------------------------- | ---------- |
| 13  | Guest top up wajib mengisi email, WhatsApp, atau salah satu? (keputusan #7, O10)                                 |            |
| 14  | Invoice diamankan dengan apa: signed URL, token, atau cukup nomor invoice? (O15)                                 |            |
| 15  | Berapa order `pending` yang boleh dimiliki satu kontak/IP, dan berapa rate limit checkout? (B4)                  |            |

### D. Operasional

| #   | Pertanyaan                                                                                                         | Jawaban PO |
| --- | ------------------------------------------------------------------------------------------------------------------ | ---------- |
| 16  | Siapa yang melihat order macet (`paid` lebih dari N menit)? Lewat dashboard, email, atau WA? (O7)                  |            |
| 17  | Aksi admin apa yang sah pada order: retry kirim, tandai refund, batalkan? (keputusan #4, O8)                       |            |
| 18  | Seberapa cepat masalah harus terdeteksi? Menentukan frekuensi reconcile `transactionStatus()` dan `status()` Melo. |            |
| 19  | Siapa yang menjalankan queue worker dan scheduler di production (service terpisah di Coolify/Forge)? (item 15)     |            |

### E. Scope dan jadwal

| #   | Pertanyaan                                                                                                          | Jawaban PO |
| --- | ------------------------------------------------------------------------------------------------------------------- | ---------- |
| 20  | Notifikasi masuk 1 bulan atau ditunda? Email saja? (P9, keputusan #6)                                               |            |
| 21  | Refund masuk scope? (`PROJECT_SPEC.md` §10 masih [TBD])                                                             |            |
| 22  | Kapan akun sandbox Duitku (E1, paling lambat H11) dan Melo (E2, paling lambat H17) tersedia? Tanpa itu, semua tetap `Http::fake()`. |   |
| 23  | Tanggal H1? Tahap 3 (checkout) seharusnya mulai H11; berapa hari yang sudah terpakai?                                |            |
| 24  | Siapa yang mengonfirmasi algoritma signature Duitku (HMAC-SHA256 vs MD5) ke dokumentasi resmi atau dashboard sandbox? (§2) |         |

## 6. Cara menguji pembayaran sebelum ada tampilan (K3)

Backend-first. Semua lewat feature test PHPUnit + `Http::fake()` (`AGENTS.md` §7), tanpa Livewire. Test butuh MySQL lokal berjalan (`topup_game_test`).

1. **Service level** (sudah ada): `DuitkuServiceTest`, `WebhookSignatureTest`. Hijaukan dulu (§2).
2. **`OrderService`** (baru): order dari `Product` + target ID. Assert snapshot nama/harga integer, `invoice` unik, `expired_at` = sekarang + 15 menit, harga dari request diabaikan.
3. **Checkout ke Duitku:** `Http::fake()` untuk `/v2/inquiry`. Assert payload (`paymentAmount` = `total`, `merchantOrderId` = invoice, `expiryPeriod`) dan `payment_reference/url/code` tersimpan. Cabang HTTP 500/timeout sesuai jawaban O1.
4. **Callback:** POST form-urlencoded ke `/webhook/duitku` dengan signature benar. `resultCode 00` → `paid` → job Melo di-dispatch (`Bus::fake()`). Cabang: `01` → `failed`; callback ganda → tidak diproses dua kali; order final → diabaikan; nominal beda → ditolak; signature salah → 403.
5. **End-to-end satu test:** order `pending` → callback paid → Melo 201 (fake) → callback Melo `success` → order `success` + `serial_number`.
6. **Sandbox nyata (manual, setelah E1):** Postman CLI ke sandbox Duitku, `DUITKU_SANDBOX=true` saja. Cukup nomor invoice + `payment_url` dari respons, tidak perlu tampilan.
7. Perintah bantu (mis. `orders:create-test`) **tidak dibuat** kecuali disetujui PO. Default: factory + test.

## 7. Urutan kerja yang disarankan

1. Selesaikan `transactionStatus()` Duitku, nyalakan MySQL, jalankan `php artisan test --compact`, commit (B2).
2. Edit migration `orders` + `products` ke integer (K2) dan sesuaikan model/form/factory/test/`DATABASE_DESIGN.md`.
3. Perbaiki M12 (`MELO_SANDBOX` default `true`) dan buat state machine transisi status.
4. `OrderService` untuk top up (tanpa `reserve()`), diuji sesuai §6.
5. `HandlePaymentCallback` (B1, B3, O9).
6. Job Melo + `HandleSupplierCallback` (O5–O7).
7. Scheduler expire order + reconcile.
8. Produk akun murni display: pastikan halaman game tidak menawarkan checkout untuk akun; tambah tombol WA / aksi admin hanya jika jawaban 6–7 menyetujui.
9. Livewire checkout top up, notifikasi, halaman admin order macet.

Tiap langkah punya rencana dan persetujuan sendiri; dokumen ini tidak mengotorisasi implementasi apa pun.
