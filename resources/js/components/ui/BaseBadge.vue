<script setup>
import { computed } from 'vue';

const { variant = 'neutral', dot = false } = defineProps({
    variant: {
        type: String,
        default: 'neutral',
        validator: (value) => ['neutral', 'brand', 'success', 'warning', 'danger', 'info'].includes(value),
    },
    dot: { type: Boolean, default: false },
});

const variantClasses = {
    neutral: 'border-border bg-surface-raised text-muted',
    brand: 'border-brand/50 bg-brand/15 text-brand-highlight',
    success: 'border-success/35 bg-success/10 text-success',
    warning: 'border-warning/35 bg-warning/10 text-warning',
    danger: 'border-danger/35 bg-danger/10 text-danger',
    info: 'border-sky-400/35 bg-sky-400/10 text-sky-300',
};

const classes = computed(() => variantClasses[variant] ?? variantClasses.neutral);
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-sm border px-2 py-0.5 font-mono text-[10px] font-semibold uppercase tracking-[0.12em]"
        :class="classes"
    >
        <span v-if="dot" class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
        <slot />
    </span>
</template>
