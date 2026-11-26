<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import BrandLogo from '../components/BrandLogo.vue';

const page = usePage();
const appName = computed(() => page.props.appName ?? 'Zeth Market');
const authUser = computed(() => page.props.auth?.user ?? null);
const links = computed(() => page.props.links);
const isHome = computed(() => page.component === 'Home');
const orderLookupUrl = computed(() => `${links.value.home}#cek-pesanan`);
const year = new Date().getFullYear();

function logout() {
    router.post(links.value.logout);
}
</script>

<template>
    <div class="min-h-screen bg-ground font-sans text-text antialiased">
        <header class="sticky top-0 z-40 border-t-2 border-b border-t-brand border-b-border bg-ground">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-6 px-4 sm:px-6 lg:px-8">
                <Link :href="links.home" class="focus-ring rounded-md">
                    <BrandLogo subtitle="Top up game & akun" />
                </Link>

                <nav class="hidden items-center gap-6 md:flex" aria-label="Navigasi utama">
                    <Link
                        :href="links.home"
                        class="focus-ring relative py-2 font-mono text-[11px] uppercase tracking-[0.18em] transition-colors after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:bg-brand after:transition-transform hover:text-text hover:after:scale-x-100"
                        :class="isHome ? 'text-text after:scale-x-100' : 'text-muted after:scale-x-0'"
                    >
                        Beranda
                    </Link>
                    <a
                        :href="orderLookupUrl"
                        class="focus-ring relative py-2 font-mono text-[11px] uppercase tracking-[0.18em] text-muted transition-colors after:absolute after:inset-x-0 after:bottom-0 after:h-px after:origin-left after:scale-x-0 after:bg-brand after:transition-transform hover:text-text hover:after:scale-x-100"
                    >
                        Cek pesanan
                    </a>
                    <template v-if="authUser">
                        <Link
                            :href="links.orders"
                            class="focus-ring rounded-md border border-border px-3.5 py-2 font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-text transition-colors hover:border-brand/60 hover:text-brand-highlight"
                        >
                            Riwayat
                        </Link>
                        <button
                            type="button"
                            class="focus-ring cursor-pointer rounded-md border border-border px-3.5 py-2 font-mono text-[11px] font-semibold uppercase tracking-[0.14em] text-muted transition-colors hover:border-brand/60 hover:text-brand-highlight"
                            @click="logout"
                        >
                            Keluar
                        </button>
                    </template>
                    <Link
                        v-else
                        :href="links.login"
                        class="focus-ring rounded-md border border-brand/50 px-4 py-2 font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-brand-highlight transition-colors hover:bg-brand hover:text-text"
                    >
                        Masuk
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 pt-6 pb-40 sm:px-6 md:pb-14 lg:px-8">
            <slot />
        </main>

        <footer class="border-t border-border bg-ground pb-16 md:pb-0">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:px-8">
                <div class="sm:col-span-2">
                    <BrandLogo size="sm" />
                    <p class="mt-3 max-w-md text-sm leading-relaxed text-muted">
                        Top up game dan beli akun game dengan proses otomatis. Masukkan ID, pilih nominal, bayar, selesai.
                    </p>
                </div>
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Menu</p>
                    <ul class="mt-3 flex flex-col gap-2.5 text-sm">
                        <li><Link :href="links.home" class="focus-ring rounded-sm text-text/80 transition-colors hover:text-brand-highlight">Beranda</Link></li>
                        <li><a :href="orderLookupUrl" class="focus-ring rounded-sm text-text/80 transition-colors hover:text-brand-highlight">Cek pesanan</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-border">
                <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-muted sm:px-6 lg:px-8">&copy; {{ year }} {{ appName }}. Semua hak dilindungi.</p>
            </div>
        </footer>

        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-ground md:hidden" aria-label="Navigasi mobile">
            <div class="mx-auto grid max-w-md grid-cols-2 font-mono text-[10px] uppercase tracking-[0.14em]">
                <Link
                    :href="links.home"
                    class="focus-ring flex flex-col items-center gap-1 py-2.5 transition-colors"
                    :class="isHome ? 'text-brand-highlight' : 'text-muted hover:text-text'"
                >
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Beranda
                </Link>
                <a
                    :href="orderLookupUrl"
                    class="focus-ring flex flex-col items-center gap-1 py-2.5 text-muted transition-colors hover:text-text"
                >
                    <svg class="size-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25Z" />
                    </svg>
                    Cek pesanan
                </a>
            </div>
        </nav>
    </div>
</template>
