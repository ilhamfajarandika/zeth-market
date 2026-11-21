<script setup>
import { computed, onBeforeUnmount, onMounted, shallowRef, watch } from 'vue';

const props = defineProps({
    banners: { type: Array, default: () => [] },
});

const AUTOPLAY_MS = 6000;

const activeIndex = shallowRef(0);
const isPaused = shallowRef(false);
const prefersReducedMotion = shallowRef(false);
const progressKey = shallowRef(0);

let timer = null;
let touchStartX = null;

const hasBanners = computed(() => props.banners.length > 0);
const hasMultiple = computed(() => props.banners.length > 1);

function goTo(index) {
    const total = props.banners.length;

    if (!total) {
        return;
    }

    activeIndex.value = ((index % total) + total) % total;
}

function stopTimer() {
    if (timer !== null) {
        window.clearInterval(timer);
        timer = null;
    }
}

function startTimer() {
    stopTimer();

    if (!hasMultiple.value || prefersReducedMotion.value || isPaused.value) {
        return;
    }

    progressKey.value += 1;
    timer = window.setInterval(() => {
        goTo(activeIndex.value + 1);
        progressKey.value += 1;
    }, AUTOPLAY_MS);
}

function select(index) {
    goTo(index);
    startTimer();
}

function next() {
    goTo(activeIndex.value + 1);
    startTimer();
}

function prev() {
    goTo(activeIndex.value - 1);
    startTimer();
}

function pause() {
    isPaused.value = true;
    stopTimer();
}

function resume() {
    isPaused.value = false;
    startTimer();
}

function handleFocusOut(event) {
    if (!event.currentTarget.contains(event.relatedTarget)) {
        resume();
    }
}

function handleTouchStart(event) {
    touchStartX = event.changedTouches[0]?.clientX ?? null;
}

function handleTouchEnd(event) {
    if (touchStartX === null) {
        return;
    }

    const delta = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
    touchStartX = null;

    if (Math.abs(delta) < 40) {
        return;
    }

    delta < 0 ? next() : prev();
}

function handleVisibilityChange() {
    document.hidden ? pause() : resume();
}

watch(
    () => props.banners.length,
    () => {
        activeIndex.value = 0;
        startTimer();
    },
);

onMounted(() => {
    prefersReducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    startTimer();
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onBeforeUnmount(() => {
    stopTimer();
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <section
        v-if="hasBanners"
        class="relative w-full overflow-hidden border-y border-border bg-ground focus-visible:ring-2 focus-visible:ring-brand-highlight focus-visible:ring-inset focus-visible:outline-none"
        aria-roledescription="carousel"
        aria-label="Banner promo"
        tabindex="0"
        @mouseenter="pause"
        @mouseleave="resume"
        @focusin="pause"
        @focusout="handleFocusOut"
        @keydown.left.prevent="prev"
        @keydown.right.prevent="next"
        @touchstart.passive="handleTouchStart"
        @touchend.passive="handleTouchEnd"
    >
        <div class="relative aspect-[3/1] w-full">
            <div
                v-for="(banner, index) in banners"
                :key="banner.id"
                class="absolute inset-0 transition-opacity duration-700 ease-out motion-reduce:transition-none"
                :class="index === activeIndex ? 'opacity-100' : 'pointer-events-none opacity-0'"
                role="group"
                aria-roledescription="slide"
                :aria-label="`${index + 1} dari ${banners.length}`"
                :aria-hidden="index !== activeIndex"
                :inert="index !== activeIndex || undefined"
            >
                <component :is="banner.linkUrl ? 'a' : 'div'" :href="banner.linkUrl || undefined" class="block size-full">
                    <img
                        :src="banner.imageUrl"
                        :alt="banner.alt"
                        class="size-full object-cover object-center"
                        :loading="index === 0 ? 'eager' : 'lazy'"
                        :fetchpriority="index === 0 ? 'high' : 'auto'"
                        decoding="async"
                    />
                </component>
            </div>

            <template v-if="hasMultiple">
                <button
                    type="button"
                    class="focus-ring absolute top-1/2 left-2 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-md border border-border bg-ground/85 text-text transition-colors hover:border-brand-highlight hover:text-brand-highlight sm:left-4 sm:size-10"
                    aria-label="Banner sebelumnya"
                    @click="prev"
                >
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </button>

                <button
                    type="button"
                    class="focus-ring absolute top-1/2 right-2 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-md border border-border bg-ground/85 text-text transition-colors hover:border-brand-highlight hover:text-brand-highlight sm:right-4 sm:size-10"
                    aria-label="Banner berikutnya"
                    @click="next"
                >
                    <svg class="size-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </button>

                <div class="absolute inset-x-0 bottom-3 z-10 flex items-center justify-center sm:bottom-4">
                    <div class="flex items-center gap-2 rounded-md border border-border bg-ground/85 px-2.5 py-2">
                        <button
                            v-for="(banner, index) in banners"
                            :key="`dot-${banner.id}`"
                            type="button"
                            class="focus-ring h-1 rounded-full transition-all"
                            :class="index === activeIndex ? 'w-7 bg-brand' : 'w-3 bg-white/35 hover:bg-white/60'"
                            :aria-label="`Tampilkan banner ${index + 1}`"
                            :aria-current="index === activeIndex ? 'true' : undefined"
                            @click="select(index)"
                        ></button>
                    </div>
                </div>

                <p class="absolute right-6 bottom-3 z-10 rounded-md border border-border bg-ground/85 px-2 py-1 font-mono text-[10px] tracking-[0.16em] text-muted sm:right-4 sm:bottom-4">
                    {{ String(activeIndex + 1).padStart(2, '0') }} / {{ String(banners.length).padStart(2, '0') }}
                </p>

                <div
                    v-if="!prefersReducedMotion"
                    :key="progressKey"
                    class="absolute inset-x-0 bottom-0 z-10 h-0.5 origin-left animate-progress bg-brand"
                    :style="{ animationPlayState: isPaused ? 'paused' : 'running' }"
                    aria-hidden="true"
                ></div>
            </template>
        </div>
    </section>
</template>
