# Urutan Pengujian Transaksi

> Pastikan di `.env`: **`DUITKU_SANDBOX=true`** dan **`MELO_SANDBOX=true`**.

## 1. Uji Otomatis (Tanpa Sandbox & Tanpa App Payment)
```bash
php artisan test --compact tests/Feature/PaymentCallbackTest.php tests/Feature/SupplierCallbackTest.php tests/Feature/DuitkuServiceTest.php tests/Feature/MeloServiceTest.php tests/Feature/WebhookSignatureTest.php
```

## 2. Uji Simulasi Lokal di Browser (Tanpa App Payment)
1. Jalankan 2 terminal:
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   php artisan queue:work
   ```
2. Buat 1 order `pending` (via checkout nanti, atau `tinker`), lalu buka `http://127.0.0.1:8000/invoice/{INVOICE}`.
3. Kirim simulasi callback Duitku `POST /webhook/duitku` (`resultCode=00` + `signature = HMAC-SHA256(merchantCode + amount + merchantOrderId, DUITKU_API_KEY)`).
   - Cek di browser: status berubah `pending → paid`.
4. Kirim simulasi webhook Melo `POST /webhook/melo` (`status=success` + `serial_number` + header `X-H2H-Signature = HMAC-SHA256(raw_body, MELO_WEBHOOK_SECRET)`).
   - Cek di browser: status berubah menjadi `success` dan **SN tampil** di invoice.
5. Uji kasus gagal & keamanan:
   - Kirim callback `00` **2 kali** → tidak diproses ganda.
   - Kirim signature salah → ditolak `403` (Duitku) / `401` (Melo).
   - Kirim `resultCode=02` atau Melo `status=failed` → order `failed` + notifikasi admin.

## 3. Uji Sandbox Asli (Duitku + Melo) — Menjelang Rilis
1. Isi kredensial sandbox `DUITKU_*` & `MELO_*` di `.env`, pastikan `SANDBOX=true`.
2. Jalankan tunnel publik (ngrok / Dev Tunnels / Cloudflare Tunnel) → arahkan ke port `8000`.
3. Daftarkan URL callback di dashboard sandbox:
   - Duitku: `https://<tunnel>/webhook/duitku`
   - Melo: `https://<tunnel>/webhook/melo`
4. Pastikan `php artisan queue:work` aktif.
5. Buat transaksi → bayar lewat **Duitku Sandbox Simulator** (tanpa transfer asli) → cek status di `/invoice/{INVOICE}`, `/admin/orders`, dan `api_logs`.
6. Selesai uji: kembalikan `APP_URL=http://127.0.0.1:8000`, kosongkan `ASSET_URL`, lalu `php artisan config:clear`.
