<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    
    <div class="vh-100 d-flex flex-column bg-ebm snap-container">
        
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shadow-sm snap-section">
            <div class="container py-0">
                
                <Link href="/" class="navbar-brand d-flex align-items-center" title="E-BoardMate Home">
                    <img 
                        src="../Pages/Public/Images/eboarmatelogo.png" 
                        alt="E-BoardMate - Verified Boarding House Locator Logo" 
                        class="d-inline-block align-top img-fluid navbar-logo"
                        style="height: 95px; margin-top: -16px; margin-bottom: -16px; object-fit: contain; image-rendering: -webkit-optimize-contrast;"
                    />
                </Link>
            </div>
        </nav>

        
        <main class="flex-grow-1">
            <slot />
        </main>

       
        <footer class="bg-white border-top pt-5 pb-4 mt-auto snap-section">
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
                        <p class="ebm-muted small pe-lg-5 mb-0">
                            A Web-Based Boarding House Locator and Reservations System for Talibon Polytechnic College. Find verified boarding houses faster, easier, and safer.
                        </p>
                    </div>

                   
                    <div class="col-lg-4 col-md-6">
                        <h6 class="fw-bold mb-3 text-ebm-text">Quick Links</h6>
                        <ul class="list-unstyled d-flex flex-column gap-2 mb-0 small">
                            <li>
                                <Link href="/" class="text-decoration-none ebm-muted text-hover-primary fw-medium">Home</Link>
                            </li>
                            <li>
                                <Link href="/map" class="text-decoration-none ebm-muted text-hover-primary fw-medium">View Map</Link>
                            </li>
                            <li>
                                <Link href="/track-reservation" class="text-decoration-none ebm-muted text-hover-primary fw-medium">Track Reservation</Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <hr class="text-muted opacity-25">

                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small ebm-muted pt-2">
                    <span>&copy; 2026 E-BoardMate. All rights reserved.</span>
                    <span>Talibon Polytechnic College Capstone Project</span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
@media (max-width: 576px) {
    .navbar-logo {
        height: 65px !important;
        margin-top: -10px !important;
        margin-bottom: -10px !important;
    }
    
    .footer-logo {
        height: 55px !important;
    }
}
</style>