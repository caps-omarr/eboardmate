<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/admin/logout');
};
</script>

<template>
    <div class="min-vh-100 bg-ebm">
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
            <div class="container py-2">
                <Link href="/admin/dashboard" class="navbar-brand d-flex align-items-center gap-2 fw-bold text-ebm-primary">
                    <span class="brand-mark">E</span>
                    <span>Super Admin Panel</span>
                </Link>

                <div class="d-flex align-items-center gap-2">
                    <Link href="/admin/dashboard" class="btn btn-sm btn-light border">
                        Dashboard
                    </Link>

                    <Link href="/admin/boarding-houses" class="btn btn-sm btn-light border">
                        Boarding Houses
                    </Link>

                    <Link href="/admin/reports" class="btn btn-sm btn-light border">
                        Reports
                    </Link>

                    <Link href="/admin/activity-logs" class="btn btn-sm btn-light border">
                        Activity Logs
                    </Link>

                    <Link href="/" class="btn btn-sm btn-ebm-outline">
                        Public Site
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