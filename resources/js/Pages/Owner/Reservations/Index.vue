<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { Modal } from 'bootstrap';
// Added onMounted and onUnmounted here
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    boardingHouse: { type: Object, default: null },
    reservations: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({ status: 'all' }) },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || null);

// --- SILENT BACKGROUND POLLING ---
let pollingInterval = null;

onMounted(() => {
    // Refresh the 'reservations' data every 10 seconds
    pollingInterval = setInterval(() => {
        router.reload({
            only: ['reservations'], // Only fetch the table data
            preserveState: true,    // Keep the active filter tab selected
            preserveScroll: true,   // Prevent the page from jumping
        });
    }, 10000);
});

onUnmounted(() => {
    // Stop polling when the user leaves this page to save resources
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
// ---------------------------------

// Archive Logic
const archiveForm = useForm({});
const archiveReservation = (reservation) => {
    if (confirm('Are you sure you want to archive this reservation? It will be hidden from this list.')) {
        archiveForm.post(reservation.archive_url, {
            preserveScroll: true,
        });
    }
};

const selectedReservation = ref(null);
const actionType = ref('approve');
const responseForm = useForm({ owner_response: '' });

const statusBadgeClass = (status) => {
    const classes = {
        pending: 'text-bg-warning',
        approved: 'text-bg-success',
        rejected: 'text-bg-danger',
        expired: 'text-bg-secondary',
        cancelled: 'text-bg-secondary'
    };
    return classes[status] || 'text-bg-secondary';
};

const openResponseModal = (reservation, type) => {
    selectedReservation.value = reservation;
    actionType.value = type;
    responseForm.reset();
    responseForm.clearErrors();
    if (type === 'approve') responseForm.owner_response = 'Your reservation has been approved. Please contact the boarding house owner for the next step.';
    Modal.getOrCreateInstance(document.getElementById('reservationResponseModal')).show();
};

const submitResponse = () => {
    const targetUrl = actionType.value === 'approve' ? selectedReservation.value.approve_url : selectedReservation.value.reject_url;
    responseForm.post(targetUrl, {
        preserveScroll: true,
        onSuccess: () => Modal.getOrCreateInstance(document.getElementById('reservationResponseModal')).hide(),
    });
};
</script>

<template>
    <OwnerLayout>
        <Head title="Reservations | E-BoardMate Owner Portal" />

        <div class="container">
            <div v-if="flashSuccess" class="alert alert-success mb-4">{{ flashSuccess }}</div>
            <div v-if="responseForm.errors.reservation" class="alert alert-danger mb-4">{{ responseForm.errors.reservation }}</div>

            <div class="mb-4">
                <span class="badge badge-soft-green mb-3">Owner Reservations</span>
                <h1 class="fw-bold mb-2">Reservation Management</h1>
                <p class="ebm-muted mb-0">View, approve, reject, or archive reservation requests for your assigned boarding house.</p>
            </div>

            <div v-if="!boardingHouse" class="ebm-card p-4 p-md-5">
                <h2 class="h4 fw-bold mb-2">No assigned boarding house yet</h2>
                <p class="ebm-muted mb-0">Your owner account does not have an assigned boarding house listing yet. Please contact the super admin.</p>
            </div>

            <div v-else class="ebm-card p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                    <div>
                        <h2 class="h5 fw-bold mb-1">{{ boardingHouse.name }}</h2>
                        <p class="ebm-muted small mb-0">Showing all reservation requests for this boarding house.</p>
                    </div>
                    <div>
                        <span v-if="boardingHouse.is_verified" class="badge text-bg-success">Verified</span>
                        <span v-else class="badge text-bg-warning">Not Verified</span>
                    </div>
                </div>

                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link text-capitalize" :class="{ 'active': filters.status === 'all' }" href="/owner/reservations?status=all">All</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-capitalize" :class="{ 'active': filters.status === 'pending' }" href="/owner/reservations?status=pending">Pending</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-capitalize" :class="{ 'active': filters.status === 'approved' }" href="/owner/reservations?status=approved">Approved</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-capitalize" :class="{ 'active': filters.status === 'rejected' }" href="/owner/reservations?status=rejected">Rejected</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-capitalize" :class="{ 'active': filters.status === 'expired' }" href="/owner/reservations?status=expired">Expired</a>
                    </li>
                </ul>

                <div v-if="reservations.length" class="table-responsive">
                    <table class="table align-middle owner-table">
                        <thead>
                            <tr>
                                <th>Reference</th><th>Guest</th><th>Move-in</th><th>Status</th><th>Message</th><th>Email</th><th>Submitted</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="res in reservations" :key="res.id">
                                <td><strong>{{ res.reference_code }}</strong></td>
                                <td>
                                    <div class="fw-semibold">{{ res.guest_name }}</div>
                                    <div class="small ebm-muted">{{ res.guest_email }}</div>
                                    <div class="small ebm-muted">{{ res.guest_phone }}</div>
                                </td>
                                <td>{{ res.preferred_move_in_date }}</td>
                                <td>
                                    <span class="badge" :class="statusBadgeClass(res.status)">{{ res.status_label }}</span>
                                    <div v-if="res.responded_at" class="small ebm-muted mt-1">Responded: {{ res.responded_at }}</div>
                                </td>
                                <td class="small">
                                    <div v-if="res.message"><strong>Guest:</strong> {{ res.message }}</div>
                                    <div v-if="res.owner_response" class="mt-2"><strong>Owner:</strong> {{ res.owner_response }}</div>
                                    <span v-if="!res.message && !res.owner_response" class="ebm-muted">None</span>
                                </td>
                                <td>
                                    <span v-if="res.email_notification_status" class="badge text-bg-light border text-dark">{{ res.email_notification_status }}</span>
                                    <span v-else class="small ebm-muted">Not sent</span>
                                </td>
                                <td class="small ebm-muted">{{ res.created_at }}</td>
                                <td>
                                    <div class="d-flex flex-column gap-2">
                                        <button v-if="res.can_respond" class="btn btn-sm btn-success" @click="openResponseModal(res, 'approve')">Approve</button>
                                        <button v-if="res.can_respond" class="btn btn-sm btn-outline-danger" @click="openResponseModal(res, 'reject')">Reject</button>
                                        <button class="btn btn-sm btn-outline-secondary" @click="archiveReservation(res)">Archive</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-5 ebm-muted">No reservations found in this category.</div>
            </div>
        </div>

        <div id="reservationResponseModal" class="modal fade" tabindex="-1" aria-hidden="true">
             <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form @submit.prevent="submitResponse">
                        <div class="modal-header">
                            <h2 class="modal-title h5 fw-bold">{{ selectedReservation?.reference_code }}</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label">Owner Response</label>
                            <textarea v-model="responseForm.owner_response" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn" :class="actionType === 'approve' ? 'btn-success' : 'btn-danger'">Save Response</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>