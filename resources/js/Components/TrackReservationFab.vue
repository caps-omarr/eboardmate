<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    hide: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();

const isVisible = computed(() => {
    if (props.hide) return false;
    // Suppress on Track Reservation page itself (user is already tracking)
    if (page.component === 'Public/TrackReservation') return false;
    // Suppress on Boarding House Detail page to prevent collision with reservation CTA
    if (page.component === 'Public/BoardingHouseDetail') return false;
    return true;
});
</script>

<template>
    <Transition name="fab-fade">
        <Link
            v-if="isVisible"
            href="/track-reservation"
            class="track-fab btn btn-success shadow-lg rounded-pill d-flex align-items-center gap-2 transition-all text-decoration-none"
            aria-label="Track Reservation Status"
            title="Track your reservation status with your reference code"
        >
            <div class="fab-icon-box bg-white text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                <i class="bi bi-clock-history fs-5"></i>
            </div>
            <span class="fab-text fw-bold me-1 text-nowrap d-none d-sm-inline">
                Track Reservation
            </span>
        </Link>
    </Transition>
</template>

<style scoped>
/* Floating Action Button (FAB) Styling */
.track-fab {
    position: fixed;
    bottom: max(1.25rem, calc(1.25rem + env(safe-area-inset-bottom, 0px)));
    right: 1.25rem;
    z-index: 1040;
    padding: 0.55rem 1rem 0.55rem 0.55rem;
    box-shadow: 0 8px 24px rgba(25, 135, 84, 0.4) !important;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1),
                background-color 0.2s ease;
    color: #ffffff !important;
    -webkit-tap-highlight-color: transparent;
}

.track-fab:hover {
    transform: translateY(-3px) scale(1.02);
    box-shadow: 0 12px 28px rgba(25, 135, 84, 0.5) !important;
    color: #ffffff !important;
}

.track-fab:active {
    transform: translateY(0) scale(0.96);
}

.fab-icon-box {
    width: 38px;
    height: 38px;
}

/* On extra-small mobile screens, render as a compact elevated circular button */
@media (max-width: 575.98px) {
    .track-fab {
        padding: 0.55rem;
        border-radius: 50% !important;
        width: 50px;
        height: 50px;
        justify-content: center;
    }

    .fab-icon-box {
        width: 34px;
        height: 34px;
    }
}

/* Smooth Slide & Scale Transitions */
.fab-fade-enter-active,
.fab-fade-leave-active {
    transition: opacity 0.25s ease, transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.fab-fade-enter-from,
.fab-fade-leave-to {
    opacity: 0;
    transform: translateY(20px) scale(0.85);
    pointer-events: none;
}
</style>
