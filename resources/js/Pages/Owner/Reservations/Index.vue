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

// --- SILENT BACKGROUND POLLING & LOADING STATE ---
let pollingInterval = null;
const isLoading = ref(false);
let unhookStart = null;
let unhookFinish = null;

onMounted(() => {
    unhookStart = router.on('start', () => isLoading.value = true);
    unhookFinish = router.on('finish', () => isLoading.value = false);

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
    if (unhookStart) unhookStart();
    if (unhookFinish) unhookFinish();
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

const filteredReservations = computed(() => {
    return props.reservations || [];
});
</script>

<template>
    <OwnerLayout>
        <Head title="Reservations | E-BoardMate Owner Portal" />

        <div class="container-fluid owner-content-wrapper px-3 max-w-desktop mx-auto">
            
            <!-- ALERTS -->
            <div v-if="flashSuccess" class="alert alert-success mb-4 shadow-sm border-0 rounded-4">{{ flashSuccess }}</div>
            <div v-if="responseForm.errors.reservation" class="alert alert-danger mb-4 shadow-sm border-0 rounded-4">{{ responseForm.errors.reservation }}</div>

            <!-- NATIVE HEADER SECTION -->
            <header class="d-flex justify-content-between align-items-center mb-4 pt-3">
                <div>
                    <h1 class="fw-bold mb-0 text-body-emphasis" style="font-size: 1.75rem;">Reservations</h1>
                    <span class="small text-body-secondary">Manage Guest Bookings</span>
                </div>
                <!-- 🚀 FIX: Fallback icon color used to guarantee visibility -->
                <button class="btn btn-light bg-body border-secondary-subtle shadow-sm rounded-circle d-flex align-items-center justify-content-center p-0" style="width: 45px; height: 45px;">
                    <i class="bi bi-funnel-fill text-secondary fs-5"></i>
                </button>
            </header>

            <section v-if="!boardingHouse" class="ebm-card p-4 p-md-5 text-center shadow-sm rounded-4 border border-secondary-subtle">
                <i class="bi bi-house-door display-4 text-secondary opacity-50 mb-3 d-block"></i>
                <h2 class="h4 fw-bold mb-2 text-body-emphasis">No assigned property</h2>
                <p class="text-body-secondary mb-0">Your owner account does not have an assigned boarding house listing yet.</p>
            </section>

            <template v-else>
                <!-- NATIVE SEGMENTED FILTER (Horizontally scrollable, non-wrapping on mobile) -->
                <div class="mb-4">
                    <div class="d-flex gap-2 overflow-x-auto text-nowrap pb-2 mb-3 scrollbar-none native-segmented-control">
                        <Link href="/owner/reservations?status=all" preserve-scroll class="flex-shrink-0 text-decoration-none" :class="{ 'active': filters.status === 'all' }">All</Link>
                        <Link href="/owner/reservations?status=pending" preserve-scroll class="flex-shrink-0 text-decoration-none" :class="{ 'active': filters.status === 'pending' }">Pending</Link>
                        <Link href="/owner/reservations?status=approved" preserve-scroll class="flex-shrink-0 text-decoration-none" :class="{ 'active': filters.status === 'approved' }">Approved</Link>
                        <Link href="/owner/reservations?status=rejected" preserve-scroll class="flex-shrink-0 text-decoration-none" :class="{ 'active': filters.status === 'rejected' }">Rejected</Link>
                        <Link href="/owner/reservations?status=expired" preserve-scroll class="flex-shrink-0 text-decoration-none" :class="{ 'active': filters.status === 'expired' }">Expired</Link>
                    </div>
                </div>

                <!-- NATIVE RESPONSIVE MULTI-COLUMN GRID -->
                <section class="mb-4">
                    
                    <!-- SKELETON GRID -->
                    <div v-if="isLoading" class="row g-3">
                        <div v-for="i in 6" :key="i" class="col-12 col-md-6 col-xl-4">
                            <div class="ebm-card bg-body rounded-4 border border-secondary-subtle p-3.5 p-md-4 shadow-sm h-100 d-flex flex-column placeholder-glow">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="placeholder rounded-circle flex-shrink-0 bg-secondary bg-opacity-25" style="width: 44px; height: 44px;"></span>
                                    <div class="flex-grow-1">
                                        <span class="placeholder col-6 py-2 rounded mb-2 d-block bg-secondary bg-opacity-25"></span>
                                        <span class="placeholder col-4 py-1 rounded d-block bg-secondary bg-opacity-25"></span>
                                    </div>
                                </div>
                                <div class="my-auto py-2">
                                    <span class="placeholder col-8 py-2 rounded mb-2 d-block bg-secondary bg-opacity-25"></span>
                                    <span class="placeholder col-10 py-2 rounded d-block bg-secondary bg-opacity-25"></span>
                                </div>
                                <div class="mt-3 pt-3 border-top border-secondary-subtle d-flex gap-2">
                                    <span class="placeholder col-6 py-3 rounded-3 bg-secondary bg-opacity-25"></span>
                                    <span class="placeholder col-6 py-3 rounded-3 bg-secondary bg-opacity-25"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LOADED RESERVATIONS GRID -->
                    <div v-else-if="filteredReservations.length" class="row g-3">
                        <div v-for="res in filteredReservations" :key="res.id" class="col-12 col-md-6 col-xl-4">
                            <div class="ebm-card reservation-card bg-body rounded-4 border border-secondary-subtle p-3.5 p-md-4 shadow-sm h-100 d-flex flex-column transition-all">
                                
                                <!-- Top Header: Avatar, Name/Ref, Status Badge -->
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2.5" style="min-width: 0;">
                                        <div class="guest-avatar bg-success-subtle text-success fw-bold flex-shrink-0 border border-success-subtle">
                                            {{ getInitials(res.guest_name) }}
                                        </div>
                                        <div style="min-width: 0;">
                                            <h3 class="h6 fw-bold mb-0.5 text-body-emphasis text-truncate" style="max-width: 155px;" :title="res.guest_name">
                                                {{ res.guest_name }}
                                            </h3>
                                            <div class="small font-monospace text-body-secondary text-truncate">
                                                Ref: {{ res.reference_code }}
                                            </div>
                                        </div>
                                    </div>

                                    <span class="badge rounded-2 px-2.5 py-1.5 shadow-sm text-center fw-semibold flex-shrink-0" :class="statusBadgeClass(res.status)">
                                        {{ res.status_label }}
                                    </span>
                                </div>

                                <!-- Middle Content: Details, Move-In, Contact -->
                                <div class="my-auto d-flex flex-column gap-2 py-1">
                                    <div class="small text-body-secondary d-flex align-items-center gap-1.5">
                                        <i class="bi bi-clock-history text-secondary flex-shrink-0"></i>
                                        <span>Requested:</span>
                                        <span class="text-body-emphasis fw-medium">{{ res.submitted_at_formatted || res.created_at || 'N/A' }}</span>
                                    </div>
                                    <div class="small text-body-secondary d-flex align-items-center gap-1.5">
                                        <i class="bi bi-calendar-event text-success flex-shrink-0"></i>
                                        <span>Move-in:</span>
                                        <strong class="text-body-emphasis">{{ res.preferred_move_in_date || 'N/A' }}</strong>
                                    </div>
                                    <div class="small text-body-secondary text-truncate" style="max-width: 100%;" :title="res.guest_email">
                                        <i class="bi bi-envelope me-1 text-primary flex-shrink-0"></i>
                                        <span>{{ res.guest_email }}</span>
                                    </div>
                                    <div v-if="res.guest_phone" class="small text-body-secondary text-truncate" :title="res.guest_phone">
                                        <i class="bi bi-telephone me-1 text-secondary flex-shrink-0"></i>
                                        <span>{{ res.guest_phone }}</span>
                                    </div>

                                    <!-- Guest Message & Landlord Response Box -->
                                    <div v-if="res.message || res.owner_response" class="bg-body-tertiary rounded-3 p-2.5 small border border-secondary-subtle mt-2">
                                        <div v-if="res.message" class="text-break mb-1.5" style="word-break: break-word;">
                                            <span class="fw-bold opacity-75 d-block text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.04em;">Guest Message</span>
                                            <span class="fst-italic text-body-emphasis">"{{ res.message }}"</span>
                                        </div>
                                        <div v-if="res.owner_response" class="text-success text-break" style="word-break: break-word;">
                                            <span class="fw-bold d-block text-uppercase" style="font-size: 0.68rem; letter-spacing: 0.04em;">Landlord Response</span>
                                            <span>{{ res.owner_response }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer: Action Buttons (Pinned to Bottom) -->
                                <div class="mt-auto pt-3 border-top border-secondary-subtle">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <template v-if="res.can_respond">
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-native-primary rounded-3 px-3 py-2 fw-bold shadow-sm flex-grow-1 text-nowrap d-flex align-items-center justify-content-center gap-1.5"
                                                style="min-height: 40px;"
                                                @click="openResponseModal(res, 'approve')"
                                            >
                                                <i class="bi bi-check-circle-fill"></i>
                                                <span>Approve</span>
                                            </button>
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-outline-danger rounded-3 px-3 py-2 fw-bold flex-grow-1 text-nowrap d-flex align-items-center justify-content-center gap-1.5"
                                                style="min-height: 40px;"
                                                @click="openResponseModal(res, 'reject')"
                                            >
                                                <i class="bi bi-x-circle"></i>
                                                <span>Decline</span>
                                            </button>
                                        </template>
                                        <button 
                                            type="button" 
                                            class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1.5"
                                            :class="{ 'w-100': !res.can_respond }"
                                            style="min-height: 40px;"
                                            @click="openArchiveModal(res)" 
                                            title="Archive Reservation"
                                        >
                                            <i class="bi bi-archive"></i>
                                            <span>Archive</span>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center p-5 bg-body rounded-4 shadow-sm border border-secondary-subtle">
                        <i class="bi bi-inbox display-4 text-secondary opacity-50 mb-3 d-block"></i>
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
                            <button type="submit" class="btn rounded-3 fw-bold shadow-sm px-4 py-2" :class="actionType === 'approve' ? 'btn-success' : 'btn-danger'" :disabled="responseForm.processing" style="min-height: 44px;">
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
                <div class="modal-content text-center p-4 shadow-lg border border-secondary-subtle bg-body rounded-4">
                    <div class="mb-3">
                        <i class="bi bi-archive text-secondary opacity-50" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="h5 fw-bold mb-2 text-body-emphasis">Archive Reservation?</h3>
                    <p class="text-body-secondary small mb-4">This reservation (<strong v-if="reservationToArchive" class="font-monospace text-body-emphasis">{{ reservationToArchive.reference_code }}</strong>) will be moved out of your active view.</p>
                    
                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary rounded-3 px-3 fw-semibold flex-grow-1" data-bs-dismiss="modal" :disabled="archiveForm.processing" style="min-height: 44px;">Cancel</button>
                        <button type="button" class="btn btn-secondary rounded-3 px-4 fw-bold shadow-sm flex-grow-1 d-flex align-items-center justify-content-center" @click="submitArchive" :disabled="archiveForm.processing" style="min-height: 44px;">
                            <span v-if="archiveForm.processing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            <span>Archive</span>
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
    max-width: 1320px;
}

.owner-content-wrapper {
    padding-bottom: calc(5.5rem + env(safe-area-inset-bottom, 0px));
}

@media (min-width: 768px) {
    .owner-content-wrapper {
        padding-bottom: 2rem;
    }
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
    padding: 8px 18px;
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

.scrollbar-none {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-none::-webkit-scrollbar {
    display: none;
}

/* Card Visuals */
.reservation-card {
    transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.reservation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08) !important;
}

.guest-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
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
    transition: all 0.2s ease;
}
.btn-native-primary:hover { 
    background-color: #059669; 
    color: white; 
}
.btn-native-outline-danger {
    background-color: transparent;
    color: #dc3545;
    border: 1px solid #dc3545;
}
.btn-native-outline-danger:hover { background-color: #dc3545; color: white; }
</style>