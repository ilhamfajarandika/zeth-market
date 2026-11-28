<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, shallowRef } from 'vue';
import StatusBadge from '../../components/StatusBadge.vue';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseCard from '../../components/ui/BaseCard.vue';
import EmptyState from '../../components/ui/EmptyState.vue';
import AppLayout from '../../layouts/AppLayout.vue';

const { orders } = defineProps({
    orders: { type: Object, required: true },
});

const ALL_STATUS = 'all';

const statusTabs = [
    { value: ALL_STATUS, label: 'Semua' },
    { value: 'pending', label: 'Menunggu' },
    { value: 'processing', label: 'Diproses' },
    { value: 'success', label: 'Sukses' },
    { value: 'failed', label: 'Gagal / Expired' },
];

const activeStatus = shallowRef(ALL_STATUS);
const query = shallowRef('');

const filteredOrders = computed(() => {
    const keyword = query.value.toLowerCase().trim();

    return orders.data.filter((order) => {
        const matchesStatus =
            activeStatus.value === ALL_STATUS ||
            (activeStatus.value === 'processing'
                ? ['paid', 'processing'].includes(order.status.value)
                : activeStatus.value === 'failed'
                  ? ['failed', 'expired', 'refunded'].includes(order.status.value)
                  : order.status.value === activeStatus.value);

        const matchesKeyword =
            keyword === '' ||
            `${order.invoice} ${order.gameName ?? ''} ${order.productName}`.toLowerCase().includes(keyword);

        return matchesStatus && matchesKeyword;
    });
});

const hasActiveFilter = computed(() => activeStatus.value !== ALL_STATUS || query.value.trim() !== '');

function resetFilters() {
    activeStatus.value = ALL_STATUS;
    query.value = '';
}
</script>

<template>
    <Head title="Riwayat transaksi" />

    <AppLayout>
        <div class="mx-auto max-w-4xl">
            <div class="flex flex-col gap-4 border-b border-border pb-6 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-brand-highlight">
                        <span class="h-px w-6 bg-brand" aria-hidden="true"></span>
                        Akun pelanggan
                    </p>
                    <h1 class="mt-3 font-display text-2xl font-bold tracking-tight sm:text-3xl">Riwayat transaksi</h1>
                    <p class="mt-1.5 text-sm text-muted">
                        {{ orders.total }} transaksi tercatat pada akunmu.
                    </p>
                </div>

                <BaseButton :href="`${route('home')}#katalog`" variant="secondary" size="md">
                    Top up lagi
                </BaseButton>
            </div>

            <div v-if="orders.data.length" class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" role="group" aria-label="Filter status transaksi">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.value"
                        type="button"
                        :aria-pressed="activeStatus === tab.value"
                        class="focus-ring shrink-0 cursor-pointer rounded-md border px-3.5 py-2 font-mono text-[11px] uppercase tracking-[0.12em] transition-colors"
                        :class="
                            activeStatus === tab.value
                                ? 'border-brand bg-brand/10 font-semibold text-text'
                                : 'border-border text-muted hover:border-brand/50 hover:text-text'
                        "
                        @click="activeStatus = tab.value"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <label class="relative w-full sm:w-64">
                    <span class="sr-only">Cari invoice atau game</span>
                    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        v-model="query"
                        type="search"
                        autocomplete="off"
                        placeholder="Cari invoice atau game"
                        class="focus-ring w-full rounded-md border border-border bg-surface py-2.5 pr-3 pl-9 text-sm text-text placeholder:text-muted focus:border-brand-highlight"
                    />
                </label>
            </div>

            <div v-if="filteredOrders.length" class="mt-6 flex flex-col gap-3">
                <BaseCard v-for="order in filteredOrders" :key="order.invoice" as="article" padding="md">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2.5">
                                <span class="font-mono text-xs font-bold tracking-wider text-text">{{ order.invoice }}</span>
                                <StatusBadge :status="order.status" />
                            </div>
                            <h2 class="mt-2 font-display text-base font-bold tracking-tight text-text">
                                {{ order.gameName ? `${order.gameName} — ` : '' }}{{ order.productName }}
                            </h2>
                            <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 font-mono text-[11px] text-muted">
                                <span v-if="order.target">ID: {{ order.target }}</span>
                                <span v-if="order.createdAt">{{ order.createdAt }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 border-t border-border pt-3 sm:flex-col sm:items-end sm:border-t-0 sm:pt-0">
                            <p class="font-mono text-base font-bold text-brand-highlight">{{ order.total }}</p>
                            <Link
                                :href="order.url"
                                class="focus-ring inline-flex items-center gap-1.5 rounded-md border border-border bg-surface-raised px-3.5 py-2 font-mono text-[11px] font-semibold uppercase tracking-[0.12em] text-text transition-colors hover:border-brand/60 hover:text-brand-highlight"
                            >
                                Lihat invoice
                                <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                                </svg>
                            </Link>
                        </div>
                    </div>
                </BaseCard>
            </div>

            <EmptyState
                v-else
                class="mt-6"
                :title="hasActiveFilter ? 'Transaksi tidak ditemukan' : 'Belum ada transaksi'"
                :description="
                    hasActiveFilter
                        ? 'Tidak ada transaksi yang cocok dengan filter atau kata kunci pencarianmu.'
                        : 'Pesanan yang kamu buat saat login akan muncul di sini.'
                "
            >
                <BaseButton v-if="hasActiveFilter" variant="secondary" @click="resetFilters">
                    Reset filter
                </BaseButton>
                <BaseButton v-else :href="`${route('home')}#katalog`">
                    Mulai top up
                </BaseButton>
            </EmptyState>

            <nav
                v-if="orders.lastPage > 1"
                class="mt-6 flex items-center justify-between gap-4 border-t border-border pt-5"
                aria-label="Navigasi halaman riwayat"
            >
                <Link
                    v-if="orders.prevUrl"
                    :href="orders.prevUrl"
                    class="focus-ring rounded-md border border-border bg-surface px-4 py-2 font-mono text-xs uppercase tracking-[0.12em] text-text transition-colors hover:border-brand/60 hover:text-brand-highlight"
                >
                    Sebelumnya
                </Link>
                <span v-else class="rounded-md border border-border px-4 py-2 font-mono text-xs uppercase tracking-[0.12em] text-muted opacity-45">
                    Sebelumnya
                </span>

                <span class="font-mono text-xs text-muted">
                    Halaman {{ orders.currentPage }} dari {{ orders.lastPage }}
                </span>

                <Link
                    v-if="orders.nextUrl"
                    :href="orders.nextUrl"
                    class="focus-ring rounded-md border border-border bg-surface px-4 py-2 font-mono text-xs uppercase tracking-[0.12em] text-text transition-colors hover:border-brand/60 hover:text-brand-highlight"
                >
                    Berikutnya
                </Link>
                <span v-else class="rounded-md border border-border px-4 py-2 font-mono text-xs uppercase tracking-[0.12em] text-muted opacity-45">
                    Berikutnya
                </span>
            </nav>
        </div>
    </AppLayout>
</template>
