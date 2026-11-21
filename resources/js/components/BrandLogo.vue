<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { subtitle = null, size = 'md' } = defineProps({
    subtitle: { type: String, default: null },
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md', 'lg'].includes(value),
    },
});

const page = usePage();
const appName = computed(() => page.props.appName ?? 'Zeth Market');
const logoUrl = computed(() => page.props.logoUrl ?? null);
const brandInitial = computed(() => appName.value.charAt(0).toUpperCase());

const iconSizeClass = computed(() => (size === 'sm' ? 'size-8 text-sm' : 'size-9 text-base'));
const titleSizeClass = computed(() => {
    if (size === 'sm') {
        return 'text-sm';
    }

    return size === 'lg' ? 'text-lg' : 'text-base';
});
</script>

<template>
    <span class="inline-flex items-center gap-3">
        <img
            v-if="logoUrl"
            :src="logoUrl"
            alt=""
            decoding="async"
            class="shrink-0 rounded-md object-contain"
            :class="iconSizeClass"
        />
        <span
            v-else
            aria-hidden="true"
            class="clip-corner-sm grid shrink-0 place-items-center bg-brand font-display font-bold leading-none text-text"
            :class="iconSizeClass"
        >
            {{ brandInitial }}
        </span>
        <span class="flex flex-col">
            <span class="font-display font-bold uppercase leading-none tracking-tight" :class="titleSizeClass">
                {{ appName }}
            </span>
            <span v-if="subtitle" class="mt-1 font-mono text-[10px] uppercase leading-none tracking-[0.22em] text-muted">
                {{ subtitle }}
            </span>
        </span>
    </span>
</template>
