<script setup>
import { computed } from 'vue';

const { variant = 'info', title = null } = defineProps({
    variant: {
        type: String,
        default: 'info',
        validator: (value) => ['info', 'success', 'warning', 'danger'].includes(value),
    },
    title: { type: String, default: null },
});

const variantClasses = {
    info: 'border-border border-l-brand bg-ground text-muted',
    success: 'border-success/30 border-l-success bg-success/5 text-text',
    warning: 'border-warning/30 border-l-warning bg-warning/5 text-text',
    danger: 'border-danger/35 border-l-danger bg-danger/10 text-text',
};

const classes = computed(() => variantClasses[variant] ?? variantClasses.info);
</script>

<template>
    <div class="rounded-md border border-l-2 px-3.5 py-3 text-xs leading-relaxed" :class="classes">
        <p v-if="title" class="mb-1 font-display text-sm font-semibold text-text">{{ title }}</p>
        <slot />
    </div>
</template>
