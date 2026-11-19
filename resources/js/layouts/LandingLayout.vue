<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, shallowRef } from 'vue';

const page = usePage();
const appName = computed(() => page.props.appName ?? 'Top Up');
const links = computed(() => page.props.links ?? { home: '/' });
const isMenuOpen = shallowRef(false);
const year = new Date().getFullYear();

const navItems = [
    { label: 'Katalog', href: '#katalog' },
    { label: 'Cara top up', href: '#cara' },
    { label: 'FAQ', href: '#faq' },
    { label: 'Cek pesanan', href: '#cek-pesanan' },
];

function closeMenu() {
    isMenuOpen.value = false;
}
</script>

<template>
    <div class="min-h-screen bg-ink font-sans text-paper">
        <a
            href="#konten"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:border focus:border-ink focus:bg-acid focus:px-4 focus:py-2 focus:font-display focus:text-sm focus:font-bold focus:text-ink"
        >
            Lompat ke konten
        </a>

        <header class="sticky top-0 z-40 border-b border-line bg-ink/90 backdrop-blur-sm">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
                <a :href="links.home" class="group flex items-center gap-3">
                    <span class="grid size-9 place-items-center border border-acid bg-acid font-display text-base font-bold text-ink transition group-hover:bg-paper">
                        {{ appName.charAt(0).toUpperCase() }}
                    </span>
                    <span class="font-display text-lg font-bold tracking-tight">{{ appName }}</span>
                </a>

                <nav class="hidden items-center gap-6 font-mono text-[11px] tracking-[0.18em] text-paper/60 uppercase md:flex" aria-label="Navigasi utama">
                    <a v-for="item in navItems" :key="item.href" :href="item.href" class="transition hover:text-acid">
                        {{ item.label }}
                    </a>
                </nav>

                <a
                    href="#katalog"
                    class="hidden items-center gap-2 border border-acid bg-acid px-4 py-2 font-display text-sm font-bold text-ink transition hover:-translate-y-0.5 hover:bg-paper md:inline-flex"
                >
                    Lihat katalog
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>

                <button
                    type="button"
                    class="grid size-10 place-items-center border border-line text-paper transition hover:border-acid hover:text-acid md:hidden"
                    :aria-expanded="isMenuOpen"
                    aria-controls="menu-mobile"
                    @click="isMenuOpen = !isMenuOpen"
                >
                    <span class="sr-only">{{ isMenuOpen ? 'Tutup menu' : 'Buka menu' }}</span>
                    <svg v-if="!isMenuOpen" class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <svg v-else class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </header>

        <div v-if="isMenuOpen" id="menu-mobile" class="fixed inset-0 z-50 flex flex-col bg-ink px-6 pt-5 pb-8 md:hidden" @keydown.escape.window="closeMenu">
            <div class="flex h-11 items-center justify-between">
                <span class="font-mono text-[11px] tracking-[0.18em] text-paper/50 uppercase">Menu</span>
                <button
                    type="button"
                    class="grid size-10 place-items-center border border-line text-paper transition hover:border-acid hover:text-acid"
                    @click="closeMenu"
                >
                    <span class="sr-only">Tutup menu</span>
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="mt-8 flex flex-col" aria-label="Navigasi seluler">
                <a
                    v-for="(item, index) in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center justify-between border-b border-line py-5 font-display text-3xl font-bold tracking-tight transition hover:text-acid"
                    @click="closeMenu"
                >
                    {{ item.label }}
                    <span class="font-mono text-xs text-paper/50">0{{ index + 1 }}</span>
                </a>
            </nav>

            <a
                href="#katalog"
                class="mt-auto inline-flex items-center justify-center gap-2 border border-acid bg-acid px-5 py-3.5 font-display text-base font-bold text-ink"
                @click="closeMenu"
            >
                Lihat katalog
                <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>

        <main id="konten">
            <slot />
        </main>

        <footer class="border-t border-line">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6">
                <div class="grid gap-8 sm:grid-cols-[1.4fr_1fr]">
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="grid size-9 place-items-center border border-acid bg-acid font-display text-base font-bold text-ink">
                                {{ appName.charAt(0).toUpperCase() }}
                            </span>
                            <span class="font-display text-lg font-bold tracking-tight">{{ appName }}</span>
                        </div>
                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-paper/60">
                            Top up game dan beli akun game dengan proses otomatis. Masukkan ID, pilih nominal, bayar, selesai.
                        </p>
                    </div>

                    <nav aria-label="Navigasi footer" class="font-mono text-[11px] tracking-[0.18em] text-paper/60 uppercase">
                        <p class="mb-4 text-paper/50">Navigasi</p>
                        <ul class="flex flex-col gap-3">
                            <li v-for="item in navItems" :key="item.href">
                                <a :href="item.href" class="transition hover:text-acid">{{ item.label }}</a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <p class="mt-10 border-t border-line pt-5 font-mono text-[10px] tracking-[0.14em] text-paper/50 uppercase">
                    &copy; {{ year }} {{ appName }}. Semua hak dilindungi.
                </p>
            </div>
        </footer>
    </div>
</template>
