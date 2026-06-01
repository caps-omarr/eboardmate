<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/owner/logout');
};
</script>

<template>
    <div class="min-vh-100 bg-ebm">
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
            <div class="container py-2">
                <Link href="/owner/dashboard" class="navbar-brand d-flex align-items-center gap-2 fw-bold text-ebm-primary">
                    <span class="brand-mark">E</span>
                    <span>Owner Portal</span>
                </Link>

                <div class="d-flex align-items-center gap-2">
                    <Link href="/owner/dashboard" class="btn btn-sm btn-light border">
                        Dashboard
                    </Link>

                    <Link href="/owner/reservations" class="btn btn-sm btn-light border">
                        Reservations
                    </Link>

                    <Link href="/owner/listing" class="btn btn-sm btn-light border">
                        My Listing
                    </Link>

                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm"
                        :disabled="logoutForm.processing"
                        @click="logout"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </nav>

        <main class="py-4">
            <slot />
        </main>
    </div>
</template>