<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { Modal } from 'bootstrap';
import { computed, ref } from 'vue';

defineProps({
    owner: {
        type: Object,
        required: true,
    },
    boardingHouse: {
        type: Object,
        default: null,
    },
    stats: {
        type: Object,
        required: true,
    },
    reservations: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success || null);

const selectedReservation = ref(null);
const actionType = ref('approve');

const responseForm = useForm({
    owner_response: '',
});

const statusBadgeClass = (status) => {
    if (status === 'pending') {
        return 'text-bg-warning';
    }

    if (status === 'approved') {
        return 'text-bg-success';
    }

    if (status === 'rejected') {
        return 'text-bg-danger';
    }

    if (status === 'expired' || status === 'cancelled') {
        return 'text-bg-secondary';
    }

    return 'text-bg-secondary';
};

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const openResponseModal = (reservation, type) => {
    selectedReservation.value = reservation;
    actionType.value = type;

    responseForm.reset();
    responseForm.clearErrors();

    if (type === 'approve') {
        responseForm.owner_response = 'Your reservation has been approved. Please contact the boarding house owner for the next step.';
    }

    const modalElement = document.getElementById('reservationResponseModal');

    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).show();
    }
};

const closeResponseModal = () => {
    const modalElement = document.getElementById('reservationResponseModal');

    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).hide();
    }
};

const submitResponse = () => {
    if (!selectedReservation.value) {
        return;
    }

    const targetUrl = actionType.value === 'approve'
        ? selectedReservation.value.approve_url
        : selectedReservation.value.reject_url;

    responseForm.post(targetUrl, {
        preserveScroll: true,
        onSuccess: () => {
            closeResponseModal();
            selectedReservation.value = null;
        },
    });
};
</script>

