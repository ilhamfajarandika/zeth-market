<script setup>
import { onScopeDispose, shallowRef } from 'vue';

const { text, label = 'Salin' } = defineProps({
    text: { type: String, required: true },
    label: { type: String, default: 'Salin' },
});

const RESET_AFTER_MS = 1500;

/** @type {import('vue').ShallowRef<'idle' | 'copied' | 'failed'>} */
const state = shallowRef('idle');
let resetTimer = null;

async function copy() {
    try {
        await navigator.clipboard.writeText(text);
        state.value = 'copied';
    } catch {
        state.value = 'failed';
    }

    clearTimeout(resetTimer);
    resetTimer = setTimeout(() => (state.value = 'idle'), RESET_AFTER_MS);
}

onScopeDispose(() => clearTimeout(resetTimer));
</script>

<template>
    <button type="button" class="shrink-0 rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium transition hover:bg-white/20" @click="copy">
        <span aria-live="polite">{{ state === 'copied' ? 'Tersalin' : state === 'failed' ? 'Gagal menyalin' : label }}</span>
    </button>
</template>
