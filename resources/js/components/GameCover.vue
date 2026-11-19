<script setup>
import { computed } from 'vue';

const { game } = defineProps({
    game: { type: Object, required: true },
});

const gradients = [
    'from-violet-500 to-fuchsia-600',
    'from-sky-500 to-indigo-600',
    'from-amber-500 to-rose-600',
    'from-emerald-500 to-teal-700',
    'from-pink-500 to-orange-500',
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
