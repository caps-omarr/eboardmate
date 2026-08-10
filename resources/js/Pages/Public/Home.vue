<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const homeMapContainer = ref(null);
const homeMap = ref(null);
const homeMarkers = ref([]);

const mapboxToken = import.meta.env.VITE_MAPBOX_TOKEN || '';
const centerLat = Number(import.meta.env.VITE_MAP_CENTER_LAT || 10.1167);
const centerLng = Number(import.meta.env.VITE_MAP_CENTER_LNG || 124.2833);

const hasMapboxToken = () => {
    return mapboxToken && mapboxToken !== 'your_public_mapbox_token_here';
};

const schoolIconSvg = `
    <svg class="home-map-icon" viewBox="0 0 24 24" aria-hidden="true" width="32" height="32">
        <path d="M3 21V9.8L12 4L21 9.8V21H3Z" fill="#198754"/>
        <path d="M8 21V12H16V21" fill="white" opacity="0.95"/>
        <path d="M10 9.5H14V12.5H10V9.5Z" fill="white" opacity="0.95"/>
    </svg>
`;

const houseIconSvg = `
    <svg class="home-map-icon" viewBox="0 0 24 24" aria-hidden="true" width="28" height="28">
        <path d="M4 11.5L12 5L20 11.5V20H5.5C4.7 20 4 19.3 4 18.5V11.5Z" fill="#0d6efd"/>
        <path d="M9.5 20V14H14.5V20" fill="white" opacity="0.95"/>
        <path d="M3 12.2L12 4.8L21 12.2" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
`;

const createPreviewMarker = (type) => {
    const element = document.createElement('div');
    element.className = type === 'tpc'
        ? 'home-map-icon-marker home-map-icon-marker-school'
        : 'home-map-icon-marker home-map-icon-marker-house';
    element.innerHTML = type === 'tpc' ? schoolIconSvg : houseIconSvg;
    return element;
};

const addPreviewMarkers = () => {
    const markerPoints = [
        { type: 'tpc', longitude: centerLng, latitude: centerLat },
        { type: 'bh', longitude: centerLng + 0.0022, latitude: centerLat + 0.0014 },
        { type: 'bh', longitude: centerLng - 0.002, latitude: centerLat - 0.0012 },
        { type: 'bh', longitude: centerLng + 0.001, latitude: centerLat - 0.0021 },
    ];

    markerPoints.forEach((point) => {
        const marker = new mapboxgl.Marker({
            element: createPreviewMarker(point.type),
            anchor: 'bottom',
            offset: [0, 0],
        })
            .setLngLat([point.longitude, point.latitude])
            .addTo(homeMap.value);
        homeMarkers.value.push(marker);
    });
};

const disableMapInteractions = () => {
    homeMap.value.scrollZoom.disable();
    homeMap.value.boxZoom.disable();
    homeMap.value.dragRotate.disable();
    homeMap.value.dragPan.disable();
    homeMap.value.keyboard.disable();
    homeMap.value.doubleClickZoom.disable();
    if (homeMap.value.touchZoomRotate) {
        homeMap.value.touchZoomRotate.disable();
    }
};

const initializeHomePreviewMap = async () => {
    if (!hasMapboxToken()) return;
    await nextTick();
    if (!homeMapContainer.value) return;

    mapboxgl.accessToken = mapboxToken;
    homeMap.value = new mapboxgl.Map({
        container: homeMapContainer.value,
        style: 'mapbox://styles/mapbox/satellite-streets-v12',
        center: [centerLng, centerLat],
        zoom: 15.6,
        minZoom: 15.6,
        maxZoom: 15.6,
        bearing: 0,
        pitch: 0,
        interactive: false,
        attributionControl: false,
    });

    disableMapInteractions();
    homeMap.value.on('load', () => {
        homeMap.value.resize();
        addPreviewMarkers();
    });
};

onMounted(() => {
    initializeHomePreviewMap();
});

