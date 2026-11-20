<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Services\Supplier\MeloService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Kirim order top up yang sudah dibayar ke Melo. Retry aman karena buyer_trx_id = invoice
 * (Melo mengembalikan transaksi yang sama tanpa debit ganda).
 */
class SendTopupToMelo implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120];

    public function __construct(public Order $order) {}

    public function uniqueId(): string
    {
        return (string) $this->order->id;
    }

    public function handle(MeloService $melo): void
    {
        if ($this->order->refresh()->status !== OrderStatus::Paid) {
            return;
        }

        $response = $this->attempt(fn (): Response => $melo->topup($this->order));

        // Hasil tidak diketahui (5xx/timeout): cek dulu apakah transaksi sudah ada sebelum mengirim ulang.
        if ($response === null || $response->serverError()) {
            $response = $this->attempt(fn (): Response => $melo->status($this->order->invoice));

            if ($response === null || ! $response->successful()) {
                throw new RuntimeException("Melo belum menerima order {$this->order->invoice}, dicoba ulang.");
            }
        }

        if ($response->clientError()) {
            $this->order->update(['supplier_message' => $response->json('message') ?? "HTTP {$response->status()}"]);
            $this->alertAdmins("Melo menolak order {$this->order->invoice} (HTTP {$response->status()}). Order tetap Dibayar, tangani manual.");

            return;
        }

        $data = (array) $response->json('data');

        DB::transaction(function () use ($data): void {
            $order = Order::query()->lockForUpdate()->findOrFail($this->order->id);

            // Webhook Melo bisa lebih dulu tiba dan sudah menyelesaikan order.
            if ($order->status === OrderStatus::Paid) {
                $order->update([
                    'status' => OrderStatus::Processing,
                    'supplier_reference' => $data['id'] ?? null,
                    'supplier_status' => $data['status'] ?? null,
                ]);
            }
        });

        HandleSupplierCallback::dispatchSync([...$data, 'buyer_trx_id' => $this->order->invoice]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->alertAdmins("Order {$this->order->invoice} gagal dikirim ke Melo setelah {$this->tries} percobaan. Order tetap Dibayar, tangani manual.");
    }

    /**
     * @param  callable(): Response  $request
     */
    private function attempt(callable $request): ?Response
    {
        try {
            return $request();
        } catch (ConnectionException) {
            return null;
        }
    }

    private function alertAdmins(string $body): void
    {
        Log::warning($body);

        Notification::make()->title('Pengiriman top up ke Melo bermasalah')->body($body)->danger()
            ->sendToDatabase(User::query()->where('role', UserRole::Admin)->get());
    }
}
