<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, shallowRef } from 'vue';
import GameCover from '../components/GameCover.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseBadge from '../components/ui/BaseBadge.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseCheckbox from '../components/ui/BaseCheckbox.vue';
import BaseInput from '../components/ui/BaseInput.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import AppLayout from '../layouts/AppLayout.vue';

// TODO (Tahap 3, Backend): form ini akan dikirim ke endpoint checkout (kontrak di docs/TASK_DIVISION.md §3.4).
const { game, productGroups } = defineProps({
    game: { type: Object, required: true },
    productGroups: { type: Array, required: true },
});

const page = usePage();

const selectedProductId = shallowRef(null);
const targetUserId = shallowRef('');
const targetZoneId = shallowRef('');
const email = shallowRef('');
const whatsapp = shallowRef('');
const idConfirmed = shallowRef(false);
const paymentGroups = ['QRIS', 'Virtual Account', 'E-Wallet'];

const selectedProduct = computed(
    () => productGroups.flatMap((group) => group.products).find((product) => product.id === selectedProductId.value) ?? null,
);

function isOutOfStock(product) {
    return product.isAccount && product.stock === 0;
}

function isSelected(product) {
    return selectedProductId.value === product.id;
}

function productCardClass(product) {
    return [
        isSelected(product)
            ? 'border-brand bg-brand/10'
            : 'border-border bg-ground hover:border-brand/50',
        isOutOfStock(product) ? 'cursor-not-allowed opacity-45' : 'cursor-pointer',
    ];
}

function stockBadgeVariant(product) {
    if (isOutOfStock(product)) {
        return 'danger';
    }

    return product.stock > 3 ? 'success' : 'warning';
}
</script>

