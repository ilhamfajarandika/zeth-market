<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import BaseButton from '../components/ui/BaseButton.vue';
import BaseCard from '../components/ui/BaseCard.vue';
import AppLayout from '../layouts/AppLayout.vue';

const { status } = defineProps({
    status: { type: Number, required: true },
});

const copyByStatus = {
    403: {
        title: 'Akses ditolak',
        message: 'Kamu tidak memiliki izin untuk membuka halaman ini.',
    },
    404: {
        title: 'Halaman tidak ditemukan',
        message: 'Halaman, game, atau nomor invoice yang kamu cari tidak tersedia atau sudah dipindahkan.',
    },
    419: {
        title: 'Sesi halaman berakhir',
        message: 'Halaman terlalu lama tidak aktif. Muat ulang halaman lalu coba kembali.',
    },
    429: {
        title: 'Terlalu banyak permintaan',
        message: 'Permintaan dikirim terlalu cepat. Tunggu beberapa saat lalu coba lagi.',
    },
    500: {
        title: 'Terjadi gangguan di server',
        message: 'Sistem mengalami kendala sementara. Coba lagi beberapa saat lagi.',
    },
    503: {
        title: 'Sedang dalam pemeliharaan',
        message: 'Layanan sedang diperbarui sementara waktu. Kembali beberapa menit lagi.',
    },
};

const content = computed(
    () =>
        copyByStatus[status] ?? {
            title: 'Terjadi kesalahan',
            message: 'Permintaan tidak dapat diproses saat ini. Silakan kembali ke beranda.',
        },
);

function reloadPage() {
    window.location.reload();
}
</script>

<template>
    <Head :title="`${status} — ${content.title}`" />

    <AppLayout>
        <div class="mx-auto flex min-h-[60vh] max-w-xl items-center py-8">
            <BaseCard variant="accent" padding="lg" class="w-full border-l-2 border-l-brand">
                <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-brand-highlight">
                    <span class="h-px w-6 bg-brand" aria-hidden="true"></span>
                    Status {{ status }}
                </p>
                <p class="mt-3 font-mono text-5xl font-bold tracking-tight text-text">{{ status }}</p>
                <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-text">{{ content.title }}</h1>
                <p class="mt-2.5 text-sm leading-relaxed text-muted">{{ content.message }}</p>

                <div class="mt-6 flex flex-wrap gap-3">
                    <BaseButton :href="route('home')" size="lg">
                        Kembali ke beranda
                    </BaseButton>
                    <BaseButton
                        v-if="status === 419 || status >= 500"
                        variant="secondary"
                        size="lg"
                        @click="reloadPage"
                    >
                        Muat ulang
                    </BaseButton>
                    <BaseButton
                        v-else
                        :href="`${route('home')}#katalog`"
                        variant="secondary"
                        size="lg"
                    >
                        Lihat katalog
                    </BaseButton>
                </div>
            </BaseCard>
        </div>
    </AppLayout>
</template>
