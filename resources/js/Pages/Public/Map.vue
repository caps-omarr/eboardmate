<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import mapboxgl from 'mapbox-gl';
import 'mapbox-gl/dist/mapbox-gl.css';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    boardingHouses: {
        type: Array,
        default: () => [],
    },
});

// Component State & Reactive Refs
const mapContainer = ref(null);
const mapInstance = ref(null);
const selectedLocation = ref(null);
const currentMapStyle = ref('mapbox://styles/mapbox/satellite-streets-v12');

// 🚀 PROGRESSIVE & NETWORK-AWARE STATE
const isMapLoaded = ref(false);
const isOnline = ref(typeof navigator !== 'undefined' ? navigator.onLine : true);
const networkType = ref('4g');

// ⚡ Cache & Abort Controller for 60FPS Performance
const routeCache = new Map();
let currentAbortController = null;
const walkingRouteDetails = ref({ distance: null, duration: null, loading: false });

const mapboxToken = import.meta.env.VITE_MAPBOX_TOKEN || '';
const centerLat = Number(import.meta.env.VITE_MAP_CENTER_LAT || 10.1167);
const centerLng = Number(import.meta.env.VITE_MAP_CENTER_LNG || 124.2833);

const initialZoom = 16;
const minZoom = 11;
const maxZoom = 18;

// Strict Bounding Box restricting user camera within Talibon, Bohol vicinity
const talibonMaxBounds = [
    [124.2800, 10.0800], // Southwest bounds [lng, lat]
    [124.3500, 10.1600]  // Northeast bounds [lng, lat]
];

const hasMapboxToken = computed(() => mapboxToken && mapboxToken !== 'your_public_mapbox_token_here');

const formatPrice = (price) => Number(price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

// --- 🌐 NETWORK INFORMATION API INSPECTION ---
const checkNetworkStatus = () => {
    if (typeof navigator === 'undefined') return;
    isOnline.value = navigator.onLine;

    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (connection) {
        networkType.value = connection.effectiveType || '4g';
    }
};

const handleOnline = () => {
    isOnline.value = true;
    if (!mapInstance.value) {
        initializeMap();
    }
};

const handleOffline = () => {
    isOnline.value = false;
};

// --- 🏠 CUSTOM CANVAS ICON ---
const houseIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="32" height="32">
    <circle cx="12" cy="12" r="11" fill="#0d6efd" stroke="#ffffff" stroke-width="2"/>
    <path d="M7 11.5L12 7L17 11.5V17H8.5C7.7 17 7 16.3 7 15.5V11.5Z" fill="white"/>
</svg>`;

// --- 🌐 GEOJSON DATA PREPARATION ---
const getBoardingHouseGeoJSON = () => {
    return {
        type: 'FeatureCollection',
        features: props.boardingHouses.map(house => {
            const lng = Number(house.longitude);
            const lat = Number(house.latitude);
            if (Number.isNaN(lng) || Number.isNaN(lat)) return null;

            return {
                type: 'Feature',
                properties: { ...house },
                geometry: { type: 'Point', coordinates: [lng, lat] },
            };
        }).filter(f => f !== null),
    };
};

// --- 🚀 ULTRA-FAST CACHED & ABORTABLE WALKING ROUTE API ---
const fetchWalkingRoute = async (startLng, startLat, houseId) => {
    if (houseId && routeCache.has(houseId)) {
        const cached = routeCache.get(houseId);
        walkingRouteDetails.value = { distance: cached.distance, duration: cached.duration, loading: false };
        if (mapInstance.value && mapInstance.value.getSource('walking-route')) {
            mapInstance.value.getSource('walking-route').setData(cached.geojson);
        }
        return;
    }

    if (currentAbortController) {
        currentAbortController.abort();
    }
    currentAbortController = new AbortController();

    walkingRouteDetails.value = { distance: null, duration: null, loading: true };
    const url = `https://api.mapbox.com/directions/v5/mapbox/walking/${startLng},${startLat};${centerLng},${centerLat}?geometries=geojson&access_token=${mapboxToken}`;

    try {
        const response = await fetch(url, { signal: currentAbortController.signal });
        const data = await response.json();

        if (data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            const routeData = {
                distance: (route.distance / 1000).toFixed(2),
                duration: Math.ceil(route.duration / 60),
                geojson: {
                    type: 'Feature',
                    properties: {},
                    geometry: route.geometry,
                },
            };

            if (houseId) routeCache.set(houseId, routeData);

            walkingRouteDetails.value = {
                distance: routeData.distance,
                duration: routeData.duration,
                loading: false,
            };

            if (mapInstance.value && mapInstance.value.getSource('walking-route')) {
                mapInstance.value.getSource('walking-route').setData(routeData.geojson);
            }
        }
    } catch (error) {
        if (error.name !== 'AbortError') {
            walkingRouteDetails.value.loading = false;
        }
    }
};

