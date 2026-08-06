<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { onMounted, onUnmounted, ref, watch, nextTick } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    reservations: {
        type: Array,
        default: () => [],
    },
    generatedAt: {
        type: String,
        required: true,
    },
});

const printReport = () => {
    window.print();
};

// Make Chart.js labels readable in both light and dark mode by defaulting to a neutral gray
Chart.defaults.color = '#8a9097'; 

// --- CHART.JS LOGIC ---
const reservationChartCanvas = ref(null);
let reservationChartInstance = null;

const renderChart = () => {
    if (reservationChartCanvas.value) {
        if (reservationChartInstance) {
            reservationChartInstance.destroy();
        }
        reservationChartInstance = new Chart(reservationChartCanvas.value, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Approved', 'Rejected', 'Expired', 'Cancelled'],
                datasets: [{
                    data: [
                        props.stats.pending_reservations,
                        props.stats.approved_reservations,
                        props.stats.rejected_reservations,
                        props.stats.expired_reservations,
                        props.stats.cancelled_reservations
                    ],
                    // Colors: Warning (Yellow), Success (Green), Danger (Red), Secondary (Gray), Dark (Dark Gray)
                    backgroundColor: ['#ffc107', '#198754', '#dc3545', '#6c757d', '#343a40'],
                    borderWidth: 0
                }]
            },
            options: { 
                maintainAspectRatio: false, 
                responsive: true, 
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'right' // Puts the labels neatly to the side of the circle
                    }
                }
            }
        });
    }
};

watch(() => props.stats, () => {
    nextTick(() => {
        renderChart();
    });
}, { deep: true });

onMounted(() => {
    nextTick(() => {
        renderChart();
    });
});

onUnmounted(() => {
    if (reservationChartInstance) {
        reservationChartInstance.destroy();
    }
});
// ----------------------

const statusBadgeClass = (status) => {
    if (status === 'pending') return 'text-bg-warning';
    if (status === 'approved') return 'text-bg-success';
    if (status === 'rejected') return 'text-bg-danger';
    if (status === 'expired' || status === 'cancelled') return 'text-bg-secondary';
    return 'text-bg-secondary';
};
</script>

<template>
    <AdminLayout>
        <Head title="Reports | E-BoardMate" />

        <div class="container-fluid py-2">
            
            <!-- HEADER SECTION -->
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-4 no-print">
                <div>
                    <span class="badge text-bg-dark mb-3 px-3 py-2">
                        Super Admin
                    </span>

                    <h1 class="text-body-emphasis fw-bold mb-2 transition-all">
                        Reports
                    </h1>

                    <p class="text-body-secondary mb-0 transition-all">
                        View and print reservation and boarding house summary reports.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-ebm-primary px-4 py-2 fw-semibold"
                    @click="printReport"
                >
                    <i class="bi bi-printer me-2"></i> Print Report
                </button>
            </div>

            <!-- PRINT HEADER -->
            <div class="print-report-header text-body-emphasis">
                <h1 class="fw-bold mb-1">
                    E-BoardMate Reservation Summary Report
                </h1>
                <p class="mb-1 text-body-secondary">
                    A Web-Based Boarding House Locator and Reservations System for Talibon Polytechnic College
                </p>
                <p class="text-body-secondary small mb-0">
                    Generated: {{ generatedAt }}
                </p>
            </div>

            <!-- STAT CARDS SECTION -->
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all">
                        <span class="text-body-secondary text-uppercase fw-semibold small mb-2 d-block">Total Owners</span>
                        <strong class="fs-2 text-body-emphasis fw-bold mb-1">{{ stats.total_owners }}</strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all">
                        <span class="text-body-secondary text-uppercase fw-semibold small mb-2 d-block">Boarding Houses</span>
                        <strong class="fs-2 text-body-emphasis fw-bold mb-1">{{ stats.total_boarding_houses }}</strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all">
                        <span class="text-body-secondary text-uppercase fw-semibold small mb-2 d-block">Approved Listings</span>
                        <strong class="fs-2 text-success fw-bold mb-1">{{ stats.approved_boarding_houses }}</strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all">
                        <span class="text-body-secondary text-uppercase fw-semibold small mb-2 d-block">Pending Listings</span>
                        <strong class="fs-2 text-warning fw-bold mb-1">{{ stats.pending_boarding_houses }}</strong>
                    </div>
                </div>
            </div>

            <!-- CHARTS & TOTALS SECTION -->
            <div class="row g-4 mb-4">
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all d-flex flex-column justify-content-center align-items-center text-center">
                        <span class="text-body-secondary text-uppercase fw-semibold small mb-2 d-block">Total Reservations</span>
                        <!-- Removed text-dark here to allow the text to switch to white in dark mode -->
                        <strong class="display-4 fw-bold text-body-emphasis">{{ stats.total_reservations }}</strong>
                        <p class="text-body-secondary mt-2 mb-0">All-time reservation requests</p>
                    </div>
                </div>
                
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all">
                        <h2 class="h5 text-body-emphasis fw-bold mb-4">Reservation Status Breakdown</h2>
                        <div style="height: 250px; position: relative;">
                            <canvas ref="reservationChartCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE SECTION -->
            <div class="card border-0 shadow-sm p-4 bg-body-tertiary transition-all">
                <div class="mb-4">
                    <h2 class="h5 text-body-emphasis fw-bold mb-1">
                        Latest Reservation Records
                    </h2>
                    <p class="text-body-secondary small mb-0">
                        Showing latest 100 reservation records.
                    </p>
                </div>

                <div v-if="reservations.length" class="table-responsive">
                    <table class="table table-hover align-middle mb-0 report-table">
                        <thead>
                            <tr>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Reference</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Guest</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Boarding House</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Move-in Date</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Status</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Submitted</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Responded</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="reservation in reservations" :key="reservation.id">
                                <td class="border-secondary-subtle">
                                    <strong class="text-body-emphasis">{{ reservation.reference_code }}</strong>
                                </td>

                                <td class="border-secondary-subtle">
                                    <div class="fw-semibold text-body-emphasis">
                                        {{ reservation.guest_name }}
                                    </div>
                                    <div class="small text-body-secondary mt-1">
                                        {{ reservation.guest_email }}
                                    </div>
                                    <div class="small text-body-secondary mt-1">
                                        {{ reservation.guest_phone }}
                                    </div>
                                </td>

                                <td class="border-secondary-subtle text-body-emphasis fw-medium">
                                    {{ reservation.boarding_house_name }}
                                </td>

                                <td class="border-secondary-subtle text-body-secondary">
                                    {{ reservation.preferred_move_in_date }}
                                </td>

                                <td class="border-secondary-subtle">
                                    <span class="badge" :class="statusBadgeClass(reservation.status)">
                                        {{ reservation.status_label }}
                                    </span>
                                </td>

                                <td class="small text-body-secondary border-secondary-subtle">
                                    {{ reservation.created_at }}
                                </td>

                                <td class="small text-body-secondary border-secondary-subtle">
                                    {{ reservation.responded_at || 'Not yet' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="d-flex flex-column align-items-center justify-content-center text-center p-5 h-100 rounded border border-secondary-subtle bg-body">
                    <div class="fs-1 mb-3">📋</div>
                    <h3 class="h5 text-body-emphasis fw-bold mb-2">No reservation records yet</h3>
                    <p class="text-body-secondary mb-0">Reservation records will appear here.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* Smooth fade transitions for colors when toggling dark mode */
.transition-all {
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, border-color 0.3s ease-in-out;
}
</style>