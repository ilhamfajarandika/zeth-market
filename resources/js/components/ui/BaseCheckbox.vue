<script setup>
import { useId } from 'vue';

const model = defineModel({ type: Boolean, default: false });

const { id = null, disabled = false, error = null } = defineProps({
    id: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: null },
});

const generatedId = useId();
const inputId = id ?? generatedId;
</script>

<template>
    <div class="flex flex-col gap-1">
        <label :for="inputId" class="flex cursor-pointer items-start gap-2.5 text-xs leading-relaxed text-muted" :class="{ 'cursor-not-allowed opacity-60': disabled }">
            <input
                :id="inputId"
                v-model="model"
                type="checkbox"
                :disabled="disabled"
                class="focus-ring mt-0.5 size-4 shrink-0 rounded-sm border border-border bg-ground accent-brand"
            />
            <span><slot /></span>
        </label>
        <p v-if="error" class="text-xs text-danger">{{ error }}</p>
    </div>
</template>
