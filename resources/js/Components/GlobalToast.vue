<script setup>
import { usePage } from '@inertiajs/vue3';
import { ref, watch, onUnmounted } from 'vue';

const page = usePage();

// Toast State
const toasts = ref([]);
let toastIdCounter = 0;

/**
 * Add a new toast notification
 * 
 * @param {string} message The notification text
 * @param {'success'|'error'|'info'|'warning'} [type='success'] Notification type
 * @param {number} [duration=4000] Duration in milliseconds before auto-dismiss
 */
const addToast = (message, type = 'success', duration = 4000) => {
    if (!message) return;

    const id = ++toastIdCounter;
    
    // Icon mapping
    const iconMap = {
        success: 'bi-check-circle-fill',
        error: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill',
        warning: 'bi-exclamation-circle-fill',
    };

    // Header title mapping
    const titleMap = {
        success: 'Success',
        error: 'Error',
        info: 'Information',
        warning: 'Warning',
    };

    const toast = {
        id,
        message,
        type,
        icon: iconMap[type] || 'bi-info-circle-fill',
        title: titleMap[type] || 'Notification',
        timer: null,
    };

    // Auto-dismiss timer
    toast.timer = setTimeout(() => {
        removeToast(id);
    }, duration);

    // Limit active toasts to 4 max
    if (toasts.value.length >= 4) {
        toasts.value.shift();
    }

    toasts.value.push(toast);
};

/**
 * Remove a toast notification by ID
 */
const removeToast = (id) => {
    const index = toasts.value.findIndex(t => t.id === id);
    if (index !== -1) {
        if (toasts.value[index].timer) {
            clearTimeout(toasts.value[index].timer);
        }
        toasts.value.splice(index, 1);
    }
};

// 🚀 Watch Inertia Flashed Session Data
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) {
            addToast(flash.success, 'success');
        }
        if (flash.error) {
            addToast(flash.error, 'error');
        }
        if (flash.info) {
            addToast(flash.info, 'info');
        }
        if (flash.warning) {
            addToast(flash.warning, 'warning');
        }
        if (flash.message) {
            addToast(flash.message, 'info');
        }
    },
    { deep: true, immediate: true }
);

onUnmounted(() => {
    toasts.value.forEach(toast => {
        if (toast.timer) clearTimeout(toast.timer);
    });
});
</script>

<template>
    <Teleport to="body">
        <!-- Toast Stack Container (Bottom Right) -->
        <div 
            class="global-toast-container position-fixed bottom-0 end-0 p-3" 
            style="z-index: 99999; pointer-events: none;"
            aria-live="polite"
            aria-atomic="true"
        >
            <TransitionGroup name="toast-slide">
                <div 
                    v-for="toast in toasts" 
                    :key="toast.id"
                    class="toast-card card border-0 rounded-4 shadow-lg overflow-hidden mb-3 pointer-events-auto transition-all bg-body"
                    :class="`border-start border-4 border-${toast.type === 'error' ? 'danger' : toast.type}`"
                    role="alert"
                    aria-live="assertive"
                    aria-atomic="true"
                >
                    <div class="card-body p-3 d-flex align-items-start gap-3 position-relative">
                        
                        <!-- Type Icon Box -->
                        <div 
                            class="toast-icon-box rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                            :class="{
                                'bg-success bg-opacity-10 text-success': toast.type === 'success',
                                'bg-danger bg-opacity-10 text-danger': toast.type === 'error',
                                'bg-info bg-opacity-10 text-info': toast.type === 'info',
                                'bg-warning bg-opacity-10 text-warning': toast.type === 'warning',
                            }"
                        >
                            <i :class="['bi', toast.icon, 'fs-5']"></i>
                        </div>

                        <!-- Toast Message Body -->
                        <div class="flex-grow-1 pe-3">
                            <div class="fw-bold small text-body-emphasis mb-1">
                                {{ toast.title }}
                            </div>
                            <div class="small text-body-secondary leading-snug">
                                {{ toast.message }}
                            </div>
                        </div>

                        <!-- Manual Dismiss Close Button -->
                        <button 
                            type="button" 
                            class="btn-close shadow-none opacity-75 small" 
                            @click="removeToast(toast.id)"
                            aria-label="Close"
                        ></button>

                    </div>

                    <!-- Progress Bar Animation -->
                    <div class="toast-progress-bar" :class="`bg-${toast.type === 'error' ? 'danger' : toast.type}`"></div>

                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.global-toast-container {
    max-width: 380px;
    width: 100%;
}

.toast-card {
    will-change: transform, opacity;
    transform: translateZ(0);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12) !important;
}

.pointer-events-auto {
    pointer-events: auto;
}

.toast-icon-box {
    width: 38px;
    height: 38px;
}

.toast-progress-bar {
    height: 3px;
    width: 100%;
    transform-origin: left center;
    animation: toastProgress 4s linear forwards;
}

@keyframes toastProgress {
    from {
        transform: scaleX(1);
    }
    to {
        transform: scaleX(0);
    }
}

/* GPU Accelerated Slide & Fade Transitions */
.toast-slide-enter-active {
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-slide-leave-active {
    transition: all 0.25s cubic-bezier(0.4, 0, 1, 1);
}

.toast-slide-enter-from {
    opacity: 0;
    transform: translateX(60px) scale(0.95) translateZ(0);
}

.toast-slide-leave-to {
    opacity: 0;
    transform: translateY(-20px) scale(0.9) translateZ(0);
}
</style>
