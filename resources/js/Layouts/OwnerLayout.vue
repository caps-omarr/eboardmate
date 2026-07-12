<script setup>
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
    <div class="d-flex min-vh-100 bg-ebm">
        
       
        <aside class="bg-white border-end d-none d-md-flex flex-column p-3 shadow-sm" style="width: 260px; position: fixed; height: 100vh; overflow-y: auto;">
            
          
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
                    :class="$page.url === link.url ? 'bg-success text-white' : 'text-dark hover-bg-light'"
                >
                    {{ link.name }}
                </Link>
            </nav>

            <hr>
            
           
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

      
        <div class="flex-grow-1 d-flex flex-column w-100" style="margin-left: 0;" :style="{'@media (min-width: 768px)': 'margin-left: 260px !important'}">
            
    
            <header class="navbar navbar-light bg-white border-bottom d-md-none px-3 py-2 shadow-sm sticky-top">
                <div class="d-flex align-items-center w-100">
                    <Link href="/owner/dashboard" class="me-auto">
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

          
            <main class="p-3 p-md-4 w-100" style="overflow-x: hidden;">
                <slot />
            </main>
        </div>

      
        <div class="offcanvas offcanvas-start d-md-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom">
                <img 
                    src="../Pages/Public/Images/eboarmatelogo.png" 
                    alt="E-BoardMate Logo" 
                    style="height: 45px; object-fit: contain;"
                />
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
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
                        class="nav-link rounded px-3 py-2 fw-semibold"
                        :class="$page.url === link.url ? 'bg-success text-white' : 'text-dark'"
                    >
                        {{ link.name }}
                    </a>
                </nav>
                
                <hr>
                
                <!-- Mobile Logout Button -->
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

.hover-bg-light:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s ease-in-out;
}

.transition-all {
    transition: all 0.2s ease-in-out;
}
</style>