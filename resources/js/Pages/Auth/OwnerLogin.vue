<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import PwaSplashScreen from '@/Components/PwaSplashScreen.vue';

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);
const deferredPrompt = ref(null);
const showGuideModal = ref(false);
const isIOS = ref(false);

onMounted(() => {
    const userAgent = window.navigator.userAgent.toLowerCase();
    isIOS.value = /iphone|ipad|ipod/.test(userAgent);

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
            deferredPrompt.value = null;
        }
    } else {
        showGuideModal.value = true;
    }
};

const submit = () => {
    form.post('/owner/login', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head>
        <title>Landlord Sign In | E-BoardMate Portal</title>
        <meta name="description" content="Sign in to the official E-BoardMate Landlord Portal to manage boarding house listings, room availability, and student reservations in Talibon, Bohol." />
    </Head>

    <PwaSplashScreen />

    <div class="min-vh-100 bg-body-tertiary d-flex flex-column justify-content-center align-items-center p-3 position-relative transition-all">
        
        <!-- Floating Theme Toggle in Top Right (No Top Navbar Bar) -->
        <div class="position-absolute top-0 end-0 p-3 p-md-4" style="z-index: 10;">
            <ThemeToggle />
        </div>

        <!-- Main MVP & Native App Login Card -->
        <main class="w-100 max-w-login my-auto">
            <div class="card border-secondary-subtle rounded-4 shadow-lg bg-body overflow-hidden transition-all position-relative">
                
                <!-- Native Card Top Accent Strip -->
                <div class="bg-success py-1"></div>

                <div class="card-body p-4 p-sm-5 text-center">
                    
                    <!-- Clean Single Official Logo Header (No Duplicate Logo Header Above) -->
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
                            Landlord PWA Portal
                        </span>
                    </div>

                    <h1 class="h4 fw-bold text-body-emphasis mb-1">Owner Sign In</h1>
                    <p class="text-body-secondary small mb-4">
                        Sign in to manage your boarding house & student reservations.
                    </p>

                    <!-- Login Form -->
                    <form @submit.prevent="submit" class="d-flex flex-column gap-3 text-start">
                        
                        <!-- Email Input -->
                        <div>
                            <label for="email" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">
                                Email Address
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-secondary-subtle text-body-secondary rounded-start-4 ps-3">
                                    <i class="bi bi-envelope-fill"></i>
                                </span>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-control bg-body-tertiary border-secondary-subtle rounded-end-4 py-2"
                                    :class="{ 'is-invalid': form.errors.email }"
                                    placeholder="landlord@example.com"
                                    autocomplete="email"
                                    required
                                    autofocus
                                >
                            </div>
                            <div v-if="form.errors.email" class="text-danger small fw-bold mt-1 ps-2">
                                {{ form.errors.email }}
                            </div>
                        </div>

                        <!-- Password Input with Show/Hide Toggle -->
                        <div>
                            <label for="password" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">
                                Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-body-tertiary border-secondary-subtle text-body-secondary rounded-start-4 ps-3">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="form-control bg-body-tertiary border-secondary-subtle py-2"
                                    :class="{ 'is-invalid': form.errors.password }"
                                    placeholder="••••••••"
                                    autocomplete="current-password"
                                    required
                                >
                                <button 
                                    type="button" 
                                    class="btn btn-outline-secondary border-secondary-subtle bg-body-tertiary rounded-end-4 px-3 text-body-secondary"
                                    @click="showPassword = !showPassword"
                                    :title="showPassword ? 'Hide Password' : 'Show Password'"
                                >
                                    <i :class="['bi', showPassword ? 'bi-eye-slash-fill' : 'bi-eye-fill']"></i>
                                </button>
                            </div>
                            <div v-if="form.errors.password" class="text-danger small fw-bold mt-1 ps-2">
                                {{ form.errors.password }}
                            </div>
                        </div>

                        <!-- Remember Me Option -->
                        <div class="d-flex align-items-center justify-content-between my-1">
                            <div class="form-check">
                                <input
                                    id="remember"
                                    v-model="form.remember"
                                    class="form-check-input shadow-none"
                                    type="checkbox"
                                >
                                <label class="form-check-label small fw-medium text-body-secondary ms-1" for="remember">
                                    Keep me signed in
                                </label>
                            </div>
                        </div>

                        <!-- Primary Submit CTA Button -->
                        <button
                            type="submit"
                            class="btn btn-native-primary rounded-pill py-3 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2 mt-2"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                            <span v-if="form.processing">Signing in...</span>
                            <span v-else><i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal</span>
                        </button>

                    </form>

                    <!-- PWA Native Mobile App Download Footer Section -->
                    <div class="mt-4 pt-3 text-center border-top border-secondary-subtle">
                        <p class="small text-body-secondary mb-2">Manage listings on the go with your mobile phone?</p>
                        <button type="button" @click="triggerInstall" class="btn btn-sm btn-outline-success rounded-pill fw-bold px-4 py-2 shadow-sm">
                            <i class="bi bi-phone-vibrate-fill me-1"></i> Install Landlord App
                        </button>
                    </div>

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
                                    <i class="bi bi-phone-vibrate text-success fs-4"></i> How to Install Landlord App
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
                                <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary-subtle">
                                    <Link href="/owner/install" class="small text-success fw-bold text-decoration-none" @click="showGuideModal = false">
                                        Open Download Page <i class="bi bi-arrow-right ms-1"></i>
                                    </Link>
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
.max-w-login {
    max-width: 420px;
    width: 100%;
}

.btn-native-primary {
    background-color: #10b981;
    color: white;
    border: none;
    font-size: 1rem;
    min-height: 48px;
    transition: all 0.2s ease;
}

.btn-native-primary:hover {
    background-color: #059669;
    color: white;
    transform: translateY(-1px);
}

.form-control:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
}
</style>