<script setup>
const { lines = 1, height = 'h-4' } = defineProps({
    lines: { type: Number, default: 1 },
    height: { type: String, default: 'h-4' },
});
</script>

<template>
    <div class="flex flex-col gap-2.5" role="status" aria-label="Memuat konten">
        <span
            v-for="index in lines"
            :key="index"
            class="block rounded-sm bg-surface-raised"
            :class="[height, index === lines && lines > 1 ? 'w-3/4' : 'w-full']"
        ></span>
        <span class="sr-only">Memuat...</span>
    </div>
</template>
