<script setup>
import { computed } from 'vue';

const {
    variant = 'primary',
    size = 'md',
    type = 'button',
    href = null,
    disabled = false,
    block = false,
} = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'ghost'].includes(value),
    },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
    type: { type: String, default: 'button' },
    href: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    block: { type: Boolean, default: false },
});

const variantClasses = {
    primary: 'bg-brand text-text hover:bg-brand-hover',
    secondary: 'border border-border bg-surface-raised text-text hover:border-brand/60 hover:text-brand-highlight',
    ghost: 'text-muted hover:text-text',
};

const sizeClasses = {
    sm: 'px-3 py-2 text-xs',
    md: 'px-5 py-2.5 text-sm',
    lg: 'px-5 py-3 text-sm',
};

const classes = computed(() => [
    'focus-ring inline-flex items-center justify-center gap-2 rounded-md font-display font-bold transition-colors active:translate-y-px',
    variantClasses[variant] ?? variantClasses.primary,
    sizeClasses[size] ?? sizeClasses.md,
    { 'w-full': block },
    disabled ? 'pointer-events-none cursor-not-allowed opacity-45' : 'cursor-pointer',
]);
</script>

<template>
    <a v-if="href" :href="href" :class="classes" :aria-disabled="disabled || undefined">
        <slot />
    </a>
    <button v-else :type="type" :disabled="disabled" :class="classes">
        <slot />
    </button>
</template>