onBeforeUnmount(() => {
    homeMarkers.value.forEach((marker) => marker.remove());
    homeMarkers.value = [];
    if (homeMap.value) homeMap.value.remove();
});
</script>

<template>
    <PublicLayout>
        <Head title="E-BoardMate | Boarding House Locator for Talibon Polytechnic College">
            <meta name="description" content="Find verified boarding houses near Talibon Polytechnic College and track your reservation online with E-BoardMate.">
        </Head>

        <!-- 🚀 CONTAINED HERO SECTION -->
        <section class="hero-section bg-body transition-all d-flex align-items-center min-vh-100">
            <div class="container py-5 mt-4 mt-lg-0">
                <div class="row align-items-center g-5">
                    
                    <!-- Left Column: Content & Badges -->
                    <div class="col-lg-6 col-xl-6 z-1">
                        <span class="badge rounded-pill border border-success-subtle bg-body text-success shadow-sm mb-3 px-3 py-2 transition-all">
                            Verified boarding houses near TPC
                        </span>

                        <h1 class="display-5 fw-bold mb-3 text-body-emphasis tracking-tight transition-all">
                            Find trusted boarding houses near Talibon Polytechnic College faster, easier, and safer.
                        </h1>

                        <p class="lead text-body-secondary mb-4 pe-lg-4 transition-all">
                            E-BoardMate helps students view verified boarding houses, check details, submit reservations, and track reservation status online without creating a student account.
                        </p>

                        <div class="d-flex flex-column flex-sm-row gap-3 mb-5">
                            <Link href="boarding-houses" class="btn btn-ebm-primary btn-lg px-4 fw-medium shadow">
                               View Boarding Houses
                            </Link>
                            <Link href="/track-reservation" class="btn bg-body border-secondary-subtle text-body-emphasis btn-lg px-4 fw-medium shadow-sm transition-all hover-bg-tertiary">
                                Track Reservation
                            </Link>
                        </div>

                        <!-- MVP Trust Badges -->
                        <div class="d-flex flex-wrap gap-4 trust-badges pt-4 border-top border-secondary-subtle transition-all">
                            <div class="trust-badge-item d-flex align-items-center gap-2">
                                <span class="fs-5 text-danger">❤️</span>
                                <div>
                                    <span class="d-block fw-bold small text-body-emphasis lh-1 transition-all">Verified & Safe</span>
                                    <span class="d-block small text-body-secondary text-nowrap transition-all" style="font-size: 0.75rem;">Trusted owners</span>
                                </div>
                            </div>
                            <div class="trust-badge-item d-flex align-items-center gap-2">
                                <span class="fs-5 text-warning">🏷️</span>
                                <div>
                                    <span class="d-block fw-bold small text-body-emphasis lh-1 transition-all">Affordable Rates</span>
                                    <span class="d-block small text-body-secondary text-nowrap transition-all" style="font-size: 0.75rem;">Student-friendly</span>
                                </div>
                            </div>
                            <div class="trust-badge-item d-flex align-items-center gap-2">
                                <span class="fs-5 text-success">🤝</span>
                                <div>
                                    <span class="d-block fw-bold small text-body-emphasis lh-1 transition-all">Community</span>
                                    <span class="d-block small text-body-secondary text-nowrap transition-all" style="font-size: 0.75rem;">Support local</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: The Faded Map -->
                    <div class="col-lg-6 col-xl-6">
                        <div class="hero-map-wrapper">
                            <div ref="homeMapContainer" class="w-100 h-100"></div>
                        </div>
                    </div>

                </div>
            </div>
        </section>

        <!-- Snap Section: About & How It Works -->
        <section class="snap-section-tertiary bg-body-tertiary d-flex align-items-center py-5 min-vh-100 transition-all">
            <div class="container py-5">
                
                <!-- Expanded About Story -->
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <span class="badge rounded-pill border border-success-subtle bg-body text-success mb-3 px-3 py-2 transition-all">
                            About E-BoardMate
                        </span>

                        <h2 class="display-6 fw-bold mb-4 text-body-emphasis tracking-tight transition-all">
                            Built exclusively for TPC students.
                        </h2>

                        <p class="lead text-body-secondary mb-0 transition-all">
                            Finding a safe and affordable place to stay shouldn't be a hassle. E-BoardMate bridges the gap between Talibon Polytechnic College students and verified local landlords. We provide a seamless, map-based platform where you can secure your bedspace online—<strong>completely free and with zero account registration required.</strong>
                        </p>
                    </div>
                </div>

                <!-- 4-Step User Manual Grid -->
                <div class="row g-4 mt-2">
                    
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto transition-all">🗺️</div>
                                <h3 class="h5 fw-bold mb-3 text-body-emphasis transition-all">1. Explore the Map</h3>
                                <p class="small text-body-secondary mb-0 transition-all">Browse the interactive map to find verified boarding houses near the TPC campus. View real-time availability, photos, and monthly rent prices.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto transition-all">📝</div>
                                <h3 class="h5 fw-bold mb-3 text-body-emphasis transition-all">2. Submit a Request</h3>
                                <p class="small text-body-secondary mb-0 transition-all">Found the perfect spot? Fill out a quick guest reservation form. All you need is your name and email. No student account or password required!</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto transition-all">⏳</div>
                                <h3 class="h5 fw-bold mb-3 text-body-emphasis transition-all">3. Wait for Review</h3>
                                <p class="small text-body-secondary mb-0 transition-all">The boarding house landlord will receive your request immediately. Please wait up to 24 hours for them to Approve or Reject based on slot availability.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto transition-all">🔍</div>
                                <h3 class="h5 fw-bold mb-3 text-body-emphasis transition-all">4. Track Your Status</h3>
                                <p class="small text-body-secondary mb-0 transition-all">After submitting, you will be given a unique <strong>EBM</strong> tracking code. Use it anytime on our tracking page to see live updates on your reservation.</p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
