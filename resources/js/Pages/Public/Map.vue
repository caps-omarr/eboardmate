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

const mapContainer = ref(null);
const mapInstance = ref(null);
const mapError = ref('');
const selectedLocation = ref(null);
const currentMapStyle = ref('mapbox://styles/mapbox/satellite-streets-v12');

// --- NEW STATE FOR ROUTING ---
const walkingRouteDetails = ref({ distance: null, duration: null, loading: false });

const mapboxToken = import.meta.env.VITE_MAPBOX_TOKEN || '';

const centerLat = Number(import.meta.env.VITE_MAP_CENTER_LAT || 10.1167);
const centerLng = Number(import.meta.env.VITE_MAP_CENTER_LNG || 124.2833);

const initialZoom = 16;
const minZoom = 14;
const maxZoom = 19;

const hasMapboxToken = computed(() => mapboxToken && mapboxToken !== 'your_public_mapbox_token_here');

const formatPrice = (price) => Number(price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

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

// --- 🚶‍♂️ REAL-TIME MAPBOX WALKING ROUTE API ---
const fetchWalkingRoute = async (startLng, startLat) => {
    walkingRouteDetails.value = { distance: null, duration: null, loading: true };
    
    // The Mapbox Directions API (Walking Profile)
    const url = `https://api.mapbox.com/directions/v5/mapbox/walking/${startLng},${startLat};${centerLng},${centerLat}?geometries=geojson&access_token=${mapboxToken}`;

    try {
        const response = await fetch(url);
        const data = await response.json();

        if (data.routes && data.routes.length > 0) {
            const route = data.routes[0];
            
            // Convert to minutes and KM
            walkingRouteDetails.value = {
                distance: (route.distance / 1000).toFixed(2), // Convert meters to km
                duration: Math.ceil(route.duration / 60), // Convert seconds to minutes
                loading: false
            };

            // Draw the line on the map
            const geojson = {
                type: 'Feature',
                properties: {},
                geometry: route.geometry
            };

            if (mapInstance.value.getSource('walking-route')) {
                mapInstance.value.getSource('walking-route').setData(geojson);
            }
        }
    } catch (error) {
        console.error("Failed to fetch walking route", error);
        walkingRouteDetails.value.loading = false;
    }
};

// Clear the drawn route when the bottom sheet closes
const clearWalkingRoute = () => {
    if (mapInstance.value && mapInstance.value.getSource('walking-route')) {
        mapInstance.value.getSource('walking-route').setData({
            type: 'FeatureCollection',
            features: []
        });
    }
};

// --- 🗺️ ADD LAYERS & REALISM ---
const addMapLayers = () => {
    const map = mapInstance.value;
    if (!map) return;

    map.setFog({ 'color': 'rgb(255, 255, 255)', 'high-color': 'rgb(200, 200, 225)', 'horizon-blend': 0.2 });

    if (!map.getSource('mapbox-dem')) {
        map.addSource('mapbox-dem', { 'type': 'raster-dem', 'url': 'mapbox://mapbox.mapbox-terrain-dem-v1', 'tileSize': 512, 'maxzoom': 14 });
        map.setTerrain({ 'source': 'mapbox-dem', 'exaggeration': 1.5 });
    }

    const loadSourcesAndLayers = () => {
        // --- 1. NEW: Empty Source for the Walking Route Line ---
        if (!map.getSource('walking-route')) {
            map.addSource('walking-route', {
                type: 'geojson',
                data: { type: 'FeatureCollection', features: [] }
            });
        }

        // --- 2. NEW: Layer for the Glowing Route Line ---
        if (!map.getLayer('walking-route-line')) {
            map.addLayer({
                id: 'walking-route-line',
                type: 'line',
                source: 'walking-route',
                layout: {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                paint: {
                    'line-color': '#0d6efd',
                    'line-width': 5,
                    'line-opacity': 0.75,
                    'line-dasharray': [1, 2] // Makes it look like a dotted walking path
                }
            });
        }

        // 3. Boarding Houses Source
        if (map.getSource('boarding-houses')) {
            map.getSource('boarding-houses').setData(getBoardingHouseGeoJSON());
        } else {
            map.addSource('boarding-houses', { type: 'geojson', data: getBoardingHouseGeoJSON(), cluster: true, clusterMaxZoom: 17, clusterRadius: 50 });
        }

        // 4. Clusters
        if (!map.getLayer('clusters')) {
            map.addLayer({
                id: 'clusters', type: 'circle', source: 'boarding-houses', filter: ['has', 'point_count'],
                paint: { 'circle-color': '#198754', 'circle-radius': ['step', ['get', 'point_count'], 20, 5, 30, 10, 40], 'circle-stroke-width': 3, 'circle-stroke-color': '#ffffff' }
            });
        }

        // 5. Cluster text
        if (!map.getLayer('cluster-count')) {
            map.addLayer({
                id: 'cluster-count', type: 'symbol', source: 'boarding-houses', filter: ['has', 'point_count'],
                layout: { 'text-field': '{point_count_abbreviated}', 'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'], 'text-size': 14 },
                paint: { 'text-color': '#ffffff' }
            });
        }

        // 6. Individual Houses
        if (!map.getLayer('unclustered-point')) {
            map.addLayer({
                id: 'unclustered-point', type: 'symbol', source: 'boarding-houses', filter: ['!', ['has', 'point_count']],
                layout: {
                    'icon-image': 'house-icon', 'icon-size': 1, 'icon-allow-overlap': true,
                    'text-field': ['get', 'name'], 'text-font': ['Open Sans Semibold', 'Arial Unicode MS Bold'], 'text-size': 13,
                    'text-offset': [0, 1.3], 'text-anchor': 'top', 'text-max-width': 12
                },
                paint: { 'text-color': '#ffffff', 'text-halo-color': 'rgba(0, 0, 0, 0.75)', 'text-halo-width': 1.5 }
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

const initializeMap = async () => {
    if (!hasMapboxToken.value) return;
    await nextTick();
    if (!mapContainer.value) return;

    mapboxgl.accessToken = mapboxToken;
    mapInstance.value = new mapboxgl.Map({
        container: mapContainer.value, style: currentMapStyle.value,
        center: [centerLng, centerLat], zoom: initialZoom,
        minZoom, maxZoom, bearing: 0, pitch: 45, dragRotate: true, attributionControl: true,
    });

    mapInstance.value.addControl(new mapboxgl.NavigationControl({ visualizePitch: true }), 'top-right');
    mapInstance.value.addControl(new mapboxgl.GeolocateControl({ positionOptions: { enableHighAccuracy: true }, trackUserLocation: true }), 'top-right');

    mapInstance.value.on('load', () => {
        addMapLayers();
        const tpcMarkerEl = document.createElement('div');
        tpcMarkerEl.innerHTML = `<div style="background:#dc3545; color:white; padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px; box-shadow:0 2px 4px rgba(0,0,0,0.3);">🏛️ TPC</div>`;
        tpcMarkerEl.style.cursor = 'pointer';
        
        tpcMarkerEl.addEventListener('click', (e) => {
            e.stopPropagation();
            clearWalkingRoute();
            selectedLocation.value = { type: 'tpc' };
            mapInstance.value.flyTo({ center: [centerLng, centerLat], zoom: 17.5, offset: [0, -100], duration: 800 });
        });

        new mapboxgl.Marker({ element: tpcMarkerEl, anchor: 'bottom' }).setLngLat([centerLng, centerLat]).addTo(mapInstance.value);

        // 🚀 NEW: Auto-Zoom Logic
        // Check if there is a "?house_id=" in the URL from the Reserve Now page
        const urlParams = new URLSearchParams(window.location.search);
        const houseIdFromUrl = urlParams.get('house_id');

        if (houseIdFromUrl) {
            // Find the specific boarding house data matching the ID in the URL
            const targetHouse = props.boardingHouses.find(h => h.id == houseIdFromUrl);
            
            if (targetHouse && targetHouse.longitude && targetHouse.latitude) {
                const lng = Number(targetHouse.longitude);
                const lat = Number(targetHouse.latitude);

                // Format the boolean values cleanly
                targetHouse.is_verified = targetHouse.is_verified === true || targetHouse.is_verified === 'true';
                targetHouse.is_full = targetHouse.is_full === true || targetHouse.is_full === 'true';

                // Simulate the click to open the bottom sheet UI
                selectedLocation.value = { type: 'house', data: targetHouse };

                // Add a tiny delay so the map finishes rendering before flying
                setTimeout(() => {
                    mapInstance.value.flyTo({ center: [lng, lat], zoom: 17, offset: [0, -100], duration: 1500 });
                }, 400);

                // Automatically draw the walking route from TPC
                fetchWalkingRoute(lng, lat);
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

    // --- TRIGGER ROUTING ON HOUSE CLICK ---
    mapInstance.value.on('click', 'unclustered-point', (e) => {
        const coordinates = e.features[0].geometry.coordinates.slice();
        const houseData = e.features[0].properties;

        houseData.is_verified = houseData.is_verified === true || houseData.is_verified === 'true';
        houseData.is_full = houseData.is_full === true || houseData.is_full === 'true';

        selectedLocation.value = { type: 'house', data: houseData };
        mapInstance.value.flyTo({ center: coordinates, zoom: 17, offset: [0, -100], duration: 800 });

        // Fetch and draw the walking route
        fetchWalkingRoute(coordinates[0], coordinates[1]);
    });

    mapInstance.value.on('mouseenter', 'clusters', () => mapInstance.value.getCanvas().style.cursor = 'pointer');
    mapInstance.value.on('mouseleave', 'clusters', () => mapInstance.value.getCanvas().style.cursor = '');
    mapInstance.value.on('mouseenter', 'unclustered-point', () => mapInstance.value.getCanvas().style.cursor = 'pointer');
    mapInstance.value.on('mouseleave', 'unclustered-point', () => mapInstance.value.getCanvas().style.cursor = '');

    mapInstance.value.on('click', (e) => {
        if (!mapInstance.value.queryRenderedFeatures(e.point, { layers: ['clusters', 'unclustered-point'] }).length) {
            selectedLocation.value = null;
            clearWalkingRoute(); // Clear the line when clicking away
        }
    });
};

const closeBottomSheet = () => {
    selectedLocation.value = null;
    clearWalkingRoute();
};

const changeMapStyle = (event) => {
    currentMapStyle.value = event.target.value;
    mapInstance.value.setStyle(currentMapStyle.value);
    mapInstance.value.once('style.load', () => addMapLayers());
};

onMounted(() => initializeMap());
onBeforeUnmount(() => { if (mapInstance.value) mapInstance.value.remove(); });
</script>

<template>
    <PublicLayout>
        <Head title="Map of Verified Boarding Houses | E-BoardMate" />

        <!-- 🚀 FIX: Removed bg-light, relying on global dark mode backgrounds -->
        <section class="py-5 bg-body transition-all min-vh-100">
            <div class="container">
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-11">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                            <div>
                                <!-- Updated Badge to adapt cleanly -->
                                <span class="badge rounded-pill border border-success-subtle bg-body text-success shadow-sm mb-2 px-3 py-2 transition-all">
                                    Interactive Map
                                </span>
                                <!-- Added text-body-emphasis for auto-adapting text color -->
                                <h1 class="fw-bold mb-1 text-body-emphasis transition-all">Explore Boarding Houses</h1>
                                <!-- Changed custom mute to Bootstrap's standard secondary text -->
                                <p class="text-body-secondary mb-0 transition-all">Use the 3D map below to find the perfect location near Talibon Polytechnic College.</p>
                            </div>
                            <div>
                                <Link href="/" class="btn bg-body border-secondary-subtle text-body-emphasis shadow-sm transition-all hover-bg-tertiary">
                                    Back to Home
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-11">
                        <div class="ebm-card p-0 overflow-hidden border border-secondary-subtle shadow-sm transition-all" style="border-radius: 16px;">
                            
                            <div class="map-wrapper position-relative">
                                
                                <div class="position-absolute p-3" style="z-index: 10; top: 0; left: 0;">
                                    <!-- 🚀 FIX: Removed hardcoded white style, added scoped custom class for frosted glass look -->
                                    <select @change="changeMapStyle" class="form-select form-select-sm shadow-sm border-secondary-subtle fw-bold map-style-selector transition-all">
                                        <option value="mapbox://styles/mapbox/satellite-streets-v12">🛰️ Satellite 3D</option>
                                        <option value="mapbox://styles/mapbox/streets-v12">🗺️ Standard Map</option>
                                        <option value="mapbox://styles/mapbox/outdoors-v12">🏞️ Outdoors</option>
                                        <option value="mapbox://styles/mapbox/dark-v11">🌙 Dark Mode</option>
                                    </select>
                                </div>

                                <div ref="mapContainer" class="ebm-map m-0 transition-all" style="height: 75vh; min-height: 550px;" />

                                <Transition name="slide-up">
                                    <div v-if="selectedLocation" class="map-bottom-sheet transition-all">
                                        <!-- Bootstrap handles dark mode toggle on .btn-close automatically via invert(1) -->
                                        <button @click="closeBottomSheet" class="btn-close shadow-none position-absolute top-0 end-0 m-3"></button>

                                        <div v-if="selectedLocation.type === 'tpc'" class="pt-2">
                                            <h3 class="h5 fw-bold mb-2 text-body-emphasis transition-all">🏛️ Talibon Polytechnic College</h3>
                                            <p class="text-body-secondary mb-0 transition-all">Map center point for distance estimations.</p>
                                        </div>

                                        <div v-if="selectedLocation.type === 'house'" class="pt-2">
                                            <div class="d-flex align-items-center gap-2 mb-2 pe-4">
                                                <h3 class="h5 fw-bold mb-0 text-truncate text-body-emphasis transition-all">{{ selectedLocation.data.name }}</h3>
                                                <span v-if="selectedLocation.data.is_verified" class="badge bg-success"><small>Verified</small></span>
                                            </div>
                                            
                                            <!-- 🚀 FIX: Replaced bg-light with bg-body-tertiary and border-secondary-subtle -->
                                            <div class="bg-body-tertiary border border-secondary-subtle rounded p-2 mb-3 d-flex align-items-center gap-3 transition-all">
                                                <div class="fs-3">🚶‍♂️</div>
                                                <div v-if="walkingRouteDetails.loading" class="text-body-secondary small transition-all">
                                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                                    Calculating exact walking route...
                                                </div>
                                                <div v-else class="lh-sm">
                                                    <div class="fw-bold text-primary">{{ walkingRouteDetails.duration }} min walk</div>
                                                    <div class="text-body-secondary small transition-all">{{ walkingRouteDetails.distance }} km to TPC campus</div>
                                                </div>
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <div class="bg-body-tertiary rounded p-2 text-center border border-secondary-subtle transition-all">
                                                        <span class="d-block text-body-secondary small transition-all">Monthly Rent</span>
                                                        <strong class="text-body-emphasis transition-all">₱{{ formatPrice(selectedLocation.data.rent_price) }}</strong>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="bg-body-tertiary rounded p-2 text-center border border-secondary-subtle transition-all">
                                                        <span class="d-block text-body-secondary small transition-all">Status</span>
                                                        <strong :class="selectedLocation.data.is_full ? 'text-danger' : 'text-success'">
                                                            {{ selectedLocation.data.is_full ? 'Full' : 'Available' }}
                                                        </strong>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary-subtle transition-all">
                                                <div class="small text-body-secondary transition-all">
                                                    <strong class="text-body-emphasis">{{ selectedLocation.data.available_rooms }}</strong> Rooms left
                                                </div>
                                                <Link :href="selectedLocation.data.detail_url || `/boarding-houses/${selectedLocation.data.slug}`" class="btn btn-ebm-primary px-4">
                                                    View Details
                                                </Link>
                                            </div>
                                        </div>
                                    </div>
                                </Transition>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
/* 🚀 FIX: Swapped hardcoded #ffffff for CSS variables so it shifts in Dark Mode */
.map-bottom-sheet {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    padding: 24px;
    border-top-left-radius: 24px;
    border-top-right-radius: 24px;
    box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.15);
    z-index: 10;
    pointer-events: auto;
}

@media (min-width: 768px) {
    .map-bottom-sheet {
        bottom: 24px; left: 24px; right: auto;
        width: 400px;
        border-radius: 16px;
        border: 1px solid var(--bs-border-color);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }
}

/* Force dark styling on the select dropdown when dark mode is active */
:global([data-bs-theme="dark"]) .map-style-selector {
    background-color: rgba(28, 28, 30, 0.90) !important;
    color: #e4e4e7 !important;
}

/* Explicitly style the <option> dropdown list to prevent white-on-white text */
:global([data-bs-theme="dark"]) .map-style-selector option {
    background-color: #1c1c1e !important; /* Solid dark background */
    color: #e4e4e7 !important; /* Light text */
}
.hover-bg-tertiary:hover {
    background-color: var(--bs-tertiary-bg) !important;
}

.transition-all {
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, border-color 0.3s ease-in-out;
}

.slide-up-enter-active, .slide-up-leave-active { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease; }
.slide-up-enter-from, .slide-up-leave-to { transform: translateY(100%); opacity: 0; }
@media (min-width: 768px) {
    .slide-up-enter-from, .slide-up-leave-to { transform: translateY(20px) scale(0.95); opacity: 0; }
}
</style>