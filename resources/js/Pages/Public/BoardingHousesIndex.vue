<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { ref, onMounted } from 'vue';

const props = defineProps({
    boardingHouses: {
        type: Array,
        required: true
    }
});

const getImageUrl = (photo) => {
    if (!photo || !photo.url) return null; 
    if (photo.url.startsWith('http')) return photo.url; 
    const cleanPath = photo.url.replace(/^storage\//, '');
    return `/storage/${cleanPath}`;
};

// --- REAL-TIME MAPBOX DISTANCE LOGIC ---
const mapboxToken = import.meta.env.VITE_MAPBOX_TOKEN || '';
const centerLat = Number(import.meta.env.VITE_MAP_CENTER_LAT || 10.1167);
const centerLng = Number(import.meta.env.VITE_MAP_CENTER_LNG || 124.2833);

// We store the real Mapbox distances in this reactive object by house.id
const realRoutes = ref({});

const fetchRealDistances = async () => {
    if (!mapboxToken || mapboxToken === 'your_public_mapbox_token_here') return;

    // Loop through all boarding houses and fetch their real walking distance
    await Promise.all(props.boardingHouses.map(async (house) => {
        // Skip if latitude/longitude is missing from the controller payload
        if (!house.longitude || !house.latitude) return;

        // Set loading state for this specific house
        realRoutes.value[house.id] = { loading: true };
        
        // Exact same API call used in Map.vue
        const url = `https://api.mapbox.com/directions/v5/mapbox/walking/${house.longitude},${house.latitude};${centerLng},${centerLat}?access_token=${mapboxToken}`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.routes && data.routes.length > 0) {
                const route = data.routes[0];
                // Update with the real road data
                realRoutes.value[house.id] = {
                    distance: (route.distance / 1000).toFixed(2),
                    duration: Math.ceil(route.duration / 60),
                    loading: false,
                    error: false
                };
            } else {
                realRoutes.value[house.id] = { loading: false, error: true };
            }
        } catch (error) {
            realRoutes.value[house.id] = { loading: false, error: true };
        }
    }));
};

// Trigger the Mapbox API calls as soon as the page loads
onMounted(() => {
    fetchRealDistances();
});
</script>

<template>
    <PublicLayout>
        <Head>
            <title>Available Boarding Houses | E-BoardMate</title>
            <meta name="description" content="Browse verified boarding houses near Talibon Polytechnic College. Find your perfect room, view locations on the map, and reserve online." />
        </Head>

        <section class="py-5 bg-body min-vh-100">
            <div class="container">
                
                <!-- Aligned Header with Back Button (Consistent with Map Page) -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
                            <div>
                                <span class="badge rounded-pill border border-success-subtle bg-body text-success shadow-sm mb-2 px-3 py-2 transition-all">
                                    Property Listings
                                </span>
                                <h1 class="fw-bold mb-1 text-body-emphasis transition-all">Available Boarding Houses</h1>
                                <p class="text-body-secondary mb-0 transition-all">Choose a boarding house to view its details, or find it on the map.</p>
                            </div>
                            <div>
                                <Link href="/" class="btn bg-body border-secondary-subtle text-body-emphasis shadow-sm transition-all hover-bg-tertiary">
                                    Back to Home
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 justify-content-center justify-content-md-start">
                    <div v-for="house in boardingHouses" :key="house.id" class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        
                        <article class="card h-100 shadow-sm border-secondary-subtle bg-body-tertiary rounded-4 overflow-hidden hover-lift transition-all position-relative">
                            
                            <div class="position-absolute top-0 end-0 m-2 z-1">
                                <span v-if="house.is_full" class="badge bg-danger shadow-sm">Fully Booked</span>
                                <span v-else class="badge bg-success shadow-sm">Available</span>
                            </div>

                            <img v-if="house.photos?.length && getImageUrl(house.photos[0])" 
                                 :src="getImageUrl(house.photos[0])" 
                                 class="card-img-top" 
                                 alt="Boarding House Image"
                                 style="height: 200px; object-fit: cover;">

                            <div v-else class="card-img-top bg-secondary-subtle d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-image text-secondary fs-1"></i>
                                <span class="visually-hidden">No image available</span>
                            </div>

                            <div class="card-body d-flex flex-column p-3">
                                <h2 class="card-title h6 fw-bold text-truncate text-body-emphasis mb-1">{{ house.name }}</h2>
                                
                                <p class="card-text text-body-secondary small mb-3 text-truncate">
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i> {{ house.address }}
                                </p>
                                
                                <div class="mt-auto">
                                    <!-- Dynamic Rooms & Distance -->
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary-subtle">
                                        <div class="text-body-secondary small fw-medium" style="font-size: 0.8rem;">
                                            <i class="bi bi-door-open me-1"></i>
                                            <span :class="house.is_full ? 'text-danger' : 'text-body-emphasis'">
                                                {{ house.available_rooms }} Room(s)
                                            </span>
                                        </div>
                                        
                                        <!-- DYNAMIC MAPBOX WALKING DISTANCE & TIME -->
                                        <div class="text-body-secondary small fw-medium text-truncate text-end" style="font-size: 0.8rem; max-width: 55%;">
                                            <i class="bi bi-person-walking text-primary me-1"></i>
                                            
                                            <!-- Show exact Mapbox data if it successfully loaded -->
                                            <span v-if="realRoutes[house.id] && !realRoutes[house.id].loading && !realRoutes[house.id].error">
                                                ~{{ realRoutes[house.id].duration }} min | {{ realRoutes[house.id].distance }} km
                                            </span>
                                            
                                            <!-- Fallback to backend data while loading Mapbox data -->
                                            <span v-else>
                                                ~{{ house.estimated_walking_mins }} min | {{ house.estimated_distance_km }} km
                                                <!-- Tiny loading spinner while Mapbox API is calculating -->
                                                <span v-if="realRoutes[house.id]?.loading" class="spinner-border spinner-border-sm text-primary ms-1" style="width: 0.7rem; height: 0.7rem;" role="status"></span>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="mb-3 d-flex justify-content-between align-items-end">
                                        <span class="text-body-secondary small" style="font-size: 0.8rem;">Starting at</span>
                                        <span class="fw-bold text-success mb-0 lh-1">
                                            ₱{{ house.rent_price }} <small class="text-body-secondary fw-normal" style="font-size: 0.75rem;">/ mo</small>
                                        </span>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <Link :href="`/boarding-houses/${house.slug}`" class="btn btn-primary btn-sm fw-medium">
                                            Reserve Now
                                        </Link>
                                        <Link :href="`/map?house_id=${house.id}`" class="btn btn-outline-secondary btn-sm fw-medium">
                                            View on Map
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div v-if="!boardingHouses.length" class="text-center text-body-secondary mt-5 p-5 bg-body-tertiary border border-secondary-subtle rounded-4">
                    <i class="bi bi-house-x display-4 mb-3 d-block"></i>
                    <h3 class="h4 text-body-emphasis">No boarding houses found</h3>
                    <p>Check back later for new listings.</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.transition-all {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out, background-color 0.3s ease-in-out, color 0.3s ease-in-out;
}
.hover-lift:hover {
    transform: translateY(-4px);
    box-shadow: 0 .25rem .75rem rgba(0,0,0,.1)!important;
}
.hover-bg-tertiary:hover {
    background-color: var(--bs-tertiary-bg) !important;
}
</style>