/* 🪄 THE MAGIC FADE MASK */
.hero-map-wrapper {
    position: relative;
    height: 400px;
    width: 100%;
    border-radius: 20px;
    overflow: hidden;
    
    /* Mobile: Fades the top edge so it blends below the text */
    -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 15%, black 100%);
    mask-image: linear-gradient(to bottom, transparent 0%, black 15%, black 100%);
}

@media (min-width: 992px) {
    .hero-map-wrapper {
        height: 550px;
        /* Desktop: Fades the left edge so it perfectly blends next to the text */
        -webkit-mask-image: linear-gradient(to right, transparent 0%, black 20%, black 100%);
        mask-image: linear-gradient(to right, transparent 0%, black 20%, black 100%);
    }
}

/* --- INTERACTIVE STYLES --- */
.hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-lift:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important;
}

.hover-bg-tertiary:hover {
    background-color: var(--bs-tertiary-bg) !important;
}

.step-icon {
    width: 70px;
    height: 70px;
    background-color: rgba(25, 135, 84, 0.15); /* Transparent green adapts to both modes */
    color: #198754;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    transition: transform 0.3s ease, background-color 0.3s ease;
}

.hover-lift:hover .step-icon {
    transform: scale(1.1);
}

.tracking-tight {
    letter-spacing: -0.5px;
}

/* Smooth fade transitions for colors when toggling dark mode */
.transition-all {
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, border-color 0.3s ease-in-out;
}
</style>

<!-- 🚀 THE FIX: UNSCOPED BRUTE-FORCE DARK MODE OVERRIDES -->
<!-- Placed outside of <style scoped> to bypass Vue encapsulation and completely crush global CSS interference -->
<style>
html[data-bs-theme="dark"] .hero-section,
body[data-bs-theme="dark"] .hero-section,
[data-bs-theme="dark"] .hero-section {
    background-color: #212529 !important;
}

html[data-bs-theme="dark"] .snap-section-tertiary,
body[data-bs-theme="dark"] .snap-section-tertiary,
[data-bs-theme="dark"] .snap-section-tertiary {
    background-color: #2b3035 !important;
}
</style>