<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, shallowRef } from 'vue';
import GameCover from '../components/GameCover.vue';
import AppLayout from '../layouts/AppLayout.vue';

// TODO (Tahap 3, Backend): form ini akan dikirim ke endpoint checkout (kontrak di docs/TASK_DIVISION.md §3.4).
const { game, productGroups } = defineProps({
    game: { type: Object, required: true },
    productGroups: { type: Array, required: true },
});

const page = usePage();

const selectedProductId = shallowRef(null);
const idConfirmed = shallowRef(false);
const paymentGroups = ['QRIS', 'Virtual Account', 'E-Wallet'];

const selectedProduct = computed(
    () => productGroups.flatMap((group) => group.products).find((product) => product.id === selectedProductId.value) ?? null,
);

const inputClass =
    'w-full rounded-xl border border-white/10 bg-slate-900 px-3 py-2.5 text-sm placeholder:text-slate-500 focus:border-violet-400 focus:ring-2 focus:ring-violet-500/30 focus:outline-none';

function isOutOfStock(product) {
    return product.isAccount && product.stock === 0;
}

function isSelected(product) {
    return selectedProductId.value === product.id;
}

function productCardClass(product) {
    return [
        isSelected(product) ? 'border-violet-400 bg-violet-500/15 ring-2 ring-violet-500/40' : 'border-white/10 bg-slate-900/60 hover:border-white/25',
        { 'cursor-not-allowed opacity-50': isOutOfStock(product) },
    ];
}

function stockClass(product) {
    if (isOutOfStock(product)) {
        return 'bg-rose-500/20 text-rose-300';
    }

    return product.stock > 3 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300';
}
</script>

