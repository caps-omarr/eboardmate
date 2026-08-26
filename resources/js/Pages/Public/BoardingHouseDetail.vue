<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Modal } from 'bootstrap';
import { computed, nextTick, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    boardingHouse: {
        type: Object,
        required: true,
    },
});

const page = usePage();

// --- 📸 LIGHTBOX & SLIDER STATE LOGIC ---
const activePhotoIndex = ref(0);
const sliderRef = ref(null);

const scrollSlider = (direction) => {
    if (sliderRef.value) {
        sliderRef.value.scrollBy({ left: direction * 320, behavior: 'smooth' });
    }
};

const openLightbox = (index) => {
    activePhotoIndex.value = index;
    const modal = Modal.getOrCreateInstance(document.getElementById('photoLightboxModal'));
    modal.show();
};

const nextPhoto = () => {
    if (activePhotoIndex.value < props.boardingHouse.photos.length - 1) {
        activePhotoIndex.value++;
    } else {
        activePhotoIndex.value = 0;
    }
};

const prevPhoto = () => {
    if (activePhotoIndex.value > 0) {
        activePhotoIndex.value--;
    } else {
        activePhotoIndex.value = props.boardingHouse.photos.length - 1;
    }
};

const handleKeydown = (e) => {
    const modalEl = document.getElementById('photoLightboxModal');
    if (modalEl && modalEl.classList.contains('show')) {
        if (e.key === 'ArrowRight') nextPhoto();
        if (e.key === 'ArrowLeft') prevPhoto();
    }
};

// --- 🧹 MODAL CLEANUP LOGIC ---
const cleanupModalBackdrop = () => {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
};

// --- LIFECYCLE HOOKS & SMART POLLING ---
let pollInterval = null;
const POLLING_RATE = 15000; // 15 seconds

const fetchFreshDetail = () => {
    router.reload({
        only: ['boardingHouse'],
        preserveScroll: true,
        preserveState: true,
    });
};

