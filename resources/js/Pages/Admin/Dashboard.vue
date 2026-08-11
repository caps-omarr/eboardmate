<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { onMounted, onUnmounted, ref, watch, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    admin: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    latestBoardingHouses: {
        type: Array,
        default: () => [],
    },
    latestReservations: {
        type: Array,
        default: () => [],
    },
});

// --- APPROVAL & REJECTION LOGIC ---
const approveBoardingHouse = (id) => {
    if (confirm('Are you sure you want to approve this boarding house? It will become visible to the public.')) {
        router.patch(`/admin/boarding-houses/${id}/approve`, {}, {
            preserveScroll: true,
            preserveState: true,
            onError: (errors) => {
                if (errors.listing) {
                    alert('Approval failed: ' + errors.listing);
                }
            }
        });
    }
};

const rejectBoardingHouse = (id) => {
    const reason = prompt('Please enter a reason for rejecting this boarding house:');
    if (reason !== null && reason.trim() !== '') {
        router.patch(`/admin/boarding-houses/${id}/reject`, { reason: reason }, {
            preserveScroll: true,
            preserveState: true,
            onError: (errors) => {
                if (errors.reason) {
                    alert('Rejection failed: ' + errors.reason);
                }
            }
        });
    } else if (reason !== null) {
        alert('A reason is required to reject a boarding house.');
    }
};
// ----------------------------------

// --- CHART.JS LOGIC WITH VUE REFS ---
const overviewCanvas = ref(null);
const distributionCanvas = ref(null);
let chartInstances = { overview: null, distribution: null };

// Universal Chart Colors (Readable in both Light and Dark modes)
const CHART_TEXT_COLOR = '#9ca3af'; // Slate gray
const CHART_GRID_COLOR = 'rgba(156, 163, 175, 0.15)'; // Transparent slate gray

const renderCharts = () => {
    // 1. Bar Chart: System Overview
    if (overviewCanvas.value) {
        if (chartInstances.overview) {
            chartInstances.overview.destroy();
        }
        chartInstances.overview = new Chart(overviewCanvas.value, {
            type: 'bar',
            data: {
                labels: ['Boarding Houses', 'Reservations', 'Owners'],
                datasets: [{
                    label: 'Total Count',
                    data: [props.stats.boarding_houses, props.stats.reservations, props.stats.owners],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107'],
                    borderRadius: 6,
                }]
            },
            options: { 
                maintainAspectRatio: false, 
                responsive: true,
                plugins: {
                    legend: { display: false } 
                },
                scales: {
                    x: { 
                        ticks: { color: CHART_TEXT_COLOR, font: { weight: '500' } }, 
                        grid: { color: CHART_GRID_COLOR, drawBorder: false } 
                    },
                    y: { 
                        ticks: { color: CHART_TEXT_COLOR, font: { weight: '500' } }, 
                        grid: { color: CHART_GRID_COLOR, drawBorder: false } 
                    }
                }
            }
        });
    }

    // 2. Doughnut Chart: Listing Distribution
    if (distributionCanvas.value) {
        if (chartInstances.distribution) {
            chartInstances.distribution.destroy();
        }
        chartInstances.distribution = new Chart(distributionCanvas.value, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending', 'Rejected', 'Deactivated'],
                datasets: [{
                    data: [
                        props.stats.approved_listings, 
                        props.stats.pending_listings, 
                        props.stats.rejected_listings,
                        props.stats.deactivated_listings
                    ],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545', '#6c757d'],
                    borderWidth: 0
                }]
            },
            options: { 
                maintainAspectRatio: false, 
                responsive: true, 
                cutout: '70%',
                plugins: {
                    legend: { 
                        labels: { color: CHART_TEXT_COLOR, font: { weight: '500' } } 
                    }
                }
            }
        });
    }
};

// Watch for changes from Silent Polling and update charts automatically
watch(() => props.stats, () => {
    nextTick(() => {
        renderCharts();
    });
}, { deep: true });
// ------------------------------------

// --- SILENT BACKGROUND POLLING ---
let pollingInterval = null;

