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
            
            // 2. Instantly remove the row from the list (Optimistic UI Update)
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

// Safely generates Initials for the Avatar
const getInitials = (name) => {
    if (!name) return '??';
    const parts = name.trim().split(/\s+/); 
    if (parts.length >= 2 && parts[0] && parts[1]) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return parts[0].substring(0, 2).toUpperCase();
};

const statusBadgeClass = (status) => {
    const classes = {
        pending: 'badge-soft-warning',
        approved: 'badge-soft-success',
        rejected: 'badge-soft-danger',
        expired: 'badge-soft-secondary',
        cancelled: 'badge-soft-secondary'
    };
    return classes[status] || 'badge-soft-secondary';
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

        <div class="container-fluid pb-5 px-0 px-md-3 max-w-desktop mx-auto">
            
            <!-- ALERTS -->
            <div v-if="flashSuccess" class="alert alert-success mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4">{{ flashSuccess }}</div>
            <div v-if="responseForm.errors.reservation" class="alert alert-danger mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4">{{ responseForm.errors.reservation }}</div>

            <!-- NATIVE HEADER SECTION -->
            <header class="d-flex justify-content-between align-items-center px-3 px-md-0 mb-4 pt-3">
                <div>
                    <h1 class="fw-bold mb-0 text-body-emphasis" style="font-size: 1.75rem;">Reservations</h1>
                    <span class="small text-body-secondary">Manage Guest Bookings</span>
                </div>
                <!-- 🚀 FIX: Fallback icon color used to guarantee visibility -->
                <button class="btn btn-light bg-body border-secondary-subtle shadow-sm rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 45px; height: 45px;">
                    <i class="bi bi-funnel-fill text-secondary fs-5"></i>
                </button>
            </header>

            <section v-if="!boardingHouse" class="mx-3 mx-md-0 ebm-card p-4 p-md-5 text-center shadow-sm rounded-4">
                <div class="fs-1 mb-3">🏠</div>
                <h2 class="h4 fw-bold mb-2">No assigned property</h2>
                <p class="text-body-secondary mb-0">Your owner account does not have an assigned boarding house listing yet.</p>
            </section>

            <template v-else>
                <!-- NATIVE SEGMENTED FILTER -->
                <div class="px-3 px-md-0 mb-4">
                    <div class="native-segmented-control d-flex overflow-x-auto hide-scrollbar">
                        <Link href="/owner/reservations?status=all" preserve-scroll class="flex-shrink-0" :class="{ 'active': filters.status === 'all' }">All</Link>
                        <Link href="/owner/reservations?status=pending" preserve-scroll class="flex-shrink-0" :class="{ 'active': filters.status === 'pending' }">Pending</Link>
                        <Link href="/owner/reservations?status=approved" preserve-scroll class="flex-shrink-0" :class="{ 'active': filters.status === 'approved' }">Approved</Link>
                        <Link href="/owner/reservations?status=rejected" preserve-scroll class="flex-shrink-0" :class="{ 'active': filters.status === 'rejected' }">Rejected</Link>
                        <Link href="/owner/reservations?status=expired" preserve-scroll class="flex-shrink-0" :class="{ 'active': filters.status === 'expired' }">Expired</Link>
                    </div>
                </div>

                <!-- NATIVE RESPONSIVE LIST VIEW (No Box Cards) -->
                <section class="px-3 px-md-0 mb-5">
                    
                    <div v-if="reservations.length" class="bg-body rounded-4 border border-secondary-subtle overflow-hidden shadow-sm">
                        
                        <!-- Desktop Header Row (Hidden on mobile) -->
                        <div class="d-none d-lg-flex align-items-center justify-content-between px-4 py-3 bg-body-tertiary border-bottom border-secondary-subtle small fw-bold text-body-secondary text-uppercase tracking-tight">
                            <div style="width: 25%;">Guest / Ref</div>
                            <div style="width: 25%;">Move-In / Contact</div>
                            <div style="width: 20%;">Status</div>
                            <div style="width: 30%;" class="text-end">Actions</div>
                        </div>

                        <div class="d-flex flex-column reservations-scroll-container">
                            
                            <div v-for="(res, index) in reservations" :key="res.id" 
                                 class="native-list-item p-3 p-md-4 transition-all"
                                 :class="{ 'border-bottom border-secondary-subtle': index !== reservations.length - 1 }">
                                
                                <div class="row align-items-center g-3">
                                    
                                    <!-- Col 1: Guest Avatar & Info -->
                                    <div class="col-12 col-lg-3">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="guest-avatar bg-success-subtle text-success fw-bold flex-shrink-0 border border-success-subtle">
                                                {{ getInitials(res.guest_name) }}
                                            </div>
                                            <div style="min-width: 0;">
                                                <h3 class="h6 fw-bold mb-1 text-body-emphasis text-truncate" style="line-height: 1.2;">
                                                    {{ res.guest_name }}
                                                </h3>
                                                <div class="small font-monospace text-body-secondary text-truncate">
                                                    Ref: {{ res.reference_code }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Col 2: Move-in & Email -->
                                    <div class="col-12 col-lg-3">
                                        <div class="small text-body-secondary d-flex flex-column gap-1">
                                            <div class="d-flex align-items-center gap-1">
                                                <i class="bi bi-calendar-event text-success"></i> Move-in: <strong class="text-body-emphasis">{{ res.preferred_move_in_date }}</strong>
                                            </div>
                                            <div class="text-truncate" :title="res.guest_email">
                                                <i class="bi bi-envelope me-1"></i> {{ res.guest_email }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Col 3: Status Badge -->
                                    <div class="col-12 col-sm-6 col-lg-2">
                                        <span class="badge rounded-pill px-3 py-2 shadow-sm d-inline-block text-center" :class="statusBadgeClass(res.status)">
                                            {{ res.status_label }}
                                        </span>
                                    </div>

                                    <!-- Col 4: Responsive Action Buttons -->
                                    <div class="col-12 col-sm-6 col-lg-4 text-sm-end mt-2 mt-sm-0">
                                        <div class="d-flex align-items-center justify-content-start justify-content-sm-end flex-wrap gap-2">
                                            <template v-if="res.can_respond">
                                                <button class="btn btn-sm btn-native-primary rounded-pill px-3 py-1.5 fw-bold shadow-sm" @click="openResponseModal(res, 'approve')">Approve</button>
                                                <button class="btn btn-sm btn-native-outline-danger rounded-pill px-3 py-1.5 fw-bold" @click="openResponseModal(res, 'reject')">Reject</button>
                                            </template>
                                            <button class="btn btn-sm btn-outline-secondary rounded-pill fw-bold px-3 py-1.5 d-inline-flex align-items-center gap-1" @click="openArchiveModal(res)" title="Archive Reservation">
                                                <i class="bi bi-archive"></i>
                                                <span>Archive</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Col 5: Guest & Owner Messages (If present) -->
                                    <div v-if="res.message || res.owner_response" class="col-12 mt-2">
                                        <div class="bg-body-tertiary rounded-3 p-3 small border border-secondary-subtle">
                                            <div v-if="res.message" class="mb-1 text-break"><span class="fw-bold opacity-75">Guest Request Message:</span> <span class="fst-italic">"{{ res.message }}"</span></div>
                                            <div v-if="res.owner_response" class="text-success text-break"><span class="fw-bold">Landlord Response:</span> {{ res.owner_response }}</div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center p-5 bg-body rounded-4 shadow-sm border border-secondary-subtle">
                        <div class="fs-1 mb-3 opacity-50">📭</div>
                        <h3 class="h6 fw-bold mb-1">No reservations found</h3>
                        <p class="text-body-secondary small mb-0">There are no requests matching this status filter.</p>
                    </div>
                </section>
            </template>
        </div>

        <!-- RESPONSE MODAL -->
        <div id="reservationResponseModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered border-0">
                <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                    <form @submit.prevent="submitResponse">
                        
                        <div class="modal-header border-bottom-0 text-white" :class="actionType === 'approve' ? 'bg-success' : 'bg-danger'">
                            <div>
                                <h2 class="modal-title h5 fw-bold mb-0">
                                    {{ actionType === 'approve' ? 'Approve' : 'Reject' }} Reservation
                                </h2>
                                <div class="small opacity-75 mt-1 font-monospace">Ref: {{ selectedReservation?.reference_code }}</div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4 bg-body">
                            <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-body-tertiary rounded-4 border border-secondary-subtle">
                                <div class="guest-avatar bg-white border border-secondary-subtle fw-bold text-body-secondary shadow-sm">
                                    {{ getInitials(selectedReservation?.guest_name) }}
                                </div>
                                <div>
                                    <div class="small text-body-secondary fw-bold text-uppercase tracking-tight">Guest Name</div>
                                    <div class="fw-bold fs-5 text-body-emphasis">{{ selectedReservation?.guest_name }}</div>
                                </div>
                            </div>

                            <label class="form-label fw-bold">Owner Response <span v-if="actionType === 'reject'" class="text-danger">*</span></label>
                            <textarea v-model="responseForm.owner_response" class="form-control bg-body-tertiary rounded-4 focus-ring focus-ring-success" :class="{ 'is-invalid': responseForm.errors.owner_response, 'focus-ring-danger': actionType === 'reject' }" rows="4" placeholder="Write a message to the guest..."></textarea>
                            <div v-if="responseForm.errors.owner_response" class="invalid-feedback fw-bold">{{ responseForm.errors.owner_response }}</div>
                        </div>

                        <div class="modal-footer bg-body-tertiary border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-medium" data-bs-dismiss="modal" :disabled="responseForm.processing">Cancel</button>
                            <button type="submit" class="btn rounded-pill fw-bold shadow-sm px-4" :class="actionType === 'approve' ? 'btn-success' : 'btn-danger'" :disabled="responseForm.processing">
                                <span v-if="responseForm.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                {{ responseForm.processing ? 'Saving...' : 'Confirm ' + (actionType === 'approve' ? 'Approval' : 'Rejection') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ARCHIVE MODAL -->
        <div id="archiveModal" class="modal fade" tabindex="-1" aria-hidden="true">
             <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content text-center p-4 shadow-lg border-0 bg-body rounded-4">
                    <div class="mb-3">
                        <i class="bi bi-archive text-secondary opacity-50" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2 text-body-emphasis">Archive Reservation?</h3>
                    <p class="text-body-secondary small mb-4">This reservation (<strong v-if="reservationToArchive" class="font-monospace text-body-emphasis">{{ reservationToArchive.reference_code }}</strong>) will be moved out of your active view.</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3 fw-medium flex-grow-1" data-bs-dismiss="modal" :disabled="archiveForm.processing">Cancel</button>
                        <button type="button" class="btn btn-secondary rounded-pill px-4 fw-bold shadow-sm flex-grow-1 d-flex align-items-center justify-content-center" @click="submitArchive" :disabled="archiveForm.processing">
                            <span v-if="archiveForm.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Archive
                        </button>
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

/* Segmented Control */
.native-segmented-control {
    background-color: rgba(var(--bs-secondary-bg-rgb), 1);
    padding: 4px;
    border-radius: 12px;
}
.native-segmented-control a {
    text-decoration: none;
    background: transparent;
    border: none;
    padding: 8px 20px;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    border-radius: 8px;
    transition: all 0.2s;
    text-align: center;
}
.native-segmented-control a.active {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}

/* List View Elements */
.native-list-item:hover {
    background-color: rgba(var(--bs-secondary-bg-rgb), 0.3);
}
.guest-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

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
.btn-native-outline-danger {
    background-color: transparent;
    color: #dc3545;
    border: 1px solid #dc3545;
}
.btn-native-outline-danger:hover { background-color: #dc3545; color: white; }

.reservations-scroll-container {
    max-height: 650px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-y;
    padding-bottom: 20px;
}

.reservations-scroll-container::-webkit-scrollbar {
    width: 6px;
}
.reservations-scroll-container::-webkit-scrollbar-thumb {
    background-color: rgba(var(--bs-secondary-rgb), 0.3);
    border-radius: 10px;
}
</style>