<template>
    <Head :title="game.name" />

    <AppLayout>
        <!-- Header game -->
        <section class="relative overflow-hidden rounded-3xl border border-white/10">
            <img v-if="game.bannerUrl" :src="game.bannerUrl" alt="" class="absolute inset-0 size-full object-cover opacity-40" />
            <div v-else class="absolute inset-0 bg-gradient-to-br from-violet-600/40 via-slate-900 to-fuchsia-600/30"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/60 to-transparent"></div>

            <div class="relative flex items-end gap-4 p-5 pt-16 sm:gap-6 sm:p-8 sm:pt-24">
                <GameCover :game="game" class="size-20 shrink-0 rounded-2xl shadow-xl ring-2 ring-white/20 sm:size-28" />
                <div class="min-w-0">
                    <Link :href="page.props.links.home" class="text-xs text-slate-400 hover:text-white">← Semua game</Link>
                    <h1 class="mt-1 truncate text-2xl font-extrabold sm:text-3xl">{{ game.name }}</h1>
                    <p class="text-slate-400">{{ game.publisher }}</p>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 font-medium text-emerald-300 ring-1 ring-emerald-500/30">⚡ Proses otomatis</span>
                        <span class="rounded-full bg-white/10 px-2.5 py-1 font-medium text-slate-300 ring-1 ring-white/15">🔒 Pembayaran aman</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- 1. Data akun -->
                <section class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h2 class="mb-4 flex items-center gap-3 font-semibold">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-violet-500 text-sm font-bold">1</span>
                        Masukkan data akun
                    </h2>
                    <div class="grid gap-3" :class="{ 'sm:grid-cols-2': game.requiresZoneId }">
                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm text-slate-300">{{ game.userIdLabel }}</span>
                            <input type="text" inputmode="numeric" :placeholder="`Masukkan ${game.userIdLabel}`" :class="inputClass" />
                        </label>
                        <label v-if="game.requiresZoneId" class="flex flex-col gap-1.5">
                            <span class="text-sm text-slate-300">{{ game.zoneIdLabel }}</span>
                            <input type="text" inputmode="numeric" :placeholder="`Masukkan ${game.zoneIdLabel}`" :class="inputClass" />
                        </label>
                    </div>
                    <p v-if="game.instruction" class="mt-3 rounded-xl bg-slate-900/60 p-3 text-xs text-slate-400">ℹ️ {{ game.instruction }}</p>
                </section>

                <!-- 2. Pilih item -->
                <section class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h2 class="mb-4 flex items-center gap-3 font-semibold">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-violet-500 text-sm font-bold">2</span>
                        Pilih item
                    </h2>

                    <template v-if="productGroups.length">
                        <div v-for="(group, groupIndex) in productGroups" :key="group.label" :class="{ 'mt-6': groupIndex > 0 }">
                            <h3 class="mb-3 text-sm font-medium text-slate-400">{{ group.label }}</h3>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <button
                                    v-for="product in group.products"
                                    :key="product.id"
                                    type="button"
                                    :disabled="isOutOfStock(product)"
                                    :aria-pressed="isSelected(product)"
                                    class="relative flex flex-col items-start rounded-xl border p-3 text-left transition"
                                    :class="productCardClass(product)"
                                    @click="selectedProductId = product.id"
                                >
                                    <span class="text-sm font-semibold">{{ product.name }}</span>
                                    <span v-if="product.strikePrice" class="mt-1 text-xs text-slate-500 line-through">{{ product.strikePrice }}</span>
                                    <span class="mt-0.5 text-sm font-bold text-violet-300">{{ product.price }}</span>

                                    <template v-if="product.isAccount">
                                        <span class="mt-2 inline-block rounded-full px-2 py-0.5 text-xs font-medium" :class="stockClass(product)">
                                            {{ isOutOfStock(product) ? 'Stok habis' : `Stok: ${product.stock}` }}
                                        </span>
                                        <span v-if="product.description" class="mt-2 line-clamp-2 text-xs text-slate-400">{{ product.description }}</span>
                                    </template>

                                    <svg v-if="isSelected(product)" aria-hidden="true" class="absolute top-2 right-2 size-5 text-violet-300" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" /></svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <p v-else class="rounded-xl border border-dashed border-white/10 py-8 text-center text-sm text-slate-400">Belum ada item untuk game ini.</p>
                </section>

                <!-- 3. Metode pembayaran -->
                <section class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h2 class="mb-1 flex items-center gap-3 font-semibold">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-violet-500 text-sm font-bold">3</span>
                        Pilih pembayaran
                    </h2>
                    <p class="mb-4 ml-10 text-xs text-slate-500">Daftar metode akan diambil dari payment gateway setelah integrasi.</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div v-for="paymentGroup in paymentGroups" :key="paymentGroup" class="flex items-center justify-between rounded-xl border border-dashed border-white/10 bg-slate-900/40 p-3 text-sm text-slate-400">
                            {{ paymentGroup }}
                            <span class="rounded bg-white/10 px-1.5 py-0.5 text-[10px] tracking-wide uppercase">Contoh</span>
                        </div>
                    </div>
                </section>

                <!-- 4. Kontak -->
                <section class="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h2 class="mb-4 flex items-center gap-3 font-semibold">
                        <span class="grid size-7 shrink-0 place-items-center rounded-lg bg-violet-500 text-sm font-bold">4</span>
                        Detail kontak
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm text-slate-300">Email</span>
                            <input type="email" placeholder="nama@email.com" :class="inputClass" />
                        </label>
                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm text-slate-300">WhatsApp</span>
                            <input type="tel" inputmode="tel" placeholder="08xxxxxxxxxx" :class="inputClass" />
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Invoice dan status pesanan dikirim ke kontak ini.</p>
                </section>
            </div>

            <!-- Ringkasan (desktop) -->
            <aside class="hidden lg:block">
                <div class="sticky top-24 rounded-2xl border border-white/10 bg-white/5 p-5">
                    <h2 class="font-semibold">Ringkasan pesanan</h2>
                    <dl class="mt-4 flex flex-col gap-3 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Game</dt><dd class="text-right">{{ game.name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Item</dt><dd class="text-right">{{ selectedProduct?.name ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Harga</dt><dd class="text-right">{{ selectedProduct?.price ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-400">Biaya admin</dt><dd class="text-right text-slate-500">Dihitung saat checkout</dd></div>
                    </dl>
                    <label class="mt-5 flex items-start gap-2 text-xs text-slate-300">
                        <input v-model="idConfirmed" type="checkbox" class="mt-0.5 rounded border-white/20 bg-slate-900 text-violet-500 focus:ring-violet-500/40" />
                        ID yang saya masukkan sudah benar. Kesalahan ID bukan tanggung jawab penjual.
                    </label>
                    <button type="button" disabled class="mt-4 w-full cursor-not-allowed rounded-xl bg-violet-500/40 py-3 text-sm font-semibold text-white/70">Beli Sekarang</button>
                    <p class="mt-2 text-center text-xs text-slate-500">Checkout aktif setelah integrasi pembayaran.</p>
                </div>
            </aside>
        </div>

        <!-- Ringkasan (HP) -->
        <div class="fixed inset-x-0 bottom-[57px] z-30 border-t border-white/10 bg-slate-900/95 px-4 py-3 backdrop-blur lg:hidden">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-xs text-slate-400">{{ selectedProduct?.name ?? 'Belum pilih item' }}</p>
                    <p class="font-bold text-violet-300">{{ selectedProduct?.price ?? '-' }}</p>
                </div>
                <button type="button" disabled class="shrink-0 cursor-not-allowed rounded-xl bg-violet-500/40 px-5 py-2.5 text-sm font-semibold text-white/70">Beli Sekarang</button>
            </div>
        </div>
    </AppLayout>
</template>
