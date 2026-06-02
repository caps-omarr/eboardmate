<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <!-- Snap Container Wrapper -->
    <div class="vh-100 d-flex flex-column bg-ebm snap-container">
        <!-- Snap Section: Navbar -->
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top shadow-sm snap-section">
            <div class="container py-2">
                <Link href="/" class="navbar-brand d-flex align-items-center gap-2 fw-bold text-ebm-primary">
                    <span class="brand-mark">E</span>
                    <span>E-BoardMate</span>
                </Link>
            </div>
        </nav>

        <main class="flex-grow-1">
            <slot />
        </main>

        <!-- Snap Section: Footer -->
        <footer class="bg-white border-top pt-5 pb-4 mt-auto snap-section">
            <div class="container">
                <div class="row gy-4 mb-4 justify-content-between">
                    <!-- Column 1: Brand & Description -->
                    <div class="col-lg-6 col-md-12">
                        <div class="d-flex align-items-center gap-2 fw-bold text-ebm-primary mb-3">
                            <span class="brand-mark">E</span>
                            <span class="fs-5">E-BoardMate</span>
                        </div>
                        <p class="ebm-muted small pe-lg-5 mb-0">
                            A Web-Based Boarding House Locator and Reservations System for Talibon Polytechnic College. Find verified boarding houses faster, easier, and safer.
                        </p>
                    </div>

                    <!-- Column 2: Quick Links (No System Portals) -->
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

                <!-- Copyright Row -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small ebm-muted pt-2">
                    <span>&copy; 2026 E-BoardMate. All rights reserved.</span>
                    <span>Talibon Polytechnic College Capstone Project</span>
                </div>
            </div>
        </footer>
    </div>
</template>