onMounted(() => {
    nextTick(() => {
        renderCharts();
    });

    pollingInterval = setInterval(() => {
        router.reload({
            only: ['stats', 'latestBoardingHouses', 'latestReservations'], 
            preserveState: true,  
            preserveScroll: true, 
        });
    }, 10000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    if (chartInstances.overview) chartInstances.overview.destroy();
    if (chartInstances.distribution) chartInstances.distribution.destroy();
});
// ---------------------------------

const listingStatusBadgeClass = (status) => {
    if (status === 'approved') return 'text-bg-success';
    if (status === 'pending') return 'text-bg-warning';
    if (status === 'rejected') return 'text-bg-danger';
    if (status === 'deactivated') return 'text-bg-secondary';
    return 'text-bg-secondary';
};

const reservationStatusBadgeClass = (status) => {
    if (status === 'pending') return 'text-bg-warning';
    if (status === 'approved') return 'text-bg-success';
    if (status === 'rejected') return 'text-bg-danger';
    if (status === 'expired' || status === 'cancelled') return 'text-bg-secondary';
    return 'text-bg-secondary';
};

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Super Admin Dashboard | E-BoardMate" />

        <div class="container pb-5">
            
            <!-- HEADER SECTION -->
            <header class="mb-4 pt-2">
                <span class="badge bg-body text-body border border-secondary-subtle mb-3 px-3 py-2 rounded-pill shadow-sm">
                    Super Admin
                </span>

                <h1 class="fw-bold mb-2 tracking-tight">
                    Super Admin Dashboard
                </h1>

                <p class="text-body-secondary mb-0 lead" style="font-size: 1.1rem;">
                    Welcome, <strong>{{ admin.name }}</strong>. Monitor owners, boarding houses, listings, and reservations.
                </p>
            </header>

            <!-- 🚀 UX FIX: 2x2 Grid on Mobile (col-6), 1-Row on Desktop (col-lg-3) -->
            <section class="row g-3 mb-4" aria-label="System Statistics">
                <div class="col-6 col-xl-3">
                    <div class="ebm-card p-3 p-md-4 shadow-sm border border-secondary-subtle h-100 d-flex flex-column justify-content-center">
                        <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">Total Owners</span>
                        <strong class="fs-2 fw-bold lh-1 text-body-emphasis mb-1">{{ stats.owners }}</strong>
                        <small class="text-body-secondary">{{ stats.active_owners }} active</small>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="ebm-card p-3 p-md-4 shadow-sm border border-secondary-subtle h-100 d-flex flex-column justify-content-center">
                        <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">Boarding Houses</span>
                        <strong class="fs-2 fw-bold lh-1 text-body-emphasis mb-1">{{ stats.boarding_houses }}</strong>
                        <small class="text-body-secondary">Total listings</small>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="ebm-card p-3 p-md-4 shadow-sm border border-warning-subtle h-100 d-flex flex-column justify-content-center">
                        <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">Pending Listings</span>
                        <strong class="fs-2 fw-bold lh-1 text-warning mb-1">{{ stats.pending_listings }}</strong>
                        <small class="text-body-secondary">Need admin review</small>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="ebm-card p-3 p-md-4 shadow-sm border border-secondary-subtle h-100 d-flex flex-column justify-content-center">
                        <span class="text-body-secondary small fw-bold text-uppercase tracking-tight mb-2">Total Reservations</span>
                        <strong class="fs-2 fw-bold lh-1 text-body-emphasis mb-1">{{ stats.reservations }}</strong>
                        <small class="text-body-secondary">{{ stats.pending_reservations }} pending</small>
                    </div>
                </div>
            </section>

            <!-- CHARTS SECTION -->
            <section class="row g-4 mb-4" aria-label="Analytical Charts">
                <div class="col-xl-8">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle">
                        <h2 class="h5 fw-bold mb-4">System Overview</h2>
                        <div style="height: 300px; position: relative; width: 100%;">
                            <canvas ref="overviewCanvas"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle">
                        <h2 class="h5 fw-bold mb-4">Listings Distribution</h2>
                        <div style="height: 300px; position: relative; width: 100%;">
                            <canvas ref="distributionCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <div class="row g-4">
                <!-- LATEST BOARDING HOUSES TABLE -->
                <section class="col-xl-7" aria-label="Latest Boarding Houses">
                    <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle h-100 d-flex flex-column">
                        <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary">
                            <h2 class="h5 fw-bold mb-1">Latest Boarding Houses</h2>
                            <p class="text-body-secondary small mb-0">Recent listings submitted or managed in the system.</p>
                        </div>

                        <!-- 🚀 UX FIX: The "Window Box" Table Scroll Wrapper -->
                        <div v-if="latestBoardingHouses.length" class="table-responsive custom-table-scroll flex-grow-1 bg-body">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Name</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Owner</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Rent</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">Actions</th>
                                    </tr>
                                </thead>

                                <tbody class="border-top-0">
                                    <tr v-for="boardingHouse in latestBoardingHouses" :key="boardingHouse.id">
                                        <td class="text-nowrap ps-4">
                                            <div class="fw-bold text-body-emphasis">{{ boardingHouse.name }}</div>
                                            <div class="small text-body-secondary">{{ boardingHouse.created_at }}</div>
                                        </td>

                                        <td class="text-nowrap">
                                            <div class="fw-medium text-body-emphasis">{{ boardingHouse.owner_name }}</div>
                                            <div class="small text-body-secondary">{{ boardingHouse.owner_email || 'No email' }}</div>
                                        </td>

                                        <td class="text-nowrap fw-medium text-body-emphasis">
                                            ₱{{ formatPrice(boardingHouse.rent_price) }}
                                        </td>

                                        <td class="text-nowrap">
                                            <span class="badge shadow-sm" :class="listingStatusBadgeClass(boardingHouse.status)">
                                                {{ boardingHouse.status }}
                                            </span>
                                            <div v-if="boardingHouse.is_verified" class="small text-success mt-1 fw-bold tracking-tight">
                                                Verified
                                            </div>
                                        </td>

                                        <td class="text-nowrap text-end pe-4">
                                            <div class="d-flex flex-column gap-2 justify-content-end align-items-end">
                                                <button 
                                                    v-if="boardingHouse.status === 'pending'" 
                                                    @click="approveBoardingHouse(boardingHouse.id)" 
                                                    class="btn btn-sm btn-success fw-medium shadow-sm w-100"
                                                >
                                                    Approve
                                                </button>
                                                <button 
                                                    v-if="boardingHouse.status === 'pending'" 
                                                    @click="rejectBoardingHouse(boardingHouse.id)" 
                                                    class="btn btn-sm btn-outline-danger fw-medium w-100"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="empty-state text-center p-5 flex-grow-1 d-flex flex-column justify-content-center align-items-center bg-body">
                            <div class="fs-1 mb-3 opacity-50">🏠</div>
                            <h3 class="h6 fw-bold mb-1">No boarding houses yet</h3>
                            <p class="text-body-secondary small mb-0">Boarding house listings will appear here.</p>
                        </div>
                    </div>
                </section>

                <!-- LATEST RESERVATIONS LIST -->
                <section class="col-xl-5" aria-label="Latest Reservations">
                    <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle h-100 d-flex flex-column">
                        <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary">
                            <h2 class="h5 fw-bold mb-1">Latest Reservations</h2>
                            <p class="text-body-secondary small mb-0">Recent reservation activity across all boarding houses.</p>
                        </div>

                        <!-- 🚀 UX FIX: The "Window Box" List Scroll Wrapper -->
                        <div v-if="latestReservations.length" class="custom-table-scroll flex-grow-1 bg-body p-0">
                            <div class="list-group list-group-flush">
                                <div v-for="reservation in latestReservations" :key="reservation.id" class="list-group-item bg-transparent p-4 border-bottom border-secondary-subtle">
                                    <div class="d-flex justify-content-between gap-3 align-items-start">
                                        <div>
                                            <strong class="d-block mb-1 font-monospace">{{ reservation.reference_code }}</strong>
                                            <p class="small text-body-secondary fw-bold text-uppercase tracking-tight mb-1">{{ reservation.boarding_house_name }}</p>
                                            <p class="small mb-0 text-body-emphasis">Guest: <span class="fw-medium">{{ reservation.guest_name }}</span></p>
                                        </div>

                                        <div class="text-end">
                                            <span class="badge shadow-sm" :class="reservationStatusBadgeClass(reservation.status)">
                                                {{ reservation.status_label }}
                                            </span>
                                            <div class="small text-body-secondary mt-2">{{ reservation.created_at }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="empty-state text-center p-5 flex-grow-1 d-flex flex-column justify-content-center align-items-center bg-body">
                            <div class="fs-1 mb-3 opacity-50">📋</div>
                            <h3 class="h6 fw-bold mb-1">No reservations yet</h3>
                            <p class="text-body-secondary small mb-0">Reservation activity will appear here.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* 🪄 UX FIX: The Custom "Window Box" Table Scroll */
.custom-table-scroll {
    max-height: 450px;
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