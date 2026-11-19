<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const appName = computed(() => page.props.appName);
const links = computed(() => page.props.links);
const isHome = computed(() => page.component === 'Home');
const orderLookupUrl = computed(() => `${links.value.home}#cek-pesanan`);
const year = new Date().getFullYear();
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 bg-gradient-to-b from-violet-600/15 to-transparent"></div>

    <header class="sticky top-0 z-40 border-b border-white/10 bg-slate-950/80 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3">
            <Link :href="links.home" class="flex items-center gap-2 text-lg font-bold">
                <span class="grid size-9 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-sm shadow-lg shadow-violet-500/30">
                    {{ appName.charAt(0).toUpperCase() }}
                </span>
                <span>{{ appName }}</span>
            </Link>

            <nav class="hidden items-center gap-1 text-sm font-medium md:flex">
                <Link :href="links.home" class="rounded-lg px-3 py-2 transition hover:bg-white/5" :class="isHome ? 'text-white' : 'text-slate-400'">Beranda</Link>
                <a :href="orderLookupUrl" class="rounded-lg px-3 py-2 text-slate-400 transition hover:bg-white/5 hover:text-white">Cek Pesanan</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-6xl px-4 pt-6 pb-40 md:pb-12">
        <slot />
    </main>

    <footer class="border-t border-white/10 pb-20 md:pb-0">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-10 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <p class="text-lg font-bold">{{ appName }}</p>
                <p class="mt-2 max-w-md text-sm text-slate-400">
                    Top up game dan beli akun game dengan proses otomatis. Masukkan ID, pilih nominal, bayar, selesai.
                </p>
            </div>
            <div>
                <p class="mb-3 text-sm font-semibold">Menu</p>
                <ul class="flex flex-col gap-2 text-sm text-slate-400">
                    <li><Link :href="links.home" class="hover:text-white">Beranda</Link></li>
                    <li><a :href="orderLookupUrl" class="hover:text-white">Cek Pesanan</a></li>
                </ul>
            </div>
        </div>
        <p class="border-t border-white/5 py-5 text-center text-xs text-slate-500">&copy; {{ year }} {{ appName }}. Semua hak dilindungi.</p>
    </footer>

    <!-- Navigasi bawah (HP) -->
    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-white/10 bg-slate-950/95 backdrop-blur md:hidden">
        <div class="mx-auto grid max-w-md grid-cols-2 text-xs">
            <Link :href="links.home" class="flex flex-col items-center gap-1 py-2.5" :class="isHome ? 'text-violet-300' : 'text-slate-400'">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
                Beranda
            </Link>
            <a :href="orderLookupUrl" class="flex flex-col items-center gap-1 py-2.5 text-slate-400">
                <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" /></svg>
                Cek Pesanan
            </a>
        </div>
    </nav>
</template>
