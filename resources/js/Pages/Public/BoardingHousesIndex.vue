<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import LazyViewport from '@/Components/LazyViewport.vue';
import { ref, onMounted, computed } from 'vue';

const page = usePage();

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
const mapboxToken = computed(() => {
    const token = page.props.mapbox_token || import.meta.env.VITE_MAPBOX_TOKEN || '';
    if (!token || token === 'your_public_mapbox_token_here') {
        console.warn('⚠️ Mapbox Warning: No valid Mapbox token provided in Inertia shared props or environment variables.');
    }
    return token;
});

const centerLat = Number(import.meta.env.VITE_MAP_CENTER_LAT || 10.1167);
const centerLng = Number(import.meta.env.VITE_MAP_CENTER_LNG || 124.2833);

const realRoutes = ref({});

const fetchRealDistances = async () => {
    if (!mapboxToken.value || mapboxToken.value === 'your_public_mapbox_token_here') return;

    await Promise.all(props.boardingHouses.map(async (house) => {
        if (!house.longitude || !house.latitude) return;

        realRoutes.value[house.id] = { loading: true };
        
        const url = `https://api.mapbox.com/directions/v5/mapbox/walking/${house.longitude},${house.latitude};${centerLng},${centerLat}?access_token=${mapboxToken.value}`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.routes && data.routes.length > 0) {
                const route = data.routes[0];
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

onMounted(() => {
    fetchRealDistances();
});
</script>

<template>
    <Head>
        <title>Boarding House Catalog | E-BoardMate</title>
        <meta name="description" content="Explore verified boarding houses near Talibon Polytechnic College. View room availability, walking distances, and reserve instantly." />
    </Head>

    <PublicLayout>
        <section class="py-4 py-md-5 bg-body-tertiary min-vh-100">
            <div class="container px-3 px-md-4">
                
                <!-- Native Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold mb-2">
                            Verified Accommodations
                        </span>
                        <h1 class="fw-bold mb-1 text-body-emphasis" style="font-size: 1.75rem;">Explore Places</h1>
                        <p class="text-body-secondary mb-0 small">Find the perfect boarding house near campus.</p>
                    </div>
                    <div>
                        <Link href="/" class="btn btn-light bg-body border-secondary-subtle text-body-emphasis shadow-sm rounded-pill px-4 fw-medium">
                            <i class="bi bi-arrow-left me-1"></i> Back to Home
                        </Link>
                    </div>
                </div>
                
                <!-- 🚀 NATIVE PROPERTY GRID WITH LAZY VIEWPORT RENDERING -->
                <div class="row g-4 justify-content-center justify-content-md-start">
                    <div v-for="house in boardingHouses" :key="house.id" class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        
                        <LazyViewport min-height="420px" root-margin="200px 0px">
                            <!-- 🚀 SKELETON FALLBACK SLOT (Prevents Cumulative Layout Shift CLS) -->
                            <template #fallback>
                                <div class="card border-0 rounded-4 shadow-sm h-100 overflow-hidden placeholder-glow bg-body" style="min-height: 420px;">
                                    <div class="placeholder bg-secondary bg-opacity-20 w-100" style="height: 220px;"></div>
                                    <div class="card-body p-4 d-flex flex-column gap-2">
                                        <div class="placeholder bg-secondary bg-opacity-20 col-8 rounded py-2"></div>
                                        <div class="placeholder bg-secondary bg-opacity-20 col-5 rounded py-1"></div>
                                        <div class="d-flex gap-2 my-2">
                                            <div class="placeholder bg-secondary bg-opacity-20 col-4 rounded-pill py-2"></div>
                                            <div class="placeholder bg-secondary bg-opacity-20 col-4 rounded-pill py-2"></div>
                                        </div>
                                        <div class="placeholder bg-secondary bg-opacity-20 col-12 rounded-pill py-3 mt-auto"></div>
                                    </div>
                                </div>
                            </template>

                            <!-- 🚀 LAZY MOUNTED PROPERTY CARD -->
                            <article class="native-property-card bg-body shadow-sm border-0 rounded-4 overflow-hidden position-relative h-100 d-flex flex-column">
                                
                                <!-- Image Header -->
                                <div class="position-relative">
                                    <Link :href="`/boarding-houses/${house.slug}`" class="d-block overflow-hidden">
                                        <img v-if="house.photos?.length && getImageUrl(house.photos[0])" 
                                             :src="getImageUrl(house.photos[0])" 
                                             class="w-100 object-fit-cover img-hover-zoom transition-all" 
                                             alt="Boarding House Image"
                                             loading="lazy"
                                             style="height: 220px;">

                                        <div v-else class="w-100 bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center" style="height: 220px;">
                                            <i class="bi bi-house-slash text-secondary opacity-50 display-4"></i>
                                        </div>
                                    </Link>

                                    <!-- Floating Badge -->
                                    <div class="position-absolute top-0 start-0 m-3 z-1">
                                        <span v-if="house.is_full" class="badge bg-danger shadow-sm rounded-pill px-3 py-2">Fully Booked</span>
                                        <span v-else class="badge bg-body text-success fw-bold shadow-sm rounded-pill px-3 py-2 border border-secondary-subtle">
                                            <i class="bi bi-circle-fill small me-1" style="font-size: 0.5rem;"></i> Available
                                        </span>
                                    </div>
                                </div>

                                <!-- Content Body -->
                                <div class="p-3 p-md-4 d-flex flex-column flex-grow-1">
                                    
                                    <!-- Title & Price -->
                                    <div class="d-flex justify-content-between align-items-start mb-1 gap-2">
                                        <Link :href="`/boarding-houses/${house.slug}`" class="text-decoration-none text-body-emphasis" style="min-width: 0;">
                                            <h2 class="h5 fw-bold mb-0 text-truncate pb-1">{{ house.name }}</h2>
                                        </Link>
                                        <div class="text-end flex-shrink-0 mt-1">
                                            <span class="h6 fw-bold text-success lh-1 mb-0 d-block">₱{{ house.rent_price }}</span>
                                            <span class="small text-body-secondary" style="font-size: 0.65rem; text-transform: uppercase;">/ month</span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-body-secondary small mb-3 text-truncate">
                                        <i class="bi bi-geo-alt-fill text-danger me-1 opacity-75"></i> {{ house.address }}
                                    </p>
                                    
                                    <!-- Native Stats Pills -->
                                    <div class="d-flex align-items-center gap-2 mb-4 pb-1">
                                        <div class="badge bg-body-tertiary text-body-emphasis border border-secondary-subtle rounded-pill px-2 py-1 fw-medium d-flex align-items-center gap-1 shadow-sm">
                                            <i class="bi bi-door-open text-primary"></i>
                                            <span :class="house.is_full ? 'text-danger' : ''">{{ house.available_rooms }} Rooms</span>
                                        </div>

                                        <div class="badge bg-body-tertiary text-body-emphasis border border-secondary-subtle rounded-pill px-2 py-1 fw-medium d-flex align-items-center gap-1 shadow-sm">
                                            <i class="bi bi-person-walking text-primary"></i>
                                            <span v-if="realRoutes[house.id] && !realRoutes[house.id].loading && !realRoutes[house.id].error">
                                                {{ realRoutes[house.id].duration }} min
                                            </span>
                                            <span v-else>
                                                {{ house.estimated_walking_mins }} min
                                                <span v-if="realRoutes[house.id]?.loading" class="spinner-border spinner-border-sm text-primary ms-1" style="width: 0.6rem; height: 0.6rem;" role="status"></span>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Actions -->
                                    <div class="d-flex gap-2 mt-auto pt-3 border-top border-secondary-subtle">
                                        <Link :href="`/boarding-houses/${house.slug}`" class="btn btn-native-primary rounded-pill fw-bold flex-grow-1 py-2 shadow-sm text-center">
                                            Reserve Now
                                        </Link>
                                        <Link :href="`/map?house_id=${house.id}`" class="btn btn-outline-secondary rounded-pill d-flex align-items-center justify-content-center px-3 py-2 shadow-sm fw-semibold text-nowrap" title="View on Interactive Map">
                                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                                            <span>View Map</span>
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        </LazyViewport>

                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!boardingHouses.length" class="text-center text-body-secondary mt-5 p-5 bg-body border border-secondary-subtle rounded-4 shadow-sm">
                    <i class="bi bi-house-x display-1 mb-3 d-block opacity-25"></i>
                    <h3 class="h4 fw-bold text-body-emphasis mb-2">No listings available</h3>
                    <p class="mb-0">Check back later for new boarding houses in your area.</p>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

<style scoped>
.native-property-card {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease;
}

.native-property-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
}

.img-hover-zoom {
    transition: transform 0.4s ease;
}

.native-property-card:hover .img-hover-zoom {
    transform: scale(1.05);
}

.btn-native-primary {
    background-color: #10b981;
    color: white;
    border: none;
    transition: all 0.2s ease;
}

.btn-native-primary:hover {
    background-color: #059669;
    color: white;
    transform: translateY(-1px);
}
</style>