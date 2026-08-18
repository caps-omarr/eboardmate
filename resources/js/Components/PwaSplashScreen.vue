<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import logo from '../Pages/Public/Images/eboarmatelogo.png';

// Reactive State
const isLoading = ref(false);
const progress = ref(0);
const statusMessage = ref('Loading E-BoardMate...');

// Memory Management: Timers & Inertia Listener Handles
let showTimer = null;
let finishTimer = null;
let progressInterval = null;

let removeStartListener = null;
let removeFinishListener = null;
let removeInvalidListener = null;
let removeExceptionListener = null;

/**
 * Clean up all active timers to prevent memory leaks
 */
const clearAllTimers = () => {
    if (showTimer) {
        clearTimeout(showTimer);
        showTimer = null;
    }
    if (finishTimer) {
        clearTimeout(finishTimer);
        finishTimer = null;
    }
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
};

/**
 * Trigger splash loading with a 150ms grace period to prevent UI flashing on fast responses
 */
const startLoading = (msg = 'Syncing Portal Data...', immediate = false) => {
    clearAllTimers();
    
    const executeStart = () => {
        statusMessage.value = msg;
        isLoading.value = true;
        progress.value = 15;
        
        progressInterval = setInterval(() => {
            if (progress.value < 88) {
                const increment = Math.floor(Math.random() * 12) + 4;
                progress.value = Math.min(progress.value + increment, 88);
            }
        }, 120);
    };

    if (immediate) {
        executeStart();
    } else {
        // 150ms grace period prevents UI flashing on fast network responses
        showTimer = setTimeout(executeStart, 150);
    }
};

/**
 * Complete progress bar and fade out splash overlay
 */
const finishLoading = () => {
    if (showTimer) {
        clearTimeout(showTimer);
        showTimer = null;
    }
    
    if (!isLoading.value) return;

    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }

    progress.value = 100;

    finishTimer = setTimeout(() => {
        isLoading.value = false;
        progress.value = 0;
    }, 200);
};

onMounted(() => {
    // Initial mount splash display
    startLoading('Loading Owner Portal...', true);
    finishTimer = setTimeout(finishLoading, 400);

    // Inertia Router Event Interceptions
    removeStartListener = router.on('start', (event) => {
        if (event?.detail?.visit?.url?.href?.includes('/owner/install')) return;
        startLoading('Navigating Owner Portal...');
    });

    removeFinishListener = router.on('finish', () => {
        finishLoading();
    });

    removeInvalidListener = router.on('invalid', () => {
        finishLoading();
    });

    removeExceptionListener = router.on('exception', () => {
        finishLoading();
    });
});

onUnmounted(() => {
    // 🛡️ Strict Garbage Collection & Listener Unregistration
    clearAllTimers();

    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
    if (removeInvalidListener) removeInvalidListener();
    if (removeExceptionListener) removeExceptionListener();
});
</script>

<template>
    <Teleport to="body">
        <Transition name="splash-fade">
            <div 
                v-if="isLoading" 
                class="pwa-splash-overlay" 
                role="dialog" 
                aria-modal="true" 
                aria-label="Application Loading Screen"
            >
                <div class="splash-card text-center p-4">

                    <!-- Accessible GPU-Accelerated Progress Bar (ScaleX transform) -->
                    <div 
                        class="splash-progress-track rounded-pill overflow-hidden mx-auto mb-3"
                        role="progressbar"
                        :aria-valuenow="progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="App Navigation Progress"
                    >
                        <div 
                            class="splash-progress-fill rounded-pill" 
                            :style="{ transform: `scaleX(${progress / 100})` }"
                        ></div>
                    </div>

                    <!-- Status Message Below Loading Bar -->
                    <div class="small fw-semibold text-body-secondary tracking-tight" aria-live="polite">
                        {{ statusMessage }}
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* GPU Layer Promotion & Hardware Acceleration */
.pwa-splash-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(var(--bs-body-bg-rgb), 0.92);
    /* Low blur backdrop filter to ensure 60fps on budget mobile devices */
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: all;
    will-change: opacity;
    transform: translateZ(0);
    backface-visibility: hidden;
}

.splash-card {
    max-width: 360px;
    width: 100%;
    will-change: transform, opacity;
    transform: translateZ(0);
}

.logo-wrapper {
    display: inline-block;
    will-change: transform;
    animation: logoGlowPulse 2s infinite ease-in-out;
}

.splash-logo {
    max-height: 70px;
    width: auto;
    object-fit: contain;
    filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.25));
}

/* GPU ScaleX Progress Bar Track & Fill */
.splash-progress-track {
    width: 200px;
    height: 6px;
    background-color: rgba(var(--bs-secondary-rgb), 0.15);
    position: relative;
}

.splash-progress-fill {
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
    transform-origin: left center;
    will-change: transform;
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes logoGlowPulse {
    0% {
        transform: scale(1) translateZ(0);
        filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.2));
    }
    50% {
        transform: scale(1.04) translateZ(0);
        filter: drop-shadow(0 6px 20px rgba(16, 185, 129, 0.4));
    }
    100% {
        transform: scale(1) translateZ(0);
        filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.2));
    }
}

.splash-fade-enter-active,
.splash-fade-leave-to {
    transition: opacity 0.25s ease-out;
}

.splash-fade-enter-from,
.splash-fade-leave-to {
    opacity: 0;
}
</style>
