<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Terapkan status transaksi Melo ke order. Dipakai untuk webhook Melo dan untuk respons
 * topup()/status() dari SendTopupToMelo. Order final tidak pernah diubah (callback bisa datang berulang).
 */
class HandleSupplierCallback implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload  data transaksi Melo (buyer_trx_id, status, serial_number, message, ...)
     */
    public function __construct(public array $payload) {}

    public function handle(): void
    {
        // Event tindakan pelanggan/2FA sudah tersimpan di api_logs dan tidak boleh menyelesaikan order.
        if (isset($this->payload['event'])) {
            return;
        }

        DB::transaction(function (): void {
            $invoice = (string) ($this->payload['buyer_trx_id'] ?? '');
            $order = Order::query()->where('invoice', $invoice)->lockForUpdate()->first();

            if ($order === null) {
                Log::warning('Callback Melo untuk order tidak dikenal.', ['invoice' => $invoice]);

                return;
            }

            if ($order->status->isFinal()) {
                return;
            }

            $status = $this->payload['status'] ?? null;
            $order->fill([
                'supplier_status' => $status,
                'supplier_reference' => $order->supplier_reference ?? ($this->payload['id'] ?? null),
            ]);

            if ($status === 'success') {
                $order->fill([
                    'status' => OrderStatus::Success,
                    'serial_number' => $this->payload['serial_number'] ?? null,
                    'completed_at' => now(),
                ]);
            } elseif ($status === 'failed') {
                $order->fill([
                    'status' => OrderStatus::Failed,
                    'supplier_message' => $this->payload['message'] ?? $this->payload['error_code'] ?? null,
                ]);
                $this->alertAdmins('Top up gagal setelah dibayar', "Melo menggagalkan order {$order->invoice}. Pembeli sudah membayar, tangani manual.");
            } elseif ($status === 'refunded') {
                $this->alertAdmins('Transaksi Melo di-refund', "Melo menandai order {$order->invoice} sebagai refunded. Status order tidak diubah, tangani manual.");
            }

            $order->save();
        });
    }

    private function alertAdmins(string $title, string $body): void
    {
        Log::warning($body);

        Notification::make()->title($title)->body($body)->danger()
            ->sendToDatabase(User::query()->where('role', UserRole::Admin)->get());
    }
}
