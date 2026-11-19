<script setup>
import { computed } from 'vue';

const { game } = defineProps({
    game: { type: Object, required: true },
});

const gradients = [
    'from-emerald-500 to-teal-800',
    'from-sky-500 to-blue-800',
    'from-amber-500 to-orange-800',
    'from-rose-500 to-red-800',
    'from-zinc-600 to-zinc-900',
];

const gradient = computed(() => {
    const hash = [...game.slug].reduce((total, character) => total + character.charCodeAt(0), 0);

    return gradients[hash % gradients.length];
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
    <div class="relative overflow-hidden bg-slate-800">
        <img v-if="game.thumbnailUrl" :src="game.thumbnailUrl" :alt="game.name" loading="lazy" class="size-full object-cover" />
        <div v-else class="grid size-full place-items-center bg-gradient-to-br" :class="gradient" role="img" :aria-label="game.name">
            <span class="text-2xl font-black tracking-tight text-white/90 drop-shadow" aria-hidden="true">{{ initials }}</span>
        </div>
    </div>
</template>
