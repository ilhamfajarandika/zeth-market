<x-layouts.app :title="'Invoice '.$order->invoice">
    <div class="mx-auto max-w-lg rounded-xl border border-white/10 bg-white/5 p-6">
        <p class="text-sm text-slate-400">Invoice</p>
        <h1 class="mb-4 text-xl font-bold">{{ $order->invoice }}</h1>

        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-slate-400">Game</dt><dd>{{ $order->game->name }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Produk</dt><dd>{{ $order->product_name }}</dd></div>
            @if ($order->target_user_id)
                <div class="flex justify-between"><dt class="text-slate-400">ID Tujuan</dt><dd>{{ $order->target_user_id }}{{ $order->target_zone_id ? ' ('.$order->target_zone_id.')' : '' }}</dd></div>
            @endif
            <div class="flex justify-between"><dt class="text-slate-400">Total</dt><dd>{{ Number::currency($order->total, 'IDR', 'id') }}</dd></div>
            <div class="flex justify-between"><dt class="text-slate-400">Status</dt><dd>{{ $order->status->getLabel() }}</dd></div>
        </dl>

        @if ($order->status === \App\Enums\OrderStatus::Success && $order->gameAccount)
            <div class="mt-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4">
                <p class="mb-2 font-semibold text-emerald-300">Detail akun</p>
                <pre class="whitespace-pre-wrap text-sm">{{ $order->gameAccount->credentials }}</pre>
            </div>
        @endif
    </div>
</x-layouts.app>
