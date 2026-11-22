<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, shallowRef } from 'vue';
import BannerSlideshow from '../components/BannerSlideshow.vue';
import GameCover from '../components/GameCover.vue';
import LandingLayout from '../layouts/LandingLayout.vue';

const { banners = [], categories } = defineProps({
    banners: { type: Array, default: () => [] },
    categories: { type: Array, required: true },
});

const ALL_CATEGORIES = 'all';

const query = shallowRef('');
const activeCategory = shallowRef(ALL_CATEGORIES);
const invoiceNumber = shallowRef('');
const invoiceMessage = shallowRef('');

const categoryTabs = computed(() => [{ slug: ALL_CATEGORIES, name: 'Semua' }, ...categories]);

const allGames = computed(() =>
    categories.flatMap((category) => category.games.map((game) => ({ ...game, category: category.slug }))),
);

const totalGames = computed(() => allGames.value.length);

const filteredGames = computed(() => {
    const keyword = query.value.toLowerCase().trim();

    return allGames.value.filter(
        (game) =>
            (activeCategory.value === ALL_CATEGORIES || activeCategory.value === game.category) &&
            `${game.name} ${game.publisher ?? ''}`.toLowerCase().includes(keyword),
    );
});

const hasActiveFilter = computed(() => activeCategory.value !== ALL_CATEGORIES || query.value.trim() !== '');

const trustItems = [
    {
        label: 'Proses otomatis',
        detail: 'Setelah pembayaran terkonfirmasi',
        icon: 'M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75Z',
    },
    {
        label: 'Guest checkout',
        detail: 'Tanpa membuat akun',
        icon: 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 16.5 0',
    },
    {
        label: 'Status transparan',
        detail: 'Pantau lewat halaman invoice',
        icon: 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    },
    {
        label: 'Akun tersedia',
        detail: 'Mengikuti data toko',
        icon: 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
    },
];

const steps = [
    {
        title: 'Pilih game',
        text: 'Cari game di katalog, lalu buka halaman game yang kamu mau.',
    },
    {
        title: 'Masukkan ID',
        text: 'Isi User ID, plus Zone ID atau Server bila game memintanya.',
    },
    {
        title: 'Pilih nominal',
        text: 'Tentukan item atau akun, lalu pilih metode pembayaran.',
    },
    {
        title: 'Selesai',
        text: 'Pesanan diteruskan otomatis setelah pembayaran terkonfirmasi.',
    },
];

const facts = [
    {
        label: 'Tanpa akun',
        title: 'Guest checkout',
        text: 'Beli tanpa mendaftar. Login hanya untuk menyimpan riwayat transaksi.',
    },
    {
        label: 'Setelah bayar',
        title: 'Proses otomatis',
        text: 'Pesanan diteruskan ke supplier tanpa menunggu balasan admin.',
    },
    {
        label: 'Dari toko',
        title: 'Akun tersedia',
        text: 'Akun yang masih dijual tampil di halaman game.',
    },
];

const faqs = [
    {
        question: 'Apakah harus punya akun untuk membeli?',
        answer: 'Tidak. Guest bisa checkout dengan mengisi kontak. Login opsional untuk menyimpan riwayat transaksi.',
    },
    {
        question: 'Berapa lama pesanan diproses?',
        answer: 'Top up diteruskan otomatis ke supplier setelah pembayaran terkonfirmasi. Status pesanan bisa dilihat di halaman invoice.',
    },
    {
        question: 'Apakah bisa membeli akun game?',
        answer: 'Bisa. Akun yang masih dijual tampil di halaman game; pembeliannya dibantu admin.',
    },
    {
        question: 'Bagaimana cara cek pesanan?',
        answer: 'Masukkan nomor invoice di bagian cek pesanan. Fitur ini aktif setelah integrasi pembayaran.',
    },
    {
        question: 'Apakah User ID saya aman?',
        answer: 'User ID hanya dipakai untuk memproses pesanan. Pastikan ID dan Zone ID benar sebelum checkout.',
    },
];

function resetFilters() {
    query.value = '';
    activeCategory.value = ALL_CATEGORIES;
}

function lookupInvoice() {
    const invoice = invoiceNumber.value.trim().toUpperCase();

    invoiceMessage.value = invoice
        ? 'Cek pesanan akan tersedia setelah integrasi pembayaran. Halaman ini masih pratinjau desain.'
        : 'Masukkan nomor invoice terlebih dahulu.';
}
</script>

