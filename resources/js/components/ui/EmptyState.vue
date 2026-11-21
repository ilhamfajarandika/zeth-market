<script setup>
const { title, description = null } = defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
});
</script>

<template>
    <div class="rounded-lg border border-dashed border-border bg-ground/60 px-6 py-10 text-center">
        <p class="font-display text-sm font-semibold text-text">{{ title }}</p>
        <p v-if="description" class="mt-1.5 text-xs text-muted">{{ description }}</p>
        <div v-if="$slots.default" class="mt-4">
            <slot />
        </div>
    </div>
</template>
