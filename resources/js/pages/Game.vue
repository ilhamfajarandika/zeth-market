<script setup>
import { Head, Link, router, useForm, useHttp, usePage } from '@inertiajs/vue3';
import { computed, shallowRef, useTemplateRef, watch } from 'vue';
import GameCover from '../components/GameCover.vue';
import BaseAlert from '../components/ui/BaseAlert.vue';
import BaseBadge from '../components/ui/BaseBadge.vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import BaseCheckbox from '../components/ui/BaseCheckbox.vue';
import BaseInput from '../components/ui/BaseInput.vue';
import EmptyState from '../components/ui/EmptyState.vue';
import AppLayout from '../layouts/AppLayout.vue';

// Kontrak checkout: docs/TASK_DIVISION.md §3.4. Harga, fee, dan total dihitung server.
const { game, products, accounts, paymentChannels, checkoutUrl, nicknameUrl } = defineProps({
    game: { type: Object, required: true },
    products: { type: Array, required: true },
    accounts: { type: Array, default: () => [] },
    paymentChannels: { type: Array, default: () => [] },
    checkoutUrl: { type: String, required: true },
    // Null jika game tidak punya kode cek nickname Melo.
    nicknameUrl: { type: String, default: null },
});

const page = usePage();

const channelGroupOrder = ['QRIS', 'E-Wallet', 'Virtual Account', 'Lainnya'];

const allProducts = products;

// ?product=ID dipakai partial reload metode bayar; dibaca ulang agar pilihan bertahan saat halaman dimuat ulang.
const productFromUrl = Number(new URL(page.url, 'http://localhost').searchParams.get('product')) || null;

// Kontak dan centang konfirmasi ID diingat di browser ini agar tidak diisi ulang. ID pembeli sendiri tidak disimpan.
const CONTACT_STORAGE_KEY = 'checkout-contact';

function loadSavedContact() {
    try {
        return JSON.parse(localStorage.getItem(CONTACT_STORAGE_KEY)) ?? {};
    } catch {
        return {};
    }
}

function saveContact(contact) {
    try {
        localStorage.setItem(CONTACT_STORAGE_KEY, JSON.stringify(contact));
    } catch {
        // Penyimpanan diblokir (mode privat atau kuota penuh): form tetap jalan, hanya tidak diingat.
    }
}

const savedContact = loadSavedContact();

const form = useForm({
    product_id: allProducts.some((product) => product.id === productFromUrl) ? productFromUrl : null,
    target_user_id: '',
    target_zone_id: '',
    email: savedContact.email ?? '',
    whatsapp: (savedContact.whatsapp ?? '').replace(/\D/g, ''),
    payment_method: null,
    id_confirmed: savedContact.id_confirmed === true,
});

// User ID dan WhatsApp hanya angka; huruf dan simbol (termasuk "+") dibuang saat diketik atau ditempel.
watch(
    () => form.target_user_id,
    (value) => (form.target_user_id = value.replace(/\D/g, '')),
);
watch(
    () => form.whatsapp,
    (value) => (form.whatsapp = value.replace(/\D/g, '')),
);

watch(
    () => [form.whatsapp, form.email, form.id_confirmed],
    ([whatsapp, email, idConfirmed]) => saveContact({ whatsapp, email, id_confirmed: idConfirmed }),
);

const isLoadingChannels = shallowRef(false);

const selectedProduct = computed(() => allProducts.find((product) => product.id === form.product_id) ?? null);

const selectedChannel = computed(() => paymentChannels.find((channel) => channel.code === form.payment_method) ?? null);

const channelGroups = computed(() =>
    channelGroupOrder
        .map((label) => ({ label, channels: paymentChannels.filter((channel) => channel.group === label) }))
        .filter((group) => group.channels.length > 0),
);

const canCheckout = computed(
    () =>
        selectedProduct.value !== null &&
        selectedChannel.value !== null &&
        form.target_user_id.trim() !== '' &&
        (!game.requiresZoneId || form.target_zone_id.trim() !== '') &&
        form.whatsapp.trim() !== '' &&
        form.id_confirmed &&
        !form.processing &&
        !nicknameHttp.processing &&
        !isLoadingChannels.value,
);

function selectProduct(product) {
    if (isSelected(product)) {
        return;
    }

    form.product_id = product.id;
    form.payment_method = null;
    form.clearErrors('product_id', 'payment_method');

    router.reload({
        only: ['paymentChannels'],
        data: { product: product.id },
        preserveScroll: true,
        onStart: () => (isLoadingChannels.value = true),
        onFinish: () => (isLoadingChannels.value = false),
    });
}

function selectChannel(channel) {
    form.payment_method = channel.code;
    form.clearErrors('payment_method');
}

