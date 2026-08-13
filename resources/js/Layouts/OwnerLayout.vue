<script setup>
import ThemeToggle from '@/Components/ThemeToggle.vue';
import PwaSplashScreen from '@/Components/PwaSplashScreen.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const logoutForm = useForm({});
const showLogoutModal = ref(false);

const confirmLogout = () => {
    showLogoutModal.value = false;
    logoutForm.post('/owner/logout');
};

const page = usePage();
const user = computed(() => page.props.auth?.user || page.props.owner || {});

const getInitials = (name) => {
    if (!name) return '??';
    const parts = name.trim().split(/\s+/); 
    if (parts.length >= 2 && parts[0] && parts[1]) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return parts[0].substring(0, 2).toUpperCase();
};

// 🚀 Native App Icon Strategy: Switch from Outline to Filled when active
const navLinks = [
    { name: 'Dashboard', url: '/owner/dashboard', icon: 'bi-house', activeIcon: 'bi-house-fill' },
    { name: 'Listing', url: '/owner/listing', icon: 'bi-journal-text', activeIcon: 'bi-journal-richtext' },
    { name: 'Reservations', url: '/owner/reservations', icon: 'bi-calendar2-check', activeIcon: 'bi-calendar2-check-fill' },
    { name: 'Settings', url: '/owner/settings', icon: 'bi-gear', activeIcon: 'bi-gear-fill' },
];
</script>

<template>
    <PwaSplashScreen />

    <div class="d-flex min-vh-100 bg-body-tertiary">
        
        <!-- ==========================================
             DESKTOP SIDEBAR
        =========================================== -->
        <aside class="bg-body border-end border-secondary-subtle d-none d-md-flex flex-column p-3 shadow-sm" style="width: 260px; position: fixed; height: 100vh; overflow-y: auto; z-index: 1030;">
            
            <div class="d-flex align-items-center mb-2 px-2">
                <Link href="/owner/dashboard" title="E-BoardMate Owner Dashboard">
                    <img 
                        src="../Pages/Public/Images/eboarmatelogo.png" 
                        alt="E-BoardMate Logo" 
                        class="img-fluid"
                        style="height: 65px; object-fit: contain; image-rendering: -webkit-optimize-contrast;"
                    />
                </Link>
            </div>
            
            <div class="text-body-secondary small fw-bold text-uppercase mb-4 px-2 tracking-tight">
                Owner Portal
            </div>

            <nav class="nav flex-column gap-2 mb-auto" aria-label="Desktop Sidebar Navigation">
                <Link 
                    v-for="link in navLinks" 
                    :key="link.name" 
                    :href="link.url" 
                    class="nav-link rounded px-3 py-2 fw-semibold transition-all d-flex align-items-center gap-3"
                    :class="$page.url === link.url ? 'bg-success text-white shadow-sm' : 'text-body hover-bg-nav'"
                >
                    <!-- Uses the active (filled) icon for desktop when selected -->
                    <i :class="['bi fs-5', $page.url === link.url ? link.activeIcon : link.icon]"></i>
                    {{ link.name }}
                </Link>
            </nav>

            <div class="px-2 mt-4 mb-3">
                <ThemeToggle />
            </div>
            
            <div class="px-2 pb-2">
                <button
                    type="button"
                    class="btn btn-outline-danger w-100 fw-bold d-flex align-items-center justify-content-center gap-2 rounded-pill"
                    @click="showLogoutModal = true"
                >
                    <i class="bi bi-box-arrow-right"></i> Logout
                </button>
            </div>
        </aside>

        <!-- ==========================================
             MAIN CONTENT AREA
        =========================================== -->
        <div class="main-content flex-grow-1 d-flex flex-column w-100 position-relative">
            <main class="p-3 p-md-4 w-100 mobile-main-padding" style="overflow-x: hidden;">
                <slot />
            </main>
        </div>

        <!-- ==========================================
             MOBILE NATIVE BOTTOM NAVIGATION
        =========================================== -->
        <div class="pwa-nav-wrapper d-md-none">
            <nav class="pwa-bottom-nav bg-body shadow-lg border border-secondary-subtle">
                <Link 
                    v-for="link in navLinks" 
                    :key="link.name" 
                    :href="link.url"
                    class="nav-item text-decoration-none"
                    :class="{ 'active text-success': $page.url === link.url, 'text-body-secondary': $page.url !== link.url }"
                >
                    <!-- The Native Glowing Top Indicator -->
                    <div class="active-indicator"></div>
                    
                    <!-- Dynamically switches between outline and filled icon -->
                    <i :class="['bi fs-4 mb-1 nav-icon', $page.url === link.url ? link.activeIcon : link.icon]"></i>
                    <span class="nav-text">{{ link.name }}</span>
                </Link>
            </nav>
        </div>

        <!-- 🚀 2-STEP LOGOUT CONFIRMATION MODAL -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showLogoutModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 9999;">
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 rounded-4 shadow-lg bg-body">
                            <div class="modal-body p-4 text-center">
                                <div class="onboarding-badge-icon mb-3 mx-auto bg-danger-subtle text-danger fs-2" style="width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    🚪
                                </div>
                                <h3 class="h5 fw-bold mb-2 text-body-emphasis">Log Out Confirmation</h3>
                                <p class="text-body-secondary small mb-4">Are you sure you want to log out of your landlord account?</p>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-secondary w-50 rounded-pill fw-semibold" @click="showLogoutModal = false">
                                        Cancel
                                    </button>
                                    <button type="button" class="btn btn-danger w-50 rounded-pill fw-bold" :disabled="logoutForm.processing" @click="confirmLogout">
                                        <span v-if="logoutForm.processing" class="spinner-border spinner-border-sm me-1"></span>
                                        Yes, Log Out
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </div>
</template>

