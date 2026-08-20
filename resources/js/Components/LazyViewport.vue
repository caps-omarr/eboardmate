<script setup>
import { useInView } from '@/Composables/useInView';

const props = defineProps({
    minHeight: {
        type: String,
        default: '250px',
    },
    rootMargin: {
        type: String,
        default: '200px 0px',
    },
    threshold: {
        type: [Number, Array],
        default: 0,
    },
    triggerOnce: {
        type: Boolean,
        default: true,
    },
});

const { targetRef, isVisible } = useInView({
    rootMargin: props.rootMargin,
    threshold: props.threshold,
    triggerOnce: props.triggerOnce,
});
</script>

<template>
    <div
        ref="targetRef"
        class="lazy-viewport-wrapper"
        :style="{ minHeight: props.minHeight }"
    >
        <!-- Rendered when inside user's active viewport -->
        <slot v-if="isVisible" />

        <!-- Placeholder Skeleton Rendered when outside viewport to prevent CLS -->
        <slot v-else name="fallback" />
    </div>
</template>

<style scoped>
.lazy-viewport-wrapper {
    width: 100%;
    transition: min-height 0.2s ease-in-out;
}
</style>
