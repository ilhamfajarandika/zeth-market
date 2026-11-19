<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, shallowRef } from 'vue';
import GameCover from '../components/GameCover.vue';
import AppLayout from '../layouts/AppLayout.vue';

const { categories } = defineProps({
    categories: { type: Array, required: true },
});

const page = usePage();

const ALL_CATEGORIES = 'all';

const query = shallowRef('');
const activeCategory = shallowRef(ALL_CATEGORIES);
const invoiceNumber = shallowRef('');

const categoryTabs = computed(() => [{ slug: ALL_CATEGORIES, name: 'Semua' }, ...categories]);

const allGames = computed(() =>
    categories.flatMap((category) => category.games.map((game) => ({ ...game, category: category.slug }))),
);
const popularGames = computed(() => allGames.value.filter((game) => game.isPopular));

const filteredGames = computed(() => {
    const keyword = query.value.toLowerCase().trim();

    return allGames.value.filter(
        (game) =>
            (activeCategory.value === ALL_CATEGORIES || activeCategory.value === game.category) &&
            `${game.name} ${game.publisher ?? ''}`.toLowerCase().includes(keyword),
    );
});

const steps = [
    ['Pilih game', 'Cari game favoritmu di daftar.'],
    ['Masukkan ID', 'Isi User ID (dan Zone ID bila diminta).'],
    ['Pilih nominal & bayar', 'Pilih item, lalu bayar lewat metode yang tersedia.'],
    ['Selesai', 'Pesanan diproses otomatis, cek status di invoice.'],
];

function lookupInvoice() {
    const invoice = invoiceNumber.value.trim().toUpperCase();

    if (invoice) {
        window.location.href = `${page.props.links.invoiceBase}/${encodeURIComponent(invoice)}`;
    }
}
</script>

