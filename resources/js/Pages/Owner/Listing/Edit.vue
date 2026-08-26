<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const isLoading = ref(false);
let unhookStart = null;
let unhookFinish = null;

onMounted(() => {
    unhookStart = router.on('start', () => isLoading.value = true);
    unhookFinish = router.on('finish', () => isLoading.value = false);
});

onUnmounted(() => {
    if (unhookStart) unhookStart();
    if (unhookFinish) unhookFinish();
});

const props = defineProps({
    boardingHouse: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success || null);
const photoInput = ref(null);
const photoPreview = ref(null); 

const form = useForm({
    description: props.boardingHouse?.description || '',
    location_description: props.boardingHouse?.location_description || '',
    address: props.boardingHouse?.address || '',
    rent_price: props.boardingHouse?.rent_price || 0,
    total_rooms: props.boardingHouse?.total_rooms || 0,
    available_rooms: props.boardingHouse?.available_rooms || 0,
    total_bedspaces: props.boardingHouse?.total_bedspaces || 0,
    available_bedspaces: props.boardingHouse?.available_bedspaces || 0,
    amenities_text: props.boardingHouse?.amenities?.join(', ') || '',
    rules: props.boardingHouse?.rules || '',
    allowed_genders: props.boardingHouse?.allowed_genders || 'Any Gender (All)',
    includes_water: props.boardingHouse?.includes_water ?? false,
    includes_electricity: props.boardingHouse?.includes_electricity ?? false,
});

const photoForm = useForm({
    photo: null,
    alt_text: '',
});

const primaryForm = useForm({});
const deleteForm = useForm({});

const submitListing = () => {
    form.put('/owner/listing', {
        preserveScroll: true,
    });
};

const submitPhoto = () => {
    photoForm.post('/owner/listing/photos', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            photoForm.reset();
            photoPreview.value = null; 
            if (photoInput.value) {
                photoInput.value.value = '';
            }
        },
    });
};

const setPhotoFile = (event) => {
    const file = event.target.files[0] || null;
    photoForm.clearErrors('photo');

    if (file) {
        if (file.size > 4 * 1024 * 1024) {
            photoForm.setError('photo', 'Mobile photo is too large. Please choose an image under 4MB.');
            photoForm.photo = null;
            photoPreview.value = null;
            if (event.target) event.target.value = '';
            return;
        }
        photoForm.photo = file;
        photoPreview.value = URL.createObjectURL(file);
    } else {
        photoForm.photo = null;
        photoPreview.value = null;
    }
};

const triggerPhotoSelect = () => {
    if (photoInput.value) {
        photoInput.value.click();
    }
};

const setPrimaryPhoto = (photo) => {
    primaryForm.post(photo.set_primary_url, {
        preserveScroll: true,
    });
};

const deletePhoto = (photo) => {
    if (!confirm('Are you sure you want to delete this photo?')) {
        return;
    }

    deleteForm.delete(photo.delete_url, {
        preserveScroll: true,
    });
};

// UI: Native Soft Badges
const statusBadgeClass = computed(() => {
    if (!props.boardingHouse) return 'badge-soft-secondary';
    if (props.boardingHouse.status === 'approved') return 'badge-soft-success';
    if (props.boardingHouse.status === 'pending') return 'badge-soft-warning';
    if (props.boardingHouse.status === 'rejected') return 'badge-soft-danger';
    if (props.boardingHouse.status === 'deactivated') return 'badge-soft-secondary';
    return 'badge-soft-secondary';
});
</script>

