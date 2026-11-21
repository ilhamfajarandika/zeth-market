<script setup>
import { useId } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number], default: '' });

const { label = null, id = null, hint = null, error = null } = defineProps({
    label: { type: String, default: null },
    id: { type: String, default: null },
    hint: { type: String, default: null },
    error: { type: String, default: null },
});

const generatedId = useId();
const inputId = id ?? generatedId;
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <label v-if="label" :for="inputId" class="font-mono text-[11px] uppercase tracking-[0.12em] text-muted">
            {{ label }}
        </label>
        <input
            :id="inputId"
            v-model="model"
            v-bind="$attrs"
            :aria-invalid="error ? 'true' : undefined"
            class="focus-ring w-full rounded-md border bg-ground px-3 py-2.5 text-sm text-text placeholder:text-muted/70 transition-colors"
            :class="error ? 'border-danger focus:border-danger' : 'border-border focus:border-brand-highlight'"
        />
        <p v-if="error" class="text-xs text-danger">{{ error }}</p>
        <p v-else-if="hint" class="text-xs text-muted">{{ hint }}</p>
    </div>
</template>
