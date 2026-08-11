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

// Make Chart.js labels readable in both light and dark mode by defaulting to a neutral slate gray
Chart.defaults.color = '#9ca3af'; 

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

        <div class="container pb-5 pt-2">
            
            <!-- HEADER SECTION -->
            <header class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-4 no-print">
                <div>
                    <span class="badge bg-body text-body border border-secondary-subtle mb-3 px-3 py-2 rounded-pill shadow-sm">
                        Super Admin
                    </span>

                    <h1 class="text-body-emphasis fw-bold mb-2 tracking-tight">
                        Reports
                    </h1>

                    <p class="text-body-secondary mb-0 lead" style="font-size: 1.1rem;">
                        View and print reservation and boarding house summary reports.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-success px-4 py-2 fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2"
                    @click="printReport"
                >
                    <i class="bi bi-printer"></i> Print Report
                </button>
            </header>

            <!-- PRINT HEADER -->
            <div class="print-report-header text-body-emphasis mb-4">
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

            <!-- 🚀 UX FIX: 2x2 Grid on Mobile (col-6), 1-Row on Desktop (col-lg-3) -->
            <section class="row g-3 mb-4" aria-label="Summary Statistics">
                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-3 p-md-4 bg-body-tertiary h-100 transition-all d-flex flex-column justify-content-center border border-secondary-subtle">
                        <span class="text-body-secondary text-uppercase fw-bold small mb-2 d-block tracking-tight">Total Owners</span>
                        <strong class="fs-2 text-body-emphasis fw-bold lh-1">{{ stats.total_owners }}</strong>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-3 p-md-4 bg-body-tertiary h-100 transition-all d-flex flex-column justify-content-center border border-secondary-subtle">
                        <span class="text-body-secondary text-uppercase fw-bold small mb-2 d-block tracking-tight">Boarding Houses</span>
                        <strong class="fs-2 text-body-emphasis fw-bold lh-1">{{ stats.total_boarding_houses }}</strong>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-3 p-md-4 bg-body-tertiary h-100 transition-all d-flex flex-column justify-content-center border border-success-subtle">
                        <span class="text-body-secondary text-uppercase fw-bold small mb-2 d-block tracking-tight">Approved Listings</span>
                        <strong class="fs-2 text-success fw-bold lh-1">{{ stats.approved_boarding_houses }}</strong>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="card border-0 shadow-sm p-3 p-md-4 bg-body-tertiary h-100 transition-all d-flex flex-column justify-content-center border border-warning-subtle">
                        <span class="text-body-secondary text-uppercase fw-bold small mb-2 d-block tracking-tight">Pending Listings</span>
                        <strong class="fs-2 text-warning fw-bold lh-1">{{ stats.pending_boarding_houses }}</strong>
                    </div>
                </div>
            </section>

            <!-- CHARTS & TOTALS SECTION -->
            <section class="row g-4 mb-4" aria-label="Reservation Breakdown">
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all d-flex flex-column justify-content-center align-items-center text-center border border-secondary-subtle">
                        <span class="text-body-secondary text-uppercase fw-bold small mb-2 d-block tracking-tight">Total Reservations</span>
                        <strong class="display-4 fw-bold text-body-emphasis lh-1 mb-2">{{ stats.total_reservations }}</strong>
                        <p class="text-body-secondary small mb-0">All-time reservation requests</p>
                    </div>
                </div>
                
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all border border-secondary-subtle">
                        <h2 class="h5 text-body-emphasis fw-bold mb-4">Reservation Status Breakdown</h2>
                        <div style="height: 250px; position: relative; width: 100%;">
                            <canvas ref="reservationChartCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- TABLE SECTION -->
            <section class="card border-0 shadow-sm p-0 overflow-hidden bg-body-tertiary transition-all border border-secondary-subtle" aria-label="Latest Reservation Records">
                <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary">
                    <h2 class="h5 text-body-emphasis fw-bold mb-1">
                        Latest Reservation Records
                    </h2>
                    <p class="text-body-secondary small mb-0">
                        Showing latest 100 reservation records.
                    </p>
                </div>

                <!-- 🚀 UX FIX: The "Window Box" Table Scroll Wrapper -->
                <div v-if="reservations.length" class="table-responsive custom-table-scroll bg-body">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Reference</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Guest</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Boarding House</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Move-in Date</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Submitted</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase pe-4">Responded</th>
                            </tr>
                        </thead>

                        <tbody class="border-top-0">
                            <tr v-for="reservation in reservations" :key="reservation.id">
                                <td class="text-nowrap ps-4 border-secondary-subtle">
                                    <strong class="text-body-emphasis font-monospace">{{ reservation.reference_code }}</strong>
                                </td>

                                <td class="text-nowrap border-secondary-subtle">
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

                                <td class="text-nowrap border-secondary-subtle text-body-emphasis fw-medium">
                                    {{ reservation.boarding_house_name }}
                                </td>

                                <td class="text-nowrap border-secondary-subtle text-body-secondary">
                                    {{ reservation.preferred_move_in_date }}
                                </td>

                                <td class="text-nowrap border-secondary-subtle">
                                    <span class="badge shadow-sm" :class="statusBadgeClass(reservation.status)">
                                        {{ reservation.status_label }}
                                    </span>
                                </td>

                                <td class="small text-body-secondary text-nowrap border-secondary-subtle">
                                    {{ reservation.created_at }}
                                </td>

                                <td class="small text-body-secondary text-nowrap border-secondary-subtle pe-4">
                                    {{ reservation.responded_at || 'Not yet' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="d-flex flex-column align-items-center justify-content-center text-center p-5 bg-body">
                    <div class="fs-1 mb-3 opacity-50">📋</div>
                    <h3 class="h5 text-body-emphasis fw-bold mb-2">No reservation records yet</h3>
                    <p class="text-body-secondary mb-0">Reservation records will appear here.</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* 🪄 UX FIX: The Custom "Window Box" Table Scroll */
.custom-table-scroll {
    max-height: 500px;
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

/* Smooth fade transitions for colors when toggling dark mode */
.transition-all {
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, border-color 0.3s ease-in-out;
}
</style>