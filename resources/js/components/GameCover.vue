<script setup>
import { computed } from 'vue';

const { game } = defineProps({
    game: { type: Object, required: true },
});

const initials = computed(() =>
    game.name
        .split(' ')
        .slice(0, 2)
        .map((word) => word.charAt(0))
        .join('')
        .toUpperCase(),
);
</script>

<template>
    <div class="relative overflow-hidden bg-surface-raised">
        <img v-if="game.thumbnailUrl" :src="game.thumbnailUrl" :alt="game.name" loading="lazy" decoding="async" class="size-full object-cover" />
        <div v-else class="relative grid size-full place-items-center bg-surface-raised" role="img" :aria-label="game.name">
            <span class="absolute inset-x-0 top-0 h-px bg-brand/70" aria-hidden="true"></span>
            <span class="font-display text-3xl font-bold tracking-tight text-text/85" aria-hidden="true">{{ initials }}</span>
            <span class="absolute bottom-3 left-3 font-mono text-[10px] uppercase tracking-[0.2em] text-muted">Game</span>
        </div>
    </div>
</template>
