<script setup>
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="vh-100 d-flex flex-column bg-body snap-container transition-all">
        
        <nav class="navbar bg-body border-bottom border-secondary-subtle sticky-top shadow-sm snap-section transition-all py-0">
            <div class="container-fluid px-3 py-0 d-flex flex-nowrap align-items-center w-100 m-0">
                
                <!-- Left Side: Logo Wrapper (Caged to 80% width max) -->
                <!-- overflow-hidden ensures the invisible image padding is ignored -->
                <div class="d-flex align-items-center justify-content-start overflow-hidden" style="width: 80%;">
                    <Link href="/" class="navbar-brand m-0 p-0" title="E-BoardMate Home">
                        <img 
                            src="../Pages/Public/Images/eboarmatelogo.png" 
                            alt="E-BoardMate - Verified Boarding House Locator Logo" 
                            class="d-inline-block align-top navbar-logo"
                            style="height: 95px; margin-top: -16px; margin-bottom: -16px; max-width: 100%; object-fit: contain; object-position: left; image-rendering: -webkit-optimize-contrast;"
                        />
                    </Link> 
                </div>

                <!-- Right Side: Theme Toggle Wrapper (Locked to 20% width) -->
                <!-- justify-content-end forces it flush against the right edge -->
                <div class="d-flex align-items-center justify-content-end ms-auto" style="width: 20%;">
                    <ThemeToggle />
                </div>
                
            </div>
        </nav>

        <main class="flex-grow-1">
            <slot />
        </main>

        <footer class="bg-body border-top border-secondary-subtle pt-5 pb-4 mt-auto snap-section transition-all">
            <div class="container">
                <div class="row gy-4 mb-4 justify-content-between">
                 
                    <div class="col-lg-6 col-md-12">
                        <div class="mb-3">
                            <img 
                                src="../Pages/Public/Images/eboarmatelogo.png" 
                                alt="E-BoardMate Logo" 
                                class="img-fluid footer-logo"
                                style="height: 75px; object-fit: contain; image-rendering: -webkit-optimize-contrast;"
                            />
                        </div>
                        <p class="text-body-secondary small pe-lg-5 mb-0 transition-all">
                            A Web-Based Boarding House Locator and Reservations System for Talibon Polytechnic College. Find verified boarding houses faster, easier, and safer.
                        </p>
                    </div>

                    <div class="col-lg-4 col-md-6">
                        <h6 class="fw-bold mb-3 text-body-emphasis transition-all">Quick Links</h6>
                        <ul class="list-unstyled d-flex flex-column gap-2 mb-0 small">
                            <li>
                                <Link href="/" class="text-decoration-none text-body-secondary text-hover-primary fw-medium transition-all">Home</Link>
                            </li>
                            <li>
                                <Link href="/map" class="text-decoration-none text-body-secondary text-hover-primary fw-medium transition-all">View Map</Link>
                            </li>
                            <li>
                                <Link href="/track-reservation" class="text-decoration-none text-body-secondary text-hover-primary fw-medium transition-all">Track Reservation</Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <hr class="text-secondary opacity-25">

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-body-secondary pt-2 transition-all">
                    <span>&copy; 2026 E-BoardMate. All rights reserved.</span>
                    <span>Talibon Polytechnic College Capstone Project</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@media (max-width: 576px) {
    
    .navbar { 
        min-height: 70px;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .navbar-logo {
        height: 65px !important;
        /* I have removed the negative margins here so it stops shrinking the parent container */
        margin-top: 0 !important; 
        margin-bottom: 0 !important;
    }
    
    .footer-logo {
        height: 55px !important;
    }
}

/* Smooth fade transitions for colors when toggling dark mode */
.transition-all {
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, border-color 0.3s ease-in-out;
}

/* Ensures custom hover state works well with dark/light dynamic text */
.text-hover-primary:hover {
    color: var(--bs-primary) !important;
}
</style>