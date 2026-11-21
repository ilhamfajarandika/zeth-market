<script setup>
import { computed } from 'vue';

const { as = 'section', variant = 'default', padding = 'md' } = defineProps({
    as: { type: String, default: 'section' },
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'raised', 'accent', 'success', 'danger'].includes(value),
    },
    padding: {
        type: String,
        default: 'md',
        validator: (value) => ['none', 'md', 'lg'].includes(value),
    },
});

const variantClasses = {
    default: 'border-border bg-surface',
    raised: 'border-border bg-surface-raised',
    accent: 'border-brand/50 bg-surface',
    success: 'border-success/40 bg-surface',
    danger: 'border-danger/40 bg-surface',
};

const paddingClasses = {
    none: '',
    md: 'p-5',
    lg: 'p-6',
};

const classes = computed(() => [
    'rounded-lg border',
    variantClasses[variant] ?? variantClasses.default,
    paddingClasses[padding] ?? paddingClasses.md,
]);
</script>

<template>
    <component :is="as" :class="classes">
        <slot />
    </component>
</template>
