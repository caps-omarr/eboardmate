<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/admin/logout');
};
</script>

<template>
    <div class="min-vh-100 bg-ebm">
       
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shadow-sm">
            <div class="container py-0">
                
                <Link href="/admin/dashboard" class="navbar-brand d-flex align-items-center" title="E-BoardMate Super Admin Dashboard">
                    <img 
                        src="../Pages/Public/Images/eboarmatelogo.png" 
                        alt="E-BoardMate Logo" 
                        class="d-inline-block align-top img-fluid navbar-logo"
                        style="height: 85px; margin-top: -16px; margin-bottom: -16px; object-fit: contain; image-rendering: -webkit-optimize-contrast;"
                    />
                    
                    <span 
                        class="vr mx-2 text-muted opacity-50 d-none d-sm-block align-self-center" 
                        style="height: 24px; display: inline-block;"
                    ></span>
                    
                    <span class="fs-5 fw-bold text-secondary d-none d-sm-inline-block tracking-tight align-self-center">
                        Super Admin Panel
                    </span>
                </Link>

                <div class="d-flex align-items-center gap-2">
                    <Link href="/admin/dashboard" class="btn btn-sm btn-light border fw-medium px-3">
                        Dashboard
                    </Link>

                    <Link href="/admin/boarding-houses" class="btn btn-sm btn-light border fw-medium px-3">
                        Boarding Houses
                    </Link>

                    <Link href="/admin/reports" class="btn btn-sm btn-light border fw-medium px-3">
                        Reports
                    </Link>

                    <Link href="/admin/activity-logs" class="btn btn-sm btn-light border fw-medium px-3">
                        Activity Logs
                    </Link>

                    <Link href="/" class="btn btn-sm btn-ebm-outline fw-medium px-3">
                        Public Site
                    </Link>

                    <button
                        type="button"
                        class="btn btn-outline-danger btn-sm fw-medium px-3"
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

<style scoped>
@media (max-width: 576px) {
    .navbar-logo {
        height: 65px !important;
        margin-top: -10px !important;
        margin-bottom: -10px !important;
    }
}
</style>