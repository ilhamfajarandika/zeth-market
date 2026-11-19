<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, shallowRef } from 'vue';
import GameCover from '../components/GameCover.vue';
import LandingLayout from '../layouts/LandingLayout.vue';

const { categories } = defineProps({
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

const heroGames = computed(() => allGames.value.slice(0, 4));

const filteredGames = computed(() => {
    const keyword = query.value.toLowerCase().trim();

    return allGames.value.filter(
        (game) =>
            (activeCategory.value === ALL_CATEGORIES || activeCategory.value === game.category) &&
            `${game.name} ${game.publisher ?? ''}`.toLowerCase().includes(keyword),
    );
});

const hasActiveFilter = computed(() => activeCategory.value !== ALL_CATEGORIES || query.value.trim() !== '');

const marqueeWords = ['Proses otomatis', 'Guest checkout', 'Stok akun real-time', 'Harga dari server', 'Top up & akun game'];

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
        title: 'Stok real-time',
        text: 'Jumlah akun tersedia mengikuti stok yang tercatat di toko.',
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
        answer: 'Bisa. Stok akun tampil di halaman game dan berkurang otomatis saat akun terjual.',
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
        <!-- Hero -->
        <section class="relative overflow-hidden border-b border-line bg-dotgrid">
            <div class="mx-auto grid min-h-[calc(100svh-4rem)] max-w-6xl items-center gap-12 px-4 py-14 sm:px-6 lg:grid-cols-[1.05fr_0.95fr] lg:py-20">
                <div>
                    <p class="flex items-center gap-3 font-mono text-[11px] tracking-[0.2em] text-paper/50 uppercase animate-enter">
                        <span class="h-px w-8 bg-acid"></span>
                        Toko top up game
                    </p>

                    <h1 class="mt-6 font-display text-[clamp(2.75rem,6vw,4.5rem)] leading-[0.95] font-bold tracking-[-0.03em] animate-enter" style="animation-delay: 80ms">
                        Isi ulang game,<br />
                        diproses <span class="text-acid">otomatis.</span>
                    </h1>

                    <p class="mt-6 max-w-xl text-base leading-relaxed text-paper/70 sm:text-lg animate-enter" style="animation-delay: 160ms">
                        Pilih game, masukkan ID, bayar. Pesanan diteruskan otomatis setelah pembayaran terkonfirmasi, tanpa menunggu balasan admin.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3 animate-enter" style="animation-delay: 240ms">
                        <a
                            href="#katalog"
                            class="inline-flex items-center gap-2 border border-acid bg-acid px-5 py-3 font-display text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:bg-paper focus-visible:ring-2 focus-visible:ring-acid focus-visible:ring-offset-2 focus-visible:ring-offset-ink focus-visible:outline-none"
                        >
                            Lihat katalog
                            <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                        <a
                            href="#cara"
                            class="inline-flex items-center gap-2 border border-line px-5 py-3 font-display text-sm font-bold text-paper transition hover:-translate-y-0.5 hover:border-paper hover:text-acid focus-visible:ring-2 focus-visible:ring-acid focus-visible:ring-offset-2 focus-visible:ring-offset-ink focus-visible:outline-none"
                        >
                            Cara top up
                        </a>
                    </div>

                    <dl class="mt-10 grid max-w-lg grid-cols-3 border-t border-line animate-enter" style="animation-delay: 320ms">
                        <div class="border-r border-line py-4 pr-3">
                            <dt class="font-mono text-[10px] tracking-[0.16em] text-paper/50 uppercase">Game</dt>
                            <dd class="mt-1 font-display text-2xl font-bold">{{ totalGames }}</dd>
                        </div>
                        <div class="border-r border-line px-3 py-4">
                            <dt class="font-mono text-[10px] tracking-[0.16em] text-paper/50 uppercase">Proses</dt>
                            <dd class="mt-1 font-display text-2xl font-bold">Otomatis</dd>
                        </div>
                        <div class="py-4 pl-3">
                            <dt class="font-mono text-[10px] tracking-[0.16em] text-paper/50 uppercase">Stok akun</dt>
                            <dd class="mt-1 font-display text-2xl font-bold">Real-time</dd>
                        </div>
                    </dl>
                </div>

                <div class="relative animate-enter" style="animation-delay: 200ms">
                    <div class="border border-line bg-surface p-4 shadow-[10px_10px_0_0_#d7ff3f] sm:p-5">
                        <div class="flex items-center justify-between gap-3 border-b border-line pb-3">
                            <p class="font-mono text-[10px] tracking-[0.18em] text-paper/50 uppercase">Katalog // Index</p>
                            <span class="font-mono text-[10px] tracking-[0.14em] text-acid uppercase">Live</span>
                        </div>

                        <div v-if="heroGames.length" class="mt-4 grid grid-cols-2 gap-3">
                            <div v-for="(game, index) in heroGames" :key="game.slug" class="group border border-line bg-ink p-2">
                                <div class="overflow-hidden">
                                    <GameCover :game="game" class="aspect-[4/5] transition duration-500 group-hover:scale-[1.03]" />
                                </div>
                                <div class="mt-2 flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-display text-xs font-bold">{{ game.name }}</p>
                                        <p class="truncate font-mono text-[9px] tracking-[0.1em] text-paper/50 uppercase">{{ game.publisher }}</p>
                                    </div>
                                    <span class="font-mono text-[9px] text-acid">0{{ index + 1 }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="mt-4 grid aspect-[4/3] place-items-center border border-dashed border-line font-mono text-[10px] tracking-[0.14em] text-paper/50 uppercase">
                            Katalog sedang disiapkan
                        </div>

                        <div class="mt-4 flex items-center justify-between border-t border-line pt-3">
                            <p class="font-mono text-[10px] tracking-[0.14em] text-paper/50 uppercase">Status pesanan</p>
                            <p class="font-display text-xs font-bold text-acid">Diproses otomatis</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Marquee -->
        <div class="overflow-hidden border-b border-acid bg-acid py-3 text-ink" aria-hidden="true">
            <div class="flex w-max animate-marquee">
                <div v-for="copy in 2" :key="copy" class="flex shrink-0 items-center gap-8 pr-8">
                    <span v-for="word in marqueeWords" :key="`${copy}-${word}`" class="flex items-center gap-8 font-display text-sm font-bold tracking-[0.18em] uppercase">
                        {{ word }}
                        <span class="font-mono text-xs">/</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Katalog -->
        <section id="katalog" aria-labelledby="katalog-title" class="scroll-mt-16 border-b border-line py-16 sm:py-20">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-mono text-[11px] tracking-[0.2em] text-acid uppercase">Katalog // 01</p>
                        <h2 id="katalog-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Jelajahi semua game</h2>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-paper/60">
                            {{ totalGames }} game tersedia. Stok akun mengikuti data toko, harga dan biaya tampil sebelum kamu membayar.
                        </p>
                    </div>

                    <label class="relative w-full sm:w-80">
                        <span class="sr-only">Cari game</span>
                        <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-paper/50" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input
                            v-model="query"
                            type="search"
                            placeholder="Cari game atau publisher"
                            class="w-full border border-line bg-surface py-3 pr-3 pl-9 text-sm text-paper placeholder:text-paper/50 focus:border-acid focus:ring-2 focus:ring-acid/30 focus:outline-none"
                        />
                    </label>
                </div>

                <div v-if="categories.length > 1" class="-mx-4 mt-8 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:px-0">
                    <button
                        v-for="category in categoryTabs"
                        :key="category.slug"
                        type="button"
                        :aria-pressed="activeCategory === category.slug"
                        class="shrink-0 border px-4 py-2 font-mono text-[11px] tracking-[0.14em] uppercase transition focus-visible:ring-2 focus-visible:ring-acid focus-visible:ring-offset-2 focus-visible:ring-offset-ink focus-visible:outline-none"
                        :class="activeCategory === category.slug ? 'border-acid bg-acid font-bold text-ink' : 'border-line text-paper/60 hover:border-paper/40 hover:text-paper'"
                        @click="activeCategory = category.slug"
                    >
                        {{ category.name }}
                    </button>
                </div>

                <div v-if="filteredGames.length" class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                    <article v-for="game in filteredGames" :key="game.slug" class="group">
                        <div class="overflow-hidden border border-line bg-surface transition duration-300 group-hover:-translate-y-0.5 group-hover:border-acid/60">
                            <GameCover :game="game" class="aspect-[3/4] transition duration-500 group-hover:scale-[1.03]" />
                        </div>
                        <h3 class="mt-3 truncate font-display text-sm font-bold transition group-hover:text-acid">{{ game.name }}</h3>
                        <p class="truncate font-mono text-[10px] tracking-[0.12em] text-paper/50 uppercase">{{ game.publisher }}</p>
                    </article>
                </div>

                <div v-else class="mt-8 border border-dashed border-line px-6 py-16 text-center">
                    <p class="text-sm text-paper/60">Tidak ada game yang cocok dengan pencarianmu.</p>
                    <button
                        v-if="hasActiveFilter"
                        type="button"
                        class="mt-5 border border-line px-4 py-2 font-display text-sm font-bold text-paper transition hover:border-acid hover:text-acid"
                        @click="resetFilters"
                    >
                        Reset filter
                    </button>
                </div>
            </div>
        </section>

        <!-- Cara top up -->
        <section id="cara" aria-labelledby="cara-title" class="scroll-mt-16 border-b border-line py-16 sm:py-20">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                <div>
                    <p class="font-mono text-[11px] tracking-[0.2em] text-acid uppercase">Cara top up // 02</p>
                    <h2 id="cara-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Empat langkah, selesai.</h2>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-paper/60">
                        Alurnya dibuat sesingkat mungkin supaya kamu bisa kembali bermain. Guest bisa checkout tanpa membuat akun.
                    </p>
                    <a
                        href="#katalog"
                        class="mt-8 inline-flex items-center gap-2 border-b border-acid pb-1 font-display text-sm font-bold text-acid transition hover:gap-3"
                    >
                        Mulai dari katalog
                        <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>

                <ol class="border-t border-line">
                    <li v-for="(step, index) in steps" :key="step.title" class="grid grid-cols-[auto_1fr] gap-5 border-b border-line py-6 sm:gap-8">
                        <span class="font-mono text-sm text-acid">0{{ index + 1 }}</span>
                        <div>
                            <h3 class="font-display text-xl font-bold tracking-tight">{{ step.title }}</h3>
                            <p class="mt-1.5 max-w-md text-sm leading-relaxed text-paper/60">{{ step.text }}</p>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Fakta -->
        <section aria-labelledby="fakta-title" class="border-b border-acid bg-acid py-16 text-ink sm:py-20">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="font-mono text-[11px] tracking-[0.2em] uppercase">Kenapa di sini // 03</p>
                        <h2 id="fakta-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Yang kamu dapat di setiap order.</h2>
                    </div>
                    <p class="max-w-xs font-mono text-[11px] leading-relaxed tracking-[0.06em] uppercase">
                        Tanpa langganan, tanpa biaya tersembunyi di luar yang tampil di invoice.
                    </p>
                </div>

                <div class="mt-10 grid gap-px border border-ink/20 bg-ink/20 sm:grid-cols-3">
                    <div v-for="fact in facts" :key="fact.title" class="bg-acid p-6 sm:p-8">
                        <p class="font-mono text-[10px] tracking-[0.18em] uppercase">{{ fact.label }}</p>
                        <h3 class="mt-4 font-display text-2xl font-bold tracking-tight">{{ fact.title }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-ink/70">{{ fact.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <section id="faq" aria-labelledby="faq-title" class="scroll-mt-16 border-b border-line py-16 sm:py-20">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                <div>
                    <p class="font-mono text-[11px] tracking-[0.2em] text-acid uppercase">FAQ // 04</p>
                    <h2 id="faq-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Pertanyaan yang sering muncul.</h2>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-paper/60">
                        Belum ketemu jawabannya? Hubungi admin lewat kanal resmi toko.
                    </p>
                </div>

                <div class="border-t border-line">
                    <details v-for="faq in faqs" :key="faq.question" class="group border-b border-line">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-display text-base font-bold [&::-webkit-details-marker]:hidden">
                            {{ faq.question }}
                            <span class="grid size-7 shrink-0 place-items-center border border-line text-paper/60 transition group-open:rotate-45 group-open:border-acid group-open:text-acid" aria-hidden="true">
                                <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            </span>
                        </summary>
                        <p class="max-w-xl pb-5 text-sm leading-relaxed text-paper/60">{{ faq.answer }}</p>
                    </details>
                </div>
            </div>
        </section>

        <!-- Cek pesanan -->
        <section id="cek-pesanan" aria-labelledby="cek-title" class="scroll-mt-16 border-b border-line py-16 sm:py-20">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="grid gap-8 border border-line bg-surface p-6 sm:p-10 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <p class="font-mono text-[11px] tracking-[0.2em] text-acid uppercase">Cek pesanan // 05</p>
                        <h2 id="cek-title" class="mt-3 font-display text-3xl font-bold tracking-tight sm:text-4xl">Lacak pesananmu.</h2>
                        <p class="mt-3 max-w-lg text-sm leading-relaxed text-paper/60">
                            Masukkan nomor invoice untuk melihat status. Fitur ini aktif setelah integrasi pembayaran.
                        </p>
                    </div>

                    <form class="flex w-full flex-col gap-2 sm:flex-row lg:w-auto" @submit.prevent="lookupInvoice">
                        <label class="sr-only" for="invoice-number">Nomor invoice</label>
                        <input
                            id="invoice-number"
                            v-model="invoiceNumber"
                            type="text"
                            placeholder="INV251113ABCD1234"
                            class="min-w-0 border border-line bg-ink px-3 py-3 font-mono text-sm tracking-[0.06em] text-paper uppercase placeholder:text-paper/50 placeholder:normal-case focus:border-acid focus:ring-2 focus:ring-acid/30 focus:outline-none sm:w-64"
                        />
                        <button
                            type="submit"
                            class="border border-acid bg-acid px-5 py-3 font-display text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:bg-paper focus-visible:ring-2 focus-visible:ring-acid focus-visible:ring-offset-2 focus-visible:ring-offset-surface focus-visible:outline-none"
                        >
                            Cek pesanan
                        </button>
                    </form>
                </div>

                <p v-if="invoiceMessage" aria-live="polite" class="mt-3 border border-line bg-surface px-4 py-3 font-mono text-[11px] leading-relaxed tracking-[0.04em] text-paper/70">
                    {{ invoiceMessage }}
                </p>
            </div>
        </section>

        <!-- CTA akhir -->
        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="border border-line bg-surface px-6 py-12 text-center sm:px-10 sm:py-16">
                    <p class="font-mono text-[11px] tracking-[0.2em] text-acid uppercase">Siap mulai</p>
                    <h2 class="mx-auto mt-4 max-w-2xl font-display text-[clamp(2rem,4vw,3.25rem)] leading-[1.02] font-bold tracking-[-0.02em]">
                        Siap isi ulang game favoritmu?
                    </h2>
                    <p class="mx-auto mt-4 max-w-xl text-sm leading-relaxed text-paper/60">
                        Pilih game, masukkan ID, dan selesaikan pembayaran. Pesanan diproses otomatis.
                    </p>
                    <a
                        href="#katalog"
                        class="mt-8 inline-flex items-center gap-2 border border-acid bg-acid px-6 py-3.5 font-display text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:bg-paper focus-visible:ring-2 focus-visible:ring-acid focus-visible:ring-offset-2 focus-visible:ring-offset-surface focus-visible:outline-none"
                    >
                        Mulai dari katalog
                        <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    </LandingLayout>
</template>