const clearWalkingRoute = () => {
    if (mapInstance.value && mapInstance.value.getSource('walking-route')) {
        mapInstance.value.getSource('walking-route').setData({
            type: 'FeatureCollection',
            features: [],
        });
    }
};

// --- 🗺️ ADD LAYERS & LIGHTWEIGHT RENDER ---
const addMapLayers = () => {
    const map = mapInstance.value;
    if (!map) return;

    const loadSourcesAndLayers = () => {
        if (!map.getSource('walking-route')) {
            map.addSource('walking-route', {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: [] },
            });
        }

        if (!map.getLayer('walking-route-line')) {
            map.addLayer({
                id: 'walking-route-line',
                type: 'line',
                source: 'walking-route',
                layout: { 'line-join': 'round', 'line-cap': 'round' },
                paint: {
                    'line-color': '#2563eb', // Vibrant Blue
                    'line-width': 5,
                    'line-opacity': 0.9,
                    'line-dasharray': [1, 2],
                },
            });
        }

        if (map.getSource('boarding-houses')) {
            map.getSource('boarding-houses').setData(getBoardingHouseGeoJSON());
        } else {
            map.addSource('boarding-houses', {
                type: 'geojson',
                data: getBoardingHouseGeoJSON(),
                cluster: true,
                clusterMaxZoom: 17,
                clusterRadius: 50,
            });
        }

        if (!map.getLayer('clusters')) {
            map.addLayer({
                id: 'clusters',
                type: 'circle',
                source: 'boarding-houses',
                filter: ['has', 'point_count'],
                paint: {
                    'circle-color': '#198754',
                    'circle-radius': ['step', ['get', 'point_count'], 20, 5, 30, 10, 40],
                    'circle-stroke-width': 3,
                    'circle-stroke-color': '#ffffff',
                },
            });
        }

        if (!map.getLayer('cluster-count')) {
            map.addLayer({
                id: 'cluster-count',
                type: 'symbol',
                source: 'boarding-houses',
                filter: ['has', 'point_count'],
                layout: {
                    'text-field': '{point_count_abbreviated}',
                    'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
                    'text-size': 14,
                },
                paint: { 'text-color': '#ffffff' },
            });
        }

        if (!map.getLayer('unclustered-point')) {
            map.addLayer({
                id: 'unclustered-point',
                type: 'symbol',
                source: 'boarding-houses',
                filter: ['!', ['has', 'point_count']],
                layout: {
                    'icon-image': 'house-icon',
                    'icon-size': 1,
                    'icon-allow-overlap': true,
                    'text-field': ['get', 'name'],
                    'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'],
                    'text-size': 13,
                    'text-offset': [0, 1.3],
                    'text-anchor': 'top',
                    'text-max-width': 12,
                },
                paint: {
                    'text-color': '#ffffff',
                    'text-halo-color': 'rgba(0, 0, 0, 0.75)',
                    'text-halo-width': 1.5,
                },
            });
        }
    };

    if (map.hasImage('house-icon')) {
        loadSourcesAndLayers();
    } else {
        const img = new Image(32, 32);
        img.onload = () => {
            if (!map.hasImage('house-icon')) map.addImage('house-icon', img);
            loadSourcesAndLayers();
        };
        img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(houseIconSvg);
    }
};

