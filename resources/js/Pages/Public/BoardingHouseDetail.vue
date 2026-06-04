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

// --- 📸 LIGHTBOX STATE & LOGIC ---
const activePhotoIndex = ref(0);

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

// --- LIFECYCLE HOOKS ---
onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
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

        <section class="py-5">
            <div class="container">
                <div class="mb-4">
                    <Link href="/map" class="btn btn-ebm-outline">
                        Back to Map
                    </Link>
                </div>

                <div class="row g-4">
                    <!-- Left Column: Details -->
                    <div class="col-lg-8">
                        <div class="ebm-card p-4 p-md-5 mb-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span v-if="boardingHouse.is_verified" class="badge text-bg-success">Verified</span>
                                        <span v-if="boardingHouse.is_full" class="badge text-bg-danger">Full</span>
                                        <span v-else class="badge text-bg-primary">Available</span>
                                    </div>
                                    <h1 class="fw-bold mb-2">{{ boardingHouse.name }}</h1>
                                    <p class="ebm-muted mb-0">{{ boardingHouse.address || 'Address not provided yet.' }}</p>
                                </div>
                            </div>
                            <p class="lead ebm-muted mb-0">
                                {{ boardingHouse.description || 'No description has been added for this boarding house yet.' }}
                            </p>
                        </div>

                        <!-- 📸 PHOTOS SECTION -->
                        <div class="ebm-card p-4 p-md-5 mb-4">
                            <h2 class="h4 fw-bold mb-3">Photos</h2>
                            <div v-if="boardingHouse.photos.length" class="row g-3">
                                <div v-for="(photo, index) in boardingHouse.photos" :key="photo.id" class="col-md-6">
                                    <div class="photo-thumbnail-container" @click="openLightbox(index)">
                                        <img :src="photo.url" :alt="photo.alt_text || boardingHouse.name" class="boarding-house-photo">
                                        <div class="photo-overlay">
                                            <span class="fs-3">⛶</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="empty-state">
                                <div class="empty-state-icon">🖼️</div>
                                <h3 class="h5 fw-bold mb-2">No photos uploaded yet</h3>
                                <p class="ebm-muted mb-0">Photos will appear here once the boarding house owner uploads images.</p>
                            </div>
                        </div>

                        <!-- LOCATION DETAILS -->
                        <div class="ebm-card p-4 p-md-5 mb-4">
                            <h2 class="h4 fw-bold mb-3">Location Details</h2>
                            <p class="ebm-muted mb-3">{{ boardingHouse.location_description || 'No location description has been provided yet.' }}</p>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="detail-mini-card">
                                        <span class="detail-label">Estimated Distance</span>
                                        <strong>{{ boardingHouse.estimated_distance_km }} km</strong>
                                        <small class="ebm-muted">from TPC</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="detail-mini-card">
                                        <span class="detail-label">Latitude</span>
                                        <strong>{{ boardingHouse.latitude }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="detail-mini-card">
                                        <span class="detail-label">Longitude</span>
                                        <strong>{{ boardingHouse.longitude }}</strong>
                                    </div>
                                </div>
                            </div>
                            <p class="small ebm-muted mt-3 mb-0">Distance is estimated using coordinates. Actual walking distance may vary.</p>
                        </div>

                        <!-- HOUSE RULES -->
                        <div class="ebm-card p-4 p-md-5">
                            <h2 class="h4 fw-bold mb-3">House Rules</h2>
                            <p class="ebm-muted mb-0">{{ boardingHouse.rules || 'No house rules have been added yet.' }}</p>
                        </div>
                    </div>

                    <!-- Right Column: Sticky Summary -->
                    <div class="col-lg-4">
                        <div class="ebm-card p-4 sticky-detail-card">
                            <h2 class="h4 fw-bold mb-3">Boarding House Summary</h2>
                            <div class="summary-list">
                                <div class="summary-item">
                                    <span>Monthly Rent</span>
                                    <strong>₱{{ formatPrice(boardingHouse.rent_price) }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Available Rooms</span>
                                    <strong>{{ boardingHouse.available_rooms }} / {{ boardingHouse.total_rooms }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Available Bedspaces</span>
                                    <strong>{{ boardingHouse.available_bedspaces }} / {{ boardingHouse.total_bedspaces }}</strong>
                                </div>
                                <div class="summary-item">
                                    <span>Status</span>
                                    <strong :class="boardingHouse.is_full ? 'text-danger' : 'text-success'">
                                        {{ boardingHouse.is_full ? 'Full' : 'Available' }}
                                    </strong>
                                </div>
                            </div>
                            <hr>
                            <h3 class="h6 fw-bold mb-3">Amenities</h3>
                            <div v-if="boardingHouse.amenities.length" class="d-flex flex-wrap gap-2 mb-4">
                                <span v-for="amenity in boardingHouse.amenities" :key="amenity" class="badge badge-soft-green">
                                    {{ amenity }}
                                </span>
                            </div>
                            <p v-else class="ebm-muted small mb-4">No amenities listed yet.</p>

                            <button v-if="!boardingHouse.is_full" type="button" class="btn btn-ebm-primary w-100" data-bs-toggle="modal" data-bs-target="#reservationModal">
                                Reserve Now
                            </button>
                            <button v-else type="button" class="btn btn-secondary w-100" disabled>
                                Reservation Unavailable
                            </button>
                            <p class="small ebm-muted mt-3 mb-0 text-center mt-3">Students can submit a guest reservation without creating an account.</p>
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
                <div class="modal-content reservation-modal-content">
                    <form @submit.prevent="submitReservation">
                        <div class="modal-header border-bottom">
                            <div class="pe-3">
                                <span class="badge badge-soft-green mb-2">Guest Reservation</span>
                                <h2 id="reservationModalLabel" class="modal-title h5 fw-bold">Reserve at {{ boardingHouse.name }}</h2>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" @click="cleanupModalBackdrop" />
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-light border reservation-important-alert">
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
                                    <input id="email" v-model="reservationForm.email" type="email" class="form-control" :class="{ 'is-invalid': reservationForm.errors.email }" placeholder="example@email.com">
                                    <div v-if="reservationForm.errors.email" class="invalid-feedback">{{ reservationForm.errors.email }}</div>
                                    <div class="form-text">This email will be used for tracking notifications.</div>
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
                                        <div class="accordion-item border border-bottom-0 rounded-top overflow-hidden">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed bg-light py-3 shadow-none text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePrivacy">
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
                                        <div class="accordion-item border rounded-bottom overflow-hidden">
                                            <h2 class="accordion-header">
                                                <button class="accordion-button collapsed bg-light py-3 shadow-none text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTerms">
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

                                <div class="col-12 mt-4 pt-4 border-top">
                                    <div class="form-check custom-checkbox">
                                        <input id="accepted_terms" v-model="reservationForm.accepted_terms" class="form-check-input shadow-none cursor-pointer" :class="{ 'is-invalid': reservationForm.errors.accepted_terms }" type="checkbox" style="width: 1.25em; height: 1.25em; margin-top: 0.15em;">
                                        <label class="form-check-label small text-dark fw-bold cursor-pointer user-select-none ps-2" for="accepted_terms">
                                            I acknowledge that I have read and agree to the Data Privacy Notice and Platform Terms & Conditions above.
                                        </label>
                                        <div v-if="reservationForm.errors.accepted_terms" class="invalid-feedback d-block fw-medium mt-2">
                                            {{ reservationForm.errors.accepted_terms }}
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="modal-footer bg-light">
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
                <div class="modal-content border-0 shadow-lg">
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
                            <p class="text-muted mb-0 px-3">{{ resultModalData.message }}</p>
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
                                <span class="text-muted">Boarding House</span>
                                <strong class="text-dark">{{ resultModalData.boarding_house_name || boardingHouse.name }}</strong>
                            </div>
                            
                            <div v-if="resultModalData.tracking_email" class="receipt-row">
                                <span class="text-muted">Tracking Email</span>
                                <strong class="text-dark">{{ resultModalData.tracking_email }}</strong>
                            </div>

                            <div class="receipt-row">
                                <span class="text-muted">Status</span>
                                <strong :class="resultModalData.type === 'success' ? 'text-success' : 'text-danger'">
                                    {{ resultModalData.status }}
                                </strong>
                            </div>
                        </div>

                        <div class="d-flex flex-column gap-2">
                            <Link v-if="resultModalData?.type === 'success'" href="/track-reservation" class="btn btn-ebm-primary w-100 py-2 fw-medium">
                                Track Reservation Now
                            </Link>
                            <button type="button" class="btn btn-light w-100 py-2 fw-medium" data-bs-dismiss="modal" @click="cleanupModalBackdrop">
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
.custom-legal-accordion .accordion-button:not(.collapsed) {
    background-color: #f4fbf7;
    color: #198754;
    box-shadow: inset 0 calc(-1 * var(--bs-accordion-border-width)) 0 var(--bs-accordion-border-color);
}
.cursor-pointer {
    cursor: pointer;
}
.user-select-none {
    user-select: none;
}

/* Modal Receipts */
.reference-code-box {
    background-color: #f4fbf7;
    border: 2px dashed #198754;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
}
.reference-label { color: #198754; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: block; }
.reference-code { display: block; font-size: 2.2rem; font-family: monospace; color: #0f5132; margin-top: 8px; letter-spacing: 3px; user-select: all; }
.receipt-details { background: #ffffff; border: 1px solid #e9ecef; border-radius: 8px; padding: 16px; }
.receipt-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f9fa; font-size: 0.95rem; }
.receipt-row:last-child { border-bottom: none; padding-bottom: 0; }
.receipt-row:first-child { padding-top: 0; }
</style>