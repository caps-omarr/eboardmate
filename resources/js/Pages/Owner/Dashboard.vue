<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { Modal } from 'bootstrap';
import { computed, ref } from 'vue';

const props = defineProps({
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

// --- UI HELPER FUNCTIONS ---

// 🚀 FIX: Safely generates Initials for the Avatar (prevents "UNDEFINED" bug)
const getInitials = (name) => {
    if (!name) return '??';
    // The /\s+/ regex safely splits by any amount of spaces
    const parts = name.trim().split(/\s+/); 
    if (parts.length >= 2 && parts[0] && parts[1]) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return parts[0].substring(0, 2).toUpperCase();
};

// Calculates percentages for the mini progress bars
const getPercentage = (value, total) => {
    if (!total || total === 0) return 0;
    return Math.round((value / total) * 100);
};

// Calculates Occupancy Rate
const occupancyRate = computed(() => {
    if (!props.boardingHouse || props.boardingHouse.total_rooms === 0) return 0;
    const occupied = props.boardingHouse.total_rooms - props.boardingHouse.available_rooms;
    return getPercentage(occupied, props.boardingHouse.total_rooms);
});

const statusBadgeClass = (status) => {
    if (status === 'pending') return 'badge-soft-warning';
    if (status === 'approved') return 'badge-soft-success';
    if (status === 'rejected') return 'badge-soft-danger';
    return 'badge-soft-secondary';
};

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    });
};