const getMapOffset = () => window.innerWidth >= 768 ? [140, 0] : [0, -80];

const initializeMap = async () => {
    if (!hasMapboxToken.value) return;
    await nextTick();
    if (!mapContainer.value) return;

    checkNetworkStatus();
    if (!isOnline.value) return;

    isMapLoaded.value = false;
    mapboxgl.accessToken = mapboxToken;

    const isSlowConnection = networkType.value === '2g' || networkType.value === 'slow-2g';

    mapInstance.value = new mapboxgl.Map({
        container: mapContainer.value,
        style: currentMapStyle.value,
        center: [centerLng, centerLat],
        zoom: initialZoom,
        minZoom,
        maxZoom,
        maxBounds: talibonMaxBounds,
        bearing: 0,
        pitch: isSlowConnection ? 0 : 30,
        dragRotate: !isSlowConnection,
        antialias: !isSlowConnection,
        attributionControl: true,
    });

    mapInstance.value.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');
    mapInstance.value.addControl(new mapboxgl.GeolocateControl({ positionOptions: { enableHighAccuracy: true }, trackUserLocation: true }), 'top-right');

    // 🚀 Idle Event Listener: Triggers smooth 0.6s opacity fade-in once all tiles & layers finish painting
    mapInstance.value.once('idle', () => {
        isMapLoaded.value = true;
    });

    mapInstance.value.on('load', () => {
        addMapLayers();

        const tpcMarkerEl = document.createElement('div');
        tpcMarkerEl.innerHTML = `<div style="background:#dc3545; color:white; padding:4px 10px; border-radius:12px; font-weight:bold; font-size:12px; box-shadow:0 4px 10px rgba(0,0,0,0.3); border:2px solid white;">🏛️ TPC Campus</div>`;
        tpcMarkerEl.style.cursor = 'pointer';

        tpcMarkerEl.addEventListener('click', (e) => {
            e.stopPropagation();
            clearWalkingRoute();
            selectedLocation.value = { type: 'tpc' };
            mapInstance.value.flyTo({ center: [centerLng, centerLat], zoom: 17.5, offset: getMapOffset(), duration: 800 });
        });

        new mapboxgl.Marker({ element: tpcMarkerEl, anchor: 'bottom' }).setLngLat([centerLng, centerLat]).addTo(mapInstance.value);

        // Auto-Zoom for ?house_id= URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const houseIdFromUrl = urlParams.get('house_id');

        if (houseIdFromUrl) {
            const targetHouse = props.boardingHouses.find(h => h.id == houseIdFromUrl);

            if (targetHouse && targetHouse.longitude && targetHouse.latitude) {
                const lng = Number(targetHouse.longitude);
                const lat = Number(targetHouse.latitude);

                targetHouse.is_verified = targetHouse.is_verified === true || targetHouse.is_verified === 'true';
                targetHouse.is_full = targetHouse.is_full === true || targetHouse.is_full === 'true';

                selectedLocation.value = { type: 'house', data: targetHouse };

                setTimeout(() => {
                    if (mapInstance.value) {
                        mapInstance.value.flyTo({ center: [lng, lat], zoom: 17, offset: getMapOffset(), duration: 1000 });
                    }
                }, 300);

                fetchWalkingRoute(lng, lat, targetHouse.id);
            }
        }
    });

    mapInstance.value.on('click', 'clusters', (e) => {
        const features = mapInstance.value.queryRenderedFeatures(e.point, { layers: ['clusters'] });
        const clusterId = features[0].properties.cluster_id;
        mapInstance.value.getSource('boarding-houses').getClusterExpansionZoom(clusterId, (err, zoom) => {
            if (err) return;
            mapInstance.value.easeTo({ center: features[0].geometry.coordinates, zoom: zoom });
        });
    });

    mapInstance.value.on('click', 'unclustered-point', (e) => {
        const coordinates = e.features[0].geometry.coordinates.slice();
        const houseData = e.features[0].properties;

        houseData.is_verified = houseData.is_verified === true || houseData.is_verified === 'true';
        houseData.is_full = houseData.is_full === true || houseData.is_full === 'true';

        selectedLocation.value = { type: 'house', data: houseData };
        mapInstance.value.flyTo({ center: coordinates, zoom: 17, offset: getMapOffset(), duration: 800 });

        fetchWalkingRoute(coordinates[0], coordinates[1], houseData.id);
    });

    mapInstance.value.on('mouseenter', 'clusters', () => mapInstance.value.getCanvas().style.cursor = 'pointer');
    mapInstance.value.on('mouseleave', 'clusters', () => mapInstance.value.getCanvas().style.cursor = '');
    mapInstance.value.on('mouseenter', 'unclustered-point', () => mapInstance.value.getCanvas().style.cursor = 'pointer');
    mapInstance.value.on('mouseleave', 'unclustered-point', () => mapInstance.value.getCanvas().style.cursor = '');

    mapInstance.value.on('click', (e) => {
        if (!mapInstance.value.queryRenderedFeatures(e.point, { layers: ['clusters', 'unclustered-point'] }).length) {
            closeBottomSheet();
        }
    });
};

