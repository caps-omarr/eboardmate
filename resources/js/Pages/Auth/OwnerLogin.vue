<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/owner/login', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Owner Login | E-BoardMate" />

    <main class="min-vh-100 d-flex align-items-center">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5">
                    <div class="ebm-card p-4 p-md-5">
                        <div class="text-center mb-4">
                            <span class="badge rounded-pill text-bg-success mb-3">
                                Owner Portal
                            </span>

                            <h1 class="h3 fw-bold mb-2">
                                Boarding House Owner Login
                            </h1>

                            <p class="ebm-muted mb-0">
                                Sign in to manage your assigned boarding house and reservations.
                            </p>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    Email Address
                                </label>

                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.email }"
                                    placeholder="owner@example.com"
                                    autocomplete="email"
                                    autofocus
                                >

                                <div v-if="form.errors.email" class="invalid-feedback">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    Password
                                </label>

                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.password }"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                >

                                <div v-if="form.errors.password" class="invalid-feedback">
                                    {{ form.errors.password }}
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input
                                    id="remember"
                                    v-model="form.remember"
                                    class="form-check-input"
                                    type="checkbox"
                                >

                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary w-100 py-2"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Signing in...</span>
                                <span v-else>Login as Owner</span>
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <Link href="/" class="small">
                                Back to homepage
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>