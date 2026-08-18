<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import PwaSplashScreen from '@/Components/PwaSplashScreen.vue';

const form = useForm({
    email: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

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
                    
                    <!-- Clean Single Official Logo Header -->
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

                </div>
            </div>
        </main>

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