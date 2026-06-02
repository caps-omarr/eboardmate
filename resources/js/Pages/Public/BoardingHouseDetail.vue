<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Modal } from 'bootstrap';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    boardingHouse: {
        type: Object,
        required: true,
    },
});

const page = usePage();

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

const hasReferenceCode = computed(() => {
    return Boolean(resultModalData.value?.reference_code);
});

const getModalInstance = (modalId) => {
    const modalElement = document.getElementById(modalId);

    if (!modalElement) {
        return null;
    }

    return Modal.getOrCreateInstance(modalElement);
};

const closeModal = (modalId) => {
    const modal = getModalInstance(modalId);

    if (modal) {
        modal.hide();
    }
};

const openModal = (modalId) => {
    const modal = getModalInstance(modalId);

    if (modal) {
        modal.show();
    }
};

const showResultModal = async () => {
    closeModal('reservationModal');

    await nextTick();

    setTimeout(() => {
        openModal('reservationResultModal');
    }, 250);
};

const copyReferenceCode = async () => {
    if (!resultModalData.value?.reference_code) {
        return;
    }

    try {
        await navigator.clipboard.writeText(resultModalData.value.reference_code);

        copyButtonText.value = 'Copied!';

        setTimeout(() => {
            copyButtonText.value = 'Copy Code';
        }, 2000);
    } catch (error) {
        copyButtonText.value = 'Copy Manually';
    }
};

