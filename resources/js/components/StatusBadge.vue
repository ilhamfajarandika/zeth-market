<script setup>
import { computed } from 'vue';

const { status } = defineProps({
    status: { type: Object, required: true },
});

const colorClasses = {
    success: 'bg-emerald-500/15 text-emerald-300 ring-emerald-500/30',
    danger: 'bg-rose-500/15 text-rose-300 ring-rose-500/30',
    warning: 'bg-amber-500/15 text-amber-300 ring-amber-500/30',
    info: 'bg-sky-500/15 text-sky-300 ring-sky-500/30',
    gray: 'bg-slate-500/15 text-slate-300 ring-slate-500/30',
};

const classes = computed(() => colorClasses[status.color] ?? colorClasses.gray);
</script>

<template>
    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1" :class="classes">
        <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>
        {{ status.label }}
    </span>
</template>
