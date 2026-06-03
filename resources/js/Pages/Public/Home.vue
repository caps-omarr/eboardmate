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
    <svg class="home-map-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M3 21V9.8L12 4L21 9.8V21H3Z" fill="currentColor"/>
        <path d="M8 21V12H16V21" fill="white" opacity="0.95"/>
        <path d="M10 9.5H14V12.5H10V9.5Z" fill="white" opacity="0.95"/>
    </svg>
`;

const houseIconSvg = `
    <svg class="home-map-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path d="M4 11.5L12 5L20 11.5V20H5.5C4.7 20 4 19.3 4 18.5V11.5Z" fill="currentColor"/>
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

        <!-- Snap Section: Hero -->
        <section class="hero-section snap-section d-flex align-items-center min-vh-100">
            <div class="container py-5">
                <div class="row align-items-center justify-content-between g-4">
                    <div class="col-lg-7">
                        <span class="badge rounded-pill badge-soft-green mb-3 px-3 py-2">
                            Verified boarding houses near TPC
                        </span>

                        <h1 class="display-5 fw-bold mb-3 text-ebm-text">
                            Find trusted boarding houses near Talibon Polytechnic College faster, easier, and safer.
                        </h1>

                        <p class="lead ebm-muted mb-4">
                            E-BoardMate helps students view verified boarding houses, check details, submit reservations, and track reservation status online without creating a student account.
                        </p>

                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <Link href="/map" class="btn btn-ebm-primary btn-lg px-4 fw-medium">
                                View Map
                            </Link>

                            <Link href="/track-reservation" class="btn btn-ebm-outline btn-lg px-4 fw-medium">
                                Track Reservation
                            </Link>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="home-map-only-card shadow-lg rounded-4 overflow-hidden">
                            <div ref="homeMapContainer" class="home-map-only-preview" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Snap Section: About & How It Works (Full Height) -->
        <section class="snap-section d-flex align-items-center py-5 min-vh-100" style="background-color: #f8fcf9;">
            <div class="container py-5">
                
                <!-- Expanded About Story -->
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <span class="badge rounded-pill badge-soft-green border-ebm-primary mb-3 px-3 py-2">
                            About E-BoardMate
                        </span>

                        <h2 class="display-6 fw-bold mb-4 text-ebm-text tracking-tight">
                            Built exclusively for TPC students.
                        </h2>

                        <p class="lead ebm-muted mb-0">
                            Finding a safe and affordable place to stay shouldn't be a hassle. E-BoardMate bridges the gap between Talibon Polytechnic College students and verified local landlords. We provide a seamless, map-based platform where you can secure your bedspace online—<strong>completely free and with zero account registration required.</strong>
                        </p>
                    </div>
                </div>

                <!-- 4-Step User Manual Grid -->
                <div class="row g-4 mt-2">
                    
                    <!-- Step 1 -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift ebm-card">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto">
                                    🗺️
                                </div>
                                <h3 class="h5 fw-bold mb-3">1. Explore the Map</h3>
                                <p class="small ebm-muted mb-0">
                                    Browse the interactive map to find verified boarding houses near the TPC campus. View real-time availability, photos, and monthly rent prices.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift ebm-card">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto">
                                    📝
                                </div>
                                <h3 class="h5 fw-bold mb-3">2. Submit a Request</h3>
                                <p class="small ebm-muted mb-0">
                                    Found the perfect spot? Fill out a quick guest reservation form. All you need is your name and email. No student account or password required!
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift ebm-card">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto">
                                    ⏳
                                </div>
                                <h3 class="h5 fw-bold mb-3">3. Wait for Review</h3>
                                <p class="small ebm-muted mb-0">
                                    The boarding house landlord will receive your request immediately. Please wait up to 24 hours for them to Approve or Reject based on slot availability.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4 hover-lift ebm-card">
                            <div class="card-body p-4 text-center">
                                <div class="step-icon mb-4 mx-auto">
                                    🔍
                                </div>
                                <h3 class="h5 fw-bold mb-3">4. Track Your Status</h3>
                                <p class="small ebm-muted mb-0">
                                    After submitting, you will be given a unique <strong>EBM</strong> tracking code. Use it anytime on our tracking page to see live updates on your reservation.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-lift:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.08) !important;
}
.step-icon {
    width: 70px;
    height: 70px;
    background-color: #eaf5ee; /* Soft green matching your theme */
    color: #198754;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    transition: transform 0.3s ease;
}
.hover-lift:hover .step-icon {
    transform: scale(1.1);
}
.tracking-tight {
    letter-spacing: -0.5px;
}
</style>