<style scoped>
.main-content {
    margin-left: 0;
}

@media (min-width: 768px) {
    .main-content {
        margin-left: 260px;
    }
}

.hover-bg-nav:hover {
    background-color: var(--bs-secondary-bg);
}

.transition-all {
    transition: all 0.2s ease-in-out;
}

/* =========================================
   PWA BOTTOM NAVIGATION - NATIVE PILL STYLE
========================================== */

/* Wrapper to hold the floating pill above the bottom edge */
.pwa-nav-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 0 16px;
    padding-bottom: calc(16px + env(safe-area-inset-bottom)); /* Respects iPhone swipe bar */
    z-index: 1040;
    pointer-events: none; /* Allows clicking through the wrapper margins */
}

/* The actual floating navigation bar */
.pwa-bottom-nav {
    display: flex;
    justify-content: space-around;
    align-items: center;
    height: 70px;
    border-radius: 24px; /* Pill shape */
    pointer-events: auto; /* Re-enables clicking on the nav bar itself */
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    background-color: rgba(var(--bs-body-bg-rgb), 0.95) !important;
}

.nav-item {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 25%;
    height: 100%;
    transition: all 0.3s ease;
}

/* The Glowing Top Line Indicator (Invisible by default) */
.active-indicator {
    position: absolute;
    top: -1px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 4px;
    background-color: transparent;
    border-radius: 0 0 4px 4px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-icon {
    transition: all 0.3s ease;
}

.nav-text {
    font-size: 0.65rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

/* =========================================
   ACTIVE STATE 
========================================== */
.nav-item.active {
    color: #10b981 !important; /* Premium Emerald Green */
}

/* Expand and glow the top indicator */
.nav-item.active .active-indicator {
    width: 40px; /* Width of the glowing line */
    background-color: #10b981;
    box-shadow: 0 3px 10px rgba(16, 185, 129, 0.6);
}

.nav-item.active .nav-icon {
    transform: translateY(-2px);
    text-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.nav-item.active .nav-text {
    font-weight: 700;
}

/* Prevents content from hiding behind the floating nav pill or phone status bar */
@media (max-width: 767px) {
    .mobile-main-padding {
        padding-top: calc(16px + env(safe-area-inset-top)) !important;
        padding-bottom: calc(110px + env(safe-area-inset-bottom)) !important;
    }
}
</style>