<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import BaseButton from '../../components/ui/BaseButton.vue';
import BaseCard from '../../components/ui/BaseCard.vue';
import BaseCheckbox from '../../components/ui/BaseCheckbox.vue';
import BaseInput from '../../components/ui/BaseInput.vue';
import AppLayout from '../../layouts/AppLayout.vue';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login.store'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Masuk" />

    <AppLayout>
        <div class="mx-auto flex min-h-[65vh] max-w-md items-center py-6">
            <BaseCard padding="lg" class="w-full border-l-2 border-l-brand">
                <p class="flex items-center gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-brand-highlight">
                    <span class="h-px w-6 bg-brand" aria-hidden="true"></span>
                    Akun pelanggan
                </p>
                <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-text">Masuk ke akunmu.</h1>
                <p class="mt-2 text-sm leading-relaxed text-muted">
                    Masuk untuk melihat riwayat transaksi. Login bersifat opsional — kamu tetap bisa checkout sebagai guest.
                </p>

                <form class="mt-6 flex flex-col gap-4" @submit.prevent="submit">
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
                        v-model="form.password"
                        label="Password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        :error="form.errors.password"
                        required
                    />

                    <BaseCheckbox v-model="form.remember">
                        Ingat saya di perangkat ini
                    </BaseCheckbox>

                    <BaseButton type="submit" block size="lg" :disabled="form.processing" class="mt-1">
                        {{ form.processing ? 'Memproses...' : 'Masuk' }}
                    </BaseButton>
                </form>

                <p class="mt-6 border-t border-border pt-5 text-center text-sm text-muted">
                    Belum punya akun?
                    <Link :href="route('register')" class="focus-ring rounded-sm font-semibold text-brand-highlight hover:text-text">
                        Daftar sekarang
                    </Link>
                </p>
            </BaseCard>
        </div>
    </AppLayout>
</template>
