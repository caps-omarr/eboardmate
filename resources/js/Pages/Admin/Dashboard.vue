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
                    x: { ticks: { color: 'var(--bs-body-color)' } },
                    y: { ticks: { color: 'var(--bs-body-color)' } }
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
                    legend: { labels: { color: 'var(--bs-body-color)' } }
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

        <div class="container">
            <div class="mb-4">
                <span class="badge text-bg-dark mb-3">Super Admin</span>
                <h1 class="fw-bold mb-2">Super Admin Dashboard</h1>
                <p class="ebm-muted mb-0">
                    Welcome, {{ admin.name }}. Monitor owners, boarding houses, listings, and reservations.
                </p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">Total Owners</span>
                        <strong class="dashboard-stat-value">{{ stats.owners }}</strong>
                        <small class="ebm-muted">{{ stats.active_owners }} active</small>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">Boarding Houses</span>
                        <strong class="dashboard-stat-value">{{ stats.boarding_houses }}</strong>
                        <small class="ebm-muted">Total listings</small>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">Pending Listings</span>
                        <strong class="dashboard-stat-value text-warning">{{ stats.pending_listings }}</strong>
                        <small class="ebm-muted">Need admin review</small>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">Total Reservations</span>
                        <strong class="dashboard-stat-value">{{ stats.reservations }}</strong>
                        <small class="ebm-muted">{{ stats.pending_reservations }} pending</small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <div class="ebm-card p-4 h-100">
                        <h2 class="h5 fw-bold mb-4">System Overview</h2>
                        <div style="height: 300px; position: relative;">
                            <canvas ref="overviewCanvas"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4">
                    <div class="ebm-card p-4 h-100">
                        <h2 class="h5 fw-bold mb-4">Listings Distribution</h2>
                        <div style="height: 300px; position: relative;">
                            <canvas ref="distributionCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Boarding Houses Table Segment -->
                <div class="col-xl-7">
                    <div class="ebm-card p-4">
                        <div class="mb-3">
                            <h2 class="h5 fw-bold mb-1">Latest Boarding Houses</h2>
                            <p class="ebm-muted small mb-0">Recent listings submitted or managed in the system.</p>
                        </div>

                        <!-- Scrollable Table Container -->
                        <div v-if="latestBoardingHouses.length" class="table-responsive custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                            <table class="table align-middle table-hover owner-table mb-0">
                                <thead class="sticky-top bg-body">
                                    <tr>
                                        <th>Name</th>
                                        <th>Owner</th>
                                        <th>Rent</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr v-for="boardingHouse in latestBoardingHouses" :key="boardingHouse.id">
                                        <td>
                                            <div class="fw-semibold">{{ boardingHouse.name }}</div>
                                            <div class="small ebm-muted">{{ boardingHouse.created_at }}</div>
                                        </td>

                                        <td>
                                            <div>{{ boardingHouse.owner_name }}</div>
                                            <div class="small ebm-muted">{{ boardingHouse.owner_email || 'No email' }}</div>
                                        </td>

                                        <td>₱{{ formatPrice(boardingHouse.rent_price) }}</td>

                                        <td>
                                            <span class="badge" :class="listingStatusBadgeClass(boardingHouse.status)">
                                                {{ boardingHouse.status }}
                                            </span>
                                            <div v-if="boardingHouse.is_verified" class="small text-success mt-1 fw-semibold">
                                                Verified
                                            </div>
                                        </td>

                                        <!-- Approve and Reject Actions Column -->
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button 
                                                    v-if="boardingHouse.status === 'pending'" 
                                                    @click="approveBoardingHouse(boardingHouse.id)" 
                                                    class="btn btn-sm btn-success fw-semibold shadow-sm"
                                                >
                                                    Approve
                                                </button>
                                                <button 
                                                    v-if="boardingHouse.status === 'pending'" 
                                                    @click="rejectBoardingHouse(boardingHouse.id)" 
                                                    class="btn btn-sm btn-danger fw-semibold shadow-sm"
                                                >
                                                    Reject
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="empty-state text-center py-4">
                            <div class="empty-state-icon fs-1 mb-2">🏠</div>
                            <h3 class="h6 fw-bold mb-1">No boarding houses yet</h3>
                            <p class="ebm-muted small mb-0">Boarding house listings will appear here.</p>
                        </div>
                    </div>
                </div>

                <!-- Latest Reservations Segment -->
                <div class="col-xl-5">
                    <div class="ebm-card p-4">
                        <div class="mb-3">
                            <h2 class="h5 fw-bold mb-1">Latest Reservations</h2>
                            <p class="ebm-muted small mb-0">Recent reservation activity across all boarding houses.</p>
                        </div>

                        <!-- Scrollable Reservations List Container -->
                        <div v-if="latestReservations.length" class="admin-reservation-list custom-scrollbar" style="max-height: 400px; overflow-y: auto;">
                            <div v-for="reservation in latestReservations" :key="reservation.id" class="admin-reservation-item border-bottom py-3">
                                <div class="d-flex justify-content-between gap-3">
                                    <div>
                                        <strong class="d-block mb-1">{{ reservation.reference_code }}</strong>
                                        <p class="small ebm-muted mb-1">{{ reservation.boarding_house_name }}</p>
                                        <p class="small mb-0">Guest: {{ reservation.guest_name }}</p>
                                    </div>

                                    <div class="text-end">
                                        <span class="badge" :class="reservationStatusBadgeClass(reservation.status)">
                                            {{ reservation.status_label }}
                                        </span>
                                        <div class="small ebm-muted mt-2">{{ reservation.created_at }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="empty-state text-center py-4">
                            <div class="empty-state-icon fs-1 mb-2">📋</div>
                            <h3 class="h6 fw-bold mb-1">No reservations yet</h3>
                            <p class="ebm-muted small mb-0">Reservation activity will appear here.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.table {
    color: var(--bs-body-color);
    border-color: var(--bs-border-color);
}
.empty-state {
    opacity: 0.8;
}

/* Custom Scrollbar Styling */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: var(--bs-border-color);
    border-radius: 10px;
}
</style>