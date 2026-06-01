<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-vh-100 d-flex flex-column bg-ebm">
        <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
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

        <footer class="bg-white border-top py-4">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                    <p class="mb-0 fw-semibold">
                        E-BoardMate
                    </p>

                    <p class="mb-0 ebm-muted small">
                        A Web-Based Boarding House Locator and Reservations System for Talibon Polytechnic College
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>