<template>
    <Head title="Beranda" />

    <AppLayout>
        <!-- Hero -->
        <section class="relative overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-violet-600/30 via-slate-900 to-fuchsia-600/20 p-6 sm:p-10">
            <div class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
            <div class="relative max-w-xl">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-violet-200 ring-1 ring-white/15">
                    <span class="size-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                    Proses otomatis 24 jam
                </span>
                <h1 class="mt-4 text-3xl leading-tight font-extrabold sm:text-4xl">
                    Top up game & beli akun,
                    <span class="bg-gradient-to-r from-violet-300 to-fuchsia-300 bg-clip-text text-transparent">cepat dan aman.</span>
                </h1>
                <p class="mt-3 text-slate-300">Masukkan ID, pilih nominal, bayar. Pesanan langsung diproses tanpa perlu menunggu admin.</p>
                <a href="#daftar-game" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-violet-500 px-5 py-3 text-sm font-semibold shadow-lg shadow-violet-500/30 transition hover:bg-violet-400">
                    Mulai top up
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                </a>
            </div>

            <dl class="relative mt-8 grid grid-cols-3 gap-3 text-center sm:max-w-lg">
                <div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
                    <dt class="text-xs text-slate-400">Game</dt>
                    <dd class="text-lg font-bold">{{ allGames.length }}</dd>
                </div>
                <div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
                    <dt class="text-xs text-slate-400">Proses</dt>
                    <dd class="text-lg font-bold">Otomatis</dd>
                </div>
                <div class="rounded-2xl bg-white/5 p-3 ring-1 ring-white/10">
                    <dt class="text-xs text-slate-400">Stok akun</dt>
                    <dd class="text-lg font-bold">Real-time</dd>
                </div>
            </dl>
        </section>

        <!-- Game populer -->
        <section v-if="popularGames.length" class="mt-10">
            <div class="mb-4 flex items-center gap-2">
                <span class="text-xl">🔥</span>
                <h2 class="text-xl font-bold">Game Populer</h2>
            </div>
            <div class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 sm:mx-0 sm:grid sm:grid-cols-3 sm:overflow-visible sm:px-0 lg:grid-cols-4">
                <Link
                    v-for="game in popularGames"
                    :key="game.slug"
                    :href="game.url"
                    class="group flex w-64 shrink-0 snap-start items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3 transition hover:border-violet-400/60 hover:bg-white/10 sm:w-auto"
                >
                    <GameCover :game="game" class="size-16 shrink-0 rounded-xl" />
                    <div class="min-w-0">
                        <p class="truncate font-semibold group-hover:text-violet-200">{{ game.name }}</p>
                        <p class="truncate text-sm text-slate-400">{{ game.publisher }}</p>
                    </div>
                </Link>
            </div>
        </section>

        <!-- Daftar game + filter -->
        <section id="daftar-game" class="mt-10 scroll-mt-24">
            <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="text-xl font-bold">Semua Game</h2>
                <label class="relative sm:w-72">
                    <span class="sr-only">Cari game</span>
                    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input
                        v-model="query"
                        type="search"
                        placeholder="Cari game..."
                        class="w-full rounded-xl border border-white/10 bg-slate-900 py-2.5 pr-3 pl-9 text-sm placeholder:text-slate-500 focus:border-violet-400 focus:ring-2 focus:ring-violet-500/30 focus:outline-none"
                    />
                </label>
            </div>

            <div v-if="categories.length > 1" class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0">
                <button
                    v-for="category in categoryTabs"
                    :key="category.slug"
                    type="button"
                    :aria-pressed="activeCategory === category.slug"
                    class="shrink-0 rounded-full px-4 py-2 text-sm font-medium transition"
                    :class="activeCategory === category.slug ? 'bg-violet-500 text-white' : 'bg-white/5 text-slate-300 hover:bg-white/10'"
                    @click="activeCategory = category.slug"
                >
                    {{ category.name }}
                </button>
            </div>

            <div v-if="filteredGames.length" class="grid grid-cols-3 gap-3 sm:grid-cols-4 sm:gap-4 lg:grid-cols-6">
                <Link v-for="game in filteredGames" :key="game.slug" :href="game.url" class="group">
                    <GameCover :game="game" class="aspect-[3/4] rounded-2xl ring-1 ring-white/10 transition group-hover:-translate-y-1 group-hover:ring-violet-400/70" />
                    <p class="mt-2 truncate text-sm font-semibold group-hover:text-violet-200">{{ game.name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ game.publisher }}</p>
                </Link>
            </div>
            <p v-else class="rounded-2xl border border-dashed border-white/10 py-10 text-center text-sm text-slate-400">Game tidak ditemukan.</p>
        </section>

        <!-- Cara top up -->
        <section class="mt-14">
            <h2 class="mb-5 text-xl font-bold">Cara Top Up</h2>
            <ol class="grid gap-3 sm:grid-cols-4">
                <li v-for="([stepTitle, stepText], index) in steps" :key="stepTitle" class="rounded-2xl border border-white/10 bg-white/5 p-4">
                    <span class="grid size-8 place-items-center rounded-lg bg-violet-500/20 text-sm font-bold text-violet-200">{{ index + 1 }}</span>
                    <p class="mt-3 font-semibold">{{ stepTitle }}</p>
                    <p class="mt-1 text-sm text-slate-400">{{ stepText }}</p>
                </li>
            </ol>
        </section>

        <!-- Cek pesanan -->
        <section id="cek-pesanan" class="mt-14 scroll-mt-24 rounded-3xl border border-white/10 bg-white/5 p-6 sm:p-8">
            <div class="sm:flex sm:items-center sm:justify-between sm:gap-8">
                <div>
                    <h2 class="text-xl font-bold">Cek Pesanan</h2>
                    <p class="mt-1 text-sm text-slate-400">Masukkan nomor invoice untuk melihat status pesananmu.</p>
                </div>
                <form class="mt-4 flex gap-2 sm:mt-0 sm:w-96" @submit.prevent="lookupInvoice">
                    <input
                        v-model="invoiceNumber"
                        type="text"
                        placeholder="Contoh: INV251113ABCD1234"
                        class="min-w-0 flex-1 rounded-xl border border-white/10 bg-slate-900 px-3 py-2.5 text-sm uppercase placeholder:text-slate-500 placeholder:normal-case focus:border-violet-400 focus:ring-2 focus:ring-violet-500/30 focus:outline-none"
                    />
                    <button type="submit" class="rounded-xl bg-violet-500 px-4 py-2.5 text-sm font-semibold transition hover:bg-violet-400">Cek</button>
                </form>
            </div>
        </section>
    </AppLayout>
</template>