<template>
    <OwnerLayout>
        <Head title="My Listing | E-BoardMate" />

        <div class="container-fluid pb-5 px-0 px-md-3 max-w-desktop mx-auto">
            
            <!-- ALERTS -->
            <div v-if="flashSuccess" class="alert alert-success mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4">
                {{ flashSuccess }}
            </div>

            <!-- NATIVE HEADER SECTION -->
            <header class="d-flex justify-content-between align-items-center px-3 px-md-0 mb-4 pt-3">
                <div>
                    <h1 class="fw-bold mb-0 text-body-emphasis" style="font-size: 1.75rem;">Property</h1>
                    <span class="small text-body-secondary">Manage your listing</span>
                </div>
                <!-- Action Icon -->
                <button class="btn btn-light bg-body shadow-sm border border-secondary-subtle rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 45px; height: 45px;">
                    <i class="bi bi-house-gear text-body-emphasis fs-5"></i>
                </button>
            </header>

            <!-- NO BOARDING HOUSE ASSIGNED -->
            <section v-if="!boardingHouse" class="mx-3 mx-md-0 ebm-card p-4 p-md-5 text-center shadow-sm rounded-4">
                <div class="fs-1 mb-3">🏠</div>
                <h2 class="h4 fw-bold mb-2">No assigned property</h2>
                <p class="text-body-secondary mb-0">Your owner account does not have an assigned boarding house listing yet. Please contact the super admin.</p>
            </section>

            <!-- 🚀 SKELETON LOADING STATE -->
            <div v-if="isLoading" class="px-3 px-md-0 placeholder-glow mb-4">
                <div class="row g-4">
                    <div class="col-lg-5">
                        <div class="ebm-card p-4 rounded-4 shadow-sm border border-secondary-subtle bg-body mb-4">
                            <span class="placeholder col-8 py-3 rounded mb-3 d-block bg-secondary bg-opacity-25"></span>
                            <span class="placeholder w-100 py-5 rounded-4 d-block bg-secondary bg-opacity-25 mb-3" style="height: 180px;"></span>
                            <span class="placeholder col-6 py-2 rounded-pill d-block bg-secondary bg-opacity-25"></span>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="ebm-card p-4 rounded-4 shadow-sm border border-secondary-subtle bg-body">
                            <span class="placeholder col-6 py-3 rounded mb-4 d-block bg-secondary bg-opacity-25"></span>
                            <div v-for="i in 4" :key="i" class="mb-3">
                                <span class="placeholder col-4 py-1 rounded mb-2 d-block bg-secondary bg-opacity-25"></span>
                                <span class="placeholder w-100 py-3 rounded bg-secondary bg-opacity-25 d-block"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="boardingHouse" class="row g-4 px-3 px-md-0 m-0 w-100">
                
                <!-- LEFT COLUMN: STATUS & PHOTOS -->
                <div class="col-lg-5 p-0 pe-lg-3">
                    
                    <!-- Hero Status Card -->
                    <div class="bg-body p-4 rounded-4 shadow-sm mb-4 border border-secondary-subtle">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h2 class="h5 fw-bold mb-0 text-body-emphasis pe-3">{{ boardingHouse.name }}</h2>
                            <span class="badge rounded-pill px-3 py-2 shadow-sm" :class="statusBadgeClass">
                                {{ boardingHouse.status }}
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-2 mb-4">
                            <span v-if="boardingHouse.is_verified" class="badge badge-soft-success rounded-pill px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i> Verified</span>
                            <span v-else class="badge badge-soft-warning rounded-pill px-2 py-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Not Verified</span>
                        </div>

                        <div class="row g-3 bg-body-tertiary p-3 rounded-4 border border-secondary-subtle">
                            <div class="col-6">
                                <span class="small text-body-secondary d-block">Latitude</span>
                                <strong class="font-monospace small">{{ boardingHouse.latitude || 'Missing' }}</strong>
                            </div>
                            <div class="col-6">
                                <span class="small text-body-secondary d-block">Longitude</span>
                                <strong class="font-monospace small">{{ boardingHouse.longitude || 'Missing' }}</strong>
                            </div>
                        </div>

                        <div v-if="boardingHouse.rejection_reason" class="alert alert-danger mt-3 mb-0 rounded-4 border-0 small">
                            <strong>Rejection Reason:</strong> {{ boardingHouse.rejection_reason }}
                        </div>
                        <div v-if="boardingHouse.deactivated_reason" class="alert alert-secondary mt-3 mb-0 rounded-4 border-0 small">
                            <strong>Deactivation Reason:</strong> {{ boardingHouse.deactivated_reason }}
                        </div>
                    </div>

                    <!-- Photo Upload Card -->
                    <div class="bg-body p-4 rounded-4 shadow-sm mb-4 border border-secondary-subtle">
                        <h2 class="h6 fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-cloud-arrow-up text-success fs-5"></i> Upload Photo
                        </h2>

                        <form @submit.prevent="submitPhoto">
                            <div class="mb-3">
                                <label 
                                    for="listing-photo" 
                                    class="btn btn-outline-secondary w-100 py-3 rounded-4 fw-semibold d-flex flex-column align-items-center justify-content-center gap-1 border-dashed bg-body-tertiary position-relative z-2 cursor-pointer"
                                    style="min-height: 90px; touch-action: manipulation;"
                                    @click="triggerPhotoSelect"
                                >
                                    <i class="bi bi-cloud-arrow-up-fill fs-3 text-success"></i>
                                    <span>Select Property Photo</span>
                                    <span class="small text-body-secondary fw-normal">JPG, PNG, WebP up to 4MB</span>
                                </label>
                                <input 
                                    id="listing-photo" 
                                    ref="photoInput" 
                                    type="file" 
                                    class="d-none" 
                                    accept="image/jpeg,image/png,image/webp" 
                                    @change="setPhotoFile"
                                >
                                
                                <!-- Instant Local Preview -->
                                <div v-if="photoPreview" class="mt-3 text-center p-2 bg-body-tertiary rounded-4 border border-secondary-subtle overflow-hidden">
                                    <img :src="photoPreview" alt="Selected Preview" class="img-fluid rounded-3" style="max-height: 160px; width: 100%; object-fit: cover;">
                                </div>
                                <div v-if="photoForm.errors.photo" class="invalid-feedback d-block fw-bold ps-2 mt-1">{{ photoForm.errors.photo }}</div>
                            </div>

                            <div class="mb-4">
                                <input id="alt_text" v-model="photoForm.alt_text" type="text" class="form-control bg-body-tertiary rounded-4" :class="{ 'is-invalid': photoForm.errors.alt_text }" placeholder="Alt Text (e.g. Front View)">
                                <div v-if="photoForm.errors.alt_text" class="invalid-feedback fw-bold ps-2">{{ photoForm.errors.alt_text }}</div>
                            </div>

                            <button type="submit" class="btn btn-native-primary rounded-pill w-100 fw-bold shadow-sm py-2" :disabled="photoForm.processing || !photoForm.photo">
                                <span v-if="photoForm.processing"><span class="spinner-border spinner-border-sm me-2"></span>Uploading...</span>
                                <span v-else>Upload Photo</span>
                            </button>
                        </form>
                    </div>

                    <!-- Uploaded Photos Horizontal Gallery -->
                    <div class="bg-body p-4 rounded-4 shadow-sm mb-4 mb-lg-0 border border-secondary-subtle">
                        <div class="d-flex justify-content-between align-items-end mb-3">
                            <div>
                                <h2 class="h6 fw-bold mb-0">Gallery</h2>
                                <p class="text-body-secondary small mb-0 mt-1">Primary photo appears first.</p>
                            </div>
                            <span class="badge bg-body-tertiary text-body-secondary border border-secondary-subtle rounded-pill">{{ boardingHouse.photos?.length || 0 }} Photos</span>
                        </div>

                        <div v-if="boardingHouse.photos && boardingHouse.photos.length" class="native-gallery-scroll d-flex gap-3 overflow-x-auto pb-3 hide-scrollbar">
                            
                            <div v-for="photo in boardingHouse.photos" :key="photo.id" class="gallery-card flex-shrink-0 position-relative rounded-4 overflow-hidden border border-secondary-subtle bg-body-tertiary">
                                
                                <!-- Primary Badge -->
                                <span v-if="photo.is_primary" class="badge badge-soft-success position-absolute top-0 start-0 m-2 rounded-pill shadow-sm" style="z-index: 10;">Primary</span>
                                
                                <img :src="photo.url" :alt="photo.alt_text || boardingHouse.name" class="gallery-image w-100 object-fit-cover" style="height: 140px;">
                                
                                <!-- 🚀 FIX: Adjusted Gap, added Delete text, widened cards -->
                                <div class="p-2 d-flex gap-2">
                                    <button v-if="!photo.is_primary" type="button" class="btn btn-sm btn-light border-secondary-subtle flex-grow-1 rounded-pill" style="font-size: 0.75rem; font-weight: 600;" :disabled="primaryForm.processing" @click="setPrimaryPhoto(photo)">
                                        Set Primary
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger flex-grow-1 rounded-pill d-flex align-items-center justify-content-center gap-1" :disabled="deleteForm.processing" @click="deletePhoto(photo)" style="font-size: 0.75rem; font-weight: 600;">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            </div>

                        </div>
                        
                        <div v-else class="text-center p-4 bg-body-tertiary rounded-4 border border-secondary-subtle">
                            <div class="fs-2 mb-2 opacity-50">🖼️</div>
                            <h3 class="h6 fw-bold mb-1">No photos yet</h3>
                            <p class="text-body-secondary small mb-0">Upload a photo above.</p>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: EDIT LISTING FORM -->
                <div class="col-lg-7 p-0 ps-lg-3">
                    <div class="bg-body p-4 p-md-5 rounded-4 shadow-sm border border-secondary-subtle">
                        <h2 class="h5 fw-bold mb-4 d-flex align-items-center gap-2">
                            <i class="bi bi-pencil-square text-success fs-4"></i> Edit Listing Details
                        </h2>

                        <form @submit.prevent="submitListing">
                            
                            <!-- Address & Rent -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label for="address" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">Full Address</label>
                                    <input id="address" v-model="form.address" type="text" class="form-control bg-body-tertiary rounded-4 py-2" :class="{ 'is-invalid': form.errors.address }" placeholder="Talibon, Bohol">
                                    <div v-if="form.errors.address" class="invalid-feedback fw-bold ps-2">{{ form.errors.address }}</div>
                                </div>
                                
                                <div class="col-12">
                                    <label for="rent_price" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">Monthly Rent (₱)</label>
                                    <input id="rent_price" v-model="form.rent_price" type="number" step="0.01" min="0" class="form-control bg-body-tertiary rounded-4 py-2 text-success fw-bold" :class="{ 'is-invalid': form.errors.rent_price }">
                                    <div v-if="form.errors.rent_price" class="invalid-feedback fw-bold ps-2">{{ form.errors.rent_price }}</div>
                                </div>
                            </div>

                            <!-- Descriptions -->
                            <div class="mb-4">
                                <label for="description" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">Description</label>
                                <textarea id="description" v-model="form.description" class="form-control bg-body-tertiary rounded-4 py-2" :class="{ 'is-invalid': form.errors.description }" rows="4" placeholder="Describe your boarding house..."></textarea>
                                <div v-if="form.errors.description" class="invalid-feedback fw-bold ps-2">{{ form.errors.description }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="location_description" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">Location Landmarks</label>
                                <textarea id="location_description" v-model="form.location_description" class="form-control bg-body-tertiary rounded-4 py-2" :class="{ 'is-invalid': form.errors.location_description }" rows="2" placeholder="e.g., near TPC gate..."></textarea>
                                <div v-if="form.errors.location_description" class="invalid-feedback fw-bold ps-2">{{ form.errors.location_description }}</div>
                            </div>

                            <!-- Rooms & Bedspaces Grid -->
                            <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mb-4">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <label for="total_rooms" class="form-label fw-bold small text-body-secondary mb-1">Total Rooms</label>
                                        <input id="total_rooms" v-model="form.total_rooms" type="number" min="0" class="form-control rounded-pill text-center" :class="{ 'is-invalid': form.errors.total_rooms }">
                                    </div>
                                    <div class="col-6">
                                        <label for="available_rooms" class="form-label fw-bold small text-body-secondary mb-1">Available Rooms</label>
                                        <input id="available_rooms" v-model="form.available_rooms" type="number" min="0" class="form-control rounded-pill text-center" :class="{ 'is-invalid': form.errors.available_rooms }">
                                    </div>
                                    <div class="col-6">
                                        <label for="total_bedspaces" class="form-label fw-bold small text-body-secondary mb-1">Total Beds</label>
                                        <input id="total_bedspaces" v-model="form.total_bedspaces" type="number" min="0" class="form-control rounded-pill text-center" :class="{ 'is-invalid': form.errors.total_bedspaces }">
                                    </div>
                                    <div class="col-6">
                                        <label for="available_bedspaces" class="form-label fw-bold small text-body-secondary mb-1">Available Beds</label>
                                        <input id="available_bedspaces" v-model="form.available_bedspaces" type="number" min="0" class="form-control rounded-pill text-center" :class="{ 'is-invalid': form.errors.available_bedspaces }">
                                    </div>
                                </div>
                            </div>

                            <!-- Gender Restrictions & Utilities -->
                            <div class="mb-4">
                                <label for="allowed_genders" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">Gender Restriction</label>
                                <select id="allowed_genders" v-model="form.allowed_genders" class="form-select bg-body-tertiary rounded-pill py-2" :class="{ 'is-invalid': form.errors.allowed_genders }">
                                    <option value="Any Gender (All)">Any Gender (All)</option>
                                    <option value="Male Only">Male Only</option>
                                    <option value="Female Only">Female Only</option>
                                    <option value="Co-ed">Co-ed</option>
                                </select>
                                <div v-if="form.errors.allowed_genders" class="invalid-feedback fw-bold ps-2">{{ form.errors.allowed_genders }}</div>
                            </div>

                            <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mb-4">
                                <span class="form-label fw-bold small text-body-secondary text-uppercase d-block mb-2">Utility Inclusions</span>
                                <div class="d-flex flex-wrap gap-4">
                                    <div class="form-check form-switch">
                                        <input id="includes_water" v-model="form.includes_water" class="form-check-input" type="checkbox">
                                        <label for="includes_water" class="form-check-label fw-medium">💧 Water Included</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input id="includes_electricity" v-model="form.includes_electricity" class="form-check-input" type="checkbox">
                                        <label for="includes_electricity" class="form-check-label fw-medium">⚡ Electricity Included</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Amenities & Rules -->
                            <div class="mb-4">
                                <label for="amenities_text" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">Amenities (Comma separated)</label>
                                <input id="amenities_text" v-model="form.amenities_text" type="text" class="form-control bg-body-tertiary rounded-pill py-2" :class="{ 'is-invalid': form.errors.amenities_text }" placeholder="WiFi, Water, Study Area">
                                <div v-if="form.errors.amenities_text" class="invalid-feedback fw-bold ps-2">{{ form.errors.amenities_text }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="rules" class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1">House Rules</label>
                                <textarea id="rules" v-model="form.rules" class="form-control bg-body-tertiary rounded-4 py-2" :class="{ 'is-invalid': form.errors.rules }" rows="3" placeholder="Curfew, visitor rules, cleanliness..."></textarea>
                                <div v-if="form.errors.rules" class="invalid-feedback fw-bold ps-2">{{ form.errors.rules }}</div>
                            </div>

                            <button type="submit" class="btn btn-native-primary rounded-pill w-100 fw-bold shadow-sm py-3 mt-2" :disabled="form.processing">
                                <span v-if="form.processing"><span class="spinner-border spinner-border-sm me-2"></span>Saving Changes...</span>
                                <span v-else>Save Property Details</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </OwnerLayout>
</template>

<style scoped>
/* Restrict max width on desktop */
.max-w-desktop {
    max-width: 1200px;
}

/* =========================================
   NATIVE APP UI COMPONENTS
========================================== */

/* Native-style Badges */
.badge-soft-success { background: rgba(25, 135, 84, 0.15); color: #198754; border: 1px solid rgba(25, 135, 84, 0.2); }
.badge-soft-warning { background: rgba(255, 193, 7, 0.15); color: #b08000; border: 1px solid rgba(255, 193, 7, 0.3); }
.badge-soft-danger { background: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); }
.badge-soft-secondary { background: rgba(108, 117, 125, 0.15); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.2); }

/* Native-style Action buttons */
.btn-native-primary {
    background-color: #10b981;
    color: white;
    border: none;
}
.btn-native-primary:hover { background-color: #059669; color: white; }

/* 📱 Mobile Horizontal Photo Gallery */
.native-gallery-scroll {
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

/* 🚀 FIX: Widened Photo Cards to perfectly fit buttons */
.gallery-card {
    width: 260px; /* Increased from 220px */
    scroll-snap-align: start;
}

/* Clean Custom Form Control Shadows */
.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
    border-color: #10b981;
}
</style>