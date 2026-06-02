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
const mapMarkers = ref([]);
const mapError = ref('');

const mapboxToken = import.meta.env.VITE_MAPBOX_TOKEN || '';

const centerLat = Number(import.meta.env.VITE_MAP_CENTER_LAT || 10.1167);
const centerLng = Number(import.meta.env.VITE_MAP_CENTER_LNG || 124.2833);

const southwestLat = import.meta.env.VITE_MAP_BOUNDS_SOUTHWEST_LAT;
const southwestLng = import.meta.env.VITE_MAP_BOUNDS_SOUTHWEST_LNG;
const northeastLat = import.meta.env.VITE_MAP_BOUNDS_NORTHEAST_LAT;
const northeastLng = import.meta.env.VITE_MAP_BOUNDS_NORTHEAST_LNG;

const initialZoom = 16;
const minZoom = 14;
const maxZoom = 19;

const hasMapboxToken = computed(() => {
    return mapboxToken && mapboxToken !== 'your_public_mapbox_token_here';
});

const hasValidBounds = computed(() => {
    return southwestLat !== 'TO_BE_PROVIDED'
        && southwestLng !== 'TO_BE_PROVIDED'
        && northeastLat !== 'TO_BE_PROVIDED'
        && northeastLng !== 'TO_BE_PROVIDED'
        && !Number.isNaN(Number(southwestLat))
        && !Number.isNaN(Number(southwestLng))
        && !Number.isNaN(Number(northeastLat))
        && !Number.isNaN(Number(northeastLng));
});

const hasBoardingHouses = computed(() => props.boardingHouses.length > 0);

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const formatDistance = (distance) => {
    const value = Number(distance || 0);

    if (value <= 0) {
        return 'Distance unavailable';
    }

    return `${value.toFixed(2)} km from TPC`;
};

const schoolIconSvg = `
    <svg class="map-marker-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path
            d="M3 21V9.8L12 4L21 9.8V21H3Z"
            fill="currentColor"
        />
        <path
            d="M8 21V12H16V21"
            fill="white"
            opacity="0.95"
        />
        <path
            d="M10 9.5H14V12.5H10V9.5Z"
            fill="white"
            opacity="0.95"
        />
    </svg>
`;

const houseIconSvg = `
    <svg class="map-marker-icon" viewBox="0 0 24 24" aria-hidden="true">
        <path
            d="M4 11.5L12 5L20 11.5V20H5.5C4.7 20 4 19.3 4 18.5V11.5Z"
            fill="currentColor"
        />
        <path
            d="M9.5 20V14H14.5V20"
            fill="white"
            opacity="0.95"
        />
        <path
            d="M3 12.2L12 4.8L21 12.2"
            fill="none"
            stroke="white"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
`;

const createMarkerElement = (type, displayName, titleText) => {
    const element = document.createElement('button');
    element.type = 'button';
    element.className = type === 'tpc'
        ? 'map-marker map-marker-school'
        : 'map-marker map-marker-house';
    element.setAttribute('aria-label', titleText);

    const nameLabel = document.createElement('span');
    nameLabel.className = type === 'tpc'
        ? 'map-marker-name-label map-marker-name-label-school'
        : 'map-marker-name-label map-marker-name-label-house';
    nameLabel.textContent = displayName;

    const iconWrapper = document.createElement('span');
    iconWrapper.className = 'map-marker-icon-wrapper';
    iconWrapper.innerHTML = type === 'tpc' ? schoolIconSvg : houseIconSvg;

    element.appendChild(nameLabel);
    element.appendChild(iconWrapper);

    return element;
};

const createTextElement = (tag, className, text) => {
    const element = document.createElement(tag);
    element.className = className;
    element.textContent = text;

    return element;
};

const createInfoRow = (label, value) => {
    const row = document.createElement('div');
    row.className = 'map-popup-info-row';

    const labelElement = createTextElement('span', '', label);
    const valueElement = createTextElement('strong', '', value);

    row.appendChild(labelElement);
    row.appendChild(valueElement);

    return row;
};

