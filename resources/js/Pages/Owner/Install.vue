<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const deferredPrompt = ref(null);
const isInstalled = ref(false);
const isIOS = ref(false);
const showGuideModal = ref(false);

onMounted(() => {
    // Detect iOS device
    const userAgent = window.navigator.userAgent.toLowerCase();
    isIOS.value = /iphone|ipad|ipod/.test(userAgent);

    // Check if running in standalone PWA mode
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        isInstalled.value = true;
    }

    // Capture the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt.value = e;
    });
});

const triggerInstall = async () => {
    if (deferredPrompt.value) {
        deferredPrompt.value.prompt();
        const { outcome } = await deferredPrompt.value.userChoice;
        if (outcome === 'accepted') {
            isInstalled.value = true;
        }
        deferredPrompt.value = null;
    } else {
        // Fallback: Open step-by-step install guide modal
        showGuideModal.value = true;
    }
};
</script>

<template>
    <Head>
        <title>Install Landlord App | E-BoardMate Portal</title>
        <meta name="description" content="Install the official E-BoardMate Landlord PWA App on your Android or iPhone for 1-tap access to boarding house reservations." />
    </Head>

    <div class="min-vh-100 bg-body-tertiary d-flex flex-column justify-content-center align-items-center p-3 position-relative transition-all">
        
        <!-- Floating Theme Toggle in Top Right (No Top Navbar Bar) -->
        <div class="position-absolute top-0 end-0 p-3 p-md-4" style="z-index: 10;">
            <ThemeToggle />
        </div>

        <!-- Main Content Card -->
        <main class="w-100 max-w-install my-auto">
            <div class="bg-body border border-secondary-subtle rounded-4 shadow-lg p-4 p-md-5 text-center position-relative overflow-hidden mx-auto">
                
                <!-- Single Official Logo Header -->
                <div class="mb-3">
                    <Link href="/" title="E-BoardMate Home">
                        <img 
                            src="../Public/Images/eboarmatelogo.png" 
                            alt="E-BoardMate Logo" 
                            class="img-fluid"
                            style="max-height: 65px; object-fit: contain;"
                        >
                    </Link>
                </div>

                <div class="mb-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold small">
                        Official Landlord PWA App
                    </span>
                </div>

                <h1 class="h3 fw-bold text-body-emphasis mb-2">Install Landlord App</h1>
                <p class="text-body-secondary small mb-4">
                    Install E-BoardMate directly onto your smartphone home screen for 1-tap access, fast loading, and real-time reservation notifications.
                </p>

                <!-- INSTALLED STATE -->
                <div v-if="isInstalled" class="alert alert-success rounded-4 border-0 p-3 mb-4 d-flex align-items-center gap-2 text-start">
                    <i class="bi bi-check-circle-fill fs-4 text-success flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold">App Already Installed!</div>
                        <div class="small">Launch E-BoardMate directly from your mobile home screen.</div>
                    </div>
                </div>

                <!-- PRIMARY INSTALL BUTTON -->
                <div v-else class="mb-4">
                    <button 
                        @click="triggerInstall" 
                        class="btn btn-ebm-primary btn-lg w-100 rounded-pill py-3 fw-bold shadow transition-all d-flex align-items-center justify-content-center gap-2"
                    >
                        <i class="bi bi-download fs-5"></i> Install App Now
                    </button>
                </div>

                <!-- FEATURES LIST -->
                <div class="row g-3 text-start border-top border-secondary-subtle pt-4">
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-lightning-charge-fill text-warning fs-5"></i>
                            <div>
                                <div class="fw-bold small text-body-emphasis">1-Tap Launch</div>
                                <div class="text-body-secondary" style="font-size: 0.75rem;">Fast home screen access</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <div>
                                <div class="fw-bold small text-body-emphasis">Role Guarded</div>
                                <div class="text-body-secondary" style="font-size: 0.75rem;">Secure landlord data</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOOTER DASHBOARD LINK -->
                <div class="mt-4 pt-2">
                    <Link href="/owner/login" class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-semibold me-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Owner Sign In
                    </Link>
                    <Link href="/owner/dashboard" class="btn btn-sm btn-outline-success rounded-pill px-4 fw-semibold">
                        Owner Dashboard <i class="bi bi-arrow-right ms-1"></i>
                    </Link>
                </div>

            </div>
        </main>

        <!-- INSTALLATION GUIDE MODAL -->
        <Teleport to="body">
            <Transition name="fade">
                <div v-if="showGuideModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.65); backdrop-filter: blur(4px); z-index: 99999;">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4 shadow-lg bg-body">
                            <div class="modal-header border-bottom-0 pb-0">
                                <h2 class="h5 fw-bold mb-0 text-body-emphasis d-flex align-items-center gap-2">
                                    <i class="bi bi-phone-vibrate text-success fs-4"></i> How to Install App
                                </h2>
                                <button type="button" class="btn-close shadow-none" @click="showGuideModal = false"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div v-if="isIOS" class="mb-3">
                                    <div class="fw-bold text-body-emphasis mb-2"><i class="bi bi-apple me-1"></i> On Safari (iPhone / iPad):</div>
                                    <ol class="small text-body-secondary mb-0 ps-3 d-flex flex-column gap-2">
                                        <li>Tap the <strong>Share</strong> button <i class="bi bi-square-and-arrow-up text-primary"></i> at the bottom of Safari.</li>
                                        <li>Scroll down and select <strong>"Add to Home Screen"</strong> <i class="bi bi-plus-square text-success"></i>.</li>
                                        <li>Tap <strong>Add</strong> in the top right corner.</li>
                                    </ol>
                                </div>
                                <div v-else class="mb-3">
                                    <div class="fw-bold text-body-emphasis mb-2"><i class="bi bi-android2 me-1"></i> On Android / Chrome / Edge:</div>
                                    <ol class="small text-body-secondary mb-0 ps-3 d-flex flex-column gap-2">
                                        <li>Tap the <strong>Browser Menu (3 Dots ⋮)</strong> in the top right corner.</li>
                                        <li>Select <strong>"Install app"</strong> or <strong>"Add to Home screen"</strong>.</li>
                                        <li>Confirm by tapping <strong>Install</strong>.</li>
                                    </ol>
                                </div>
                                <div class="d-flex justify-content-end pt-3 border-top border-secondary-subtle">
                                    <button type="button" class="btn btn-success rounded-pill px-4 fw-bold" @click="showGuideModal = false">
                                        Got It!
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Footer -->
        <footer class="mt-auto py-3 text-center small text-body-secondary">
            &copy; {{ new Date().getFullYear() }} E-BoardMate • Talibon Polytechnic College Housing System
        </footer>

    </div>
</template>

<style scoped>
.max-w-install {
    max-width: 480px;
    width: 100%;
}
.btn-ebm-primary {
    background-color: #10b981;
    color: white;
    border: none;
    min-height: 48px;
}
.btn-ebm-primary:hover {
    background-color: #059669;
    color: white;
}
</style>
