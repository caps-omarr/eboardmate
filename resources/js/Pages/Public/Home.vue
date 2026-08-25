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
                        <h1
                            class="display-4 fw-bold mb-4 text-body-emphasis tracking-tight transition-all"
                        >
                            Find trusted boarding houses near Talibon
                            Polytechnic College faster, easier, and safer.
                        </h1>

                        <p
                            class="lead text-body-secondary mb-4 transition-all pe-lg-4"
                        >
                            E-BoardMate helps students view verified boarding
                            houses, check details, submit reservations, and
                            track reservation status online without creating a
                            student account.
                        </p>

                        <!-- Personality-Driven Hero CTAs -->
                        <div class="d-grid d-sm-flex gap-3 mb-5">
                            <Link
                                href="/boarding-houses"
                                class="btn btn-ebm-primary btn-lg px-4 fw-bold shadow transition-all rounded-pill d-inline-flex align-items-center justify-content-center gap-2"
                            >
                                <span>Find Accommodations</span>
                            </Link>
                            <Link
                                href="/map"
                                class="btn btn-outline-success btn-lg px-4 fw-bold shadow-sm transition-all rounded-pill d-inline-flex align-items-center justify-content-center gap-2"
                            >
                                <span>View Interactive Map</span>
                            </Link>
                        </div>

                        <!-- Trust Badges -->
                        <div
                            class="row g-3 g-md-4 pt-4 border-top border-secondary-subtle transition-all"
                        >
                            <div class="col-6 col-md-auto">
                                <div
                                    class="trust-badge-item d-flex align-items-center gap-2"
                                >
                                    <span class="fs-5 text-danger lh-1"
                                        >❤️</span
                                    >
                                    <div>
                                        <span
                                            class="d-block fw-bold small text-body-emphasis lh-1 mb-1 transition-all"
                                            >Verified & Safe</span
                                        >
                                        <span
                                            class="d-block small text-body-secondary text-nowrap transition-all"
                                            style="font-size: 0.75rem"
                                            >Trusted owners</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-auto">
                                <div
                                    class="trust-badge-item d-flex align-items-center gap-2"
                                >
                                    <span class="fs-5 text-warning lh-1"
                                        >🏷️</span
                                    >
                                    <div>
                                        <span
                                            class="d-block fw-bold small text-body-emphasis lh-1 mb-1 transition-all"
                                            >Affordable Rates</span
                                        >
                                        <span
                                            class="d-block small text-body-secondary text-nowrap transition-all"
                                            style="font-size: 0.75rem"
                                            >Student-friendly</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-auto">
                                <div
                                    class="trust-badge-item d-flex align-items-center gap-2"
                                >
                                    <span class="fs-5 text-success lh-1"
                                        >🤝</span
                                    >
                                    <div>
                                        <span
                                            class="d-block fw-bold small text-body-emphasis lh-1 mb-1 transition-all"
                                            >Community</span
                                        >
                                        <span
                                            class="d-block small text-body-secondary text-nowrap transition-all"
                                            style="font-size: 0.75rem"
                                            >Support local</span
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
                <div class="row justify-content-center text-center mb-5">
                    <div class="col-lg-8">
                        <span
                            class="badge rounded-pill border border-success-subtle bg-body text-success mb-3 px-3 py-2 transition-all shadow-sm"
                        >
                            About E-BoardMate • System Guide
                        </span>

                        <h2
                            class="display-6 fw-bold mb-4 text-body-emphasis tracking-tight transition-all"
                        >
                            Built exclusively for TPC students.
                        </h2>

                        <p class="lead text-body-secondary mb-0 transition-all">
                            Finding a safe and affordable place to stay near campus shouldn't be stressful. E-BoardMate bridges the gap between Talibon Polytechnic College students and verified local landlords. We provide a seamless, map-based platform where you can secure your bedspace online—<strong
                                >completely free and with zero account registration required.</strong
                            >
                        </p>

                        <!-- Premium Live Tracker Card -->
                        <div class="row justify-content-center mt-5 w-100 mx-0">
                            <div class="col-12 col-md-10 col-lg-7 col-xl-6 px-3">
                                <div class="card border-0 shadow-lg rounded-4 text-start" style="background-color: #212529;">
                                    <div class="card-body p-4 p-md-5">
                                        
                                        <!-- Glowing Badge -->
                                        <div class="d-inline-flex align-items-center mb-3 px-3 py-1 rounded-pill border border-success text-success bg-success bg-opacity-10 small fw-semibold">
                                            <i class="bi bi-clock-history me-2"></i> Live Tracker
                                        </div>
                                        
                                        <!-- Typography -->
                                        <h3 class="text-white fw-bold mb-3">Already Reserved a Spot?</h3>
                                        <p class="text-secondary mb-4" style="font-size: 1.05rem; line-height: 1.6;">
                                            Check your real-time approval status and landlord message using your reservation tracking reference code.
                                        </p>
                                        
                                        <!-- Full-Width Action Button -->
                                        <Link href="/track-reservation" class="btn btn-success w-100 py-3 fw-bold rounded-pill shadow-sm text-white fs-6 transition-all hover-lift">
                                            Track Reservation Status
                                        </Link>
                                        
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4-STEP SYSTEM FLOW CARDS -->
                <div class="row g-4 mt-2">
                    <div class="col-md-6 col-lg-3">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all"
                        >
                            <div class="card-body p-4 text-center">
                                <div
                                    class="step-icon mb-4 mx-auto transition-all"
                                >
                                    🗺️
                                </div>
                                <h3
                                    class="h5 fw-bold mb-3 text-body-emphasis transition-all"
                                >
                                    1. Explore Map & List
                                </h3>
                                <p
                                    class="small text-body-secondary mb-0 transition-all"
                                >
                                    Browse verified boarding house listings around TPC. View photo galleries, room rates, available bedspaces, and real-time walking distances to campus.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all"
                        >
                            <div class="card-body p-4 text-center">
                                <div
                                    class="step-icon mb-4 mx-auto transition-all"
                                >
                                    📝
                                </div>
                                <h3
                                    class="h5 fw-bold mb-3 text-body-emphasis transition-all"
                                >
                                    2. Reserve (No Account)
                                </h3>
                                <p
                                    class="small text-body-secondary mb-0 transition-all"
                                >
                                    Found your spot? Fill out a 60-second guest request form with your name, phone, and email. No student passwords or account registration required!
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all"
                        >
                            <div class="card-body p-4 text-center">
                                <div
                                    class="step-icon mb-4 mx-auto transition-all"
                                >
                                    ⏳
                                </div>
                                <h3
                                    class="h5 fw-bold mb-3 text-body-emphasis transition-all"
                                >
                                    3. Landlord Review
                                </h3>
                                <p
                                    class="small text-body-secondary mb-0 transition-all"
                                >
                                    The verified landlord receives your request instantly. You'll receive an automated email containing your unique <strong>EBM</strong> tracking code.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <div
                            class="card h-100 border border-secondary-subtle bg-body shadow-sm rounded-4 hover-lift transition-all"
                        >
                            <div class="card-body p-4 text-center">
                                <div
                                    class="step-icon mb-4 mx-auto transition-all"
                                >
                                    🔍
                                </div>
                                <h3
                                    class="h5 fw-bold mb-3 text-body-emphasis transition-all"
                                >
                                    4. Track Live Status
                                </h3>
                                <p
                                    class="small text-body-secondary mb-0 transition-all"
                                >
                                    Enter your <strong>EBM</strong> tracking code on our Track Reservation page anytime to view live approval status and unlock contact info!
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
