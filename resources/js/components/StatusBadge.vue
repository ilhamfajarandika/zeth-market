<script setup>
import { computed } from 'vue';
import BaseBadge from './ui/BaseBadge.vue';

const { status } = defineProps({
    status: { type: Object, required: true },
});

const variantMap = {
    success: 'success',
    danger: 'danger',
    warning: 'warning',
    info: 'info',
    gray: 'neutral',
};

const variant = computed(() => variantMap[status.color] ?? 'neutral');
</script>

<template>
    <BaseBadge :variant="variant" dot>
        {{ status.label }}
    </BaseBadge>
</template>