// --- MODAL LOGIC ---
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
    if (!selectedReservation.value) return;

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

        <div class="container-fluid pb-5 px-0 px-md-3 max-w-desktop mx-auto">
            
            <!-- ALERTS -->
            <div v-if="flashSuccess" class="alert alert-success mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4">
                {{ flashSuccess }}
            </div>
            <div v-if="responseForm.errors.reservation" class="alert alert-danger mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4">
                {{ responseForm.errors.reservation }}
            </div>

            <!-- NATIVE HEADER SECTION -->
            <header class="d-flex justify-content-between align-items-center px-3 px-md-0 mb-4 pt-3">
                <h1 class="fw-bold mb-0 text-body-emphasis" style="font-size: 1.75rem;">Dashboard</h1>
                <!-- Owner Avatar (Circle image or initials) -->
                <div class="owner-avatar shadow-sm border border-2 border-white bg-success text-white fw-bold d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0">
                    <img v-if="owner.profile_photo_url || owner.avatar" :src="owner.profile_photo_url || owner.avatar" :alt="owner.name" class="w-100 h-100 object-fit-cover">
                    <span v-else>{{ getInitials(owner.name) }}</span>
                </div>
            </header>

            <!-- NO BOARDING HOUSE ASSIGNED -->
            <section v-if="!boardingHouse" class="mx-3 mx-md-0 ebm-card p-4 p-md-5 text-center shadow-sm rounded-4">
                <div class="fs-1 mb-3">🏠</div>
                <h2 class="h4 fw-bold mb-2">No assigned boarding house</h2>
                <p class="text-body-secondary mb-0">Contact the super admin to link your property.</p>
            </section>

            <!-- MAIN DASHBOARD CONTENT -->
            <template v-else>
                
                <!-- 🚀 RESPONSIVE BOOTSTRAP 5 STATS GRID (Stacks vertically on small screens) -->
                <section class="row g-3 px-3 px-md-0 mb-4" aria-label="Dashboard Statistics">
                    
                    <!-- Total Reservations Card -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="native-card card-highlight d-flex flex-column justify-content-between h-100 p-4 rounded-4">
                            <div>
                                <strong class="fs-1 fw-bold lh-1 mb-1 d-block">{{ stats.total }}</strong>
                                <span class="small opacity-75">Total Reservations</span>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small opacity-75 mb-1 font-monospace" style="font-size: 0.7rem;">
                                    <span>0%</span>
                                    <span>{{ getPercentage(stats.approved, stats.total) }}% Apprv</span>
                                </div>
                                <div class="native-progress bg-black bg-opacity-25 rounded-pill">
                                    <div class="native-progress-bar bg-white rounded-pill" :style="`width: ${getPercentage(stats.approved, stats.total)}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Action Card -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="native-card bg-body shadow-sm d-flex flex-column justify-content-between h-100 p-4 rounded-4 border border-secondary-subtle">
                            <div>
                                <strong class="fs-1 fw-bold lh-1 mb-1 text-body-emphasis d-block">{{ stats.pending }}</strong>
                                <span class="small text-body-secondary">Pending Action</span>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-body-secondary mb-1 font-monospace" style="font-size: 0.7rem;">
                                    <span>0%</span>
                                    <span>{{ getPercentage(stats.pending, stats.total) }}%</span>
                                </div>
                                <div class="native-progress bg-secondary bg-opacity-10 rounded-pill">
                                    <div class="native-progress-bar bg-warning rounded-pill" :style="`width: ${getPercentage(stats.pending, stats.total)}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rooms Occupied Card -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="native-card bg-body shadow-sm d-flex flex-column justify-content-between h-100 p-4 rounded-4 border border-secondary-subtle">
                            <div>
                                <strong class="fs-1 fw-bold lh-1 mb-1 text-body-emphasis d-block">
                                    {{ boardingHouse.total_rooms - boardingHouse.available_rooms }}/{{ boardingHouse.total_rooms }}
                                </strong>
                                <span class="small text-body-secondary">Rooms Occupied</span>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex justify-content-between small text-body-secondary mb-1 font-monospace" style="font-size: 0.7rem;">
                                    <span>0%</span>
                                    <span>{{ occupancyRate }}%</span>
                                </div>
                                <div class="native-progress bg-secondary bg-opacity-10 rounded-pill">
                                    <div class="native-progress-bar bg-success rounded-pill" :style="`width: ${occupancyRate}%`"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Rent Card -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="native-card bg-body shadow-sm d-flex flex-column justify-content-between h-100 p-4 rounded-4 border border-secondary-subtle">
                            <div>
                                <strong class="fs-3 fw-bold lh-1 mb-1 text-success d-block">₱{{ formatPrice(boardingHouse.rent_price) }}</strong>
                                <span class="small text-body-secondary">Monthly Rent</span>
                            </div>
                            <div class="mt-3">
                                <div class="d-flex align-items-center gap-1">
                                    <span v-if="boardingHouse.is_verified" class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1">Verified</span>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-2 py-1">{{ boardingHouse.status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- MOBILE-FIRST RESPONSIVE SCROLLABLE RESERVATION LIST -->
                <section class="px-3 px-md-0 mb-5" aria-label="Latest Reservations">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-0 text-body-emphasis">Recent Activity</h2>
                            <span class="small text-body-secondary">Latest guest reservation requests</span>
                        </div>
                        <Link href="/owner/reservations" class="btn btn-sm btn-outline-success rounded-pill fw-semibold px-3">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </Link>
                    </div>

                    <div v-if="reservations.length" class="recent-activity-scroll-container pe-1">
                        <div class="native-list-group d-flex flex-column gap-3">
                            
                            <!-- Reservation Item Card -->
                            <div v-for="reservation in reservations" :key="reservation.id" class="native-list-item bg-body shadow-sm p-3 rounded-4 d-flex align-items-center gap-3 border border-secondary-subtle">
                                
                                <!-- Guest Avatar -->
                                <div class="guest-avatar bg-success-subtle text-success fw-bold flex-shrink-0 border border-success-subtle">
                                    {{ getInitials(reservation.guest_name) }}
                                </div>

                                <!-- Info -->
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h3 class="h6 fw-bold mb-0 text-truncate text-body-emphasis pe-2" style="line-height: 1.2;">{{ reservation.guest_name }}</h3>
                                        <span class="small font-monospace text-body-secondary flex-shrink-0">{{ reservation.created_at.split(' ')[0] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="small text-body-secondary text-truncate">Move-in: <strong class="text-body-emphasis">{{ reservation.preferred_move_in_date }}</strong></span>
                                        <span class="badge rounded-pill px-2 py-1" :class="statusBadgeClass(reservation.status)">
                                            {{ reservation.status_label }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div v-if="reservation.can_respond" class="d-flex flex-column gap-2 ms-2 flex-shrink-0">
                                    <button type="button" class="btn btn-sm btn-native-primary rounded-pill px-3 fw-bold shadow-sm" @click="openResponseModal(reservation, 'approve')">
                                        Approve
                                    </button>
                                    <button type="button" class="btn btn-sm btn-native-outline-danger rounded-pill px-3 fw-bold" @click="openResponseModal(reservation, 'reject')">
                                        Reject
                                    </button>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <div v-else class="text-center p-5 bg-body rounded-4 shadow-sm border border-secondary-subtle">
                        <div class="fs-1 mb-3 opacity-50">📋</div>
                        <h3 class="h6 fw-bold mb-1">No reservations yet</h3>
                        <p class="text-body-secondary small mb-0">Student requests will appear here.</p>
                    </div>
                </section>
                
            </template>
        </div>

        <!-- MODAL -->
        <div id="reservationResponseModal" class="modal fade" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered border-0">
                <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
                    <form @submit.prevent="submitResponse">
                        
                        <!-- Dynamic Header Color -->
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

                            <label for="owner_response" class="form-label fw-bold">
                                Owner Response <span v-if="actionType === 'reject'" class="text-danger">*</span>
                            </label>
                            
                            <textarea
                                id="owner_response"
                                v-model="responseForm.owner_response"
                                class="form-control bg-body-tertiary rounded-4 focus-ring focus-ring-success"
                                :class="{ 'is-invalid': responseForm.errors.owner_response }"
                                rows="4"
                                placeholder="Write a short message for the guest..."
                            />
                            <div v-if="responseForm.errors.owner_response" class="invalid-feedback fw-bold">
                                {{ responseForm.errors.owner_response }}
                            </div>
                        </div>

                        <div class="modal-footer bg-body-tertiary border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4 fw-medium" data-bs-dismiss="modal" :disabled="responseForm.processing">
                                Cancel
                            </button>
                            <button type="submit" class="btn rounded-pill fw-bold shadow-sm px-4" :class="actionType === 'approve' ? 'btn-success' : 'btn-danger'" :disabled="responseForm.processing">
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
/* Restrict max width on desktop */
.max-w-desktop {
    max-width: 1200px;
}

/* =========================================
   NATIVE APP UI COMPONENTS
========================================== */

/* Owner Avatar Header */
.owner-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    font-size: 1.1rem;
}

/* 2x2 Grid System */
.native-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
@media (min-width: 768px) {
    .native-grid { grid-template-columns: repeat(4, 1fr); }
}

.native-card {
    border-radius: 20px;
    padding: 20px;
    min-height: 140px;
}

/* Deep Emerald Highlight Card */
.card-highlight {
    background: linear-gradient(135deg, #022c22 0%, #065f46 100%);
    color: #ffffff;
    box-shadow: 0 10px 25px rgba(6, 95, 70, 0.4);
}

/* Mini Progress Bars */
.native-progress {
    height: 6px;
    border-radius: 10px;
    width: 100%;
    overflow: hidden;
}
.native-progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Segmented Control (Monthly/Weekly/Today style) */
.native-segmented-control {
    background-color: rgba(var(--bs-secondary-bg-rgb), 1);
    padding: 4px;
    border-radius: 12px;
}
.native-segmented-control button {
    background: transparent;
    border: none;
    padding: 6px 16px;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    border-radius: 8px;
    transition: all 0.2s;
}
.native-segmented-control button.active {
    background-color: var(--bs-body-bg);
    color: var(--bs-body-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

/* List View Enhancements */
.guest-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.badge-soft-success { background: rgba(25, 135, 84, 0.15); color: #198754; border: 1px solid rgba(25, 135, 84, 0.2); }
.badge-soft-warning { background: rgba(255, 193, 7, 0.15); color: #b08000; border: 1px solid rgba(255, 193, 7, 0.3); }
.badge-soft-danger { background: rgba(220, 53, 69, 0.15); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); }
.badge-soft-secondary { background: rgba(108, 117, 125, 0.15); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.2); }

/* Native-style buttons */
.btn-native-primary {
    background-color: #10b981;
    color: white;
    border: none;
    font-size: 0.75rem;
}
.btn-native-primary:hover { background-color: #059669; color: white; }
.btn-native-outline-danger {
    background-color: transparent;
    color: #dc3545;
    border: 1px solid #dc3545;
    font-size: 0.75rem;
}
.btn-native-outline-danger:hover { background-color: #dc3545; color: white; }

.recent-activity-scroll-container {
    max-height: 420px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-y;
    padding-bottom: 30px;
}

/* Custom smooth scrollbar */
.recent-activity-scroll-container::-webkit-scrollbar {
    width: 5px;
}
.recent-activity-scroll-container::-webkit-scrollbar-thumb {
    background-color: rgba(var(--bs-secondary-rgb), 0.3);
    border-radius: 10px;
}
</style>