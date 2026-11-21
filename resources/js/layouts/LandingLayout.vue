<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const page = usePage();
const appName = computed(() => page.props.appName ?? 'Top Up');
const logoUrl = computed(() => page.props.logoUrl ?? null);
const links = computed(() => page.props.links);
const brandInitial = computed(() => appName.value.charAt(0).toUpperCase());
const year = new Date().getFullYear();

const navItems = [
    { label: 'Katalog', href: '#katalog' },
    { label: 'Cara top up', href: '#cara' },
    { label: 'FAQ', href: '#faq' },
    { label: 'Cek pesanan', href: '#cek-pesanan' },
];

const isMenuOpen = ref(false);
const toggleButton = ref(null);
const closeButton = ref(null);

let desktopQuery = null;

function closeMenuOnDesktop(event) {
    if (event.matches) {
        closeMenu();
    }
}

function openMenu() {
    isMenuOpen.value = true;
}

function closeMenu() {
    if (!isMenuOpen.value) {
        return;
    }

    isMenuOpen.value = false;
    nextTick(() => toggleButton.value?.focus());
}

watch(isMenuOpen, async (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';

    if (isOpen) {
        await nextTick();
        closeButton.value?.focus();
    }
});

onMounted(() => {
    desktopQuery = window.matchMedia('(min-width: 768px)');
    desktopQuery.addEventListener('change', closeMenuOnDesktop);
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    desktopQuery?.removeEventListener('change', closeMenuOnDesktop);
});
</script>