const closeBottomSheet = () => {
    selectedLocation.value = null;
    clearWalkingRoute();
    if (mapInstance.value) {
        mapInstance.value.flyTo({
            center: [centerLng, centerLat],
            zoom: 15.5,
            offset: [0, 0],
            pitch: 30,
            bearing: 0,
            duration: 800,
        });
    }
};

const changeMapStyle = (event) => {
    currentMapStyle.value = event.target.value;
    isMapLoaded.value = false;
    mapInstance.value.setStyle(currentMapStyle.value);
    mapInstance.value.once('style.load', () => addMapLayers());
    mapInstance.value.once('idle', () => isMapLoaded.value = true);
};

onMounted(() => {
    checkNetworkStatus();
    if (typeof window !== 'undefined') {
        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);
    }
    initializeMap();
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('online', handleOnline);
        window.removeEventListener('offline', handleOffline);
    }
    if (currentAbortController) currentAbortController.abort();
    if (mapInstance.value) {
        mapInstance.value.remove();
        mapInstance.value = null;
    }
});
</script>

<template>
    <PublicLayout>
        <Head title="Map of Boarding Houses | E-BoardMate">
            <meta name="description" content="Explore verified boarding houses near Talibon Polytechnic College on an interactive map." />
        </Head>

        <!-- 🔍 SEO MICRODATA -->
        <div class="visually-hidden">
            <h2>Verified Boarding Houses Near Talibon Polytechnic College</h2>
            <article v-for="house in boardingHouses" :key="`seo-${house.id}`" itemscope itemtype="https://schema.org/Accommodation">
                <h3 itemprop="name">{{ house.name }}</h3>
                <p itemprop="description">Verified student boarding house near Talibon Polytechnic College.</p>
                <span itemprop="price">₱{{ formatPrice(house.rent_price) }}/month</span>
                <a :href="house.detail_url">{{ house.name }} Details</a>
            </article>
        </div>

        <section class="map-page-wrapper bg-body transition-all position-relative">
            
            <!-- 📌 CLEAN TOP BAR: BACK BUTTON & MAP STYLE SELECTOR -->
            <div class="map-top-bar d-flex align-items-center justify-content-between px-3 py-2 bg-body border-bottom border-secondary-subtle z-3 position-relative">
                <Link href="/boarding-houses" class="btn btn-sm border-secondary-subtle bg-body text-body-emphasis shadow-sm rounded-pill fw-semibold px-3 transition-all hover-bg-tertiary d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left text-success fs-6"></i> Back to Boarding Houses
                </Link>

                <div class="d-flex align-items-center gap-2">
                    <select @change="changeMapStyle" class="form-select form-select-sm shadow-sm border-secondary-subtle fw-medium map-style-selector rounded-pill px-3 transition-all" style="max-width: 180px;">
                        <option value="mapbox://styles/mapbox/satellite-streets-v12">🛰️ Satellite 3D</option>
                        <option value="mapbox://styles/mapbox/streets-v12">🗺️ Standard Map</option>
                        <option value="mapbox://styles/mapbox/outdoors-v12">🏞️ Outdoors</option>
                        <option value="mapbox://styles/mapbox/dark-v11">🌙 Dark Mode</option>
                    </select>
                </div>
            </div>

            <!-- MAP CONTAINER WRAPPER WITH ABSOLUTE SKELETON & OFFLINE OVERLAYS -->
            <div class="position-relative w-100 flex-grow-1 overflow-hidden">

                <!-- 🚀 BOOTSTRAP 5 SKELETON OVERLAY (Pulsing placeholder prevents CLS) -->
                <div 
                    v-if="!isMapLoaded && isOnline" 
                    class="map-skeleton-overlay placeholder-glow d-flex flex-column align-items-center justify-content-center p-4 text-center bg-body"
                >
                    <div class="spinner-border text-success mb-3" style="width: 2.75rem; height: 2.75rem;" role="status">
                        <span class="visually-hidden">Loading map tiles...</span>
                    </div>
                    <div class="placeholder col-8 col-md-4 py-2 rounded-3 mb-2 bg-secondary bg-opacity-20"></div>
                    <div class="placeholder col-6 col-md-3 py-1 rounded-3 bg-secondary bg-opacity-20 mb-3"></div>
                    <span class="small text-body-secondary fw-semibold">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i> Rendering Talibon Satellite Map...
                    </span>
                    <span v-if="networkType === '2g' || networkType === 'slow-2g'" class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill mt-2 px-3 py-1 small">
                        Slow Network Detected (Low Bandwidth Mode)
                    </span>
                </div>

                <!-- 🚀 OFFLINE NETWORK OVERLAY -->
                <div 
                    v-if="!isOnline" 
                    class="map-offline-overlay d-flex flex-column align-items-center justify-content-center p-4 text-center bg-body"
                >
                    <div class="offline-icon-box bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                        <i class="bi bi-wifi-off fs-1"></i>
                    </div>
                    <h3 class="h5 fw-bold text-body-emphasis mb-2">Map Requires Internet Connection</h3>
                    <p class="text-body-secondary small mb-4" style="max-width: 320px;">
                        Unable to fetch satellite tiles. Please check your mobile data or WiFi connection.
                    </p>
                    <button type="button" @click="initializeMap" class="btn btn-sm btn-success rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-arrow-clockwise me-1"></i> Retry Connection
                    </button>
                </div>

                <!-- MAPBOX CANVAS CONTAINER -->
                <div 
                    ref="mapContainer" 
                    class="ebm-map-full w-100 h-100 transition-opacity" 
                    :style="{ opacity: isMapLoaded ? 1 : 0, transition: 'opacity 0.6s ease-in-out' }"
                />

            </div>

            <!-- BOTTOM SHEET / SIDE DRAWER CARD -->
            <Transition name="slide-up">
                <div v-if="selectedLocation" class="map-bottom-sheet transition-all border border-secondary-subtle shadow">
                    <button @click="closeBottomSheet" class="btn-close shadow-none position-absolute top-0 end-0 m-3" title="Close panel"></button>

                    <div v-if="selectedLocation.type === 'tpc'" class="pt-2">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="fs-4">🏛️</span>
                            <h3 class="h5 fw-bold mb-0 text-body-emphasis transition-all">Talibon Polytechnic College</h3>
                        </div>
                        <p class="text-body-secondary small mb-0 transition-all">Official TPC campus location used for real-time walking distance calculations.</p>
                    </div>

                    <div v-if="selectedLocation.type === 'house'" class="pt-2">
                        <div class="d-flex align-items-start gap-3 mb-3">
                            <img 
                                :src="selectedLocation.data.primary_photo_url || '/images/default-boarding-house.jpg'" 
                                :alt="selectedLocation.data.name" 
                                class="rounded-3 object-fit-cover shadow-sm flex-shrink-0"
                                style="width: 68px; height: 68px;"
                            />
                            <div class="overflow-hidden pe-3">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <h3 class="h5 fw-bold mb-0 text-truncate text-body-emphasis transition-all">{{ selectedLocation.data.name }}</h3>
                                    <span v-if="selectedLocation.data.is_verified" class="badge bg-success rounded-pill px-2 py-1"><small>Verified</small></span>
                                </div>
                                <p class="text-body-secondary small mb-0 text-truncate">Near TPC Campus, Talibon</p>
                            </div>
                        </div>

                        <!-- Walking Route Card -->
                        <div class="bg-body-tertiary border border-secondary-subtle rounded-3 p-3 mb-3 d-flex align-items-center gap-3 transition-all">
                            <span class="fs-3 lh-1">🚶‍♂️</span>
                            <div v-if="walkingRouteDetails.loading" class="text-body-secondary small transition-all">
                                <span class="spinner-border spinner-border-sm text-primary me-1" role="status" aria-hidden="true"></span>
                                Calculating walking route to TPC...
                            </div>
                            <div v-else class="lh-sm">
                                <div class="fw-bold text-success fs-6">{{ walkingRouteDetails.duration }} min walk</div>
                                <div class="text-body-secondary small transition-all">{{ walkingRouteDetails.distance }} km from TPC Campus</div>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-body-tertiary rounded-3 p-2 text-center border border-secondary-subtle transition-all">
                                    <span class="d-block text-body-secondary small transition-all">Monthly Rent</span>
                                    <strong class="text-body-emphasis fs-6 transition-all">₱{{ formatPrice(selectedLocation.data.rent_price) }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-body-tertiary rounded-3 p-2 text-center border border-secondary-subtle transition-all">
                                    <span class="d-block text-body-secondary small transition-all">Status</span>
                                    <strong :class="selectedLocation.data.is_full ? 'text-danger' : 'text-success'" class="fs-6">
                                        {{ selectedLocation.data.is_full ? 'Full' : 'Available' }}
                                    </strong>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary-subtle transition-all">
                            <div class="small text-body-secondary transition-all">
                                <strong class="text-body-emphasis">{{ selectedLocation.data.available_rooms || 0 }}</strong> Rooms available
                            </div>
                            <Link :href="selectedLocation.data.detail_url || `/boarding-houses/${selectedLocation.data.slug}`" class="btn btn-ebm-primary px-4 rounded-pill fw-bold">
                                View Details <i class="bi bi-arrow-right ms-1"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </Transition>
        </section>
    </PublicLayout>
</template>

<style scoped>
.map-page-wrapper {
    height: calc(100vh - 70px);
    height: calc(100dvh - 70px);
    display: flex;
    flex-direction: column;
    width: 100%;
}

.ebm-map-full {
    flex-grow: 1;
    width: 100%;
}

.map-skeleton-overlay,
.map-offline-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 5;
    background-color: var(--bs-body-bg);
}

.map-bottom-sheet {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    padding: 20px;
    border-top-left-radius: 24px;
    border-top-right-radius: 24px;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
    z-index: 10;
    pointer-events: auto;
}

@media (min-width: 768px) {
    .map-bottom-sheet {
        bottom: 24px; left: 24px; right: auto;
        width: 380px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }
}

:global([data-bs-theme="dark"]) .map-style-selector {
    background-color: rgba(28, 28, 30, 0.90) !important;
    color: #e4e4e7 !important;
}

:global([data-bs-theme="dark"]) .map-style-selector option {
    background-color: #1c1c1e !important;
    color: #e4e4e7 !important;
}

.hover-bg-tertiary:hover {
    background-color: var(--bs-tertiary-bg) !important;
}

.transition-all {
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, border-color 0.3s ease-in-out;
}

.slide-up-enter-active, .slide-up-leave-active { 
    transition: transform 0.35s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease; 
}
.slide-up-enter-from, .slide-up-leave-to { 
    transform: translateY(100%); 
    opacity: 0; 
}
@media (min-width: 768px) {
    .slide-up-enter-from, .slide-up-leave-to { 
        transform: translateY(20px) scale(0.95); 
        opacity: 0; 
    }
}
</style>