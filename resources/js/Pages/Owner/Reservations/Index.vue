<script setup>
import { Head, useForm, usePage, router, Link } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { Modal } from 'bootstrap';
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

// --- NEW ARCHIVE LOGIC WITH OPTIMISTIC UI ---
const archiveForm = useForm({});
const reservationToArchive = ref(null);

const openArchiveModal = (reservation) => {
    reservationToArchive.value = reservation;
    Modal.getOrCreateInstance(document.getElementById('archiveModal')).show();
};

const submitArchive = () => {
    if (!reservationToArchive.value) return;
    
    archiveForm.post(reservationToArchive.value.archive_url, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            // 1. Hide the Modal
            Modal.getOrCreateInstance(document.getElementById('archiveModal')).hide();
            
            // 2. Instantly remove the row from the table (Optimistic UI Update)
            const index = props.reservations.findIndex(r => r.id === reservationToArchive.value.id);
            if (index !== -1) {
                props.reservations.splice(index, 1);
            }

            // 3. Reset state
            reservationToArchive.value = null; 
        },
    });
};
// ----------------------------------------------

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
                        <Link 
                            class="nav-link text-capitalize" 
                            :class="{ 'active': filters.status === 'all' }" 
                            href="/owner/reservations?status=all"
                            preserve-scroll
                        >
                            All
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link 
                            class="nav-link text-capitalize" 
                            :class="{ 'active': filters.status === 'pending' }" 
                            href="/owner/reservations?status=pending"
                            preserve-scroll
                        >
                            Pending
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link 
                            class="nav-link text-capitalize" 
                            :class="{ 'active': filters.status === 'approved' }" 
                            href="/owner/reservations?status=approved"
                            preserve-scroll
                        >
                            Approved
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link 
                            class="nav-link text-capitalize" 
                            :class="{ 'active': filters.status === 'rejected' }" 
                            href="/owner/reservations?status=rejected"
                            preserve-scroll
                        >
                            Rejected
                        </Link>
                    </li>
                    <li class="nav-item">
                        <Link 
                            class="nav-link text-capitalize" 
                            :class="{ 'active': filters.status === 'expired' }" 
                            href="/owner/reservations?status=expired"
                            preserve-scroll
                        >
                            Expired
                        </Link>
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
                                        <button class="btn btn-sm btn-outline-secondary" @click="openArchiveModal(res)">Archive</button>
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

        <div id="archiveModal" class="modal fade" tabindex="-1" aria-hidden="true">
             <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content text-center p-4">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="bi bi-box-arrow-in-down" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M3.5 10a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 0 0 1h2A1.5 1.5 0 0 0 14 9.5v-8A1.5 1.5 0 0 0 12.5 0h-9A1.5 1.5 0 0 0 2 1.5v8A1.5 1.5 0 0 0 3.5 11h2a.5.5 0 0 0 0-1h-2z"/>
                            <path fill-rule="evenodd" d="M7.646 15.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 14.293V5.5a.5.5 0 0 0-1 0v8.793l-2.146-2.147a.5.5 0 0 0-.708.708l3 3z"/>
                        </svg>
                    </div>
                    <h3 class="h5 fw-bold mb-2">Archive Reservation?</h3>
                    <p class="text-muted small mb-4">This reservation (<strong v-if="reservationToArchive">{{ reservationToArchive.reference_code }}</strong>) will be removed from your active view.</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" :disabled="archiveForm.processing">Cancel</button>
                        <button type="button" class="btn btn-secondary px-4 d-flex align-items-center" @click="submitArchive" :disabled="archiveForm.processing">
                            <span v-if="archiveForm.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Yes, Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </OwnerLayout>
</template>