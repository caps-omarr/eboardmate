<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
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
});

// --- APPROVAL & REJECTION LOGIC ---
const approveBoardingHouse = (id) => {
    if (confirm('Are you sure you want to approve this boarding house? It will become visible on the public map.')) {
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

// --- CHART.JS LOGIC WITH VUE REFS ---
const overviewCanvas = ref(null);
const distributionCanvas = ref(null);
let chartInstances = { overview: null, distribution: null };

const CHART_TEXT_COLOR = '#9ca3af';
const CHART_GRID_COLOR = 'rgba(156, 163, 175, 0.15)';

const renderCharts = () => {
    // 1. Bar Chart: Capacity & Bedspace Telemetry
    if (overviewCanvas.value) {
        if (chartInstances.overview) {
            chartInstances.overview.destroy();
        }
        chartInstances.overview = new Chart(overviewCanvas.value, {
            type: 'bar',
            data: {
                labels: ['Total Bedspaces', 'Occupied Beds', 'Available Beds', 'Total Rooms', 'Available Rooms'],
                datasets: [{
                    label: 'Units',
                    data: [
                        props.stats.total_bedspaces,
                        props.stats.occupied_bedspaces,
                        props.stats.available_bedspaces,
                        props.stats.total_rooms,
                        props.stats.available_rooms,
                    ],
                    backgroundColor: ['#0d6efd', '#fd7e14', '#198754', '#6f42c1', '#20c997'],
                    borderRadius: 8,
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

watch(() => props.stats, () => {
    nextTick(() => {
        renderCharts();
    });
}, { deep: true });

// --- SILENT BACKGROUND POLLING ---
let pollingInterval = null;

onMounted(() => {
    nextTick(() => {
        renderCharts();
    });

    pollingInterval = setInterval(() => {
        router.reload({
            only: ['stats', 'latestBoardingHouses'],
            preserveScroll: true,
            preserveState: true,
        });
    }, 15000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
    if (chartInstances.overview) chartInstances.overview.destroy();
    if (chartInstances.distribution) chartInstances.distribution.destroy();
});

const listingStatusBadgeClass = (status) => {
    if (status === 'approved') return 'badge-soft-success';
    if (status === 'pending') return 'badge-soft-warning';
    if (status === 'rejected') return 'badge-soft-danger';
    if (status === 'deactivated') return 'badge-soft-secondary';
    return 'badge-soft-secondary';
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
        <Head title="System Telemetry Dashboard | E-BoardMate" />

        <div class="container-fluid max-w-desktop mx-auto pb-5 pt-2 px-3 px-md-4">
            
            <!-- HEADER SECTION -->
            <header class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <span class="badge bg-body text-body border border-secondary-subtle mb-2 px-3 py-2 rounded-pill shadow-sm">
                        Executive Control Center
                    </span>
                    <h1 class="text-body-emphasis fw-bold mb-1 tracking-tight">
                        Overview and Analytics
                    </h1>
                    <p class="text-body-secondary mb-0">
                        High-level capacity, property verification, and owner infrastructure metrics.
                    </p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <Link href="/admin/reports" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-bar-graph"></i> Universal Reports
                    </Link>
                    <Link href="/admin/boarding-houses" class="btn btn-success rounded-pill px-3 shadow-sm d-flex align-items-center gap-2">
                        <i class="bi bi-houses-fill"></i> Manage Properties
                    </Link>
                </div>
            </header>

            <!-- TELEMETRY KPI CARDS -->
            <section class="row g-3 mb-4" aria-label="Platform Health KPI Summary">
                
                <!-- Boarding Houses -->
                <div class="col-sm-6 col-xl-3">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small text-body-secondary fw-bold text-uppercase tracking-tight">Boarding Houses</span>
                            <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-buildings-fill fs-5"></i>
                            </div>
                        </div>
                        <div class="h2 fw-bold text-body-emphasis mb-1">{{ stats.boarding_houses }}</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-soft-success rounded-pill small">{{ stats.approved_listings }} Verified</span>
                            <span v-if="stats.pending_listings > 0" class="badge badge-soft-warning rounded-pill small">{{ stats.pending_listings }} Pending</span>
                        </div>
                    </div>
                </div>

                <!-- Verified Rate Ratio -->
                <div class="col-sm-6 col-xl-3">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small text-body-secondary fw-bold text-uppercase tracking-tight">Verification Rate</span>
                            <div class="p-2 rounded-circle bg-success bg-opacity-10 text-success">
                                <i class="bi bi-patch-check-fill fs-5"></i>
                            </div>
                        </div>
                        <div class="h2 fw-bold text-body-emphasis mb-1">{{ stats.verification_rate }}%</div>
                        <div class="progress rounded-pill bg-secondary bg-opacity-10" style="height: 6px;">
                            <div class="progress-bar bg-success rounded-pill" role="progressbar" :style="{ width: stats.verification_rate + '%' }"></div>
                        </div>
                    </div>
                </div>

                <!-- Bedspace Capacity Utilization -->
                <div class="col-sm-6 col-xl-3">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small text-body-secondary fw-bold text-uppercase tracking-tight">Bed Occupancy Rate</span>
                            <div class="p-2 rounded-circle bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-pie-chart-fill fs-5"></i>
                            </div>
                        </div>
                        <div class="h2 fw-bold text-body-emphasis mb-1">{{ stats.occupancy_rate }}%</div>
                        <div class="small text-body-secondary">
                            <strong>{{ stats.occupied_bedspaces }}</strong> occupied / {{ stats.total_bedspaces }} total beds
                        </div>
                    </div>
                </div>

                <!-- Owner Ecosystem -->
                <div class="col-sm-6 col-xl-3">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small text-body-secondary fw-bold text-uppercase tracking-tight">Owner Accounts</span>
                            <div class="p-2 rounded-circle bg-info bg-opacity-10 text-info">
                                <i class="bi bi-people-fill fs-5"></i>
                            </div>
                        </div>
                        <div class="h2 fw-bold text-body-emphasis mb-1">{{ stats.owners }}</div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-soft-success rounded-pill small">{{ stats.active_owners }} Active</span>
                            <span v-if="stats.inactive_owners > 0" class="badge badge-soft-secondary rounded-pill small">{{ stats.inactive_owners }} Inactive</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CHARTS SECTION -->
            <section class="row g-4 mb-4" aria-label="Analytical Telemetry Charts">
                <div class="col-xl-8">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-0">Capacity & Bedspace Telemetry</h2>
                                <span class="small text-body-secondary">Available vs occupied unit allocations</span>
                            </div>
                            <span class="badge bg-body-tertiary text-body-secondary border border-secondary-subtle rounded-pill">Real-time</span>
                        </div>
                        <div style="height: 300px; position: relative; width: 100%;">
                            <canvas ref="overviewCanvas"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-0">Listing Status Breakdown</h2>
                                <span class="small text-body-secondary">Approval pipeline health</span>
                            </div>
                        </div>
                        <div style="height: 300px; position: relative; width: 100%;">
                            <canvas ref="distributionCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TELEMETRY HIGHLIGHTS & ACTIONABLE LISTINGS -->
            <div class="row g-4">
                
                <!-- LATEST BOARDING HOUSES FOR REVIEW -->
                <section class="col-xl-8" aria-label="Latest Boarding Houses">
                    <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle rounded-4 h-100 d-flex flex-column bg-body">
                        <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="h5 fw-bold mb-1">Recent Property Submissions</h2>
                                <p class="text-body-secondary small mb-0">Pending and newly registered boarding house properties.</p>
                            </div>
                            <Link href="/admin/boarding-houses" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                View All ({{ stats.boarding_houses }})
                            </Link>
                        </div>

                        <div v-if="latestBoardingHouses.length" class="table-responsive custom-table-scroll flex-grow-1 bg-body">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Property</th>
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
                                            ₱{{ formatPrice(boardingHouse.rent_price) }}/mo
                                        </td>

                                        <td class="text-nowrap">
                                            <span class="badge shadow-sm rounded-pill px-3 py-1 text-capitalize" :class="listingStatusBadgeClass(boardingHouse.status)">
                                                {{ boardingHouse.status }}
                                            </span>
                                            <div v-if="boardingHouse.is_verified" class="small text-success mt-1 fw-bold tracking-tight">
                                                <i class="bi bi-check-circle-fill"></i> Verified
                                            </div>
                                        </td>

                                        <td class="text-nowrap text-end pe-4">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <button 
                                                    v-if="boardingHouse.status === 'pending'" 
                                                    class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-medium"
                                                    @click="approveBoardingHouse(boardingHouse.id)" 
                                                >
                                                    Approve
                                                </button>
                                                <button 
                                                    v-if="boardingHouse.status === 'pending'" 
                                                    class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-medium"
                                                    @click="rejectBoardingHouse(boardingHouse.id)" 
                                                >
                                                    Reject
                                                </button>
                                                <Link 
                                                    :href="`/admin/boarding-houses`" 
                                                    class="btn btn-sm btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center"
                                                    style="width: 32px; height: 32px;"
                                                    title="Inspect"
                                                >
                                                    <i class="bi bi-arrow-right"></i>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="text-center p-5 flex-grow-1 d-flex flex-column justify-content-center align-items-center bg-body">
                            <div class="fs-1 mb-2 opacity-50">🏠</div>
                            <h3 class="h6 fw-bold mb-1">No boarding houses yet</h3>
                            <p class="text-body-secondary small mb-0">Boarding house listings will appear here.</p>
                        </div>
                    </div>
                </section>

                <!-- SYSTEM CAPACITY CARD -->
                <section class="col-xl-4" aria-label="System Capacity Utilization">
                    <div class="ebm-card p-4 shadow-sm border border-secondary-subtle rounded-4 h-100 d-flex flex-column justify-content-between bg-body">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="p-2 rounded-circle bg-success bg-opacity-10 text-success">
                                    <i class="bi bi-shield-check fs-5"></i>
                                </div>
                                <div>
                                    <h2 class="h5 fw-bold mb-0">Platform Health</h2>
                                    <span class="small text-body-secondary">Infrastructure status</span>
                                </div>
                            </div>

                            <div class="list-group list-group-flush mb-4">
                                <div class="list-group-item bg-transparent px-0 py-3 border-bottom border-secondary-subtle d-flex justify-content-between align-items-center">
                                    <span class="text-body-secondary">Total Bed Capacity</span>
                                    <span class="fw-bold font-monospace text-body-emphasis">{{ stats.total_bedspaces }}</span>
                                </div>
                                <div class="list-group-item bg-transparent px-0 py-3 border-bottom border-secondary-subtle d-flex justify-content-between align-items-center">
                                    <span class="text-body-secondary">Available Bedspaces</span>
                                    <span class="fw-bold font-monospace text-success">{{ stats.available_bedspaces }}</span>
                                </div>
                                <div class="list-group-item bg-transparent px-0 py-3 border-bottom border-secondary-subtle d-flex justify-content-between align-items-center">
                                    <span class="text-body-secondary">Total Room Inventory</span>
                                    <span class="fw-bold font-monospace text-body-emphasis">{{ stats.total_rooms }}</span>
                                </div>
                                <div class="list-group-item bg-transparent px-0 py-3 border-bottom border-secondary-subtle d-flex justify-content-between align-items-center">
                                    <span class="text-body-secondary">Total Reservations Processed</span>
                                    <span class="fw-bold font-monospace text-primary">{{ stats.reservations }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-body-tertiary rounded-3 border border-secondary-subtle">
                            <div class="small fw-bold text-body-emphasis mb-1">
                                <i class="bi bi-info-circle-fill text-primary me-1"></i> Data Privacy Compliance
                            </div>
                            <div class="small text-body-secondary">
                                Student transactional records and reservations are isolated in the Enterprise Reports module for privacy-first export.
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-table-scroll {
    max-height: 480px;
    overflow-y: auto;
    overflow-x: auto;
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

.sticky-header {
    position: sticky;
    top: 0;
    z-index: 2;
    box-shadow: inset 0 -1px 0 var(--bs-border-color);
}

.tracking-tight {
    letter-spacing: -0.02em;
}

.badge-soft-success {
    background-color: rgba(25, 135, 84, 0.15);
    color: #198754;
    border: 1px solid rgba(25, 135, 84, 0.25);
}

.badge-soft-warning {
    background-color: rgba(255, 193, 7, 0.2);
    color: #997404;
    border: 1px solid rgba(255, 193, 7, 0.35);
}

.badge-soft-danger {
    background-color: rgba(220, 53, 69, 0.15);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.25);
}

.badge-soft-secondary {
    background-color: rgba(108, 117, 125, 0.15);
    color: #6c757d;
    border: 1px solid rgba(108, 117, 125, 0.25);
}
</style>