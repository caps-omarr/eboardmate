<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const isLoading = ref(false);
const progress = ref(0);
const statusMessage = ref('Loading E-BoardMate...');

let progressInterval = null;
let showTimer = null;

const startLoading = (msg = 'Syncing Portal Data...', immediate = false) => {
    if (showTimer) clearTimeout(showTimer);
    
    const trigger = () => {
        statusMessage.value = msg;
        isLoading.value = true;
        progress.value = 15;
        
        if (progressInterval) clearInterval(progressInterval);
        progressInterval = setInterval(() => {
            if (progress.value < 85) {
                progress.value += Math.floor(Math.random() * 15) + 5;
            }
        }, 120);
    };

    if (immediate) {
        trigger();
    } else {
        showTimer = setTimeout(trigger, 180); // 180ms grace period prevents glitching on fast clicks!
    }
};

const finishLoading = () => {
    if (showTimer) clearTimeout(showTimer);
    if (!isLoading.value) return;
    progress.value = 100;
    setTimeout(() => {
        isLoading.value = false;
        if (progressInterval) clearInterval(progressInterval);
    }, 200);
};

let removeStart = null;
let removeFinish = null;

onMounted(() => {
    // Show splash screen on initial page mount briefly
    startLoading('Loading Owner Portal...', true);
    setTimeout(finishLoading, 450);

    // Inertia Router Navigation Hooks for Real-Time Loading
    removeStart = router.on('start', (event) => {
        // Skip splash screen for install page links or instant actions to prevent glitching
        if (event?.detail?.visit?.url?.href?.includes('/owner/install')) return;
        startLoading('Navigating Owner Portal...');
    });

    removeFinish = router.on('finish', () => {
        finishLoading();
    });
});

onUnmounted(() => {
    if (removeStart) removeStart();
    if (removeFinish) removeFinish();
    if (progressInterval) clearInterval(progressInterval);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="isLoading" class="pwa-splash-screen" role="status" aria-label="Loading E-BoardMate Portal">
                <div class="splash-content text-center p-4">
                    
                    <!-- Glowing Logo Container -->
                    <div class="logo-pulse-wrapper mb-4">
                        <img 
                            src="../Pages/Public/Images/eboarmatelogo.png" 
                            alt="E-BoardMate Logo" 
                            class="splash-logo img-fluid"
                        >
                    </div>

                    <!-- App Badge -->
                    <div class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold mb-3 small">
                        Landlord PWA Portal
                    </div>

                    <!-- Real-Time Progress Bar -->
                    <div class="splash-progress-container bg-secondary bg-opacity-20 rounded-pill overflow-hidden mx-auto mb-3 shadow-inner">
                        <div 
                            class="splash-progress-bar bg-success rounded-pill" 
                            :style="{ width: `${progress}%` }"
                        ></div>
                    </div>

                    <!-- Status Message -->
                    <div class="small fw-semibold text-body-secondary tracking-tight">
                        {{ statusMessage }}
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.pwa-splash-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(var(--bs-body-bg-rgb), 0.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: all;
}

.splash-content {
    max-width: 360px;
    width: 100%;
}

.logo-pulse-wrapper {
    display: inline-block;
    animation: logoPulse 2s infinite ease-in-out;
}

.splash-logo {
    max-height: 80px;
    width: auto;
    object-fit: contain;
    filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.25));
}

.splash-progress-container {
    width: 220px;
    height: 6px;
}

.splash-progress-bar {
    height: 100%;
    transition: width 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
}

@keyframes logoPulse {
    0% {
        transform: scale(1);
        filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.25));
    }
    50% {
        transform: scale(1.04);
        filter: drop-shadow(0 8px 24px rgba(16, 185, 129, 0.45));
    }
    100% {
        transform: scale(1);
        filter: drop-shadow(0 4px 12px rgba(16, 185, 129, 0.25));
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