// Cek nickname hanya menampilkan hasil; "Lanjut bayar" tetap tersedia walau cek gagal (usulan dokumen, belum dikonfirmasi PO).
const nicknameHttp = useHttp({ target_user_id: '', target_zone_id: '' });
const nicknameDialog = useTemplateRef('nicknameDialog');
const nicknamePlayer = shallowRef(null);

// Loading cek ditampilkan di tombol Beli Sekarang; dialog terbuka setelah hasilnya ada.
const buyButtonLabel = computed(() => {
    if (form.processing) {
        return 'Memproses…';
    }

    return nicknameHttp.processing ? 'Mengecek…' : 'Beli Sekarang';
});

async function submit() {
    if (!canCheckout.value) {
        return;
    }

    nicknamePlayer.value = null;

    if (nicknameUrl) {
        nicknameHttp.target_user_id = form.target_user_id;
        nicknameHttp.target_zone_id = form.target_zone_id;

        try {
            nicknamePlayer.value = (await nicknameHttp.post(nicknameUrl)) ?? null;
        } catch {
            nicknamePlayer.value = null;
        }
    }

    nicknameDialog.value.showModal();
}

function closeNicknameDialog() {
    nicknameDialog.value.close();
}

function confirmCheckout() {
    closeNicknameDialog();
    form.post(checkoutUrl, { preserveScroll: true });
}

function isSelected(product) {
    return form.product_id === product.id;
}

function productCardClass(product) {
    return isSelected(product)
        ? 'border-brand bg-brand/10'
        : 'border-border bg-ground hover:border-brand/50';
}

function channelCardClass(channel) {
    return form.payment_method === channel.code
        ? 'border-brand bg-brand/10'
        : 'border-border bg-ground hover:border-brand/50';
}
</script>