<template>
    <a
        href="#konten"
        class="focus-ring sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-brand focus:px-4 focus:py-2 focus:font-mono focus:text-xs focus:uppercase focus:tracking-[0.18em] focus:text-text"
    >
        Lewati ke konten
    </a>

    <div class="min-h-screen bg-ground font-sans text-text antialiased">
        <header class="sticky top-0 z-40 border-t-2 border-b border-t-brand border-b-border bg-ground">
            <div class="mx-auto flex h-16 max-w-7xl items-center gap-6 px-4 sm:px-6 lg:px-8">
                <Link :href="links.home" class="focus-ring flex items-center gap-3 rounded-md">
                    <img
                        v-if="logoUrl"
                        :src="logoUrl"
                        :alt="appName"
                        class="size-9 shrink-0 rounded-md object-contain"
                    />
                    <span
                        v-else
                        class="clip-corner-sm grid size-9 place-items-center bg-brand font-display text-base font-bold leading-none text-text"
                    >
                        {{ brandInitial }}
                    </span>
                    <span class="flex flex-col">
                        <span class="font-display text-base font-bold uppercase leading-none tracking-tight">
                            {{ appName }}
                        </span>
                        <span class="mt-1 font-mono text-[10px] uppercase leading-none tracking-[0.22em] text-muted">
                            Top up game &amp; akun
                        </span>
                    </span>
                </Link>

                <nav class="ml-auto hidden items-center gap-6 md:flex" aria-label="Navigasi utama">
                    <a
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        class="focus-ring relative py-2 font-mono text-[11px] uppercase tracking-[0.18em] text-muted transition-colors after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-brand after:transition-transform hover:text-text hover:after:scale-x-100"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <a
                    href="#katalog"
                    class="focus-ring hidden rounded-md border border-brand/50 px-4 py-2.5 font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-highlight transition-colors hover:bg-brand hover:text-text md:ml-2 md:inline-flex"
                >
                    Lihat katalog
                </a>

                <button
                    ref="toggleButton"
                    type="button"
                    class="focus-ring ml-auto grid size-10 place-items-center rounded-md border border-border text-muted transition-colors hover:border-brand/60 hover:text-text md:hidden"
                    :aria-expanded="isMenuOpen"
                    aria-controls="menu-mobile"
                    aria-label="Buka menu"
                    @click="openMenu"
                >
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
        </header>

        <div
            v-if="isMenuOpen"
            id="menu-mobile"
            class="fixed inset-0 z-50 overflow-y-auto bg-ground md:hidden"
            role="dialog"
            aria-modal="true"
            aria-label="Menu navigasi"
            @keydown.escape.window="closeMenu"
        >
            <div class="flex h-16 items-center justify-between border-b border-border px-4">
                <div class="flex flex-col">
                    <span class="font-display text-sm font-bold uppercase tracking-tight">{{ appName }}</span>
                    <span class="mt-1 font-mono text-[10px] uppercase tracking-[0.22em] text-muted">Menu</span>
                </div>
                <button
                    ref="closeButton"
                    type="button"
                    class="focus-ring grid size-10 place-items-center rounded-md border border-border text-muted transition-colors hover:border-brand/60 hover:text-text"
                    aria-label="Tutup menu"
                    @click="closeMenu"
                >
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="px-4 py-6" aria-label="Navigasi mobile">
                <a
                    v-for="(item, index) in navItems"
                    :key="item.href"
                    :href="item.href"
                    class="focus-ring group flex items-center gap-4 border-b border-border py-5"
                    @click="closeMenu"
                >
                    <span class="font-mono text-[10px] text-brand-highlight">0{{ index + 1 }}</span>
                    <span class="font-display text-3xl font-bold uppercase tracking-tight transition-colors group-hover:text-brand-highlight">
                        {{ item.label }}
                    </span>
                </a>
            </nav>

            <div class="px-4 pb-10">
                <a
                    href="#katalog"
                    class="focus-ring flex items-center justify-between rounded-md bg-brand px-5 py-4 font-mono text-xs font-semibold uppercase tracking-[0.18em] text-text transition-colors hover:bg-brand-hover"
                    @click="closeMenu"
                >
                    Lihat katalog
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <p class="mt-6 font-mono text-[10px] uppercase tracking-[0.18em] text-muted">
                    Proses otomatis 24 jam
                </p>
            </div>
        </div>

        <main id="konten">
            <slot />
        </main>

        <footer class="border-t border-border bg-ground">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-[1.5fr_1fr_1fr] lg:px-8">
                <div>
                    <div class="flex items-center gap-3">
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="appName"
                            class="size-9 shrink-0 rounded-md object-contain"
                        />
                        <span
                            v-else
                            class="clip-corner-sm grid size-9 place-items-center bg-brand font-display text-base font-bold leading-none text-text"
                        >
                            {{ brandInitial }}
                        </span>
                        <span class="font-display text-lg font-bold uppercase tracking-tight">{{ appName }}</span>
                    </div>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-muted">
                        Top up game dan beli akun game dengan proses otomatis. Pilih game, masukkan ID, bayar, lalu pantau status pesanan.
                    </p>
                    <div class="mt-5 flex items-center gap-2 font-mono text-[10px] uppercase tracking-[0.18em] text-muted">
                        <span class="h-px w-6 bg-brand"></span>
                        Proses otomatis
                    </div>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Navigasi</p>
                    <ul class="mt-4 flex flex-col gap-3 text-sm">
                        <li><a href="#katalog" class="focus-ring rounded-sm text-text/80 transition-colors hover:text-brand-highlight">Katalog game</a></li>
                        <li><a href="#cara" class="focus-ring rounded-sm text-text/80 transition-colors hover:text-brand-highlight">Cara top up</a></li>
                        <li><a href="#faq" class="focus-ring rounded-sm text-text/80 transition-colors hover:text-brand-highlight">FAQ</a></li>
                        <li><a href="#cek-pesanan" class="focus-ring rounded-sm text-text/80 transition-colors hover:text-brand-highlight">Cek pesanan</a></li>
                    </ul>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Informasi</p>
                    <ul class="mt-4 flex flex-col gap-3 text-sm text-text/80">
                        <li>Guest checkout tanpa akun</li>
                        <li>Status pesanan transparan</li>
                        <li>Invoice dan bukti pembayaran</li>
                        <li>Stok akun mengikuti data toko</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-border">
                <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-muted sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <p>&copy; {{ year }} {{ appName }}. Semua hak dilindungi.</p>
                    <p class="font-mono uppercase tracking-[0.16em]">Top up game &amp; akun game</p>
                </div>
            </div>
        </footer>
    </div>
</template>
