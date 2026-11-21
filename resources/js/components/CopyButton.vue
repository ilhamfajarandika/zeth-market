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
    <button
        type="button"
        class="focus-ring shrink-0 cursor-pointer rounded-md border px-3 py-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.12em] transition-colors active:translate-y-px"
        :class="
            state === 'copied'
                ? 'border-success/50 bg-success/10 text-success'
                : state === 'failed'
                  ? 'border-danger/50 bg-danger/10 text-danger'
                  : 'border-border bg-surface-raised text-text hover:border-brand/60 hover:text-brand-highlight'
        "
        @click="copy"
    >
        <span aria-live="polite">{{ state === 'copied' ? 'Tersalin' : state === 'failed' ? 'Gagal menyalin' : label }}</span>
    </button>
</template>