const submitReservation = () => {
    reservationForm.post(`/boarding-houses/${props.boardingHouse.slug}/reservations`, {
        preserveScroll: true,
        onSuccess: (responsePage) => {
            resultModalData.value = responsePage.props.flash?.reservation_result || {
                type: 'success',
                title: 'Reservation Submitted',
                message: 'Your reservation request has been submitted successfully.',
                boarding_house_name: props.boardingHouse.name,
                status: 'Pending',
            };

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
                    status: 'Not Submitted',
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
            <meta
                name="description"
                :content="`View rent price, available rooms, photos, amenities, and reservation details for ${boardingHouse.name}, a verified boarding house near Talibon Polytechnic College.`"
            >
        </Head>

        <section class="py-5">
            <div class="container">
                <div class="mb-4">
                    <Link href="/map" class="btn btn-ebm-outline">
                        Back to Map
                    </Link>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="ebm-card p-4 p-md-5 mb-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span
                                            v-if="boardingHouse.is_verified"
                                            class="badge text-bg-success"
                                        >
                                            Verified
                                        </span>

                                        <span
                                            v-if="boardingHouse.is_full"
                                            class="badge text-bg-danger"
                                        >
                                            Full
                                        </span>

                                        <span
                                            v-else
                                            class="badge text-bg-primary"
                                        >
                                            Available
                                        </span>
                                    </div>

                                    <h1 class="fw-bold mb-2">
                                        {{ boardingHouse.name }}
                                    </h1>

                                    <p class="ebm-muted mb-0">
                                        {{ boardingHouse.address || 'Address not provided yet.' }}
                                    </p>
                                </div>
                            </div>

                            <p class="lead ebm-muted mb-0">
                                {{ boardingHouse.description || 'No description has been added for this boarding house yet.' }}
                            </p>
                        </div>

                        <div class="ebm-card p-4 p-md-5 mb-4">
                            <h2 class="h4 fw-bold mb-3">
                                Photos
                            </h2>

                            <div
                                v-if="boardingHouse.photos.length"
                                class="row g-3"
                            >
                                <div
                                    v-for="photo in boardingHouse.photos"
                                    :key="photo.id"
                                    class="col-md-6"
                                >
                                    <img
                                        :src="photo.url"
                                        :alt="photo.alt_text || boardingHouse.name"
                                        class="boarding-house-photo"
                                    >
                                </div>
                            </div>

                            <div
                                v-else
                                class="empty-state"
                            >
                                <div class="empty-state-icon">
                                    🖼️
                                </div>

                                <h3 class="h5 fw-bold mb-2">
                                    No photos uploaded yet
                                </h3>

                                <p class="ebm-muted mb-0">
                                    Photos will appear here once the boarding house owner uploads images.
                                </p>
                            </div>
                        </div>

                        <div class="ebm-card p-4 p-md-5 mb-4">
                            <h2 class="h4 fw-bold mb-3">
                                Location Details
                            </h2>

                            <p class="ebm-muted mb-3">
                                {{ boardingHouse.location_description || 'No location description has been provided yet.' }}
                            </p>

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

                            <p class="small ebm-muted mt-3 mb-0">
                                Distance is estimated using coordinates. Actual walking distance may vary.
                            </p>
                        </div>

                        <div class="ebm-card p-4 p-md-5">
                            <h2 class="h4 fw-bold mb-3">
                                House Rules
                            </h2>

                            <p class="ebm-muted mb-0">
                                {{ boardingHouse.rules || 'No house rules have been added yet.' }}
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="ebm-card p-4 sticky-detail-card">
                            <h2 class="h4 fw-bold mb-3">
                                Boarding House Summary
                            </h2>

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

                            <h3 class="h6 fw-bold mb-3">
                                Amenities
                            </h3>

                            <div
                                v-if="boardingHouse.amenities.length"
                                class="d-flex flex-wrap gap-2 mb-4"
                            >
                                <span
                                    v-for="amenity in boardingHouse.amenities"
                                    :key="amenity"
                                    class="badge badge-soft-green"
                                >
                                    {{ amenity }}
                                </span>
                            </div>

                            <p
                                v-else
                                class="ebm-muted small mb-4"
                            >
                                No amenities listed yet.
                            </p>

                            <button
                                v-if="!boardingHouse.is_full"
                                type="button"
                                class="btn btn-ebm-primary w-100"
                                data-bs-toggle="modal"
                                data-bs-target="#reservationModal"
                            >
                                Reserve Now
                            </button>

                            <button
                                v-else
                                type="button"
                                class="btn btn-secondary w-100"
                                disabled
                            >
                                Reservation Unavailable
                            </button>

                            <p class="small ebm-muted mt-3 mb-0">
                                Students can submit a guest reservation without creating an account.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div
            id="reservationModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="reservationModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-scrollable reservation-dialog">
                <div class="modal-content reservation-modal-content">
                    <form @submit.prevent="submitReservation">
                        <div class="modal-header">
                            <div class="pe-3">
                                <span class="badge badge-soft-green mb-2">
                                    Guest Reservation
                                </span>

                                <h2
                                    id="reservationModalLabel"
                                    class="modal-title h5 fw-bold"
                                >
                                    Reserve at {{ boardingHouse.name }}
                                </h2>
                            </div>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            />
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-light border reservation-important-alert">
                                <strong>Important:</strong>
                                You can only have one active reservation for the same boarding house.
                                Active reservations include pending and approved reservations.
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label
                                        for="full_name"
                                        class="form-label"
                                    >
                                        Full Name <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        id="full_name"
                                        v-model="reservationForm.full_name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': reservationForm.errors.full_name }"
                                        placeholder="Enter your full name"
                                    >

                                    <div
                                        v-if="reservationForm.errors.full_name"
                                        class="invalid-feedback"
                                    >
                                        {{ reservationForm.errors.full_name }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="email"
                                        class="form-label"
                                    >
                                        Email Address <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        id="email"
                                        v-model="reservationForm.email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': reservationForm.errors.email }"
                                        placeholder="example@email.com"
                                    >

                                    <div
                                        v-if="reservationForm.errors.email"
                                        class="invalid-feedback"
                                    >
                                        {{ reservationForm.errors.email }}
                                    </div>

                                    <div class="form-text">
                                        This email will be used for tracking and reservation status notifications.
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="phone"
                                        class="form-label"
                                    >
                                        Phone Number <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        id="phone"
                                        v-model="reservationForm.phone"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': reservationForm.errors.phone }"
                                        placeholder="09XXXXXXXXX"
                                    >

                                    <div
                                        v-if="reservationForm.errors.phone"
                                        class="invalid-feedback"
                                    >
                                        {{ reservationForm.errors.phone }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="preferred_move_in_date"
                                        class="form-label"
                                    >
                                        Preferred Move-in Date <span class="text-danger">*</span>
                                    </label>

                                    <input
                                        id="preferred_move_in_date"
                                        v-model="reservationForm.preferred_move_in_date"
                                        type="date"
                                        class="form-control"
                                        :class="{ 'is-invalid': reservationForm.errors.preferred_move_in_date }"
                                    >

                                    <div
                                        v-if="reservationForm.errors.preferred_move_in_date"
                                        class="invalid-feedback"
                                    >
                                        {{ reservationForm.errors.preferred_move_in_date }}
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label
                                        for="message"
                                        class="form-label"
                                    >
                                        Message to Owner
                                    </label>

                                    <textarea
                                        id="message"
                                        v-model="reservationForm.message"
                                        class="form-control"
                                        :class="{ 'is-invalid': reservationForm.errors.message }"
                                        rows="4"
                                        placeholder="Optional message, question, or note for the boarding house owner"
                                    />

                                    <div
                                        v-if="reservationForm.errors.message"
                                        class="invalid-feedback"
                                    >
                                        {{ reservationForm.errors.message }}
                                    </div>
                                </div>
                            </div>

                            <div class="reservation-notice mt-4">
                                <div class="notice-icon mt-0" aria-hidden="true">🔒</div>
                                <div class="notice-content w-100">
                                    <div 
                                        class="d-flex justify-content-between align-items-center" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#privacyNoticeCollapse" 
                                        style="cursor: pointer;"
                                        aria-expanded="false" 
                                        aria-controls="privacyNoticeCollapse"
                                    >
                                        <h3 class="h6 fw-bold mb-0 text-dark">Privacy Notice</h3>
                                        <span class="ebm-muted small">▼</span>
                                    </div>
                                    <div class="collapse" id="privacyNoticeCollapse">
                                        <p class="small ebm-muted mt-2 mb-0">
                                            Your personal information (name, email address, phone number, and preferred move-in date) will be used strictly for processing this reservation, tracking your status, and owner communication. Your contact details will never be displayed publicly on E-BoardMate. Only the assigned boarding house owner and system administrators have access to your reservation record.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="reservation-notice mt-3">
                                <div class="notice-icon mt-0" aria-hidden="true">📌</div>
                                <div class="notice-content w-100">
                                    <div 
                                        class="d-flex justify-content-between align-items-center" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#termsCollapse" 
                                        style="cursor: pointer;"
                                        aria-expanded="false" 
                                        aria-controls="termsCollapse"
                                    >
                                        <h3 class="h6 fw-bold mb-0 text-dark">Terms and Conditions</h3>
                                        <span class="ebm-muted small">▼</span>
                                    </div>
                                    
                                    <div class="collapse" id="termsCollapse">
                                        <div class="small ebm-muted mt-2 mb-3">
                                            By submitting this reservation, you agree to the following system rules:
                                            <ul class="mb-0 mt-1 ps-3">
                                                <li class="mb-1"><strong>Single Active Reservation:</strong> You may only have one active (Pending or Approved) reservation for this specific boarding house at a time.</li>
                                                <li class="mb-1"><strong>24-Hour Expiration:</strong> Pending reservations will automatically expire after 24 hours if the boarding house owner does not respond.</li>
                                                <li><strong>Re-applying:</strong> If your reservation is rejected, declined, expired, or cancelled, your slot is freed and you may submit a new reservation request.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <div class="form-check mt-3 pt-3 border-top">
                                        <input
                                            id="accepted_terms"
                                            v-model="reservationForm.accepted_terms"
                                            class="form-check-input"
                                            :class="{ 'is-invalid': reservationForm.errors.accepted_terms }"
                                            type="checkbox"
                                        >
                                        <label
                                            class="form-check-label small text-dark fw-medium"
                                            for="accepted_terms"
                                        >
                                            I understand and agree to the reservation terms and privacy notice.
                                        </label>
                                        <div
                                            v-if="reservationForm.errors.accepted_terms"
                                            class="invalid-feedback d-block"
                                        >
                                            {{ reservationForm.errors.accepted_terms }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                                :disabled="reservationForm.processing"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary"
                                :disabled="reservationForm.processing"
                            >
                                <span v-if="reservationForm.processing">
                                    Submitting...
                                </span>

                                <span v-else>
                                    Submit Reservation
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            id="reservationResultModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="reservationResultModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered reservation-result-dialog">
                <div class="modal-content reservation-result-modal-content">
                    <div class="modal-header border-0 pb-0">
                        <button
                            type="button"
                            class="btn-close ms-auto"
                            data-bs-dismiss="modal"
                            aria-label="Close"
                        />
                    </div>

                    <div
                        v-if="resultModalData"
                        class="modal-body pt-0"
                    >
                        <div class="reservation-result-receipt">
                            <div
                                class="reservation-result-icon"
                                :class="resultModalData.type === 'success' ? 'is-success' : 'is-error'"
                            >
                                <span v-if="resultModalData.type === 'success'">✓</span>
                                <span v-else>!</span>
                            </div>

                            <h2
                                id="reservationResultModalLabel"
                                class="h4 fw-bold text-center mb-2"
                            >
                                {{ resultModalData.title }}
                            </h2>

                            <p class="text-center ebm-muted mb-4">
                                {{ resultModalData.message }}
                            </p>

                            <div
                                v-if="hasReferenceCode"
                                class="reference-code-box mb-4"
                            >
                                <span class="reference-label">
                                    Reference Code
                                </span>

                                <strong class="reference-code">
                                    {{ resultModalData.reference_code }}
                                </strong>

                                <button
                                    type="button"
                                    class="btn btn-sm btn-ebm-outline mt-3"
                                    @click="copyReferenceCode"
                                >
                                    {{ copyButtonText }}
                                </button>
                            </div>

                            <div class="receipt-details">
                                <div class="receipt-row">
                                    <span>Boarding House</span>
                                    <strong>{{ resultModalData.boarding_house_name || boardingHouse.name }}</strong>
                                </div>

                                <div
                                    v-if="resultModalData.tracking_email"
                                    class="receipt-row"
                                >
                                    <span>Tracking Email</span>
                                    <strong>{{ resultModalData.tracking_email }}</strong>
                                </div>

                                <div class="receipt-row">
                                    <span>Status</span>
                                    <strong
                                        :class="resultModalData.type === 'success' ? 'text-success' : 'text-danger'"
                                    >
                                        {{ resultModalData.status }}
                                    </strong>
                                </div>

                                <div
                                    v-if="resultModalData.expires_at"
                                    class="receipt-row"
                                >
                                    <span>Pending Until</span>
                                    <strong>{{ resultModalData.expires_at }}</strong>
                                </div>
                            </div>

                            <div
                                v-if="resultModalData.type === 'success'"
                                class="alert alert-light border mt-4 mb-0 text-start"
                            >
                                <strong>Next step:</strong>
                                Take a screenshot or copy your reference code. You will use it together with your email address to track your reservation.
                            </div>

                            <div
                                v-else
                                class="alert alert-warning mt-4 mb-0 text-start"
                            >
                                You may track your existing reservation or contact the boarding house owner if needed.
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <Link
                            v-if="resultModalData?.type === 'success'"
                            href="/track-reservation"
                            class="btn btn-ebm-primary"
                        >
                            Track Reservation
                        </Link>

                        <button
                            type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>