const createBoardingHousePopupContent = (boardingHouse) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'map-popup-card';

    const header = document.createElement('div');
    header.className = 'map-popup-header';

    const title = createTextElement('strong', 'map-popup-title', boardingHouse.name);
    const verifiedBadge = createTextElement('span', 'badge text-bg-success', 'Verified');

    header.appendChild(title);
    header.appendChild(verifiedBadge);
    wrapper.appendChild(header);

    const distance = createTextElement(
        'div',
        'map-popup-distance',
        formatDistance(boardingHouse.estimated_distance_km)
    );
    wrapper.appendChild(distance);

    const infoGrid = document.createElement('div');
    infoGrid.className = 'map-popup-info-grid';

    infoGrid.appendChild(createInfoRow('Rent', `₱${formatPrice(boardingHouse.rent_price)}`));
    infoGrid.appendChild(createInfoRow('Rooms', `${boardingHouse.available_rooms}`));
    infoGrid.appendChild(createInfoRow('Bedspaces', `${boardingHouse.available_bedspaces}`));

    wrapper.appendChild(infoGrid);

    const status = createTextElement(
        'div',
        boardingHouse.is_full ? 'map-popup-status is-full' : 'map-popup-status is-available',
        boardingHouse.is_full ? 'Full' : 'Available'
    );
    wrapper.appendChild(status);

    const detailsLink = document.createElement('a');
    detailsLink.href = boardingHouse.detail_url;
    detailsLink.className = 'btn btn-sm btn-ebm-primary w-100 mt-3';
    detailsLink.textContent = 'View Details';
    wrapper.appendChild(detailsLink);

    return wrapper;
};

const createTpcPopupContent = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'map-popup-card';

    const title = createTextElement('strong', 'map-popup-title', 'Talibon Polytechnic College');
    const description = createTextElement(
        'p',
        'map-popup-description mb-0',
        'Map center point for nearby boarding house search.'
    );

    wrapper.appendChild(title);
    wrapper.appendChild(description);

    return wrapper;
};

const clearMarkers = () => {
    mapMarkers.value.forEach((marker) => marker.remove());
    mapMarkers.value = [];
};

const addTpcMarker = () => {
    const popup = new mapboxgl.Popup({
        offset: [0, -32],
        closeButton: true,
        closeOnClick: true,
        maxWidth: '280px',
        focusAfterOpen: false,
        autoPanPadding: { top: 60, bottom: 60, left: 60, right: 60 }
    }).setDOMContent(createTpcPopupContent());

    const marker = new mapboxgl.Marker({
        element: createMarkerElement(
            'tpc',
            'TPC',
            'Talibon Polytechnic College marker'
        ),
        anchor: 'bottom',
        offset: [0, 0],
    })
        .setLngLat([centerLng, centerLat])
        .setPopup(popup)
        .addTo(mapInstance.value);

    mapMarkers.value.push(marker);
};

const addBoardingHouseMarkers = () => {
    props.boardingHouses.forEach((boardingHouse) => {
        const longitude = Number(boardingHouse.longitude);
        const latitude = Number(boardingHouse.latitude);

        if (Number.isNaN(longitude) || Number.isNaN(latitude)) {
            return;
        }

        const popup = new mapboxgl.Popup({
            offset: [0, -32],
            closeButton: true,
            closeOnClick: true,
            maxWidth: '280px',
            focusAfterOpen: false,
            autoPanPadding: { top: 60, bottom: 60, left: 60, right: 60 }
        }).setDOMContent(createBoardingHousePopupContent(boardingHouse));

        const marker = new mapboxgl.Marker({
            element: createMarkerElement(
                'boarding-house',
                boardingHouse.name,
                `${boardingHouse.name} boarding house marker`
            ),
            anchor: 'bottom',
            offset: [0, 0],
        })
            .setLngLat([longitude, latitude])
            .setPopup(popup)
            .addTo(mapInstance.value);

        mapMarkers.value.push(marker);
    });
};

const addMarkers = () => {
    clearMarkers();
    addTpcMarker();
    addBoardingHouseMarkers();
};

