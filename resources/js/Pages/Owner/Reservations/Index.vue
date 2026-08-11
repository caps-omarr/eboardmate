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

        <div class="container pb-5">
            
            <!-- ALERTS -->
            <div v-if="flashSuccess" class="alert alert-success mb-4 shadow-sm border-0">{{ flashSuccess }}</div>
            <div v-if="responseForm.errors.reservation" class="alert alert-danger mb-4 shadow-sm border-0">{{ responseForm.errors.reservation }}</div>

            <!-- HEADER SECTION -->
            <header class="mb-4 pt-2">
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mb-3 px-3 py-2 rounded-pill shadow-sm">Owner Reservations</span>
                <h1 class="fw-bold mb-2 tracking-tight">Reservation Management</h1>
                <p class="text-body-secondary mb-0 lead" style="font-size: 1.1rem;">View, approve, reject, or archive reservation requests for your assigned boarding house.</p>
            </header>

            <section v-if="!boardingHouse" class="ebm-card p-4 p-md-5 text-center shadow-sm border border-secondary-subtle">
                <div class="fs-1 mb-3">🏠</div>
                <h2 class="h4 fw-bold mb-2">No assigned boarding house yet</h2>
                <p class="text-body-secondary mb-0">Your owner account does not have an assigned boarding house listing yet. Please contact the super admin.</p>
            </section>

            <section v-else class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle d-flex flex-column" style="min-height: 600px;">
                
                <!-- Card Header -->
                <div class="p-4 bg-body-tertiary border-bottom border-secondary-subtle">
                    <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1 text-body-emphasis">{{ boardingHouse.name }}</h2>
                            <p class="text-body-secondary small mb-0">Showing all reservation requests for this boarding house.</p>
                        </div>
                        <div>
                            <span v-if="boardingHouse.is_verified" class="badge text-bg-success shadow-sm">Verified</span>
                            <span v-else class="badge text-bg-warning shadow-sm">Not Verified</span>
                        </div>
                    </div>

                    <!-- 🚀 UX FIX: Swipeable Nav Tabs for Mobile -->
                    <div class="nav-tabs-wrapper">
                        <ul class="nav nav-tabs flex-nowrap border-bottom-0 pb-1">
                            <li class="nav-item">
                                <Link 
                                    class="nav-link text-capitalize fw-medium" 
                                    :class="{ 'active fw-bold': filters.status === 'all' }" 
                                    href="/owner/reservations?status=all"
                                    preserve-scroll
                                >
                                    All
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link 
                                    class="nav-link text-capitalize fw-medium" 
                                    :class="{ 'active fw-bold': filters.status === 'pending' }" 
                                    href="/owner/reservations?status=pending"
                                    preserve-scroll
                                >
                                    Pending
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link 
                                    class="nav-link text-capitalize fw-medium" 
                                    :class="{ 'active fw-bold': filters.status === 'approved' }" 
                                    href="/owner/reservations?status=approved"
                                    preserve-scroll
                                >
                                    Approved
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link 
                                    class="nav-link text-capitalize fw-medium" 
                                    :class="{ 'active fw-bold': filters.status === 'rejected' }" 
                                    href="/owner/reservations?status=rejected"
                                    preserve-scroll
                                >
                                    Rejected
                                </Link>
                            </li>
                            <li class="nav-item">
                                <Link 
                                    class="nav-link text-capitalize fw-medium" 
                                    :class="{ 'active fw-bold': filters.status === 'expired' }" 
                                    href="/owner/reservations?status=expired"
                                    preserve-scroll
                                >
                                    Expired
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- 🚀 UX FIX: The "Window Box" Table Scroll Wrapper -->
                <div v-if="reservations.length" class="table-responsive custom-table-scroll flex-grow-1 bg-body">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Reference</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Guest</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Move-in</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Message</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Email</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Submitted</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <tr v-for="res in reservations" :key="res.id">
                                <td class="text-nowrap ps-4">
                                    <strong class="font-monospace">{{ res.reference_code }}</strong>
                                </td>
                                <td class="text-nowrap">
                                    <div class="fw-bold text-body-emphasis">{{ res.guest_name }}</div>
                                    <div class="small text-body-secondary">{{ res.guest_email }}</div>
                                    <div class="small text-body-secondary">{{ res.guest_phone }}</div>
                                </td>
                                <td class="text-nowrap fw-medium">{{ res.preferred_move_in_date }}</td>
                                <td class="text-nowrap">
                                    <span class="badge shadow-sm" :class="statusBadgeClass(res.status)">{{ res.status_label }}</span>
                                    <div v-if="res.responded_at" class="small text-body-secondary mt-1">Responded: <br>{{ res.responded_at }}</div>
                                </td>
                                <!-- Added min-width so the message doesn't get crushed -->
                                <td class="small" style="min-width: 250px; max-width: 350px;">
                                    <div v-if="res.message" class="mb-1"><strong class="text-body-emphasis">Guest:</strong> {{ res.message }}</div>
                                    <div v-if="res.owner_response" class="p-2 bg-body-tertiary border border-secondary-subtle rounded mt-2">
                                        <strong class="text-body-emphasis">Owner:</strong> {{ res.owner_response }}
                                    </div>
                                    <span v-if="!res.message && !res.owner_response" class="text-body-secondary fst-italic">None</span>
                                </td>
                                <td class="text-nowrap">
                                    <span v-if="res.email_notification_status" class="badge bg-body-secondary text-body border border-secondary-subtle">{{ res.email_notification_status }}</span>
                                    <span v-else class="small text-body-secondary fst-italic">Not sent</span>
                                </td>
                                <td class="small text-body-secondary text-nowrap">{{ res.created_at }}</td>
                                <td class="text-nowrap text-end pe-4">
                                    <div class="d-flex flex-column gap-2 justify-content-end align-items-end">
                                        <template v-if="res.can_respond">
                                            <button class="btn btn-sm btn-success shadow-sm w-100" @click="openResponseModal(res, 'approve')">Approve</button>
                                            <button class="btn btn-sm btn-outline-danger w-100" @click="openResponseModal(res, 'reject')">Reject</button>
                                        </template>
                                        <button class="btn btn-sm btn-outline-secondary w-100" @click="openArchiveModal(res)">Archive</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="empty-state text-center p-5 flex-grow-1 d-flex flex-column justify-content-center align-items-center bg-body">
                    <div class="fs-1 mb-3 opacity-50">📭</div>
                    <h3 class="h5 fw-bold mb-2">No reservations found</h3>
                    <p class="text-body-secondary mb-0">There are no reservations matching this status.</p>
                </div>
            </section>
        </div>

        <!-- RESPONSE MODAL (Upgraded to match Dashboard) -->
        <div id="reservationResponseModal" class="modal fade" tabindex="-1" aria-labelledby="reservationResponseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered border-0">
                <div class="modal-content shadow-lg border-0 overflow-hidden">
                    <form @submit.prevent="submitResponse">
                        
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

                            <label class="form-label fw-bold">Owner Response <span v-if="actionType === 'reject'" class="text-danger">*</span></label>
                            <textarea v-model="responseForm.owner_response" class="form-control bg-body-tertiary focus-ring focus-ring-success" :class="{ 'is-invalid': responseForm.errors.owner_response, 'focus-ring-danger': actionType === 'reject' }" rows="4" placeholder="Write a message to the guest..."></textarea>
                            <div v-if="responseForm.errors.owner_response" class="invalid-feedback fw-bold">{{ responseForm.errors.owner_response }}</div>

                            <div class="alert alert-primary bg-primary bg-opacity-10 text-primary border-primary border-opacity-25 mt-4 mb-0 d-flex align-items-start gap-2">
                                <span>📧</span>
                                <small>The system will attempt to send this message to the guest via email.</small>
                            </div>
                        </div>

                        <div class="modal-footer bg-body-tertiary border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-outline-secondary fw-medium" data-bs-dismiss="modal" :disabled="responseForm.processing">Cancel</button>
                            <button type="submit" class="btn fw-bold shadow-sm px-4" :class="actionType === 'approve' ? 'btn-success' : 'btn-danger'" :disabled="responseForm.processing">
                                <span v-if="responseForm.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ responseForm.processing ? 'Saving...' : 'Confirm ' + (actionType === 'approve' ? 'Approval' : 'Rejection') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ARCHIVE MODAL (Refined styling) -->
        <div id="archiveModal" class="modal fade" tabindex="-1" aria-hidden="true">
             <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content text-center p-4 shadow-lg border-0 bg-body">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="bi bi-box-arrow-in-down opacity-50" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M3.5 10a.5.5 0 0 1-.5-.5v-8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 .5.5v8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 0 0 1h2A1.5 1.5 0 0 0 14 9.5v-8A1.5 1.5 0 0 0 12.5 0h-9A1.5 1.5 0 0 0 2 1.5v8A1.5 1.5 0 0 0 3.5 11h2a.5.5 0 0 0 0-1h-2z"/>
                            <path fill-rule="evenodd" d="M7.646 15.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 14.293V5.5a.5.5 0 0 0-1 0v8.793l-2.146-2.147a.5.5 0 0 0-.708.708l3 3z"/>
                        </svg>
                    </div>
                    <h3 class="h5 fw-bold mb-2 text-body-emphasis">Archive Reservation?</h3>
                    <p class="text-body-secondary small mb-4">This reservation (<strong v-if="reservationToArchive" class="font-monospace text-body-emphasis">{{ reservationToArchive.reference_code }}</strong>) will be removed from your active view.</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal" :disabled="archiveForm.processing">Cancel</button>
                        <button type="button" class="btn btn-secondary px-4 d-flex align-items-center shadow-sm" @click="submitArchive" :disabled="archiveForm.processing">
                            <span v-if="archiveForm.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Yes, Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </OwnerLayout>
</template>

<style scoped>
/* 🪄 UX FIX: Swipeable Navigation Tabs for Mobile */
.nav-tabs-wrapper {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none;  /* IE and Edge */
}
.nav-tabs-wrapper::-webkit-scrollbar {
    display: none; /* Chrome, Safari and Opera */
}
.nav-tabs .nav-link {
    white-space: nowrap; /* Prevents text from wrapping to a new line */
}

/* 🪄 UX FIX: The Custom "Window Box" Table Scroll */
.custom-table-scroll {
    max-height: calc(100vh - 350px); /* Dynamic height based on screen size */
    min-height: 400px;
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
    box-shadow: inset 0 -1px 0 var(--bs-border-color);
}

/* Typography refinements */
.tracking-tight {
    letter-spacing: -0.02em;
}
</style>