const startPolling = () => {
    if (!pollInterval) {
        pollInterval = setInterval(fetchFreshDetail, POLLING_RATE);
    }
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

const handleVisibilityChange = () => {
    if (document.hidden) {
        stopPolling();
    } else {
        fetchFreshDetail();
        startPolling();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    startPolling();
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    stopPolling();
    document.removeEventListener('visibilitychange', handleVisibilityChange);
    cleanupModalBackdrop();
});

// --- 📝 RESERVATION LOGIC ---
const reservationForm = useForm({
    full_name: '',
    email: '',
    phone: '',
    preferred_move_in_date: '',
    message: '',
    accepted_terms: false,
});

const resultModalData = ref(null);
const copyButtonText = ref('Copy Code');

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const hasReferenceCode = computed(() => Boolean(resultModalData.value?.reference_code));

const getModalInstance = (modalId) => {
    const modalElement = document.getElementById(modalId);
    if (!modalElement) return null;
    return Modal.getOrCreateInstance(modalElement);
};

const closeModal = (modalId) => {
    const modal = getModalInstance(modalId);
    if (modal) {
        modal.hide();
        setTimeout(cleanupModalBackdrop, 300);
    }
};

const openModal = (modalId) => {
    const modal = getModalInstance(modalId);
    if (modal) modal.show();
};

const showResultModal = async () => {
    closeModal('reservationModal');
    await nextTick();
    setTimeout(() => {
        openModal('reservationResultModal');
    }, 250);
};

const copyReferenceCode = async () => {
    if (!resultModalData.value?.reference_code) return;
    try {
        await navigator.clipboard.writeText(resultModalData.value.reference_code);
        copyButtonText.value = 'Copied!';
        setTimeout(() => copyButtonText.value = 'Copy Code', 2000);
    } catch (error) {
        copyButtonText.value = 'Copy Manually';
    }
};

const submitReservation = () => {
    reservationForm.post(`/boarding-houses/${props.boardingHouse.slug}/reservations`, {
        preserveScroll: true,
        onSuccess: (pageResponse) => {
            const flashData = pageResponse.props.flash?.reservation_result 
                           || pageResponse.props.reservation_result 
                           || page.props.flash?.reservation_result;

            if (flashData) {
                resultModalData.value = flashData;
            } else {
                resultModalData.value = {
                    type: 'success',
                    title: 'Reservation Submitted',
                    message: 'Your reservation request has been submitted successfully. Please check your email for tracking details.',
                    boarding_house_name: props.boardingHouse.name,
                    status: 'Pending',
                    reference_code: null,
                };
            }
            reservationForm.reset();
            showResultModal();
        },
        onError: (errors) => {
            if (errors.reservation) {
                resultModalData.value = {
                    type: 'danger',
                    title: 'Reservation Not Submitted',
                    message: errors.reservation,
                    boarding_house_name: props.boardingHouse.name,
                    status: 'Failed',
                    reference_code: null,
                    tracking_email: reservationForm.email || null,
                };
                showResultModal();
            }
        },
    });
};
</script>

<template>
    <PublicLayout>
        <Head :title="`${boardingHouse.name} | Verified Boarding House near TPC`">
            <meta name="description" :content="`View rent price, available rooms, photos, amenities, and reservation details for ${boardingHouse.name}.`">
        </Head>

        <section class="py-4 py-md-5 bg-body transition-all min-vh-100">
            <div class="container">
                
                <!-- 📌 CONSISTENT BACK BUTTON -->
                <div class="mb-4">
                    <Link href="/boarding-houses" class="btn btn-sm border-secondary-subtle bg-body text-body-emphasis shadow-sm rounded-pill fw-semibold px-3 py-2 transition-all hover-bg-tertiary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-arrow-left text-success fs-6"></i> Back to Boarding Houses
                    </Link>
                </div>

                <div class="row g-4">
                    <!-- LEFT COLUMN: MAIN CONTENT -->
                    <div class="col-lg-8">
                        
                        <!-- 🏠 HERO HEADER CARD -->
                        <div class="ebm-card p-4 p-md-5 mb-4 border border-secondary-subtle rounded-4 shadow-sm bg-body transition-all">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <span v-if="boardingHouse.is_verified" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-semibold">
                                    <i class="bi bi-patch-check-fill me-1"></i> Verified Owner
                                </span>
                                <span :class="boardingHouse.is_full ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-primary-subtle text-primary border-primary-subtle'" class="badge border rounded-pill px-3 py-2 fw-semibold">
                                    {{ boardingHouse.is_full ? '🔴 Full' : '🟢 Available for Rent' }}
                                </span>
                                <span v-if="boardingHouse.allowed_genders" class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-3 py-2 fw-semibold">
                                    <i class="bi bi-person-heart me-1"></i> {{ boardingHouse.allowed_genders }}
                                </span>
                            </div>

                            <h1 class="display-6 fw-bold mb-2 text-body-emphasis tracking-tight transition-all">
                                {{ boardingHouse.name }}
                            </h1>

                            <p class="text-body-secondary d-flex align-items-center flex-wrap gap-2 mb-4 transition-all">
                                <span><i class="bi bi-geo-alt-fill text-danger me-1"></i> {{ boardingHouse.address || 'Talibon, Bohol' }}</span>
                                <span class="text-body-tertiary">•</span>
                                <span class="badge bg-body-tertiary text-body-secondary border border-secondary-subtle rounded-pill px-2 py-1">
                                    <i class="bi bi-person-walking text-success me-1"></i> {{ boardingHouse.estimated_distance_km }} km to TPC Campus
                                </span>
                            </p>

                            <!-- QUICK SPECS GRID -->
                            <div class="row g-3 p-3 bg-body-tertiary rounded-4 border border-secondary-subtle mb-4 transition-all">
                                <div class="col-6 col-md-3 text-center">
                                    <span class="d-block text-body-secondary small mb-1">Monthly Rent</span>
                                    <strong class="text-success fs-5 fw-bold d-block">₱{{ formatPrice(boardingHouse.rent_price) }}</strong>
                                    <span class="small text-body-secondary" style="font-size: 0.75rem;">
                                        {{ 
                                            boardingHouse.includes_water && boardingHouse.includes_electricity ? 'Includes Water & Electricity' :
                                            boardingHouse.includes_water ? 'Includes Water only' :
                                            boardingHouse.includes_electricity ? 'Includes Electricity only' :
                                            'Utilities excluded'
                                        }}
                                    </span>
                                </div>
                                <div class="col-6 col-md-3 text-center border-start border-secondary-subtle">
                                    <span class="d-block text-body-secondary small mb-1">Rooms</span>
                                    <strong class="text-body-emphasis fs-5 fw-bold">{{ boardingHouse.available_rooms }} / {{ boardingHouse.total_rooms }}</strong>
                                </div>
                                <div class="col-6 col-md-3 text-center border-start-md border-secondary-subtle">
                                    <span class="d-block text-body-secondary small mb-1">Bedspaces</span>
                                    <strong class="text-body-emphasis fs-5 fw-bold">{{ boardingHouse.available_bedspaces }} / {{ boardingHouse.total_bedspaces }}</strong>
                                </div>
                                <div class="col-6 col-md-3 text-center border-start border-secondary-subtle">
                                    <span class="d-block text-body-secondary small mb-1">Distance</span>
                                    <strong class="text-body-emphasis fs-5 fw-bold">{{ boardingHouse.estimated_distance_km }} km</strong>
                                </div>
                            </div>

                            <h2 class="h5 fw-bold mb-2 text-body-emphasis">About this Boarding House</h2>
                            <p class="text-body-secondary mb-0 transition-all lh-lg">
                                {{ boardingHouse.description || 'No description has been added for this boarding house yet.' }}
                            </p>
                        </div>

                        <!-- 📸 PHOTOS SECTION (Horizontal Swipe Slider) -->
                        <div class="ebm-card p-4 p-md-5 mb-4 border border-secondary-subtle rounded-4 shadow-sm bg-body transition-all">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h2 class="h4 fw-bold mb-0 text-body-emphasis">Photos</h2>
                                <span v-if="boardingHouse.photos && boardingHouse.photos.length" class="badge rounded-pill bg-body-tertiary text-body-secondary border border-secondary-subtle px-3 py-2 fw-medium">
                                    <i class="bi bi-images me-1 text-primary"></i> {{ boardingHouse.photos.length }} Photos
                                </span>
                            </div>

                            <div v-if="boardingHouse.photos && boardingHouse.photos.length" class="photo-slider-wrapper position-relative">
                                <div ref="sliderRef" class="photo-slider-track d-flex gap-3 overflow-x-auto pb-3 pt-1 hide-scrollbar">
                                    <div 
                                        v-for="(photo, index) in boardingHouse.photos" 
                                        :key="photo.id" 
                                        class="photo-slider-item flex-shrink-0 position-relative rounded-4 overflow-hidden shadow-sm"
                                        @click="openLightbox(index)"
                                    >
                                        <img :src="photo.url" :alt="photo.alt_text || boardingHouse.name" class="photo-slider-img">
                                        
                                        <div class="photo-slider-overlay d-flex align-items-center justify-content-center">
                                            <span class="btn btn-sm btn-light rounded-circle shadow-sm">
                                                <i class="bi bi-arrows-angle-expand"></i>
                                            </span>
                                        </div>

                                        <span v-if="photo.is_primary" class="badge bg-primary position-absolute top-0 start-0 m-3 shadow-sm rounded-pill px-3 py-1">Primary</span>
                                    </div>
                                </div>

                                <!-- Navigation controls for desktop -->
                                <button v-if="boardingHouse.photos.length > 1" @click="scrollSlider(-1)" class="slider-arrow slider-arrow-prev btn btn-light rounded-circle shadow-sm border d-none d-md-flex align-items-center justify-content-center" title="Previous photo">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <button v-if="boardingHouse.photos.length > 1" @click="scrollSlider(1)" class="slider-arrow slider-arrow-next btn btn-light rounded-circle shadow-sm border d-none d-md-flex align-items-center justify-content-center" title="Next photo">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>

                            <div v-else class="empty-state text-center p-4 bg-body-tertiary rounded-4 border border-secondary-subtle">
                                <div class="empty-state-icon fs-2 mb-2">🖼️</div>
                                <h3 class="h6 fw-bold mb-1 text-body-emphasis">No photos uploaded yet</h3>
                                <p class="text-body-secondary small mb-0">Photos will appear here once the boarding house owner uploads images.</p>
                            </div>
                        </div>

                        <!-- 📍 LOCATION & DISTANCE DETAILS -->
                        <div class="ebm-card p-4 p-md-5 mb-4 border border-secondary-subtle rounded-4 shadow-sm bg-body transition-all">
                            <h2 class="h4 fw-bold mb-3 text-body-emphasis">Location & Accessibility</h2>
                            <p class="text-body-secondary mb-4 transition-all">{{ boardingHouse.location_description || 'Conveniently located near Talibon Polytechnic College.' }}</p>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="bg-body-tertiary p-3 rounded-3 border border-secondary-subtle text-center">
                                        <span class="d-block text-body-secondary small mb-1">Estimated Distance</span>
                                        <strong class="text-success fs-5">{{ boardingHouse.estimated_distance_km }} km</strong>
                                        <small class="d-block text-body-secondary">from TPC Campus</small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="bg-body-tertiary p-3 rounded-3 border border-secondary-subtle h-100 d-flex align-items-center justify-content-between">
                                        <div>
                                            <span class="d-block text-body-secondary small mb-1">Interactive Map Navigation</span>
                                            <p class="small text-body-emphasis mb-0">View walking routes and nearby landmarks on our 3D satellite map.</p>
                                        </div>
                                        <Link :href="`/map?house_id=${boardingHouse.id}`" class="btn btn-sm btn-outline-success rounded-pill px-3 text-nowrap fw-semibold">
                                            Open Map <i class="bi bi-box-arrow-up-right ms-1"></i>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 📜 HOUSE RULES -->
                        <div class="ebm-card p-4 p-md-5 border border-secondary-subtle rounded-4 shadow-sm bg-body transition-all">
                            <h2 class="h4 fw-bold mb-3 text-body-emphasis"><i class="bi bi-clipboard-check text-primary me-2"></i> House Rules</h2>
                            <p class="text-body-secondary mb-0 transition-all lh-lg">{{ boardingHouse.rules || 'No specific house rules listed.' }}</p>
                        </div>

                    </div>

                    <!-- RIGHT COLUMN: STICKY RESERVATION SIDEBAR -->
                    <div class="col-lg-4">
                        <div class="ebm-card p-4 border border-secondary-subtle rounded-4 shadow-sm bg-body sticky-detail-card transition-all">
                            
                            <!-- Price Card Header -->
                            <div class="border-bottom border-secondary-subtle pb-3 mb-4">
                                <span class="text-body-secondary small d-block mb-1">Monthly Rent Rate</span>
                                <div class="d-flex align-items-baseline gap-1 mb-3">
                                    <h2 class="display-6 fw-bold text-success mb-0">₱{{ formatPrice(boardingHouse.rent_price) }}</h2>
                                    <span class="text-body-secondary">/ month</span>
                                </div>

                                <!-- Utility Transparency Breakdown -->
                                <div class="bg-body-tertiary p-3 rounded-3 border border-secondary-subtle small d-flex flex-column gap-2">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-body-secondary"><i class="bi bi-droplet-fill text-primary me-1"></i> Water Utility:</span>
                                        <strong v-if="boardingHouse.includes_water" class="text-success">Included</strong>
                                        <span v-else class="text-body-emphasis fw-medium">{{ boardingHouse.water_billing_details || 'Not included' }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="text-body-secondary"><i class="bi bi-lightning-fill text-warning me-1"></i> Electricity:</span>
                                        <strong v-if="boardingHouse.includes_electricity" class="text-success">Included</strong>
                                        <span v-else class="text-body-emphasis fw-medium">{{ boardingHouse.electricity_billing_details || 'Not included' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Info -->
                            <div class="mb-4">
                                <h3 class="h6 fw-bold mb-3 text-body-emphasis">Availability Summary</h3>
                                <ul class="list-group list-group-flush rounded-3 border border-secondary-subtle overflow-hidden mb-3">
                                    <li class="list-group-item bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3">
                                        <span class="text-body-secondary small">Available Rooms</span>
                                        <strong class="text-body-emphasis">{{ boardingHouse.available_rooms }} / {{ boardingHouse.total_rooms }}</strong>
                                    </li>
                                    <li class="list-group-item bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3">
                                        <span class="text-body-secondary small">Available Bedspaces</span>
                                        <strong class="text-body-emphasis">{{ boardingHouse.available_bedspaces }} / {{ boardingHouse.total_bedspaces }}</strong>
                                    </li>
                                    <li class="list-group-item bg-body-tertiary d-flex justify-content-between align-items-center py-2 px-3">
                                        <span class="text-body-secondary small">Current Status</span>
                                        <strong :class="boardingHouse.is_full ? 'text-danger' : 'text-success'">
                                            {{ boardingHouse.is_full ? 'Fully Occupied' : 'Accepting Reservations' }}
                                        </strong>
                                    </li>
                                </ul>
                            </div>

                            <!-- Amenities -->
                            <div class="mb-4">
                                <h3 class="h6 fw-bold mb-3 text-body-emphasis">Included Amenities</h3>
                                <div v-if="boardingHouse.amenities && boardingHouse.amenities.length" class="d-flex flex-wrap gap-2">
                                    <span v-for="amenity in boardingHouse.amenities" :key="amenity" class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-medium">
                                        <i class="bi bi-check-circle-fill me-1"></i> {{ amenity }}
                                    </span>
                                </div>
                                <p v-else class="text-body-secondary small mb-0">No amenities listed yet.</p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex flex-column gap-3 pt-2">
                                <button 
                                    v-if="!boardingHouse.is_full" 
                                    type="button" 
                                    class="btn btn-ebm-primary w-100 rounded-pill py-3 fw-bold shadow transition-all fs-6" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#reservationModal"
                                >
                                    <i class="bi bi-calendar-check-fill me-2"></i> Reserve Now
                                </button>
                                <button v-else type="button" class="btn btn-secondary w-100 rounded-pill py-3 fw-bold" disabled>
                                    Reservation Unavailable (Full)
                                </button>
                            </div>

                            <p class="small text-body-secondary mt-3 mb-0 text-center" style="font-size: 0.8rem;">
                                🔒 Fast & free guest reservation. No student account required.
                            </p>

                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- 🖼️ THE FULLSCREEN LIGHTBOX MODAL -->
        <div id="photoLightboxModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content bg-dark bg-opacity-75 border-0" style="backdrop-filter: blur(10px);">
                    <div class="modal-header border-0 pb-0 position-absolute top-0 end-0 z-3">
                        <button type="button" class="btn-close btn-close-white p-3 m-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body d-flex align-items-center justify-content-center p-0 position-relative">
                        <button v-if="boardingHouse.photos.length > 1" @click="prevPhoto" class="lightbox-nav-btn prev-btn">&#10094;</button>

                        <Transition name="fade" mode="out-in">
                            <img v-if="boardingHouse.photos.length" :key="activePhotoIndex" :src="boardingHouse.photos[activePhotoIndex].url" :alt="boardingHouse.name" class="lightbox-image" />
                        </Transition>

                        <button v-if="boardingHouse.photos.length > 1" @click="nextPhoto" class="lightbox-nav-btn next-btn">&#10095;</button>

                        <div v-if="boardingHouse.photos.length > 1" class="lightbox-counter">
                            {{ activePhotoIndex + 1 }} / {{ boardingHouse.photos.length }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 📝 THE RESERVATION INPUT FORM MODAL -->
        <div id="reservationModal" class="modal fade" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable reservation-dialog">
                <!-- Replaced basic modal-content with ebm-card for unified dark mode background and border -->
                <div class="modal-content ebm-card reservation-modal-content">
                    <form @submit.prevent="submitReservation">
                        <div class="modal-header border-bottom border-secondary">
                            <div class="pe-3">
                                <span class="badge badge-soft-green mb-2">Guest Reservation</span>
                                <h2 id="reservationModalLabel" class="modal-title h5 fw-bold">Reserve at {{ boardingHouse.name }}</h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="cleanupModalBackdrop" />
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-secondary border-secondary reservation-important-alert">
                                <strong>Important:</strong> You can only have one active reservation for the same boarding house.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input id="full_name" v-model="reservationForm.full_name" type="text" class="form-control" :class="{ 'is-invalid': reservationForm.errors.full_name }" placeholder="Enter your full name">
                                    <div v-if="reservationForm.errors.full_name" class="invalid-feedback">{{ reservationForm.errors.full_name }}</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input id="email" v-model="reservationForm.email" type="email" class="form-control" :class="{ 'is-invalid': reservationForm.errors.email }" placeholder="your-gmail@email.com">
                                    <div v-if="reservationForm.errors.email" class="invalid-feedback">{{ reservationForm.errors.email }}</div>
                                    <div class="form-text ebm-muted">This email will be used for tracking notifications.</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input id="phone" v-model="reservationForm.phone" type="text" class="form-control" :class="{ 'is-invalid': reservationForm.errors.phone }" placeholder="09XXXXXXXXX">
                                    <div v-if="reservationForm.errors.phone" class="invalid-feedback">{{ reservationForm.errors.phone }}</div>
                                </div>

                                <div class="col-md-6">
                                    <label for="preferred_move_in_date" class="form-label">Preferred Move-in Date <span class="text-danger">*</span></label>
                                    <input id="preferred_move_in_date" v-model="reservationForm.preferred_move_in_date" type="date" class="form-control" :class="{ 'is-invalid': reservationForm.errors.preferred_move_in_date }">
                                    <div v-if="reservationForm.errors.preferred_move_in_date" class="invalid-feedback">{{ reservationForm.errors.preferred_move_in_date }}</div>
                                </div>

                                <div class="col-12">
                                    <label for="message" class="form-label">Message to Owner</label>
                                    <textarea id="message" v-model="reservationForm.message" class="form-control" :class="{ 'is-invalid': reservationForm.errors.message }" rows="3" placeholder="Optional message, question, or note"></textarea>
                                    <div v-if="reservationForm.errors.message" class="invalid-feedback">{{ reservationForm.errors.message }}</div>
                                </div>

                                <!-- 🛡️ Legal Protection & DPA Compliance Accordions -->
                                <div class="col-12 mt-4">
                                    <div class="accordion custom-legal-accordion shadow-sm" id="legalAccordion">
                                        
                                        <!-- Privacy Notice -->
                                        <!-- Changed borders to use var(--bs-border-color) seamlessly -->
                                        <div class="accordion-item border-bottom-0 rounded-top overflow-hidden">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-3 shadow-none fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrivacy">
                                                    <span class="me-2">🔒</span> Data Privacy Notice
                                                </button>
                                            </h2>
                                            <div id="collapsePrivacy" class="accordion-collapse collapse" data-bs-parent="#legalAccordion">
                                                <div class="accordion-body small ebm-muted pt-3 pb-4">
                                                    <p class="mb-3">By submitting this form, you consent to the collection and processing of your personal information in accordance with the <strong>Data Privacy Act of 2012 (RA 10173)</strong>.</p>
                                                    <ul class="mb-0 ps-3">
                                                        <li class="mb-2"><strong>Purpose of Collection:</strong> Your full name, email address, and phone number are collected exclusively to process your reservation request, facilitate direct communication with the landlord, and provide tracking updates.</li>
                                                        <li class="mb-2"><strong>Information Accuracy:</strong> You are legally required to provide accurate and active contact details. The use of aliases or falsified information will result in the immediate forfeiture of your reservation request.</li>
                                                        <li><strong>Data Security:</strong> E-BoardMate will never sell your data to third parties. Your information is securely encrypted and shared <em>only</em> with the specific boarding house owner you have selected.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Terms & Conditions -->
                                        <div class="accordion-item rounded-bottom overflow-hidden">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed py-3 shadow-none fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTerms">
                                                    <span class="me-2">⚖️</span> Platform Terms & Conditions
                                                </button>
                                            </h2>
                                            <div id="collapseTerms" class="accordion-collapse collapse" data-bs-parent="#legalAccordion">
                                                <div class="accordion-body small ebm-muted pt-3 pb-4">
                                                    <ul class="mb-0 ps-3">
                                                        <li class="mb-2"><strong>Nature of Request:</strong> Submitting this form constitutes a <em>reservation request</em>, not a legally binding lease agreement. Slot allocation is strictly subject to the boarding house owner's final verification and approval.</li>
                                                        <li class="mb-2"><strong>Platform Disclaimer:</strong> E-BoardMate serves solely as an intermediary locator software. We do not manage properties, dictate rental prices, or hold liability for landlord-tenant disputes.</li>
                                                        <li class="mb-2"><strong>System-Wide Limit:</strong> To prevent system abuse and ensure fairness to all students, you may only hold <strong>one active (Pending or Approved) reservation across the entire E-BoardMate system</strong> at any given time.</li>
                                                        <li><strong>24-Hour Expiration:</strong> Pending reservations automatically expire if unacknowledged by the owner within 24 hours. If a request is rejected or expires, the slot is freed, and you may submit a new request.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        
                                    </div>
                                </div>

                                <div class="col-12 mt-4 pt-4 border-top border-secondary">
                                    <div class="form-check custom-checkbox">
                                        <input id="accepted_terms" v-model="reservationForm.accepted_terms" class="form-check-input shadow-none cursor-pointer" :class="{ 'is-invalid': reservationForm.errors.accepted_terms }" type="checkbox" style="width: 1.25em; height: 1.25em; margin-top: 0.15em;">
                                        <!-- Removed text-dark to allow variable inheritance -->
                                        <label class="form-check-label small fw-bold cursor-pointer user-select-none ps-2" for="accepted_terms">
                                            I acknowledge that I have read and agree to the Data Privacy Notice and Platform Terms & Conditions above.
                                        </label>
                                        <div v-if="reservationForm.errors.accepted_terms" class="invalid-feedback d-block fw-medium mt-2">
                                            {{ reservationForm.errors.accepted_terms }}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Removed pure bg-light, relying on modal's dark mode background -->
                        <div class="modal-footer border-top border-secondary">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" @click="cleanupModalBackdrop" :disabled="reservationForm.processing">Cancel</button>
                            
                            <button type="submit" class="btn btn-ebm-primary px-4" :disabled="reservationForm.processing">
                                <span v-if="reservationForm.processing">Submitting...</span>
                                <span v-else>Submit Reservation</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 🧾 THE RESULT RECEIPT MODAL -->
        <div id="reservationResultModal" class="modal fade" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <!-- Apply ebm-card for seamless dark mode -->
                <div class="modal-content ebm-card border-secondary shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="cleanupModalBackdrop" />
                    </div>

                    <div v-if="resultModalData" class="modal-body px-4 pt-0 pb-4">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" 
                                 :class="resultModalData.type === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
                                 style="width: 60px; height: 60px; font-size: 24px;">
                                <span v-if="resultModalData.type === 'success'">✓</span>
                                <span v-else>!</span>
                            </div>
                            <h2 class="h4 fw-bold mb-2">{{ resultModalData.title }}</h2>
                            <p class="ebm-muted mb-0 px-3">{{ resultModalData.message }}</p>
                        </div>

                        <div v-if="hasReferenceCode" class="reference-code-box mb-4">
                            <span class="reference-label">Your Tracking Code</span>
                            <strong class="reference-code">{{ resultModalData.reference_code }}</strong>
                            <button type="button" class="btn btn-sm btn-outline-success mt-3" @click="copyReferenceCode">
                                {{ copyButtonText }}
                            </button>
                        </div>

                        <div class="receipt-details mb-4">
                            <div class="receipt-row">
                                <span class="ebm-muted">Boarding House</span>
                                <strong class="text-body">{{ resultModalData.boarding_house_name || boardingHouse.name }}</strong>
                            </div>
                            
                            <div v-if="resultModalData.tracking_email" class="receipt-row">
                                <span class="ebm-muted">Tracking Email</span>
                                <strong class="text-body">{{ resultModalData.tracking_email }}</strong>
                            </div>

                            <div class="receipt-row border-0">
                                <span class="ebm-muted">Status</span>
                                <strong :class="resultModalData.type === 'success' ? 'text-success' : 'text-danger'">
                                    {{ resultModalData.status }}
                                </strong>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <Link v-if="resultModalData?.type === 'success'" href="/track-reservation" class="btn btn-ebm-primary w-100 py-2 fw-medium">
                                Track Reservation Now
                            </Link>
                            <button type="button" class="btn btn-secondary w-100 py-2 fw-medium" data-bs-dismiss="modal" @click="cleanupModalBackdrop">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
/* 📸 Horizontal Photo Slider Gallery */
.photo-slider-track {
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
}

.photo-slider-item {
    scroll-snap-align: start;
    cursor: pointer;
    width: 82vw;
    max-width: 320px;
    height: 220px;
    background-color: var(--ebm-bg);
}

@media (min-width: 768px) {
    .photo-slider-item {
        width: 350px;
        height: 245px;
    }
}

.photo-slider-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.photo-slider-item:hover .photo-slider-img {
    transform: scale(1.05);
}

.photo-slider-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.photo-slider-item:hover .photo-slider-overlay {
    opacity: 1;
}

.slider-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 2.75rem;
    height: 2.75rem;
    z-index: 5;
    opacity: 0.9;
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.slider-arrow:hover {
    opacity: 1;
    transform: translateY(-50%) scale(1.1);
}

.slider-arrow-prev {
    left: -0.75rem;
}

.slider-arrow-next {
    right: -0.75rem;
}

.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

/* Hover Effects for the Grid Photos */
.photo-thumbnail-container {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
.photo-thumbnail-container .boarding-house-photo {
    width: 100%;
    height: 250px;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.photo-thumbnail-container:hover .boarding-house-photo {
    transform: scale(1.05);
}
.photo-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.3);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.photo-thumbnail-container:hover .photo-overlay {
    opacity: 1;
}

/* Lightbox Modal Styles */
.lightbox-image {
    max-height: 90vh;
    max-width: 90vw;
    object-fit: contain;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    border-radius: 8px;
}
.lightbox-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.1);
    color: white;
    border: none;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    font-size: 24px;
    z-index: 10;
    cursor: pointer;
    transition: background 0.2s ease, transform 0.2s ease;
}
.lightbox-nav-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-50%) scale(1.1);
}
.prev-btn { left: 20px; }
.next-btn { right: 20px; }

.lightbox-counter {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    background: rgba(0,0,0,0.6);
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 1px;
}

/* Vue Transitions */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scale(0.98);
}

/* Legal Accordion Tweaks */
.custom-legal-accordion .accordion-item {
    background-color: transparent;
    border-color: var(--bs-border-color);
}
.custom-legal-accordion .accordion-button {
    background-color: transparent;
    color: var(--bs-body-color);
}
.custom-legal-accordion .accordion-button:not(.collapsed) {
    background-color: rgba(25, 135, 84, 0.1);
    color: #198754;
    box-shadow: inset 0 calc(-1 * var(--bs-accordion-border-width)) 0 var(--bs-border-color);
}
.cursor-pointer {
    cursor: pointer;
}
.user-select-none {
    user-select: none;
}

/* Modal Receipts */
.reference-code-box {
    background-color: rgba(25, 135, 84, 0.05);
    border: 2px dashed #198754;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
}
.reference-label { color: #198754; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; }
.reference-code { display: block; font-size: 2.2rem; font-family: monospace; color: #198754; margin-top: 8px; letter-spacing: 3px; user-select: all; }
.receipt-details { 
    background-color: transparent; 
    border: 1px solid var(--bs-border-color); 
    border-radius: 8px; 
    padding: 16px; 
}
.receipt-row { 
    display: flex; 
    justify-content: space-between; 
    padding: 10px 0; 
    border-bottom: 1px solid var(--bs-border-color); 
    font-size: 0.95rem; 
}
.receipt-row:last-child { border-bottom: none; padding-bottom: 0; }
.receipt-row:first-child { padding-top: 0; }
</style>