<template>
    <Head title="Top up game & akun game" />

    <LandingLayout>
        <BannerSlideshow :banners="banners" />

        <section v-if="!banners.length" class="border-b border-border bg-surface">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-brand-highlight">Promo</p>
                    <p class="mt-1 font-display text-2xl font-bold tracking-tight">Top up game &amp; akun game</p>
                </div>
                <a
                    href="#katalog"
                    class="focus-ring inline-flex items-center justify-center gap-2 rounded-md border border-border px-5 py-3 font-display text-sm font-semibold text-text transition-colors hover:border-brand/60 hover:text-brand-highlight"
                >
                    Lihat katalog
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </section>

        <section class="relative overflow-hidden border-b border-border">
            <div class="pointer-events-none absolute inset-0 bg-tech-grid opacity-40" aria-hidden="true"></div>
            <div class="relative mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1.35fr_0.65fr] lg:items-end lg:gap-12 lg:py-14">
                <div class="animate-enter">
                    <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">
                        <span class="h-px w-8 bg-brand" aria-hidden="true"></span>
                        Top up &amp; akun game
                    </p>
                    <h1 class="mt-4 font-display text-[clamp(2.4rem,6vw,4.5rem)] leading-[0.95] font-bold tracking-[-0.03em]">
                        Isi ulang game.<br />
                        <span class="text-brand-highlight">Beli akun game.</span>
                    </h1>
                </div>

                <div class="animate-enter lg:pb-1" style="animation-delay: 80ms">
                    <p class="max-w-md text-sm leading-relaxed text-muted sm:text-base">
                        Pilih game, masukkan ID, dan bayar. Pesanan diteruskan otomatis setelah pembayaran terkonfirmasi. Guest bisa checkout tanpa membuat akun.
                    </p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a
                            href="#katalog"
                            class="focus-ring inline-flex items-center gap-2 rounded-md bg-brand px-5 py-3 font-display text-sm font-bold text-text transition-colors hover:bg-brand-hover"
                        >
                            Lihat katalog
                            <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <a
                            href="#cara"
                            class="focus-ring inline-flex items-center gap-2 rounded-md border border-border px-5 py-3 font-display text-sm font-semibold text-text transition-colors hover:border-brand/60 hover:text-brand-highlight"
                        >
                            Cara top up
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section aria-label="Keunggulan layanan" class="border-b border-border bg-surface">
            <ul class="mx-auto grid max-w-7xl grid-cols-2 gap-px bg-border sm:grid-cols-4">
                <li v-for="item in trustItems" :key="item.label" class="bg-surface px-4 py-5 sm:px-6">
                    <div class="flex items-center gap-3">
                        <svg class="size-4 shrink-0 text-brand-highlight" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                        </svg>
                        <p class="font-display text-sm font-semibold leading-tight">{{ item.label }}</p>
                    </div>
                    <p class="mt-2 pl-7 text-xs leading-relaxed text-muted">{{ item.detail }}</p>
                </li>
            </ul>
        </section>

        <section id="katalog" aria-labelledby="katalog-title" class="scroll-mt-20 border-b border-border py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">
                            <span class="h-px w-8 bg-brand" aria-hidden="true"></span>
                            Katalog
                        </p>
                        <h2 id="katalog-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Pilih game.</h2>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-muted">
                            {{ totalGames }} game tersedia. Akun yang dijual tampil di halaman game; harga dan biaya tampil di invoice sebelum pembayaran.
                        </p>
                    </div>

                    <label class="relative w-full lg:w-80">
                        <span class="sr-only">Cari game</span>
                        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input
                            v-model="query"
                            type="search"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="Cari game atau publisher"
                            class="focus-ring w-full rounded-md border border-border bg-surface-raised py-3 pr-3 pl-9 text-sm text-text placeholder:text-muted focus:border-brand-highlight"
                        />
                    </label>
                </div>

                <div v-if="categories.length > 1" class="-mx-4 mt-8 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0" role="group" aria-label="Filter kategori">
                    <button
                        v-for="category in categoryTabs"
                        :key="category.slug"
                        type="button"
                        :aria-pressed="activeCategory === category.slug"
                        class="focus-ring shrink-0 rounded-md border px-4 py-2 font-mono text-[11px] uppercase tracking-[0.14em] transition-colors"
                        :class="
                            activeCategory === category.slug
                                ? 'border-brand bg-brand/10 font-semibold text-text'
                                : 'border-border text-muted hover:border-brand/50 hover:text-text'
                        "
                        @click="activeCategory = category.slug"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <div v-if="filteredGames.length" class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    <a v-for="game in filteredGames" :key="game.slug" :href="game.url" class="focus-ring group block rounded-lg">
                        <div class="relative overflow-hidden rounded-lg border border-border bg-surface transition duration-300 group-hover:-translate-y-0.5 group-hover:border-brand/70">
                            <GameCover :game="game" class="aspect-square" />
                            <span
                                v-if="game.isPopular"
                                class="absolute top-2 left-2 rounded-sm bg-brand px-1.5 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.14em] text-text"
                            >
                                Populer
                            </span>
                        </div>
                        <div class="mt-2.5 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="truncate font-display text-sm font-semibold transition-colors group-hover:text-brand-highlight">{{ game.name }}</h3>
                                <p class="mt-0.5 truncate font-mono text-[10px] uppercase tracking-[0.1em] text-muted">{{ game.publisher }}</p>
                            </div>
                            <svg class="mt-0.5 size-3.5 shrink-0 text-brand-highlight opacity-0 transition-opacity group-hover:opacity-100" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                        </div>
                    </a>
                </div>

                <div v-else class="mt-8 rounded-lg border border-dashed border-border px-6 py-16 text-center">
                    <p class="text-sm text-muted">Tidak ada game yang cocok dengan pencarianmu.</p>
                    <button
                        v-if="hasActiveFilter"
                        type="button"
                        class="focus-ring mt-5 rounded-md border border-border px-4 py-2 font-display text-sm font-semibold text-text transition-colors hover:border-brand/60 hover:text-brand-highlight"
                        @click="resetFilters"
                    >
                        Reset filter
                    </button>
                </div>
            </div>
        </section>

        <section id="cara" aria-labelledby="cara-title" class="scroll-mt-20 border-b border-border bg-surface py-16 sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                <div>
                    <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">
                        <span class="h-px w-8 bg-brand" aria-hidden="true"></span>
                        Cara top up
                    </p>
                    <h2 id="cara-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Empat langkah.</h2>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-muted">
                        Alurnya dibuat sesingkat mungkin supaya kamu bisa kembali bermain. Guest bisa checkout tanpa membuat akun.
                    </p>
                    <a href="#katalog" class="focus-ring mt-6 inline-flex items-center gap-2 rounded-sm font-display text-sm font-semibold text-brand-highlight transition-colors hover:text-text">
                        Mulai dari katalog
                        <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>

                <ol class="grid gap-4 sm:grid-cols-2">
                    <li v-for="(step, index) in steps" :key="step.title" class="rounded-lg border border-border bg-ground p-5">
                        <span class="font-mono text-xs text-brand-highlight">0{{ index + 1 }}</span>
                        <h3 class="mt-3 font-display text-lg font-semibold tracking-tight">{{ step.title }}</h3>
                        <p class="mt-1.5 text-sm leading-relaxed text-muted">{{ step.text }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <section aria-labelledby="fakta-title" class="border-b border-border py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">
                            <span class="h-px w-8 bg-brand" aria-hidden="true"></span>
                            Layanan
                        </p>
                        <h2 id="fakta-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Yang kamu dapat.</h2>
                    </div>
                    <p class="max-w-xs font-mono text-[10px] uppercase leading-relaxed tracking-[0.14em] text-muted">
                        Harga item dan biaya tampil di invoice sebelum pembayaran.
                    </p>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-3">
                    <div v-for="fact in facts" :key="fact.title" class="rounded-lg border border-border bg-surface p-6">
                        <span class="block h-px w-8 bg-brand" aria-hidden="true"></span>
                        <p class="mt-4 font-mono text-[10px] uppercase tracking-[0.18em] text-brand-highlight">{{ fact.label }}</p>
                        <h3 class="mt-2 font-display text-xl font-semibold tracking-tight">{{ fact.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted">{{ fact.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section id="faq" aria-labelledby="faq-title" class="scroll-mt-20 border-b border-border py-16 sm:py-20">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                <div>
                    <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">
                        <span class="h-px w-8 bg-brand" aria-hidden="true"></span>
                        FAQ
                    </p>
                    <h2 id="faq-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Pertanyaan yang sering muncul.</h2>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-muted">
                        Belum ketemu jawabannya? Hubungi admin lewat kanal resmi toko.
                    </p>
                </div>

                <div class="border-t border-border">
                    <details v-for="faq in faqs" :key="faq.question" class="group border-b border-border">
                        <summary class="focus-ring flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-display text-base font-semibold transition-colors group-open:text-brand-highlight [&::-webkit-details-marker]:hidden">
                            {{ faq.question }}
                            <span class="grid size-7 shrink-0 place-items-center rounded-sm border border-border text-muted transition-colors group-open:rotate-45 group-open:border-brand group-open:text-brand-highlight" aria-hidden="true">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </span>
                        </summary>
                        <p class="max-w-xl pb-5 text-sm leading-relaxed text-muted">{{ faq.answer }}</p>
                    </details>
                </div>
            </div>
        </section>

        <section id="cek-pesanan" aria-labelledby="cek-title" class="scroll-mt-20 border-b border-border py-16 sm:py-20">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="relative overflow-hidden rounded-lg border border-border bg-surface p-6 sm:p-10">
                    <span class="absolute inset-y-0 left-0 w-0.5 bg-brand" aria-hidden="true"></span>
                    <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                        <div>
                            <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">
                                <span class="h-px w-8 bg-brand" aria-hidden="true"></span>
                                Cek pesanan
                            </p>
                            <h2 id="cek-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Lacak pesananmu.</h2>
                            <p class="mt-3 max-w-lg text-sm leading-relaxed text-muted">
                                Masukkan nomor invoice untuk melihat status. Fitur ini aktif setelah integrasi pembayaran.
                            </p>
                        </div>

                        <form class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto" @submit.prevent="lookupInvoice">
                            <label class="sr-only" for="invoice-number">Nomor invoice</label>
                            <input
                                id="invoice-number"
                                v-model="invoiceNumber"
                                type="text"
                                autocomplete="off"
                                placeholder="INV251113ABCD1234"
                                class="focus-ring min-w-0 rounded-md border border-border bg-ground px-3 py-3 font-mono text-sm tracking-[0.06em] text-text uppercase placeholder:text-muted placeholder:normal-case focus:border-brand-highlight sm:w-64"
                            />
                            <button
                                type="submit"
                                class="focus-ring rounded-md bg-brand px-5 py-3 font-display text-sm font-bold text-text transition-colors hover:bg-brand-hover"
                            >
                                Cek pesanan
                            </button>
                        </form>
                    </div>

                    <p v-if="invoiceMessage" aria-live="polite" class="relative mt-4 rounded-md border border-border bg-ground px-4 py-3 font-mono text-[11px] leading-relaxed tracking-[0.04em] text-muted">
                        {{ invoiceMessage }}
                    </p>
                </div>
            </div>
        </section>

        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                <div class="clip-corner relative overflow-hidden border border-border bg-surface px-6 py-14 sm:px-10 sm:py-20">
                    <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-40 bg-brand-hatch opacity-60 lg:block" aria-hidden="true"></div>
                    <div class="relative max-w-2xl">
                        <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">
                            <span class="h-px w-8 bg-brand" aria-hidden="true"></span>
                            Siap mulai
                        </p>
                        <h2 class="mt-4 font-display text-[clamp(2rem,4vw,3.25rem)] leading-[1.02] font-bold tracking-[-0.02em]">
                            Pilih game, lalu selesaikan pesanan.
                        </h2>
                        <p class="mt-4 max-w-xl text-sm leading-relaxed text-muted">
                            Masukkan ID, pilih nominal, dan bayar. Status pesanan bisa dipantau lewat invoice.
                        </p>
                        <a
                            href="#katalog"
                            class="focus-ring mt-7 inline-flex items-center gap-2 rounded-md border border-brand/60 bg-brand/10 px-5 py-3 font-display text-sm font-bold text-brand-highlight transition-colors hover:bg-brand hover:text-text"
                        >
                            Mulai dari katalog
                            <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
