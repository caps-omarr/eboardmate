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

        <div class="container">
            <div
                v-if="flashSuccess"
                class="alert alert-success mb-4"
            >
                {{ flashSuccess }}
            </div>

            <div
                v-if="responseForm.errors.reservation"
                class="alert alert-danger mb-4"
            >
                {{ responseForm.errors.reservation }}
            </div>

            <div class="mb-4">
                <span class="badge badge-soft-green mb-3">
                    Boarding House Owner
                </span>

                <h1 class="fw-bold mb-2">
                    Owner Dashboard
                </h1>

                <p class="ebm-muted mb-0">
                    Welcome, {{ owner.name }}. Manage your assigned boarding house and monitor student reservations.
                </p>
            </div>

            <div
                v-if="!boardingHouse"
                class="ebm-card p-4 p-md-5"
            >
                <h2 class="h4 fw-bold mb-2">
                    No assigned boarding house yet
                </h2>

                <p class="ebm-muted mb-0">
                    Your owner account does not have an assigned boarding house listing yet. Please contact the super admin.
                </p>
            </div>

            <template v-else>
                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-xl-3">
                        <div class="ebm-card p-4">
                            <span class="dashboard-stat-label">
                                Total Reservations
                            </span>

                            <strong class="dashboard-stat-value">
                                {{ stats.total }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="ebm-card p-4">
                            <span class="dashboard-stat-label">
                                Pending
                            </span>

                            <strong class="dashboard-stat-value text-warning">
                                {{ stats.pending }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="ebm-card p-4">
                            <span class="dashboard-stat-label">
                                Approved
                            </span>

                            <strong class="dashboard-stat-value text-success">
                                {{ stats.approved }}
                            </strong>
                        </div>
                    </div>

                    <div class="col-md-6 col-xl-3">
                        <div class="ebm-card p-4">
                            <span class="dashboard-stat-label">
                                Rejected / Expired
                            </span>

                            <strong class="dashboard-stat-value text-secondary">
                                {{ stats.rejected + stats.expired }}
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="ebm-card p-4">
                            <h2 class="h5 fw-bold mb-3">
                                Assigned Boarding House
                            </h2>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span
                                    v-if="boardingHouse.is_verified"
                                    class="badge text-bg-success"
                                >
                                    Verified
                                </span>

                                <span
                                    v-else
                                    class="badge text-bg-warning"
                                >
                                    Not Verified
                                </span>

                                <span class="badge text-bg-light border text-dark">
                                    {{ boardingHouse.status }}
                                </span>
                            </div>

                            <h3 class="h5 fw-bold mb-2">
                                {{ boardingHouse.name }}
                            </h3>

                            <div class="summary-list mt-4">
                                <div class="summary-item">
                                    <span>Monthly Rent</span>
                                    <strong>₱{{ formatPrice(boardingHouse.rent_price) }}</strong>
                                </div>

                                <div class="summary-item">
                                    <span>Rooms</span>
                                    <strong>{{ boardingHouse.available_rooms }} / {{ boardingHouse.total_rooms }}</strong>
                                </div>

                                <div class="summary-item">
                                    <span>Bedspaces</span>
                                    <strong>{{ boardingHouse.available_bedspaces }} / {{ boardingHouse.total_bedspaces }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="ebm-card p-4">
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                                <div>
                                    <h2 class="h5 fw-bold mb-1">
                                        Latest Reservations
                                    </h2>

                                    <p class="ebm-muted small mb-0">
                                        These are the latest reservation requests for your assigned boarding house.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="reservations.length"
                                class="table-responsive"
                            >
                                <table class="table align-middle owner-table">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Guest</th>
                                            <th>Move-in Date</th>
                                            <th>Status</th>
                                            <th>Email</th>
                                            <th>Submitted</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="reservation in reservations"
                                            :key="reservation.id"
                                        >
                                            <td>
                                                <strong>{{ reservation.reference_code }}</strong>
                                            </td>

                                            <td>
                                                <div class="fw-semibold">
                                                    {{ reservation.guest_name }}
                                                </div>

                                                <div class="small ebm-muted">
                                                    {{ reservation.guest_email }}
                                                </div>

                                                <div class="small ebm-muted">
                                                    {{ reservation.guest_phone }}
                                                </div>
                                            </td>

                                            <td>
                                                {{ reservation.preferred_move_in_date }}
                                            </td>

                                            <td>
                                                <span
                                                    class="badge"
                                                    :class="statusBadgeClass(reservation.status)"
                                                >
                                                    {{ reservation.status_label }}
                                                </span>
                                            </td>

                                            <td>
                                                <span
                                                    v-if="reservation.email_notification_status"
                                                    class="badge text-bg-light border text-dark"
                                                >
                                                    {{ reservation.email_notification_status }}
                                                </span>

                                                <span
                                                    v-else
                                                    class="small ebm-muted"
                                                >
                                                    Not sent
                                                </span>
                                            </td>

                                            <td class="small ebm-muted">
                                                {{ reservation.created_at }}
                                            </td>

                                            <td>
                                                <div
                                                    v-if="reservation.can_respond"
                                                    class="d-flex flex-column gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-success"
                                                        @click="openResponseModal(reservation, 'approve')"
                                                    >
                                                        Approve
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        @click="openResponseModal(reservation, 'reject')"
                                                    >
                                                        Reject
                                                    </button>
                                                </div>

                                                <span
                                                    v-else
                                                    class="small ebm-muted"
                                                >
                                                    Responded
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-else
                                class="empty-state"
                            >
                                <div class="empty-state-icon">
                                    📋
                                </div>

                                <h3 class="h5 fw-bold mb-2">
                                    No reservations yet
                                </h3>

                                <p class="ebm-muted mb-0">
                                    New student reservation requests will appear here.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div
            id="reservationResponseModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="reservationResponseModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content reservation-result-modal-content">
                    <form @submit.prevent="submitResponse">
                        <div class="modal-header">
                            <div>
                                <span
                                    class="badge mb-2"
                                    :class="actionType === 'approve' ? 'text-bg-success' : 'text-bg-danger'"
                                >
                                    {{ actionType === 'approve' ? 'Approve Reservation' : 'Reject Reservation' }}
                                </span>

                                <h2
                                    id="reservationResponseModalLabel"
                                    class="modal-title h5 fw-bold"
                                >
                                    {{ selectedReservation?.reference_code }}
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
                            <p class="ebm-muted">
                                Guest:
                                <strong class="text-dark">
                                    {{ selectedReservation?.guest_name }}
                                </strong>
                            </p>

                            <label
                                for="owner_response"
                                class="form-label"
                            >
                                Owner Response
                                <span
                                    v-if="actionType === 'reject'"
                                    class="text-danger"
                                >
                                    *
                                </span>
                            </label>

                            <textarea
                                id="owner_response"
                                v-model="responseForm.owner_response"
                                class="form-control"
                                :class="{ 'is-invalid': responseForm.errors.owner_response }"
                                rows="4"
                                placeholder="Write a short message for the guest"
                            />

                            <div
                                v-if="responseForm.errors.owner_response"
                                class="invalid-feedback"
                            >
                                {{ responseForm.errors.owner_response }}
                            </div>

                            <div class="alert alert-light border mt-3 mb-0">
                                After saving, the system will attempt to send an email notification to the guest.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                                :disabled="responseForm.processing"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="btn"
                                :class="actionType === 'approve' ? 'btn-success' : 'btn-danger'"
                                :disabled="responseForm.processing"
                            >
                                <span v-if="responseForm.processing">
                                    Saving...
                                </span>

                                <span v-else>
                                    {{ actionType === 'approve' ? 'Approve Reservation' : 'Reject Reservation' }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>