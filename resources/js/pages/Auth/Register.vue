<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseCard from '../../components/ui/BaseCard.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import AppLayout from '../../layouts/AppLayout.vue';

const page = usePage();

const form = useForm({
    name: '',
    email: '',
    whatsapp: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(page.props.links.register, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Daftar akun" />

    <AppLayout>
        <div class="mx-auto flex min-h-[65vh] max-w-md items-center py-6">
            <BaseCard padding="lg" class="w-full border-l-2 border-l-brand">
                <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-brand-highlight">
                    <span class="h-px w-6 bg-brand" aria-hidden="true"></span>
                    Akun pelanggan
                </p>
                <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-text">Buat akun baru.</h1>
                <p class="mt-2 text-sm leading-relaxed text-muted">
                    Simpan riwayat top up dan pembelian akun game dalam satu tempat.
                </p>

                <form class="mt-6 flex flex-col gap-4" @submit.prevent="submit">
                    <BaseInput
                        v-model="form.name"
                        label="Nama"
                        type="text"
                        autocomplete="name"
                        placeholder="Nama lengkap"
                        :error="form.errors.name"
                        required
                    />

                    <BaseInput
                        v-model="form.email"
                        label="Email"
                        type="email"
                        autocomplete="email"
                        placeholder="nama@email.com"
                        :error="form.errors.email"
                        required
                    />

                    <BaseInput
                        v-model="form.whatsapp"
                        label="WhatsApp (opsional)"
                        type="tel"
                        inputmode="tel"
                        autocomplete="tel"
                        placeholder="08xxxxxxxxxx"
                        :error="form.errors.whatsapp"
                    />

                    <BaseInput
                        v-model="form.password"
                        label="Password"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Minimal 8 karakter"
                        :error="form.errors.password"
                        required
                    />

                    <BaseInput
                        v-model="form.password_confirmation"
                        label="Konfirmasi password"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Ulangi password"
                        :error="form.errors.password_confirmation"
                        required
                    />

                    <BaseButton type="submit" block size="lg" :disabled="form.processing" class="mt-1">
                        {{ form.processing ? 'Mendaftarkan...' : 'Buat Akun' }}
                    </BaseButton>
                </form>

                <p class="mt-6 border-t border-border pt-5 text-center text-sm text-muted">
                    Sudah punya akun?
                    <Link :href="page.props.links.login" class="focus-ring rounded-sm font-semibold text-brand-highlight hover:text-text">
                        Masuk di sini
                    </Link>
                </p>
            </BaseCard>
        </div>
    </AppLayout>
</template>
