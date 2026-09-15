<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/admin/login', {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Super Admin Login | E-BoardMate" />

    <main class="min-vh-100 d-flex align-items-center bg-body-tertiary">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5">
                    <div class="ebm-card p-4 p-md-5 rounded-4 shadow-sm border border-secondary-subtle bg-body">
                        <div class="text-start mb-4">
                            <span class="text-uppercase small fw-bold text-secondary tracking-wider d-block mb-1">
                                Administrative Access
                            </span>

                            <h1 class="h3 fw-bold text-body-emphasis mb-2">
                                Super Admin Login
                            </h1>

                            <p class="text-body-secondary mb-0 small">
                                Sign in to oversee properties, verify listings, and inspect system audit trails.
                            </p>
                        </div>

                        <form @submit.prevent="submit">
                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold small text-body-secondary text-uppercase mb-2">
                                    Email Address
                                </label>

                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="form-control rounded-3 border-secondary-subtle"
                                    style="min-height: 44px;"
                                    :class="{ 'is-invalid': form.errors.email }"
                                    placeholder="admin@example.com"
                                    autocomplete="email"
                                    autofocus
                                >

                                <div v-if="form.errors.email" class="invalid-feedback">
                                    {{ form.errors.email }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label fw-bold small text-body-secondary text-uppercase mb-2">
                                    Password
                                </label>

                                <input
                                    id="password"
                                    v-model="form.password"
                                    type="password"
                                    class="form-control rounded-3 border-secondary-subtle"
                                    style="min-height: 44px;"
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

                                <label class="form-check-label small text-body-secondary" for="remember">
                                    Keep me signed in
                                </label>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-success w-100 py-2.5 rounded-3 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2"
                                style="min-height: 48px;"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="spinner-border spinner-border-sm"></span>
                                <span v-if="form.processing">Authenticating...</span>
                                <span v-else>Sign In to Admin Console</span>
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <Link href="/" class="small text-decoration-none text-body-secondary">
                                &larr; Back to accommodation directory
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</template>