<template>
    <OwnerLayout>
        <Head title="Owner Dashboard | E-BoardMate" />

        <div class="container pb-5">
            
            <!-- ALERTS -->
            <div v-if="flashSuccess" class="alert alert-success mb-4 shadow-sm border-0">
                {{ flashSuccess }}
            </div>

            <div v-if="responseForm.errors.reservation" class="alert alert-danger mb-4 shadow-sm border-0">
                {{ responseForm.errors.reservation }}
            </div>

            <!-- HEADER SECTION -->
            <header class="mb-4 pt-2">
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mb-3 px-3 py-2 rounded-pill shadow-sm">
                    Boarding House Owner
                </span>

                <h1 class="fw-bold mb-2 tracking-tight">
                    Owner Dashboard
                </h1>

                <p class="text-body-secondary mb-0 lead" style="font-size: 1.1rem;">
                    Welcome, <strong>{{ owner.name }}</strong>. Manage your assigned boarding house and monitor student reservations.
                </p>
            </header>

            <!-- NO BOARDING HOUSE ASSIGNED -->
            <section v-if="!boardingHouse" class="ebm-card p-4 p-md-5 text-center shadow-sm border border-secondary-subtle">
                <div class="fs-1 mb-3">🏠</div>
                <h2 class="h4 fw-bold mb-2">No assigned boarding house yet</h2>
                <p class="text-body-secondary mb-0">
                    Your owner account does not have an assigned boarding house listing yet. Please contact the super admin to link your property.
                </p>
            </section>

            <!-- MAIN DASHBOARD CONTENT -->
            <template v-else>
                
                <!-- 🚀 UX FIX: 2x2 Grid on Mobile (col-6), 1-Row on Desktop (col-lg-3) -->
                <section class="row g-3 mb-4" aria-label="Dashboard Statistics">
                    <div class="col-6 col-lg-3">
                        <div class="ebm-card p-3 p-md-4 shadow-sm border border-secondary-subtle h-100 d-flex flex-column justify-content-center">
                            <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">
                                Total Reservations
                            </span>
                            <strong class="fs-2 fw-bold lh-1 text-body-emphasis">
                                {{ stats.total }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="ebm-card p-3 p-md-4 shadow-sm border border-warning-subtle h-100 d-flex flex-column justify-content-center">
                            <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">
                                Pending
                            </span>
                            <strong class="fs-2 fw-bold lh-1 text-warning">
                                {{ stats.pending }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="ebm-card p-3 p-md-4 shadow-sm border border-success-subtle h-100 d-flex flex-column justify-content-center">
                            <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">
                                Approved
                            </span>
                            <strong class="fs-2 fw-bold lh-1 text-success">
                                {{ stats.approved }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-6 col-lg-3">
                        <div class="ebm-card p-3 p-md-4 shadow-sm border border-secondary-subtle h-100 d-flex flex-column justify-content-center">
                            <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">
                                Rejected / Expired
                            </span>
                            <strong class="fs-2 fw-bold lh-1 text-secondary">
                                {{ stats.rejected + stats.expired }}
                            </strong>
                        </div>
                    </div>
                </section>

                <div class="row g-4">
                    
                    <!-- PROPERTY DETAILS -->
                    <section class="col-lg-4" aria-label="Property Details">
                        <div class="ebm-card p-4 shadow-sm border border-secondary-subtle h-100">
                            <h2 class="h5 fw-bold mb-3">Assigned Boarding House</h2>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span v-if="boardingHouse.is_verified" class="badge text-bg-success shadow-sm">Verified</span>
                                <span v-else class="badge text-bg-warning shadow-sm">Not Verified</span>
                                <span class="badge bg-body-secondary text-body border border-secondary-subtle shadow-sm">{{ boardingHouse.status }}</span>
                            </div>

                            <h3 class="h4 fw-bold mb-4 text-body-emphasis">{{ boardingHouse.name }}</h3>

                            <ul class="list-group list-group-flush border-top border-secondary-subtle pt-2">
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-3 border-secondary-subtle">
                                    <span class="text-body-secondary">Monthly Rent</span>
                                    <strong class="text-body-emphasis fs-5">₱{{ formatPrice(boardingHouse.rent_price) }}</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-3 border-secondary-subtle">
                                    <span class="text-body-secondary">Rooms</span>
                                    <strong class="text-body-emphasis">{{ boardingHouse.available_rooms }} / {{ boardingHouse.total_rooms }}</strong>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-3 border-secondary-subtle">
                                    <span class="text-body-secondary">Bedspaces</span>
                                    <strong class="text-body-emphasis">{{ boardingHouse.available_bedspaces }} / {{ boardingHouse.total_bedspaces }}</strong>
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- LATEST RESERVATIONS TABLE -->
                    <section class="col-lg-8" aria-label="Latest Reservations">
                        <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle h-100 d-flex flex-column">
                            
                            <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary">
                                <h2 class="h5 fw-bold mb-1">Latest Reservations</h2>
                                <p class="text-body-secondary small mb-0">These are the latest reservation requests for your assigned boarding house.</p>
                            </div>

                            <!-- 🚀 UX FIX: The "Window Box" Table Scroll Wrapper -->
                            <div v-if="reservations.length" class="table-responsive custom-table-scroll flex-grow-1 bg-body">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <!-- 🚀 UX FIX: Sticky headers and text-nowrap prevent squishing -->
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Reference</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Guest</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Move-in Date</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Email</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Submitted</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody class="border-top-0">
                                        <tr v-for="reservation in reservations" :key="reservation.id">
                                            
                                            <td class="text-nowrap ps-3">
                                                <strong class="font-monospace">{{ reservation.reference_code }}</strong>
                                            </td>

                                            <td class="text-nowrap">
                                                <div class="fw-bold text-body-emphasis">{{ reservation.guest_name }}</div>
                                                <div class="small text-body-secondary">{{ reservation.guest_email }}</div>
                                                <div class="small text-body-secondary">{{ reservation.guest_phone }}</div>
                                            </td>

                                            <td class="text-nowrap">
                                                <span class="fw-medium">{{ reservation.preferred_move_in_date }}</span>
                                            </td>

                                            <td class="text-nowrap">
                                                <span class="badge shadow-sm" :class="statusBadgeClass(reservation.status)">
                                                    {{ reservation.status_label }}
                                                </span>
                                            </td>

                                            <td class="text-nowrap">
                                                <span v-if="reservation.email_notification_status" class="badge bg-body-secondary text-body border border-secondary-subtle">
                                                    {{ reservation.email_notification_status }}
                                                </span>
                                                <span v-else class="small text-body-secondary fst-italic">Not sent</span>
                                            </td>

                                            <td class="small text-body-secondary text-nowrap">
                                                {{ reservation.created_at }}
                                            </td>

                                            <td class="text-nowrap text-end pe-3">
                                                <div v-if="reservation.can_respond" class="d-flex flex-column gap-2 justify-content-end align-items-end">
                                                    <button type="button" class="btn btn-sm btn-success shadow-sm w-100" @click="openResponseModal(reservation, 'approve')">
                                                        Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger w-100" @click="openResponseModal(reservation, 'reject')">
                                                        Reject
                                                    </button>
                                                </div>
                                                <span v-else class="small text-body-secondary fw-medium bg-body-tertiary px-2 py-1 rounded border border-secondary-subtle">
                                                    Responded
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div v-else class="empty-state text-center p-5 flex-grow-1 d-flex flex-column justify-content-center align-items-center">
                                <div class="fs-1 mb-3 opacity-50">📋</div>
                                <h3 class="h5 fw-bold mb-2">No reservations yet</h3>
                                <p class="text-body-secondary mb-0">New student reservation requests will appear here.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </template>
        </div>

        <!-- MODAL -->
        <div id="reservationResponseModal" class="modal fade" tabindex="-1" aria-labelledby="reservationResponseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered border-0">
                <div class="modal-content shadow-lg border-0 overflow-hidden">
                    <form @submit.prevent="submitResponse">
                        
                        <!-- Dynamic Header Color based on Action -->
                        <div class="modal-header border-bottom-0 text-white" :class="actionType === 'approve' ? 'bg-success' : 'bg-danger'">
                            <div>
                                <h2 id="reservationResponseModalLabel" class="modal-title h5 fw-bold mb-0">
                                    {{ actionType === 'approve' ? 'Approve' : 'Reject' }} Reservation
                                </h2>
                                <div class="small opacity-75 mt-1 font-monospace">Ref: {{ selectedReservation?.reference_code }}</div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4 bg-body">
                            <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-tertiary rounded border border-secondary-subtle">
                                <div class="fs-2 lh-1">👤</div>
                                <div>
                                    <div class="small text-body-secondary fw-bold text-uppercase tracking-tight">Guest Name</div>
                                    <div class="fw-bold fs-5 text-body-emphasis">{{ selectedReservation?.guest_name }}</div>
                                </div>
                            </div>

                            <label for="owner_response" class="form-label fw-bold">
                                Owner Response <span v-if="actionType === 'reject'" class="text-danger">*</span>
                            </label>
                            
                            <textarea
                                id="owner_response"
                                v-model="responseForm.owner_response"
                                class="form-control bg-body-tertiary focus-ring focus-ring-success"
                                :class="{ 'is-invalid': responseForm.errors.owner_response, 'focus-ring-danger': actionType === 'reject' }"
                                rows="4"
                                placeholder="Write a short message for the guest explaining the approval or rejection..."
                            />
                            <div v-if="responseForm.errors.owner_response" class="invalid-feedback fw-bold">
                                {{ responseForm.errors.owner_response }}
                            </div>

                            <div class="alert alert-primary bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 mt-4 mb-0 d-flex align-items-start gap-2">
                                <span>📧</span>
                                <small>After saving, the system will attempt to instantly send an email notification with your message to the guest.</small>
                            </div>
                        </div>

                        <div class="modal-footer bg-body-tertiary border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-outline-secondary fw-medium" data-bs-dismiss="modal" :disabled="responseForm.processing">
                                Cancel
                            </button>
                            <button type="submit" class="btn fw-bold shadow-sm px-4" :class="actionType === 'approve' ? 'btn-success' : 'btn-danger'" :disabled="responseForm.processing">
                                <span v-if="responseForm.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ responseForm.processing ? 'Saving...' : 'Confirm ' + (actionType === 'approve' ? 'Approval' : 'Rejection') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>

<style scoped>
/* 🪄 UX FIX: The Custom "Window Box" Table Scroll */
.custom-table-scroll {
    max-height: 500px;
    overflow-y: auto;
    overflow-x: auto;
    
    /* Sleek slim scrollbars for modern UI */
    scrollbar-width: thin;
    scrollbar-color: rgba(108, 117, 125, 0.5) transparent;
}

.custom-table-scroll::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.custom-table-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.custom-table-scroll::-webkit-scrollbar-thumb {
    background-color: rgba(108, 117, 125, 0.5);
    border-radius: 10px;
}

/* 🪄 UX FIX: Sticky Header */
.sticky-header {
    position: sticky;
    top: 0;
    z-index: 2;
    box-shadow: inset 0 -1px 0 var(--bs-border-color); /* Adds the bottom border cleanly under the sticky header */
}

/* Typography refinements */
.tracking-tight {
    letter-spacing: -0.02em;
}
</style>