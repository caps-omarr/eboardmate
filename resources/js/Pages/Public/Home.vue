<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import mapboxgl from "mapbox-gl";
import "mapbox-gl/dist/mapbox-gl.css";
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from "vue";

const page = usePage();

const homeMapContainer = ref(null);
const homeMap = ref(null);
const homeMarkers = ref([]);

// 🚀 RUNTIME DYNAMIC MAPBOX TOKEN RESOLUTION (Inertia Shared Prop -> env fallback)
const mapboxToken = computed(() => {
    const token = page.props.mapbox_token || import.meta.env.VITE_MAPBOX_TOKEN || "";
    if (!token || token === "your_public_mapbox_token_here") {
        console.warn("⚠️ Mapbox Warning: No valid Mapbox token provided in Inertia shared props or environment variables.");
    }
    return token;
});

const centerLat = Number(import.meta.env.VITE_MAP_CENTER_LAT || 10.1167);
const centerLng = Number(import.meta.env.VITE_MAP_CENTER_LNG || 124.2833);

const hasMapboxToken = () => {
    return Boolean(mapboxToken.value && mapboxToken.value !== "your_public_mapbox_token_here");
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
    const element = document.createElement("div");
    element.className =
        type === "tpc"
            ? "home-map-icon-marker home-map-icon-marker-school"
            : "home-map-icon-marker home-map-icon-marker-house";
    element.innerHTML = type === "tpc" ? schoolIconSvg : houseIconSvg;
    return element;
};

