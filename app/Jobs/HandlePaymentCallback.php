<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Proses callback Duitku yang signature-nya sudah diverifikasi controller.
 * Idempotent: hanya order pending yang berubah, jadi callback ulang tidak mengirim top up dua kali.
 */
class HandlePaymentCallback implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload  body callback Duitku tanpa field signature
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $invoice = (string) ($this->payload['merchantOrderId'] ?? '');
            $order = Order::query()->where('invoice', $invoice)->lockForUpdate()->first();

            // Akun game dijual manual via WhatsApp (K1), jadi hanya order top up yang diproses.
            if ($order === null || $order->product_type !== ProductType::Topup) {
                Log::warning('Callback Duitku untuk order tidak dikenal atau bukan top up.', ['invoice' => $invoice]);

                return;
            }

            $isPaid = ($this->payload['resultCode'] ?? null) === '00';

            if ($order->status !== OrderStatus::Pending) {
                if ($isPaid && in_array($order->status, [OrderStatus::Expired, OrderStatus::Failed], true)) {
                    $this->alertAdmins('Pembayaran masuk untuk order yang sudah ditutup', "Order {$order->invoice} sudah berstatus {$order->status->getLabel()} saat pembayaran lunas diterima. Tangani manual.");
                }

                return;
            }

            if ($isPaid && (int) ($this->payload['amount'] ?? 0) !== $order->total) {
                $this->alertAdmins('Nominal pembayaran tidak cocok', "Callback order {$order->invoice} membawa nominal {$this->payload['amount']}, tagihan {$order->total}. Order tidak diproses.");

                return;
            }

            if ($isPaid) {
                $order->update([
                    'status' => OrderStatus::Paid,
                    'paid_at' => now(),
                    'payment_reference' => $this->payload['reference'] ?? $order->payment_reference,
                ]);

                SendTopupToMelo::dispatch($order)->afterCommit();
            } elseif (($this->payload['resultCode'] ?? null) === '01') {
                $order->update(['status' => OrderStatus::Failed]);
            }
        });
    }

    private function alertAdmins(string $title, string $body): void
    {
        Log::warning($body);

        Notification::make()->title($title)->body($body)->danger()
            ->sendToDatabase(User::query()->where('role', UserRole::Admin)->get());
    }
}