const initializeMap = async () => {
    if (!hasMapboxToken.value) {
        mapError.value = 'Mapbox token is not configured yet. Please add your public Mapbox token in the .env file.';
        return;
    }

    await nextTick();

    if (!mapContainer.value) {
        mapError.value = 'Map container is not available.';
        return;
    }

    mapboxgl.accessToken = mapboxToken;

    const mapOptions = {
        container: mapContainer.value,
        style: 'mapbox://styles/mapbox/satellite-streets-v12',
        center: [centerLng, centerLat],
        zoom: initialZoom,
        minZoom,
        maxZoom,
        bearing: 0,
        pitch: 0,
        dragRotate: false,
        pitchWithRotate: false,
        cooperativeGestures: true,
        attributionControl: true,
    };

    if (hasValidBounds.value) {
        mapOptions.maxBounds = [
            [Number(southwestLng), Number(southwestLat)],
            [Number(northeastLng), Number(northeastLat)],
        ];
    }

    mapInstance.value = new mapboxgl.Map(mapOptions);

    mapInstance.value.addControl(
        new mapboxgl.NavigationControl({
            visualizePitch: false,
            showCompass: false,
        }),
        'top-right'
    );

    mapInstance.value.scrollZoom.setWheelZoomRate(1 / 350);
    mapInstance.value.dragRotate.disable();

    if (mapInstance.value.touchZoomRotate) {
        mapInstance.value.touchZoomRotate.disableRotation();
    }

    mapInstance.value.on('load', () => {
        mapInstance.value.resize();
        addMarkers();
    });

    mapInstance.value.on('moveend', () => {
        mapInstance.value.setBearing(0);
        mapInstance.value.setPitch(0);
    });
};

onMounted(() => {
    initializeMap();
});

onBeforeUnmount(() => {
    clearMarkers();

    if (mapInstance.value) {
        mapInstance.value.remove();
    }
});
</script>

<template>
    <PublicLayout>
        <Head title="Map of Verified Boarding Houses near TPC | E-BoardMate">
            <meta
                name="description"
                content="View verified boarding houses near Talibon Polytechnic College using the E-BoardMate interactive satellite-streets map."
            >
        </Head>

        <section class="py-5">
            <div class="container">
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-11">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                            <div>
                                <span class="badge rounded-pill badge-soft-green mb-3">
                                    Public Map
                                </span>

                                <h1 class="fw-bold mb-2">
                                    Verified Boarding Houses Near Talibon Polytechnic College
                                </h1>

                                <p class="ebm-muted mb-0">
                                    Explore approved boarding houses using a satellite-streets map centered on Talibon Polytechnic College.
                                </p>
                            </div>

                            <div>
                                <Link href="/" class="btn btn-ebm-outline">
                                    Back to Home
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-11">
                        <div class="ebm-card p-3 p-md-4">
                            <div
                                v-if="mapError"
                                class="alert alert-warning mb-3"
                            >
                                {{ mapError }}
                            </div>

                            <div class="map-help-bar mb-3">
                                <div>
                                    <strong>Map Tip:</strong>
                                    Scroll normally on the page. Use two fingers on mobile or Ctrl + scroll on desktop to zoom the map.
                                </div>

                                <div>
                                    Zoom range: {{ minZoom }} to {{ maxZoom }}
                                </div>
                            </div>

                            <div
                                ref="mapContainer"
                                class="ebm-map"
                            />

                            <!-- IMPROVED MAP STATUS LEGEND -->
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 p-3 border rounded-3 bg-light">
                                <div class="small ebm-muted d-flex align-items-center gap-2">
                                    <span aria-hidden="true" class="fs-5">🗺️</span>
                                    <span><strong class="text-dark">Map Style:</strong> Satellite Streets</span>
                                </div>

                                <div class="small ebm-muted d-flex align-items-center gap-2">
                                    <span aria-hidden="true" class="fs-5">📍</span>
                                    <span><strong class="text-dark">Map Center:</strong> TPC</span>
                                </div>

                                <div class="small ebm-muted d-flex align-items-center gap-2">
                                    <span aria-hidden="true" class="fs-5">✅</span>
                                    <span><strong class="text-dark">Verified Listings:</strong> {{ boardingHouses.length }}</span>
                                </div>

                                <div class="small ebm-muted d-flex align-items-center gap-2">
                                    <span aria-hidden="true" class="fs-5">📏</span>
                                    <span><strong class="text-dark">Distance:</strong> Shown in marker popup</span>
                                </div>
                            </div>

                            <div
                                v-if="!hasBoardingHouses"
                                class="alert alert-light border mt-4 mb-0"
                            >
                                No verified boarding houses are available on the map yet. Once the admin approves listings with coordinates, they will appear here.
                            </div>

                            <div
                                v-if="!hasValidBounds"
                                class="alert alert-info mt-4 mb-0"
                            >
                                Boundary coordinates are still placeholders. The map is usable now, but focus-bound panning will be finalized after you provide the Southwest and Northeast coordinates.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>