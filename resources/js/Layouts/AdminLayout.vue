<script setup>
import ThemeToggle from '@/Components/ThemeToggle.vue';
import GlobalToast from '@/Components/GlobalToast.vue';
import { Link, useForm, router } from '@inertiajs/vue3';

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/admin/logout');
};


const navLinks = [
    { name: 'Dashboard', url: '/admin/dashboard' },
    { name: 'Boarding Houses', url: '/admin/boarding-houses' },
    { name: 'Owners', url: '/admin/owners' },
    { name: 'Reports', url: '/admin/reports' },
    { name: 'Activity Logs', url: '/admin/activity-logs' },
    { name: 'Public Site', url: '/' },
];

const mobileNavigate = (url) => {
    const closeBtn = document.querySelector('#mobileSidebar .btn-close');
    if (closeBtn) closeBtn.click();
    
    router.visit(url);
};

const mobileLogout = () => {
    const closeBtn = document.querySelector('#mobileSidebar .btn-close');
    if (closeBtn) closeBtn.click();
    
    logout();
};
</script>

<template>
    <GlobalToast />
    <div class="d-flex min-vh-100 bg-body transition-all">
        
        <!-- ==========================================
             DESKTOP SIDEBAR
             bg-body-tertiary provides the slightly elevated card-like color
        =========================================== -->
        <aside class="bg-body-tertiary border-end d-none d-md-flex flex-column p-3 shadow-sm transition-all" style="width: 260px; position: fixed; height: 100vh; overflow-y: auto;">
            
            <!-- Logo Section -->
            <div class="d-flex align-items-center mb-2 px-2">
                <Link href="/admin/dashboard" title="E-BoardMate Super Admin Dashboard">
                    <img 
                        src="../Pages/Public/Images/eboarmatelogo.png" 
                        alt="E-BoardMate Logo" 
                        class="img-fluid"
                        style="height: 65px; object-fit: contain; image-rendering: -webkit-optimize-contrast;"
                    />
                </Link>
            </div>
            
            <!-- Admin Panel Title -->
            <div class="text-body-secondary small fw-bold text-uppercase mb-4 px-2 tracking-tight">
                Super Admin Panel
            </div>

            <!-- Navigation Links -->
            <nav class="nav flex-column gap-2 mb-auto" aria-label="Desktop Sidebar Navigation">
                <Link 
                    v-for="link in navLinks" 
                    :key="link.name" 
                    :href="link.url" 
                    class="nav-link rounded px-3 py-2 fw-semibold transition-all"
                    :class="$page.url === link.url ? 'bg-success text-white' : 'text-body hover-bg-adaptive'"
                >
                    {{ link.name }}
                </Link>
            </nav>

            <!-- Bottom Action Section -->
            <div class="mt-3">
                <hr class="mb-3 border-secondary-subtle">
                
                <!-- Theme Toggle Icon -->
                <div class="d-flex justify-content-start px-2 mb-3">
                    <ThemeToggle />
                </div>
                
                <!-- Logout Button -->
                <div class="px-2 pb-2">
                    <button
                        type="button"
                        class="btn btn-outline-danger w-100 fw-bold"
                        :disabled="logoutForm.processing"
                        @click="logout"
                    >
                        Logout
                    </button>
                </div>
            </div>
        </aside>

        <!-- ==========================================
             MAIN CONTENT WRAPPER
        =========================================== -->
        <div class="flex-grow-1 d-flex flex-column w-100 transition-all" style="margin-left: 0;" :style="{'@media (min-width: 768px)': 'margin-left: 260px !important'}">
            
            <!-- Mobile Header (Visible only on small screens) -->
            <header class="navbar bg-body-tertiary border-bottom d-md-none px-3 py-2 shadow-sm sticky-top transition-all">
                <div class="d-flex align-items-center w-100">
                    <Link href="/admin/dashboard" class="me-auto">
                        <img 
                            src="../Pages/Public/Images/eboarmatelogo.png" 
                            alt="E-BoardMate Logo" 
                            class="img-fluid"
                            style="height: 50px; object-fit: contain;"
                        />
                    </Link>
                    <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </header>

            <!-- Main Page Content Slots Into Here -->
            <main class="p-3 p-md-4 w-100" style="overflow-x: hidden;">
                <slot />
            </main>
        </div>

        <!-- ==========================================
             MOBILE OFFCANVAS SIDEBAR
        =========================================== -->
        <div class="offcanvas offcanvas-start d-md-none bg-body-tertiary transition-all" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom border-secondary-subtle">
                <img 
                    src="../Pages/Public/Images/eboarmatelogo.png" 
                    alt="E-BoardMate Logo" 
                    style="height: 45px; object-fit: contain;"
                />
                <!-- Bootstrap 5.3 handles the close button color automatically in dark mode -->
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            
            <div class="offcanvas-body d-flex flex-column">
                <div class="text-body-secondary small fw-bold text-uppercase mb-3 px-2">
                    Super Admin Panel
                </div>
                
                <nav class="nav flex-column gap-2 mb-auto" aria-label="Mobile Sidebar Navigation">
                    <a 
                        v-for="link in navLinks" 
                        :key="link.name" 
                        href="#"
                        @click.prevent="mobileNavigate(link.url)"
                        class="nav-link rounded px-3 py-2 fw-semibold transition-all"
                        :class="$page.url === link.url ? 'bg-success text-white' : 'text-body hover-bg-adaptive'"
                    >
                        {{ link.name }}
                    </a>
                </nav>
                
                <hr class="border-secondary-subtle">

                <div class="d-flex justify-content-start px-2 mb-3">
                    <ThemeToggle />
                </div>
                
                <button
                    type="button"
                    class="btn btn-outline-danger w-100 fw-bold mb-3"
                    :disabled="logoutForm.processing"
                    @click="mobileLogout"
                >
                    Logout
                </button>
            </div>
        </div>

    </div>
</template>

<style scoped>
@media (min-width: 768px) {
    .flex-grow-1 {
        margin-left: 260px !important;
    }
}

/* 
  Theme-Adaptive Hover Effect:
  Uses Bootstrap's RGB emphasis variable. This creates a subtle dark grey hover 
  in light mode, and a subtle soft white hover in dark mode. 
*/
.hover-bg-adaptive {
    transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
}
.hover-bg-adaptive:hover {
    background-color: rgba(var(--bs-emphasis-color-rgb), 0.08);
}

.transition-all {
    transition: all 0.3s ease-in-out;
}
</style>