<template>

    <Head :title="game.name" />

    <AppLayout>
        <section class="relative overflow-hidden rounded-lg border border-border bg-surface">
            <img v-if="game.bannerUrl" :src="game.bannerUrl" alt=""
                class="absolute inset-0 size-full object-cover opacity-30" />
            <div v-else class="pointer-events-none absolute inset-0 bg-tech-grid opacity-45" aria-hidden="true"></div>
            <span class="absolute inset-x-0 top-0 h-0.5 bg-brand" aria-hidden="true"></span>

            <div class="relative flex items-end gap-4 p-5 pt-10 sm:gap-6 sm:p-8 sm:pt-14">
                <GameCover :game="game" class="size-20 shrink-0 rounded-lg border border-border sm:size-28" />
                <div class="min-w-0">
                    <Link :href="page.props.links.home"
                        class="focus-ring inline-flex items-center gap-1.5 rounded-sm font-mono text-[11px] uppercase tracking-[0.14em] text-muted transition-colors hover:text-brand-highlight">
                        <svg class="size-3.5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                        Semua game
                    </Link>
                    <h1 class="mt-2 truncate font-display text-2xl font-bold tracking-tight sm:text-3xl">{{ game.name }}
                    </h1>
                    <p class="mt-0.5 font-mono text-xs uppercase tracking-[0.12em] text-muted">{{ game.publisher }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <BaseBadge variant="success">Proses otomatis</BaseBadge>
                        <BaseBadge variant="neutral">Pembayaran aman</BaseBadge>
                    </div>
                </div>
            </div>
        </section>

        <form class="mt-6 grid gap-6 lg:grid-cols-3" novalidate @submit.prevent="submit">
            <div class="flex flex-col gap-5 lg:col-span-2">
                <BaseCard>
                    <h2 class="mb-4 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span
                            class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">01</span>
                        Masukkan data akun
                    </h2>
                    <div class="grid gap-3" :class="{ 'sm:grid-cols-2': game.requiresZoneId }">
                        <BaseInput v-model="form.target_user_id" :label="game.userIdLabel"
                            :error="form.errors.target_user_id" required type="text" inputmode="numeric"
                            autocomplete="off" :placeholder="`Masukkan ${game.userIdLabel}`" />
                        <BaseInput v-if="game.requiresZoneId" v-model="form.target_zone_id" :label="game.zoneIdLabel"
                            :error="form.errors.target_zone_id" required type="text" inputmode="numeric"
                            autocomplete="off" :placeholder="`Masukkan ${game.zoneIdLabel}`" />
                    </div>
                    <BaseAlert v-if="game.instruction" class="mt-4">
                        {{ game.instruction }}
                    </BaseAlert>
                </BaseCard>

                <BaseCard>
                    <h2 class="mb-4 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span
                            class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">02</span>
                        Pilih item
                    </h2>

                    <template v-if="products.length">
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <button v-for="product in products" :key="product.id" type="button"
                                :aria-pressed="isSelected(product)"
                                class="focus-ring relative flex cursor-pointer flex-col items-start rounded-md border p-3.5 text-left transition-colors"
                                :class="productCardClass(product)" @click="selectProduct(product)">
                                <span class="pr-6 font-display text-sm font-semibold leading-snug text-text">{{
                                    product.name }}</span>
                                <span v-if="product.strikePrice"
                                    class="mt-1.5 font-mono text-xs text-muted line-through">{{ product.strikePrice
                                    }}</span>
                                <span class="mt-1 font-mono text-sm font-bold text-brand-highlight">{{ product.price
                                    }}</span>

                                <svg v-if="isSelected(product)" aria-hidden="true"
                                    class="absolute top-2.5 right-2.5 size-4 text-brand-highlight" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12Zm13.36-1.814a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z"
                                        clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.product_id" class="mt-3 text-xs text-danger">{{ form.errors.product_id }}
                        </p>
                    </template>
                    <EmptyState v-else title="Belum ada item"
                        description="Produk untuk game ini belum tersedia saat ini." />
                </BaseCard>

                <BaseCard v-if="accounts.length">
                    <h2 class="font-display text-base font-bold tracking-tight">Akun game dijual</h2>
                    <p class="mb-4 mt-1 text-xs text-muted">Akun dijual manual. Hubungi admin untuk membeli akun ini.
                    </p>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        <li v-for="account in accounts" :key="account.id"
                            class="rounded-md border border-border bg-ground p-3.5">
                            <p class="font-display text-sm font-semibold text-text">{{ account.name }}</p>
                            <p v-if="account.publicInfo" class="mt-1 text-xs text-muted">{{ account.publicInfo }}</p>
                            <p class="mt-2 font-mono text-sm font-bold text-brand-highlight">{{ account.price }}</p>
                        </li>
                    </ul>
                </BaseCard>

                <BaseCard>
                    <h2 class="mb-4 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span
                            class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">03</span>
                        Pilih pembayaran
                    </h2>

                    <p v-if="!selectedProduct" class="text-sm text-muted">Pilih item dulu untuk melihat metode
                        pembayaran dan biayanya.
                    </p>
                    <p v-else-if="isLoadingChannels" class="text-sm text-muted" role="status">Memuat metode pembayaran…
                    </p>
                    <BaseAlert v-else-if="!channelGroups.length" variant="warning">
                        Metode pembayaran belum tersedia. Coba pilih item lagi beberapa saat lagi.
                    </BaseAlert>
                    <template v-else>
                        <div v-for="(group, groupIndex) in channelGroups" :key="group.label"
                            :class="{ 'mt-4': groupIndex > 0 }">
                            <h3 class="mb-2 font-mono text-[10px] uppercase tracking-[0.16em] text-muted">{{ group.label
                                }}</h3>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                <button v-for="channel in group.channels" :key="channel.code" type="button"
                                    :aria-pressed="form.payment_method === channel.code"
                                    class="focus-ring flex min-w-0 cursor-pointer items-center gap-2 rounded-md border px-2.5 py-2 text-left transition-colors"
                                    :class="channelCardClass(channel)" @click="selectChannel(channel)">
                                    <span class="grid h-6 w-10 shrink-0 place-items-center rounded-sm bg-white">
                                        <img v-if="channel.iconUrl" :src="channel.iconUrl" alt="" loading="lazy"
                                            class="max-h-4 max-w-9 object-contain" />
                                        <span v-else class="font-mono text-[9px] font-bold text-ground">{{ channel.code
                                            }}</span>
                                    </span>
                                    <span class="min-w-0 flex-1 truncate text-xs font-medium text-text"
                                        :title="channel.name">{{ channel.name }}</span>
                                    <span class="shrink-0 font-mono text-[11px] text-muted">+{{ channel.fee }}</span>
                                </button>
                            </div>
                        </div>
                    </template>
                    <p v-if="form.errors.payment_method" class="mt-3 text-xs text-danger">{{ form.errors.payment_method
                        }}</p>
                </BaseCard>

                <BaseCard>
                    <h2 class="mb-4 flex items-center gap-3 font-display text-base font-bold tracking-tight">
                        <span
                            class="clip-corner-sm grid size-7 shrink-0 place-items-center bg-brand font-mono text-xs font-bold text-text">04</span>
                        Detail kontak
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <BaseInput v-model="form.whatsapp" label="WhatsApp" :error="form.errors.whatsapp" type="tel"
                            inputmode="numeric" autocomplete="tel" required placeholder="08xxxxxxxxxx" />
                        <BaseInput v-model="form.email" label="Email" :error="form.errors.email" type="email"
                            autocomplete="email" placeholder="nama@email.com" />
                    </div>
                    <p class="mt-3 text-xs text-muted">Status pesanan dikirim ke kontak ini. Kolom bertanda <span
                            class="text-danger">*</span> wajib diisi.</p>

                    <BaseCheckbox v-model="form.id_confirmed" :error="form.errors.id_confirmed"
                        class="mt-5 border-t border-border pt-4">
                        ID yang saya masukkan sudah benar. Kesalahan ID bukan tanggung jawab penjual.
                    </BaseCheckbox>
                </BaseCard>
            </div>

            <aside class="hidden lg:block">
                <BaseCard as="div" class="sticky top-24">
                    <p class="font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Ringkasan</p>
                    <h2 class="mt-1 font-display text-lg font-bold tracking-tight">Detail pesanan</h2>
                    <dl class="mt-4 flex flex-col gap-3 border-t border-border pt-4 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Game</dt>
                            <dd class="text-right font-medium">{{ game.name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Item</dt>
                            <dd class="text-right font-medium">{{ selectedProduct?.name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Harga</dt>
                            <dd class="text-right font-mono">{{ selectedProduct?.price ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Metode bayar</dt>
                            <dd class="text-right font-medium">{{ selectedChannel?.name ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted">Biaya admin</dt>
                            <dd class="text-right font-mono">{{ selectedChannel?.fee ?? '-' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 border-t border-border pt-3">
                            <dt class="font-medium">Total</dt>
                            <dd class="text-right font-mono font-bold text-brand-highlight">{{ selectedChannel?.total ??
                                '-' }}</dd>
                        </div>
                    </dl>

                    <BaseButton type="submit" :disabled="!canCheckout" block size="lg" class="mt-5">
                        {{ buyButtonLabel }}
                    </BaseButton>
                </BaseCard>
            </aside>

            <div class="fixed inset-x-0 bottom-[57px] z-30 border-t border-border bg-surface px-4 py-3 lg:hidden">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate text-xs text-muted">
                            {{ selectedProduct ? [selectedProduct.name, selectedChannel?.name].filter(Boolean).join(' · ') : 'Belum pilih item' }}
                        </p>
                        <p class="font-mono text-sm font-bold text-brand-highlight">{{ selectedChannel?.total ??
                            selectedProduct?.price ?? '-' }}</p>
                    </div>
                    <BaseButton type="submit" :disabled="!canCheckout" size="md" class="shrink-0">
                        {{ buyButtonLabel }}
                    </BaseButton>
                </div>
            </div>
        </form>

        <dialog id="nickname-dialog" ref="nicknameDialog" closedby="any" aria-labelledby="nickname-dialog-title"
            class="m-auto w-[calc(100%-2rem)] max-w-md overscroll-contain rounded-lg border border-border bg-surface p-0 text-text backdrop:overflow-hidden backdrop:overscroll-contain backdrop:bg-black/70">
            <div class="flex flex-col gap-4 p-5 sm:p-6">
                <h2 id="nickname-dialog-title" class="font-display text-lg font-bold tracking-tight">Konfirmasi pesanan
                </h2>

                <BaseAlert v-if="!nicknameUrl">Cek nickname belum tersedia untuk game ini.</BaseAlert>
                <div v-else-if="nicknamePlayer?.username"
                    class="rounded-md border border-success/30 bg-success/5 p-3.5">
                    <p class="font-mono text-[11px] uppercase tracking-[0.12em] text-muted">Nickname</p>
                    <p class="break-all font-display text-lg font-bold">{{ nicknamePlayer.username }}</p>
                </div>
                <BaseAlert v-else variant="warning">
                    Nickname tidak dapat dipastikan. Periksa kembali {{ game.userIdLabel }} sebelum membayar.
                </BaseAlert>

                <dl class="grid gap-2.5 rounded-md border border-border bg-ground p-3.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">{{ game.userIdLabel }}</dt>
                        <dd class="break-all text-right font-mono">
                            {{ form.target_user_id }}<template v-if="game.requiresZoneId"> ({{ form.target_zone_id
                                }})</template>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Item</dt>
                        <dd class="text-right">{{ selectedProduct?.name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Metode</dt>
                        <dd class="text-right">{{ selectedChannel?.name }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 border-t border-border pt-2.5">
                        <dt class="font-medium">Total</dt>
                        <dd class="text-right font-mono font-bold text-brand-highlight">{{ selectedChannel?.total ?? '-'
                            }}</dd>
                    </div>
                </dl>

                <div class="grid grid-cols-2 gap-3">
                    <BaseButton variant="secondary" @click="closeNicknameDialog">Batal</BaseButton>
                    <BaseButton :disabled="form.processing" @click="confirmCheckout">Lanjut
                        bayar
                    </BaseButton>
                </div>
            </div>
        </dialog>
    </AppLayout>
</template>
