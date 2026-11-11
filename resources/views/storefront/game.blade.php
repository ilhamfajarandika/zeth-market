<x-layouts.app :title="$game->name">
    <h1 class="mb-2 text-2xl font-bold">{{ $game->name }}</h1>
    <p class="mb-8 text-slate-400">{{ $game->publisher }}</p>

    {{-- TODO (minggu 2): ganti dengan komponen Livewire checkout (input ID, pilih produk, pilih metode bayar). --}}
    <section class="mb-8 rounded-xl border border-white/10 bg-white/5 p-4">
        <h2 class="mb-3 font-semibold">1. Masukkan data akun</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <input type="text" placeholder="{{ $game->user_id_label }}" class="rounded-lg border border-white/10 bg-slate-900 px-3 py-2">
            @if ($game->requires_zone_id)
                <input type="text" placeholder="{{ $game->zone_id_label }}" class="rounded-lg border border-white/10 bg-slate-900 px-3 py-2">
            @endif
        </div>
    </section>

    @foreach ($products as $typeLabel => $items)
        <section class="mb-8">
            <h2 class="mb-3 font-semibold">{{ $typeLabel }}</h2>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($items as $product)
                    @php($outOfStock = $product->isAccount() && $product->available_accounts_count === 0)

                    <div @class([
                        'rounded-xl border p-4',
                        'border-white/10 bg-white/5' => ! $outOfStock,
                        'border-white/5 bg-white/[0.02] opacity-50' => $outOfStock,
                    ])>
                        <p class="font-medium">{{ $product->name }}</p>
                        <p class="mt-1 text-violet-300">{{ Number::currency($product->price, 'IDR', 'id') }}</p>

                        @if ($product->isAccount())
                            <p @class([
                                'mt-2 inline-block rounded-full px-2 py-0.5 text-xs',
                                'bg-emerald-500/20 text-emerald-300' => $product->available_accounts_count > 3,
                                'bg-amber-500/20 text-amber-300' => $product->available_accounts_count > 0 && $product->available_accounts_count <= 3,
                                'bg-rose-500/20 text-rose-300' => $outOfStock,
                            ])>
                                {{ $outOfStock ? 'Stok habis' : 'Stok: '.$product->available_accounts_count }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</x-layouts.app>
