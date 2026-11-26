<script setup>
import { Head, Link, usePage, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import CopyButton from '../components/CopyButton.vue';
import StatusBadge from '../components/StatusBadge.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import AppLayout from '../layouts/AppLayout.vue';

const { order } = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();

// Status berubah lewat callback Duitku/Melo di server; cek ulang tiap 5 detik sampai status final.
const { stop } = usePoll(1000, { only: ['order'] }, { autoStart: !order.status.isFinal });
watch(
    () => order.status.isFinal,
    (isFinal) => isFinal && stop(),
);

const progressSteps = ['Pesanan dibuat', 'Dibayar', 'Diproses', 'Selesai'];
const stepByStatus = { pending: 0, paid: 1, processing: 2, success: 3 };
const currentStep = computed(() => stepByStatus[order.status.value] ?? null);

const details = computed(() =>
    [
        ['Game', order.gameName],
        ['Item', order.productName],
        ['ID tujuan', order.target],
        ['Metode bayar', order.paymentMethod],
        ['Tanggal', order.createdAt],
    ].filter(([, value]) => value),
);
</script>

<template>

    <Head :title="`Invoice ${order.invoice}`" />

    <AppLayout>
        <div class="mx-auto flex max-w-2xl flex-col gap-5">
            <BaseCard padding="lg">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Nomor invoice</p>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                            <h1 class="font-mono text-xl font-bold tracking-wide sm:text-2xl">{{ order.invoice }}</h1>
                            <CopyButton :text="order.invoice" />
                        </div>
                    </div>
                    <StatusBadge :status="order.status" />
                </div>

                <ol v-if="currentStep !== null" class="mt-6 grid grid-cols-4 gap-2">
                    <li v-for="(stepLabel, index) in progressSteps" :key="stepLabel" class="flex flex-col gap-2">
                        <span class="h-1 rounded-xs" :class="index <= currentStep ? 'bg-brand' : 'bg-border'"></span>
                        <span class="font-mono text-[10px] uppercase tracking-[0.06em] sm:text-[11px]"
                            :class="index <= currentStep ? 'text-text' : 'text-muted'">
                            {{ stepLabel }}
                        </span>
                    </li>
                </ol>

                <BaseAlert v-else variant="danger" class="mt-5">
                    Pesanan ini berstatus <strong>{{ order.status.label }}</strong>. Hubungi admin jika kamu sudah
                    melakukan pembayaran.
                </BaseAlert>
            </BaseCard>

            <BaseCard padding="lg">
                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Rincian</p>
                <h2 class="mt-1 mb-4 font-display text-lg font-bold tracking-tight">Detail pesanan</h2>
                <dl class="flex flex-col gap-3 text-sm">
                    <div v-for="[label, value] in details" :key="label" class="flex justify-between gap-4">
                        <dt class="text-muted">{{ label }}</dt>
                        <dd class="text-right font-medium">{{ value }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-border pt-3">
                        <dt class="text-muted">Harga</dt>
                        <dd class="font-mono">{{ order.price }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Biaya admin</dt>
                        <dd class="font-mono">{{ order.fee }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-border pt-3 text-base font-bold">
                        <dt class="font-display">Total</dt>
                        <dd class="font-mono text-brand-highlight">{{ order.total }}</dd>
                    </div>
                </dl>
            </BaseCard>

            <BaseCard v-if="order.payment" variant="accent" padding="lg">
                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-brand-highlight">Pembayaran</p>
                <h2 class="mt-1 font-display text-lg font-bold tracking-tight">Instruksi pembayaran</h2>
                <p v-if="order.expiresAt" class="mt-1.5 text-sm text-muted">
                    Bayar sebelum <strong class="text-text">{{ order.expiresAt }}</strong>.
                </p>

                <div v-if="order.payment.qrImage" class="mt-4 flex flex-col items-center gap-2">
                    <img :src="order.payment.qrImage" alt="Kode QRIS" class="size-64 rounded-md bg-white p-3" />
                    <p class="text-sm text-muted">Scan dengan aplikasi e-wallet atau m-banking yang mendukung QRIS.</p>
                </div>

                <div v-else-if="order.payment.code"
                    class="mt-4 flex items-center justify-between gap-3 rounded-md border border-border bg-ground p-4">
                    <code class="font-mono text-lg break-all text-text">{{ order.payment.code }}</code>
                    <CopyButton :text="order.payment.code" />
                </div>

                <BaseButton v-if="order.payment.url" :href="order.payment.url" target="_blank" rel="noopener" block
                    size="lg" class="mt-4">
                    Bayar sekarang
                </BaseButton>

                <p v-if="!order.payment.code && !order.payment.url" class="mt-3 text-sm text-muted">
                    Instruksi pembayaran akan tampil di sini setelah integrasi payment gateway.
                </p>
            </BaseCard>

            <BaseCard v-if="order.serialNumber" variant="success" padding="lg">
                <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-success">Selesai</p>
                <h2 class="mt-1 font-display text-lg font-bold text-text">Top up berhasil</h2>
                <p class="mt-1.5 text-sm text-muted">Nomor referensi (SN):</p>
                <div
                    class="mt-3 flex items-center justify-between gap-3 rounded-md border border-border bg-ground p-3.5">
                    <code class="font-mono text-sm break-all text-text">{{ order.serialNumber }}</code>
                    <CopyButton :text="order.serialNumber" />
                </div>
            </BaseCard>

            <Link :href="page.props.links.home"
                class="focus-ring mx-auto inline-flex items-center gap-1.5 rounded-sm font-mono text-xs uppercase tracking-[0.14em] text-muted transition-colors hover:text-brand-highlight">
                <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali ke beranda
            </Link>
        </div>
    </AppLayout>
</template>