const addPreviewMarkers = () => {
    const markerPoints = [
        { type: "tpc", longitude: centerLng, latitude: centerLat },
        {
            type: "bh",
            longitude: centerLng + 0.0022,
            latitude: centerLat + 0.0014,
        },
        {
            type: "bh",
            longitude: centerLng - 0.002,
            latitude: centerLat - 0.0012,
        },
        {
            type: "bh",
            longitude: centerLng + 0.001,
            latitude: centerLat - 0.0021,
        },
    ];

    markerPoints.forEach((point) => {
        const marker = new mapboxgl.Marker({
            element: createPreviewMarker(point.type),
            anchor: "bottom",
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

const handleResize = () => {
    if (typeof window === "undefined") return;
    if (window.innerWidth >= 992) {
        if (!homeMap.value) {
            initializeHomePreviewMap();
        }
    } else {
        if (homeMap.value) {
            homeMarkers.value.forEach((marker) => marker.remove());
            homeMarkers.value = [];
            homeMap.value.remove();
            homeMap.value = null;
        }
    }
};

const initializeHomePreviewMap = async () => {
    if (typeof window === "undefined" || window.innerWidth < 992) return;
    if (!hasMapboxToken()) return;
    await nextTick();
    if (!homeMapContainer.value) return;

    mapboxgl.accessToken = mapboxToken.value;
    homeMap.value = new mapboxgl.Map({
        container: homeMapContainer.value,
        style: "mapbox://styles/mapbox/satellite-streets-v12",
        center: [centerLng, centerLat],
        zoom: 15.6,
        minZoom: 11,
        maxZoom: 18,
        maxBounds: [
            [124.2800, 10.0800],
            [124.3500, 10.1600]
        ],
        bearing: 0,
        pitch: 0,
        interactive: false,
        attributionControl: false,
    });

    disableMapInteractions();
    homeMap.value.on("load", () => {
        if (homeMap.value) {
            homeMap.value.resize();
            addPreviewMarkers();
        }
    });
};

onMounted(() => {
    if (typeof window !== "undefined" && window.innerWidth >= 992) {
        initializeHomePreviewMap();
    }
    if (typeof window !== "undefined") {
        window.addEventListener("resize", handleResize);
    }
});

onBeforeUnmount(() => {
    if (typeof window !== "undefined") {
        window.removeEventListener("resize", handleResize);
    }
    homeMarkers.value.forEach((marker) => marker.remove());
    homeMarkers.value = [];
    if (homeMap.value) homeMap.value.remove();
});
</script>

<template>
    <PublicLayout>
        <Head
            title="E-BoardMate | Boarding House Locator for Talibon Polytechnic College"
        >
            <meta
                name="description"
                content="Find verified boarding houses near Talibon Polytechnic College and track your reservation online with E-BoardMate."
            />
        </Head>

        <!-- 🚀 RESPONSIVE HERO SECTION -->
        <section
            class="hero-section hero-layout position-relative overflow-hidden bg-body"
        >
            <!-- Layer 0: The Full Screen Map (Desktop Only) -->
            <div
                class="hero-map-wrapper position-absolute top-0 start-0 w-100 h-100 d-none d-lg-block"
                style="z-index: 0"
            >
                <div ref="homeMapContainer" class="w-100 h-100"></div>
            </div>

            <!-- Layer 1: Directional Theme Overlay (Desktop Only) -->
            <div
                class="hero-map-overlay position-absolute top-0 start-0 w-100 h-100 transition-all d-none d-lg-block"
                style="z-index: 1"
            ></div>

            <!-- Layer 2: The Hero Content -->
            <div
                class="container hero-content-container position-relative text-start"
                style="z-index: 2"
            >
                <div class="row">
                    <div class="col-lg-8 col-xl-6">
                        <p class="text-uppercase tracking-wider small fw-semibold text-success mb-2">
                            Talibon Polytechnic College Housing Locator
                        </p>
                        <h1
                            class="display-5 fw-bold mb-4 text-body-emphasis tracking-tight transition-all"
                        >
                            Verified student accommodations near campus.
                        </h1>

                        <p
                            class="lead text-body-secondary mb-4 transition-all pe-lg-4"
                        >
                            Browse verified boarding houses, inspect real walking distances, submit reservation requests, and check live status online—with zero account registration required.
                        </p>

                        <!-- High-Affordance Hero CTAs -->
                        <div class="d-grid d-sm-flex gap-3 mb-5">
                            <Link
                                href="/boarding-houses"
                                class="btn btn-ebm-primary btn-lg px-4 fw-bold shadow-sm transition-all rounded-3 d-inline-flex align-items-center justify-content-center gap-2"
                                style="min-height: 48px;"
                            >
                                <i class="bi bi-search"></i>
                                <span>Explore Accommodations</span>
                            </Link>
                            <Link
                                href="/map"
                                class="btn btn-outline-success btn-lg px-4 fw-bold shadow-sm transition-all rounded-3 d-inline-flex align-items-center justify-content-center gap-2"
                                style="min-height: 48px;"
                            >
                                <i class="bi bi-geo-alt"></i>
                                <span>Open Interactive Map</span>
                            </Link>
                        </div>

                        <!-- Trust Badges (Uniform 100% Outlined Icons) -->
                        <div
                            class="row g-3 g-md-4 pt-4 border-top border-secondary-subtle transition-all"
                        >
                            <div class="col-6 col-md-auto">
                                <div
                                    class="trust-badge-item d-flex align-items-center gap-2"
                                >
                                    <div class="p-2 rounded-2 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i class="bi bi-patch-check fs-5"></i>
                                    </div>
                                    <div>
                                        <span
                                            class="d-block fw-bold small text-body-emphasis lh-1 mb-1 transition-all"
                                            >Verified Listings</span
                                        >
                                        <span
                                            class="d-block small text-body-secondary text-nowrap transition-all"
                                            style="font-size: 0.75rem"
                                            >Vetted local owners</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-auto">
                                <div
                                    class="trust-badge-item d-flex align-items-center gap-2"
                                >
                                    <div class="p-2 rounded-2 bg-warning-subtle text-warning-emphasis d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i class="bi bi-tag fs-5"></i>
                                    </div>
                                    <div>
                                        <span
                                            class="d-block fw-bold small text-body-emphasis lh-1 mb-1 transition-all"
                                            >Student Rates</span
                                        >
                                        <span
                                            class="d-block small text-body-secondary text-nowrap transition-all"
                                            style="font-size: 0.75rem"
                                            >Transparent pricing</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-auto">
                                <div
                                    class="trust-badge-item d-flex align-items-center gap-2"
                                >
                                    <div class="p-2 rounded-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i class="bi bi-shield-lock fs-5"></i>
                                    </div>
                                    <div>
                                        <span
                                            class="d-block fw-bold small text-body-emphasis lh-1 mb-1 transition-all"
                                            >Guest Safe</span
                                        >
                                        <span
                                            class="d-block small text-body-secondary text-nowrap transition-all"
                                            style="font-size: 0.75rem"
                                            >DPA-compliant data</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Snap Section: About E-BoardMate & System Manual Flow -->
        <section
            class="snap-section-tertiary bg-body-tertiary d-flex align-items-center py-5 min-vh-100 transition-all"
        >
            <div class="container py-5">
                <!-- ABOUT SECTION (LEFT-ALIGNED INSTITUTIONAL HEADER) -->
                <div class="row justify-content-center text-center mb-5 pb-lg-2">
                    <div class="col-12 col-lg-8 mx-auto">
                        <p class="text-uppercase tracking-wider small fw-semibold text-success mb-2">
                            How E-BoardMate Works
                        </p>

                        <h2
                            class="display-6 fw-bold mb-3 text-body-emphasis tracking-tight transition-all"
                        >
                            Securing student housing made simple.
                        </h2>

                        <p class="fs-6 text-secondary transition-all mb-0 mx-auto" style="line-height: 1.7; max-width: 720px;">
                            E-BoardMate connects Talibon Polytechnic College students with verified local landlords through an interactive walking-distance map. Reserve your bedspace directly with zero student account registration required.
                        </p>
                    </div>
                </div>

                <!-- 4-STEP SYSTEM FLOW CARDS (LEFT-ALIGNED SCANNABLE CARDS WITH CONCENTRIC RADII) -->
                <div class="guide-scroll-container gap-3 py-2">
                    <!-- Step 1 -->
                    <div class="guide-card-wrapper">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 guide-card-interactive transition-all"
                        >
                            <div class="card-body p-4 text-start d-flex flex-column align-items-start justify-content-between">
                                <div>
                                    <div
                                        class="step-icon d-inline-flex align-items-center justify-content-center p-3 rounded-3 bg-success-subtle text-success border border-success-subtle mb-4 transition-all"
                                        style="width: 48px; height: 48px;"
                                    >
                                        <i class="bi bi-map fs-5"></i>
                                    </div>
                                    <h3
                                        class="h6 fw-bold mb-2 text-body-emphasis transition-all"
                                    >
                                        1. Explore Listings & Map
                                    </h3>
                                    <p
                                        class="small text-body-secondary mb-0 transition-all"
                                    >
                                        Browse verified boarding houses around TPC. Inspect photos, amenities, room rates, and real-time walking distances.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="guide-card-wrapper">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 guide-card-interactive transition-all"
                        >
                            <div class="card-body p-4 text-start d-flex flex-column align-items-start justify-content-between">
                                <div>
                                    <div
                                        class="step-icon d-inline-flex align-items-center justify-content-center p-3 rounded-3 bg-success-subtle text-success border border-success-subtle mb-4 transition-all"
                                        style="width: 48px; height: 48px;"
                                    >
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </div>
                                    <h3
                                        class="h6 fw-bold mb-2 text-body-emphasis transition-all"
                                    >
                                        2. Request Bedspace
                                    </h3>
                                    <p
                                        class="small text-body-secondary mb-0 transition-all"
                                    >
                                        Select your room and submit a simple guest request form with your contact details. No account creation needed.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="guide-card-wrapper">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 guide-card-interactive transition-all"
                        >
                            <div class="card-body p-4 text-start d-flex flex-column align-items-start justify-content-between">
                                <div>
                                    <div
                                        class="step-icon d-inline-flex align-items-center justify-content-center p-3 rounded-3 bg-success-subtle text-success border border-success-subtle mb-4 transition-all"
                                        style="width: 48px; height: 48px;"
                                    >
                                        <i class="bi bi-clock-history fs-5"></i>
                                    </div>
                                    <h3
                                        class="h6 fw-bold mb-2 text-body-emphasis transition-all"
                                    >
                                        3. Landlord Verification
                                    </h3>
                                    <p
                                        class="small text-body-secondary mb-0 transition-all"
                                    >
                                        The property owner reviews your request and you receive an immediate automated email with your unique reference code.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="guide-card-wrapper">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 guide-card-interactive transition-all"
                        >
                            <div class="card-body p-4 text-start d-flex flex-column align-items-start justify-content-between">
                                <div>
                                    <div
                                        class="step-icon d-inline-flex align-items-center justify-content-center p-3 rounded-3 bg-success-subtle text-success border border-success-subtle mb-4 transition-all"
                                        style="width: 48px; height: 48px;"
                                    >
                                        <i class="bi bi-search fs-5"></i>
                                    </div>
                                    <h3
                                        class="h6 fw-bold mb-2 text-body-emphasis transition-all"
                                    >
                                        4. Live Status Tracking
                                    </h3>
                                    <p
                                        class="small text-body-secondary mb-0 transition-all"
                                    >
                                        Check your approval status anytime on our Track Reservation page and view landlord contact details upon approval.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
/* Mobile Scroll Snapping & Hide Scrollbar */
@media (max-width: 991.98px) {
    .guide-scroll-container {
        display: flex !important;
        flex-wrap: nowrap !important;
        justify-content: flex-start !important;
        overflow-x: auto !important;
        scroll-snap-type: x mandatory;
        scroll-padding-left: 1rem;
        padding-left: 1rem !important;
        padding-right: 1rem !important;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .guide-scroll-container::-webkit-scrollbar {
        display: none;
    }
    .guide-card-wrapper {
        flex: 0 0 280px !important;
        width: 280px !important;
        scroll-snap-align: start;
    }
}

/* Desktop Grid layout: 4 Equal Columns in 1 Row */
@media (min-width: 992px) {
    .guide-scroll-container {
        display: flex !important;
        flex-wrap: nowrap !important;
        justify-content: center !important;
        overflow: visible !important;
    }
    .guide-card-wrapper {
        flex: 1 1 0px !important;
        width: 100% !important;
    }
}

/* Card Interactions */
.guide-card-interactive {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid rgba(0,0,0,0.05); /* Subtle border */
}

/* Desktop Hover Effect (Only on devices that can hover) */
@media (hover: hover) and (pointer: fine) {
    .guide-card-interactive:hover {
        transform: translateY(-8px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,0.1) !important;
    }
}

/* 🪄 RESPONSIVE HERO LAYOUT */
.hero-section {
    background:
        radial-gradient(
            circle at 90% 20%,
            rgba(46, 125, 91, 0.08),
            transparent 30rem
        ),
        var(--bs-body-bg);
}

.hero-layout {
    min-height: calc(100vh - 70px);
    min-height: calc(100dvh - 70px);
    display: flex;
    align-items: center;
}

.hero-content-container {
    padding-top: 3.5rem;
    padding-bottom: 3.5rem;
    width: 100%;
}

@media (min-width: 992px) {
    .hero-content-container {
        padding-top: 4.5rem;
        padding-bottom: 4.5rem;
    }

    .hero-map-overlay {
        pointer-events: none;
        background: linear-gradient(
            to right,
            rgba(var(--bs-body-bg-rgb), 1) 0%,
            rgba(var(--bs-body-bg-rgb), 0.96) 45%,
            rgba(var(--bs-body-bg-rgb), 0) 100%
        );
    }
}

/* --- INTERACTIVE STYLES --- */
.hover-lift {
    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease;
}
.hover-lift:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.08) !important;
}

.hover-bg-tertiary:hover {
    background-color: var(--bs-tertiary-bg) !important;
}

.step-icon {
    width: 70px;
    height: 70px;
    background-color: rgba(25, 135, 84, 0.15);
    color: #198754;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    transition:
        transform 0.3s ease,
        background-color 0.3s ease;
}

.hover-lift:hover .step-icon {
    transform: scale(1.1);
}

.tracking-tight {
    letter-spacing: -0.5px;
}

/* Smooth fade transitions */
.transition-all {
    transition:
        background-color 0.4s ease-in-out,
        color 0.4s ease-in-out,
        border-color 0.4s ease-in-out;
}
</style>

<!-- 🚀 UNSCOPED BRUTE-FORCE DARK MODE OVERRIDES -->
<style>
html[data-bs-theme="dark"] .snap-section-tertiary,
body[data-bs-theme="dark"] .snap-section-tertiary,
[data-bs-theme="dark"] .snap-section-tertiary {
    background-color: #2b3035 !important;
}
</style>
