<script setup>
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link, useForm, router } from '@inertiajs/vue3';

const logoutForm = useForm({});

const logout = () => {
    logoutForm.post('/owner/logout');
};

const navLinks = [
    { name: 'Dashboard', url: '/owner/dashboard' },
    { name: 'Reservations', url: '/owner/reservations' },
    { name: 'My Listing', url: '/owner/listing' },
    { name: 'Settings', url: '/owner/settings' },
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
    <div class="d-flex min-vh-100 bg-body-tertiary">
        
        <!-- DESKTOP SIDEBAR -->
        <!-- Added z-index to prevent overlapping issues -->
        <aside class="bg-body border-end border-secondary d-none d-md-flex flex-column p-3 shadow-sm" style="width: 260px; position: fixed; height: 100vh; overflow-y: auto; z-index: 1030;">
            
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
            
            <div class="text-muted small fw-bold text-uppercase mb-4 px-2 tracking-tight">
                Owner Portal
            </div>

            <nav class="nav flex-column gap-2 mb-auto" aria-label="Desktop Sidebar Navigation">
                <Link 
                    v-for="link in navLinks" 
                    :key="link.name" 
                    :href="link.url" 
                    class="nav-link rounded px-3 py-2 fw-semibold transition-all"
                    :class="$page.url === link.url ? 'bg-success text-white' : 'text-body hover-bg-nav'"
                >
                    {{ link.name }}
                </Link>
            </nav>

            <!-- Theme Toggle -->
            <div class="px-2 mt-4 mb-3">
                <ThemeToggle />
            </div>
            
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
        </aside>

        <!-- MAIN CONTENT AREA -->
        <!-- 🚀 FIX: Removed the broken inline media query and replaced it with the 'main-content' class -->
        <div class="main-content flex-grow-1 d-flex flex-column w-100">
            
            <!-- MOBILE HEADER -->
            <header class="navbar bg-body border-bottom border-secondary d-md-none px-3 py-2 shadow-sm sticky-top" style="z-index: 1020;">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <Link href="/owner/dashboard">
                        <img 
                            src="../Pages/Public/Images/eboarmatelogo.png" 
                            alt="E-BoardMate Logo" 
                            class="img-fluid"
                            style="height: 50px; object-fit: contain;"
                        />
                    </Link>
                    
                    <button class="navbar-toggler border-0 px-2 shadow-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </div>
            </header>

            <!-- 🚀 FIX: Kept overflow-x: hidden to contain wide tables on mobile screens -->
            <main class="p-3 p-md-4 w-100" style="overflow-x: hidden;">
                <slot />
            </main>
        </div>

        <!-- MOBILE OFFCANVAS SIDEBAR -->
        <div class="offcanvas offcanvas-start d-md-none text-body bg-body border-end-0" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom border-secondary">
                <img 
                    src="../Pages/Public/Images/eboarmatelogo.png" 
                    alt="E-BoardMate Logo" 
                    style="height: 45px; object-fit: contain;"
                />
                <button type="button" class="btn-close text-reset shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            
            <div class="offcanvas-body d-flex flex-column">
                <div class="text-muted small fw-bold text-uppercase mb-3 px-2">
                    Owner Portal
                </div>
                
                <nav class="nav flex-column gap-2 mb-auto" aria-label="Mobile Sidebar Navigation">
                    <a 
                        v-for="link in navLinks" 
                        :key="link.name" 
                        href="#"
                        @click.prevent="mobileNavigate(link.url)"
                        class="nav-link rounded px-3 py-2 fw-semibold transition-all"
                        :class="$page.url === link.url ? 'bg-success text-white' : 'text-body hover-bg-nav'"
                    >
                        {{ link.name }}
                    </a>
                </nav>
                
                <div class="mb-3 px-1 mt-4">
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
/* 🚀 FIX: Proper responsive margin logic for the main content area */
.main-content {
    margin-left: 0;
}

@media (min-width: 768px) {
    .main-content {
        margin-left: 260px; /* Exact width of the desktop sidebar */
    }
}

.hover-bg-nav:hover {
    background-color: var(--bs-secondary-bg);
    transition: background-color 0.2s ease-in-out;
}

.transition-all {
    transition: all 0.2s ease-in-out;
}
</style>