<template>
    <Head :title="game.name" />

    <AppLayout>
        <section class="relative overflow-hidden rounded-lg border border-border bg-surface">
            <img v-if="game.bannerUrl" :src="game.bannerUrl" alt="" class="absolute inset-0 size-full object-cover opacity-30" />
            <div v-else class="pointer-events-none absolute inset-0 bg-tech-grid opacity-45" aria-hidden="true"></div>
            <span class="absolute inset-x-0 top-0 h-0.5 bg-brand" aria-hidden="true"></span>

            <div class="relative flex items-end gap-4 p-5 pt-10 sm:gap-6 sm:p-8 sm:pt-14">
                <GameCover :game="game" class="size-20 shrink-0 rounded-lg border border-border sm:size-28" />
                <div class="min-w-0">
                    <Link
                        :href="page.props.links.home"
                        class="focus-ring inline-flex items-center gap-1.5 rounded-sm font-mono text-[11px] uppercase tracking-[0.14em] text-muted transition-colors hover:text-brand-highlight"
                    >
                        <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Semua game
                    </Link>
                    <h1 class="mt-2 truncate font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ game.name }}</h1>
                    <p class="mt-0.5 font-mono text-xs uppercase tracking-[0.12em] text-muted">{{ game.publisher }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <BaseBadge variant="success">Proses otomatis</BaseBadge>
                        <BaseBadge variant="neutral">Pembayaran aman</BaseBadge>
                    </div>
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-5 lg:col-span-2">
                <BaseCard>
                    <h2 class="mb-4 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">01</span>
                        Masukkan data akun
                    </h2>
                    <div class="grid gap-3" :class="{ 'sm:grid-cols-2': game.requiresZoneId }">
                        <BaseInput
                            v-model="targetUserId"
                            :label="game.userIdLabel"
                            type="text"
                            inputmode="numeric"
                            :placeholder="`Masukkan ${game.userIdLabel}`"
                        />
                        <BaseInput
                            v-if="game.requiresZoneId"
                            v-model="targetZoneId"
                            :label="game.zoneIdLabel"
                            type="text"
                            inputmode="numeric"
                            :placeholder="`Masukkan ${game.zoneIdLabel}`"
                        />
                    </div>
                    <BaseAlert v-if="game.instruction" class="mt-4">
                        {{ game.instruction }}
                    </BaseAlert>
                </BaseCard>

                <BaseCard>
                    <h2 class="mb-4 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">02</span>
                        Pilih item
                    </h2>

                    <template v-if="productGroups.length">
                        <div v-for="(group, groupIndex) in productGroups" :key="group.label" :class="{ 'mt-6': groupIndex > 0 }">
                            <h3 class="mb-3 font-mono text-[11px] uppercase tracking-[0.16em] text-muted">{{ group.label }}</h3>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                <button
                                    v-for="product in group.products"
                                    :key="product.id"
                                    type="button"
                                    :disabled="isOutOfStock(product)"
                                    :aria-pressed="isSelected(product)"
                                    class="focus-ring relative flex flex-col items-start rounded-md border p-3.5 text-left transition-colors"
                                    :class="productCardClass(product)"
                                    @click="selectedProductId = product.id"
                                >
                                    <span class="pr-6 font-display text-sm font-semibold leading-snug text-text">{{ product.name }}</span>
                                    <span v-if="product.strikePrice" class="mt-1.5 font-mono text-xs text-muted line-through">{{ product.strikePrice }}</span>
                                    <span class="mt-1 font-mono text-sm font-bold text-brand-highlight">{{ product.price }}</span>

                                    <template v-if="product.isAccount">
                                        <BaseBadge :variant="stockBadgeVariant(product)" class="mt-2.5">
                                            {{ isOutOfStock(product) ? 'Stok habis' : `Stok: ${product.stock}` }}
                                        </BaseBadge>
                                        <span v-if="product.description" class="mt-2 line-clamp-2 text-xs text-muted">{{ product.description }}</span>
                                    </template>

                                    <svg
                                        v-if="isSelected(product)"
                                        aria-hidden="true"
                                        class="absolute top-2.5 right-2.5 size-4 text-brand-highlight"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <EmptyState v-else title="Belum ada item" description="Produk untuk game ini belum tersedia saat ini." />
                </BaseCard>

                <BaseCard>
                    <h2 class="mb-1 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">03</span>
                        Pilih pembayaran
                    </h2>
                    <p class="mb-4 pl-10 text-xs text-muted">Daftar metode akan diambil dari payment gateway setelah integrasi.</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div
                            v-for="paymentGroup in paymentGroups"
                            :key="paymentGroup"
                            class="flex items-center justify-between rounded-md border border-dashed border-border bg-ground px-3.5 py-3 text-sm text-muted"
                        >
                            <span>{{ paymentGroup }}</span>
                            <BaseBadge variant="neutral">Contoh</BaseBadge>
                        </div>
                    </div>
                </BaseCard>

                <BaseCard>
                    <h2 class="mb-4 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">04</span>
                        Detail kontak
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <BaseInput v-model="email" label="Email" type="email" placeholder="nama@email.com" />
                        <BaseInput v-model="whatsapp" label="WhatsApp" type="tel" inputmode="tel" placeholder="08xxxxxxxxxx" />
                    </div>
                    <p class="mt-3 text-xs text-muted">Invoice dan status pesanan dikirim ke kontak ini.</p>
                </BaseCard>
            </div>

            <aside class="hidden lg:block">
                <BaseCard as="div" class="sticky top-24">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Ringkasan</p>
                    <h2 class="mt-1 font-display text-lg font-bold tracking-tight">Detail pesanan</h2>
                    <dl class="mt-4 flex flex-col gap-3 border-t border-border pt-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Game</dt>
                            <dd class="text-right font-medium">{{ game.name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Item</dt>
                            <dd class="text-right font-medium">{{ selectedProduct?.name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Harga</dt>
                            <dd class="text-right font-mono font-bold text-brand-highlight">{{ selectedProduct?.price ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Biaya admin</dt>
                            <dd class="text-right text-xs text-muted">Dihitung saat checkout</dd>
                        </div>
                    </dl>

                    <BaseCheckbox v-model="idConfirmed" class="mt-5">
                        ID yang saya masukkan sudah benar. Kesalahan ID bukan tanggung jawab penjual.
                    </BaseCheckbox>

                    <BaseButton disabled block size="lg" class="mt-4">
                        Beli Sekarang
                    </BaseButton>
                    <p class="mt-2.5 text-center font-mono text-[10px] uppercase tracking-[0.12em] text-muted">
                        Checkout aktif setelah integrasi pembayaran
                    </p>
                </BaseCard>
            </aside>
        </div>

        <div class="fixed inset-x-0 bottom-[57px] z-30 border-t border-border bg-surface px-4 py-3 lg:hidden">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-xs text-muted">{{ selectedProduct?.name ?? 'Belum pilih item' }}</p>
                    <p class="font-mono text-sm font-bold text-brand-highlight">{{ selectedProduct?.price ?? '-' }}</p>
                </div>
                <BaseButton disabled size="md" class="shrink-0">
                    Beli Sekarang
                </BaseButton>
            </div>
        </div>
    </AppLayout>
</template>
