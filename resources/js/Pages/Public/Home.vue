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
        <section class="hero-section snap-section d-flex align-items-center">
            <div class="container py-5">
                <div class="row align-items-center justify-content-between g-4">
                    <div class="col-lg-7">
                        <span class="badge rounded-pill badge-soft-green mb-3">
                            Verified boarding houses near TPC
                        </span>

                        <h1 class="display-5 fw-bold mb-3 text-ebm-text">
                            Find trusted boarding houses near Talibon Polytechnic College faster, easier, and safer.
                        </h1>

                        <p class="lead ebm-muted mb-4">
                            E-BoardMate helps students view verified boarding houses, check details, submit reservations, and track reservation status online without creating a student account.
                        </p>

                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <Link href="/map" class="btn btn-ebm-primary btn-lg px-4">
                                View Map
                            </Link>

                            <Link href="/track-reservation" class="btn btn-ebm-outline btn-lg px-4">
                                Track Reservation
                            </Link>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="home-map-only-card">
                            <div ref="homeMapContainer" class="home-map-only-preview" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Snap Section: About -->
        <section class="snap-section d-flex align-items-center py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="card border-0 rounded-4 shadow-sm bg-soft-green">
                            <div class="card-body p-4 p-md-5 text-center">
                                <span class="badge rounded-pill badge-soft-green border-ebm-primary mb-3 px-3 py-2">
                                    About E-BoardMate
                                </span>

                                <h2 class="fw-bold mb-4 text-ebm-text">
                                    Built to help TPC students find boarding houses more easily.
                                </h2>

                                <p class="lead mb-0 ebm-muted">
                                    E-BoardMate is designed to help students of Talibon Polytechnic College find verified boarding houses near the school. It provides a simple map-based way to view available boarding houses, check important details, and send reservation requests without needing a student account.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>