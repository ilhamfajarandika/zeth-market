<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CopyButton from '../components/CopyButton.vue';
import StatusBadge from '../components/StatusBadge.vue';
import AppLayout from '../layouts/AppLayout.vue';

const { order } = defineProps({
    order: { type: Object, required: true },
});

const page = usePage();

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
            <!-- Kepala invoice -->
            <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-slate-400">Nomor invoice</p>
                        <div class="mt-1 flex items-center gap-2">
                            <h1 class="text-xl font-bold tracking-wide sm:text-2xl">{{ order.invoice }}</h1>
                            <CopyButton :text="order.invoice" />
                        </div>
                    </div>
                    <StatusBadge :status="order.status" />
                </div>

                <ol v-if="currentStep !== null" class="mt-6 grid grid-cols-4 gap-2">
                    <li v-for="(stepLabel, index) in progressSteps" :key="stepLabel" class="flex flex-col gap-2">
                        <span class="h-1.5 rounded-full" :class="index <= currentStep ? 'bg-violet-400' : 'bg-white/10'"></span>
                        <span class="text-[11px] sm:text-xs" :class="index <= currentStep ? 'text-slate-200' : 'text-slate-500'">{{ stepLabel }}</span>
                    </li>
                </ol>
                <p v-else class="mt-5 rounded-xl bg-rose-500/10 p-3 text-sm text-rose-200 ring-1 ring-rose-500/20">
                    Pesanan ini berstatus <strong>{{ order.status.label }}</strong>. Hubungi admin jika kamu sudah melakukan pembayaran.
                </p>
            </section>

            <!-- Rincian -->
            <section class="rounded-3xl border border-white/10 bg-white/5 p-6">
                <h2 class="mb-4 font-semibold">Rincian pesanan</h2>
                <dl class="flex flex-col gap-3 text-sm">
                    <div v-for="[label, value] in details" :key="label" class="flex justify-between gap-4">
                        <dt class="text-slate-400">{{ label }}</dt>
                        <dd class="text-right">{{ value }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-white/10 pt-3">
                        <dt class="text-slate-400">Harga</dt>
                        <dd>{{ order.price }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-400">Biaya admin</dt>
                        <dd>{{ order.fee }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 text-base font-bold">
                        <dt>Total</dt>
                        <dd class="text-violet-300">{{ order.total }}</dd>
                    </div>
                </dl>
            </section>

            <!-- Pembayaran -->
            <section v-if="order.payment" class="rounded-3xl border border-violet-500/30 bg-violet-500/10 p-6">
                <h2 class="font-semibold">Instruksi pembayaran</h2>
                <p v-if="order.expiresAt" class="mt-1 text-sm text-slate-300">Bayar sebelum <strong>{{ order.expiresAt }}</strong>.</p>

                <div v-if="order.payment.code" class="mt-4 flex items-center justify-between gap-3 rounded-xl bg-slate-950/60 p-4">
                    <code class="font-mono text-lg break-all">{{ order.payment.code }}</code>
                    <CopyButton :text="order.payment.code" />
                </div>

                <a v-if="order.payment.url" :href="order.payment.url" target="_blank" rel="noopener" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-violet-500 py-3 text-sm font-semibold transition hover:bg-violet-400">
                    Bayar sekarang
                </a>

                <p v-if="!order.payment.code && !order.payment.url" class="mt-3 text-sm text-slate-400">Instruksi pembayaran akan tampil di sini setelah integrasi payment gateway.</p>
            </section>

            <!-- Hasil: SN top up -->
            <section v-if="order.serialNumber" class="rounded-3xl border border-emerald-500/30 bg-emerald-500/10 p-6">
                <h2 class="font-semibold text-emerald-300">Top up berhasil</h2>
                <p class="mt-1 text-sm text-slate-300">Nomor referensi (SN):</p>
                <code class="mt-2 block font-mono text-sm break-all">{{ order.serialNumber }}</code>
            </section>

            <!-- Hasil: detail akun -->
            <section v-if="order.credentials" class="rounded-3xl border border-emerald-500/30 bg-emerald-500/10 p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-semibold text-emerald-300">Detail akun</h2>
                    <CopyButton :text="order.credentials" label="Salin semua" />
                </div>
                <pre class="mt-3 rounded-xl bg-slate-950/60 p-4 font-mono text-sm whitespace-pre-wrap">{{ order.credentials }}</pre>
                <p class="mt-3 text-xs text-slate-400">Segera ganti password dan amankan akun setelah login.</p>
            </section>

            <Link :href="page.props.links.home" class="text-center text-sm text-slate-400 hover:text-white">← Kembali ke beranda</Link>
        </div>
    </AppLayout>
</template>
