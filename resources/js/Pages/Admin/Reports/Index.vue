<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { onMounted, onUnmounted, ref, watch, nextTick, computed } from 'vue';
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
    directoryReport: {
        type: Array,
        default: () => [],
    },
    boardingHousesList: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            boarding_house_id: 'all',
            status: 'all',
            date_from: '',
            date_to: '',
            search: '',
        }),
    },
    generatedAt: {
        type: String,
        required: true,
    },
});

// --- REPORT MODE (TABS) ---
const activeReportTab = ref('reservations'); // 'reservations' | 'directory'

// --- FILTER CONTROLS ---
const selectedBh = ref(props.filters?.boarding_house_id || 'all');
const selectedStatus = ref(props.filters?.status || 'all');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const searchQuery = ref(props.filters?.search || '');
let searchTimer = null;

const applyFilters = () => {
    router.get('/admin/reports', {
        boarding_house_id: selectedBh.value,
        status: selectedStatus.value,
        date_from: dateFrom.value,
        date_to: dateTo.value,
        search: searchQuery.value,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const handleSearchInput = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 350);
};

const resetFilters = () => {
    selectedBh.value = 'all';
    selectedStatus.value = 'all';
    dateFrom.value = '';
    dateTo.value = '';
    searchQuery.value = '';
    router.get('/admin/reports', {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// --- PRINT PIPELINE ---
const printReport = () => {
    window.print();
};

// --- ONE-CLICK COPY REFERENCE CODE ---
const copiedCode = ref(null);
const copyReferenceCode = (code) => {
    navigator.clipboard.writeText(code).then(() => {
        copiedCode.value = code;
        setTimeout(() => {
            if (copiedCode.value === code) copiedCode.value = null;
        }, 2000);
    });
};

// --- CHART.JS LOGIC ---
Chart.defaults.color = '#9ca3af';
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
                        position: 'right'
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

const statusBadgeClass = (status) => {
    if (status === 'pending') return 'badge-soft-warning';
    if (status === 'approved') return 'badge-soft-success';
    if (status === 'rejected') return 'badge-soft-danger';
    if (status === 'expired' || status === 'cancelled') return 'badge-soft-secondary';
    return 'badge-soft-secondary';
};

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

// --- CELL NUMBER FORMATTER (Matches Official TPC Document format) ---
const formatCellNumber = (phone) => {
    if (!phone) return 'N/A';
    const clean = String(phone).replace(/[^0-9]/g, '');
    if (clean.startsWith('63') && clean.length === 12) {
        return clean.substring(2);
    }
    if (clean.startsWith('09') && clean.length === 11) {
        return clean.substring(1);
    }
    return clean || phone;
};

// --- DIRECT PDF EXPORT URL ---
const pdfExportUrl = computed(() => {
    const params = new URLSearchParams();
    params.set('type', activeReportTab.value);
    if (activeReportTab.value === 'reservations') {
        if (selectedBh.value && selectedBh.value !== 'all') params.set('boarding_house_id', selectedBh.value);
        if (selectedStatus.value && selectedStatus.value !== 'all') params.set('status', selectedStatus.value);
        if (dateFrom.value) params.set('date_from', dateFrom.value);
        if (dateTo.value) params.set('date_to', dateTo.value);
        if (searchQuery.value) params.set('search', searchQuery.value);
    }
    return `/admin/reports/export-pdf?${params.toString()}`;
});
</script>

<template>
    <AdminLayout>
        <Head title="Enterprise Reports & Audits | E-BoardMate" />

        <div class="container-fluid max-w-desktop mx-auto pb-5 pt-2 px-3 px-md-4">
            
            <!-- 🖨️ OFFICIAL TPC INSTITUTIONAL PRINT HEADER (Visible ONLY during print) -->
            <div class="d-none d-print-block print-official-header mb-3">
                <div class="text-center mb-2">
                    <img src="/images/tpc-header-banner.jpg" alt="Talibon Polytechnic College Banner" class="print-banner-img" />
                </div>
                <div class="print-document-title">
                    {{ activeReportTab === 'directory' ? 'LIST OF BOARDING HOUSE OWNERS AND LANDLORDS/LANDLADIES' : 'LIST OF BOARDING HOUSE RESERVATIONS AND BOOKING AUDIT' }}
                </div>
            </div>

            <!-- ON-SCREEN HEADER (Hidden in print) -->
            <header class="d-print-none mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <span class="text-uppercase small fw-bold text-secondary tracking-wider d-block mb-1">
                        Compliance & Audit Engine
                    </span>
                    <h1 class="h3 text-body-emphasis fw-bold mb-1 tracking-tight">
                        Administrative Reports & Privacy-Compliant Exports
                    </h1>
                    <p class="text-body-secondary mb-0">
                        Universal reservation auditing, boarding house directory verification, and formal PDF/print exports.
                    </p>
                </div>

                <!-- Export PDF & Print Action Buttons -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a 
                        :href="pdfExportUrl" 
                        target="_blank"
                        class="btn btn-primary rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2 fw-semibold text-nowrap"
                        style="min-height: 44px;"
                        title="Download official formatted PDF file directly"
                    >
                        <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                        <span>Save as PDF</span>
                    </a>

                    <button 
                        type="button" 
                        class="btn btn-outline-secondary rounded-3 px-3 py-2 shadow-sm d-flex align-items-center gap-2 fw-semibold text-nowrap"
                        style="min-height: 44px;"
                        @click="printReport"
                        title="Open Browser Print Dialog"
                    >
                        <i class="bi bi-printer-fill fs-5"></i>
                        <span>Print Layout</span>
                    </button>
                </div>
            </header>

            <!-- STATS & CHART SUMMARY (Hidden in print) -->
            <section class="row g-4 mb-4 d-print-none" aria-label="Report Analytics Overview">
                <div class="col-lg-7">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <h2 class="h5 fw-bold mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-graph-up-arrow text-primary"></i> System Activity Overview
                        </h2>
                        <div class="row g-3 text-center">
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-body-tertiary border border-secondary-subtle">
                                    <div class="small text-body-secondary fw-bold text-uppercase">Properties</div>
                                    <div class="h3 fw-bold text-body-emphasis mb-0 mt-1">{{ stats.total_boarding_houses }}</div>
                                    <span class="badge badge-soft-success rounded-2 mt-1">{{ stats.approved_boarding_houses }} active</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-body-tertiary border border-secondary-subtle">
                                    <div class="small text-body-secondary fw-bold text-uppercase">Owners</div>
                                    <div class="h3 fw-bold text-body-emphasis mb-0 mt-1">{{ stats.total_owners }}</div>
                                    <span class="badge badge-soft-primary rounded-2 mt-1">Verified</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-body-tertiary border border-secondary-subtle">
                                    <div class="small text-body-secondary fw-bold text-uppercase">Bookings</div>
                                    <div class="h3 fw-bold text-body-emphasis mb-0 mt-1">{{ stats.total_reservations }}</div>
                                    <span class="badge badge-soft-warning rounded-2 mt-1">{{ stats.pending_reservations }} pending</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="ebm-card p-4 h-100 shadow-sm border border-secondary-subtle rounded-4 bg-body">
                        <h2 class="h6 fw-bold mb-2">Reservation Status Breakdown</h2>
                        <div style="height: 140px; position: relative; width: 100%;">
                            <canvas ref="reservationChartCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </section>

            <!-- REPORT MODE SWITCHER TABS (Hidden in print) -->
            <div class="d-print-none d-flex align-items-center gap-2 mb-4 border-bottom border-secondary-subtle pb-3">
                <button 
                    type="button" 
                    class="btn rounded-3 px-3 py-2 fw-semibold d-flex align-items-center gap-2"
                    style="min-height: 44px;"
                    :class="activeReportTab === 'reservations' ? 'btn-success shadow-sm' : 'btn-light border border-secondary-subtle text-body-secondary'"
                    @click="activeReportTab = 'reservations'"
                >
                    <i class="bi bi-calendar-check"></i>
                    <span>Reservation Master List</span>
                    <span class="badge bg-white text-dark rounded-2 ms-1">{{ reservations.length }}</span>
                </button>

                <button 
                    type="button" 
                    class="btn rounded-3 px-3 py-2 fw-semibold d-flex align-items-center gap-2"
                    style="min-height: 44px;"
                    :class="activeReportTab === 'directory' ? 'btn-success shadow-sm' : 'btn-light border border-secondary-subtle text-body-secondary'"
                    @click="activeReportTab = 'directory'"
                >
                    <i class="bi bi-buildings"></i>
                    <span>Boarding House Directory</span>
                    <span class="badge bg-white text-dark rounded-2 ms-1">{{ directoryReport.length }}</span>
                </button>
            </div>

            <!-- ========================================================= -->
            <!-- 1. RESERVATIONS MASTER LIST VIEW                          -->
            <!-- ========================================================= -->
            <div v-if="activeReportTab === 'reservations'">
                
                <!-- UNIVERSAL FILTER BAR (Hidden in print) -->
                <div class="ebm-card p-4 rounded-4 shadow-sm border border-secondary-subtle bg-body-tertiary mb-4 d-print-none">
                    <div class="row g-3 align-items-end">
                        
                        <!-- Boarding House Select -->
                        <div class="col-md-3">
                            <label for="filter_bh" class="form-label fw-bold small text-body-secondary text-uppercase mb-2">Boarding House</label>
                            <select id="filter_bh" v-model="selectedBh" class="form-select rounded-3 border-secondary-subtle" style="min-height: 44px;" @change="applyFilters">
                                <option value="all">All Boarding Houses (Global)</option>
                                <option v-for="house in boardingHousesList" :key="house.id" :value="house.id">
                                    {{ house.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Status Select -->
                        <div class="col-md-2">
                            <label for="filter_status" class="form-label fw-bold small text-body-secondary text-uppercase mb-2">Status</label>
                            <select id="filter_status" v-model="selectedStatus" class="form-select rounded-3 border-secondary-subtle" style="min-height: 44px;" @change="applyFilters">
                                <option value="all">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <!-- Date From -->
                        <div class="col-md-2">
                            <label for="filter_date_from" class="form-label fw-bold small text-body-secondary text-uppercase mb-2">Move-in From</label>
                            <input id="filter_date_from" v-model="dateFrom" type="date" class="form-control rounded-3 border-secondary-subtle" style="min-height: 44px;" @change="applyFilters">
                        </div>

                        <!-- Date To -->
                        <div class="col-md-2">
                            <label for="filter_date_to" class="form-label fw-bold small text-body-secondary text-uppercase mb-2">Move-in To</label>
                            <input id="filter_date_to" v-model="dateTo" type="date" class="form-control rounded-3 border-secondary-subtle" style="min-height: 44px;" @change="applyFilters">
                        </div>

                        <!-- Text Search -->
                        <div class="col-md-2">
                            <label for="filter_search" class="form-label fw-bold small text-body-secondary text-uppercase mb-2">Search</label>
                            <input 
                                id="filter_search" 
                                v-model="searchQuery" 
                                type="text" 
                                class="form-control rounded-3 border-secondary-subtle" 
                                style="min-height: 44px;"
                                placeholder="Code, Guest, House..."
                                @input="handleSearchInput"
                            >
                        </div>

                        <!-- Reset Button -->
                        <div class="col-md-1">
                            <button type="button" class="btn btn-outline-secondary w-100 rounded-3 d-flex align-items-center justify-content-center" style="min-height: 44px;" title="Reset Filters" @click="resetFilters">
                                <i class="bi bi-arrow-counterclockwise fs-5"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- PRIVACY COMPLIANCE NOTICE -->
                <div class="p-3 mb-4 rounded-4 border border-secondary-subtle bg-body d-flex align-items-center gap-3 d-print-none shadow-sm">
                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                        <i class="bi bi-shield-check fs-5"></i>
                    </div>
                    <div class="small">
                        <strong class="text-body-emphasis">Data Privacy Standard Enforced:</strong>
                        <span class="text-body-secondary ms-1">
                            In compliance with Student Data Privacy standards, student contact numbers are masked (e.g. 0912***6789) and personal booking messages are stripped from physical and PDF print outputs.
                        </span>
                    </div>
                </div>

                <!-- RESERVATIONS DATA TABLE -->
                <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle rounded-4 bg-body">
                    
                    <div v-if="reservations.length" class="table-responsive">
                        <table class="table table-hover align-middle mb-0 print-table">
                            <thead>
                                <tr class="bg-body-tertiary">
                                    <th scope="col" class="ps-4 py-3 text-uppercase small text-body-secondary fw-bold">EBM Reference</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold">Guest Name</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold">Boarding House</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold">Move-In Date</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold d-print-none">Contact (Masked)</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold">Status</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold text-end pe-4">Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="res in reservations" :key="res.id">
                                    
                                    <!-- EBM Reference Code with Copy -->
                                    <td class="ps-4 py-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="font-monospace fw-bold text-body-emphasis">{{ res.reference_code }}</span>
                                            <button 
                                                type="button" 
                                                class="btn btn-sm btn-link text-decoration-none p-0 d-print-none"
                                                :title="copiedCode === res.reference_code ? 'Copied!' : 'Copy Code'"
                                                @click="copyReferenceCode(res.reference_code)"
                                            >
                                                <i :class="copiedCode === res.reference_code ? 'bi bi-check2-circle text-success' : 'bi bi-clipboard text-secondary'"></i>
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Guest Name -->
                                    <td class="py-3 fw-medium text-body-emphasis">
                                        {{ res.guest_name }}
                                    </td>

                                    <!-- Boarding House -->
                                    <td class="py-3 text-body-emphasis">
                                        <div>{{ res.boarding_house_name }}</div>
                                        <div class="small text-body-secondary d-print-none">{{ res.boarding_house_address }}</div>
                                    </td>

                                    <!-- Move-in Date -->
                                    <td class="py-3 text-nowrap">
                                        {{ res.preferred_move_in_date }}
                                    </td>

                                    <!-- Contact (Masked for privacy, hidden in print) -->
                                    <td class="py-3 font-monospace small text-body-secondary d-print-none">
                                        {{ res.guest_phone_masked }}
                                    </td>

                                    <!-- Status -->
                                    <td class="py-3">
                                        <span class="badge rounded-2 px-2.5 py-1 text-capitalize print-badge" :class="statusBadgeClass(res.status)">
                                            {{ res.status_label }}
                                        </span>
                                    </td>

                                    <!-- Submitted -->
                                    <td class="py-3 text-end pe-4 small text-body-secondary text-nowrap">
                                        {{ res.created_at }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 🖨️ OFFICIAL PRINT TABLE FOR RESERVATIONS (MATCHES INSTITUTIONAL FORMAT) -->
                    <div v-if="reservations.length" class="d-none d-print-block">
                        <table class="official-print-table">
                            <thead>
                                <tr>
                                    <th style="width: 4%; text-align: center;">NO.</th>
                                    <th style="width: 14%; text-align: center;">REF CODE</th>
                                    <th style="width: 18%; text-align: left;">GUEST NAME</th>
                                    <th style="width: 22%; text-align: left;">NAME OF BOARDING HOUSE</th>
                                    <th style="width: 11%; text-align: center;">MOVE-IN DATE</th>
                                    <th style="width: 11%; text-align: center;">CELL #</th>
                                    <th style="width: 10%; text-align: center;">STATUS</th>
                                    <th style="width: 10%; text-align: center;">SUBMITTED</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(res, index) in reservations" :key="res.id">
                                    <td class="text-center">{{ index + 1 }}</td>
                                    <td class="text-center font-monospace">{{ res.reference_code }}</td>
                                    <td class="text-uppercase">{{ res.guest_name }}</td>
                                    <td class="text-uppercase">{{ res.boarding_house_name }}</td>
                                    <td class="text-center">{{ res.preferred_move_in_date }}</td>
                                    <td class="text-center font-monospace">{{ formatCellNumber(res.guest_phone_masked) }}</td>
                                    <td class="text-center text-uppercase">{{ res.status_label }}</td>
                                    <td class="text-center small">{{ res.created_at }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-else class="text-center p-5">
                        <i class="bi bi-folder-x display-5 text-secondary opacity-50 mb-2 d-block"></i>
                        <h3 class="h5 fw-bold mb-1">No reservations found</h3>
                        <p class="text-body-secondary small mb-3">No reservation records matched your filter criteria.</p>
                        <button class="btn btn-sm btn-outline-secondary rounded-3 px-3 py-2" @click="resetFilters">
                            Reset Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================================= -->
            <!-- 2. BOARDING HOUSE SYSTEM DIRECTORY VIEW                   -->
            <!-- ========================================================= -->
            <div v-else-if="activeReportTab === 'directory'">
                
                <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle rounded-4 bg-body">
                    <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary d-print-none">
                        <h2 class="h5 text-body-emphasis fw-bold mb-1">Master Boarding House Directory</h2>
                        <p class="text-body-secondary small mb-0">Complete roster of registered properties, owner contact details, coordinates, and capacities.</p>
                    </div>

                    <!-- ON-SCREEN DIRECTORY TABLE (HIDDEN IN PRINT) -->
                    <div class="table-responsive d-print-none">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="bg-body-tertiary">
                                    <th scope="col" class="ps-3 py-3 text-center text-uppercase small text-body-secondary fw-bold" style="width: 5%;">NO.</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold" style="width: 22%;">NAME OF OWNER</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold" style="width: 22%;">NAME OF BOARDING HOUSE</th>
                                    <th scope="col" class="py-3 text-uppercase small text-body-secondary fw-bold" style="width: 25%;">ADDRESS</th>
                                    <th scope="col" class="py-3 text-center text-uppercase small text-body-secondary fw-bold" style="width: 14%;">CELL #</th>
                                    <th scope="col" class="py-3 text-end pe-3 text-uppercase small text-body-secondary fw-bold" style="width: 12%;">STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(house, index) in directoryReport" :key="house.id">
                                    <td class="ps-3 py-3 text-center fw-semibold text-body-secondary">{{ index + 1 }}</td>
                                    <td class="py-3 fw-bold text-uppercase text-body-emphasis">{{ house.owner_name }}</td>
                                    <td class="py-3 fw-medium text-uppercase text-body-emphasis">{{ house.name }}</td>
                                    <td class="py-3 text-uppercase small text-body-secondary">{{ house.address }}</td>
                                    <td class="py-3 text-center font-monospace small fw-semibold">{{ formatCellNumber(house.owner_phone) }}</td>
                                    <td class="py-3 text-end pe-3">
                                        <span class="badge rounded-2 px-2.5 py-1 text-capitalize" :class="statusBadgeClass(house.status)">
                                            {{ house.status }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 🖨️ OFFICIAL PRINT TABLE FOR DIRECTORY (EXACT MATCH TO TPC ATTACHED PHOTO) -->
                    <div class="d-none d-print-block">
                        <table class="official-print-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%; text-align: center;">NO.</th>
                                    <th style="width: 25%; text-align: left;">NAME OF OWNER</th>
                                    <th style="width: 25%; text-align: left;">NAME OF BOARDING HOUSE</th>
                                    <th style="width: 29%; text-align: left;">ADDRESS</th>
                                    <th style="width: 16%; text-align: center;">CELL #</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(house, index) in directoryReport" :key="house.id">
                                    <td class="text-center">{{ index + 1 }}</td>
                                    <td class="text-uppercase">{{ house.owner_name }}</td>
                                    <td class="text-uppercase">{{ house.name }}</td>
                                    <td class="text-uppercase">{{ house.address }}</td>
                                    <td class="text-center font-monospace">{{ formatCellNumber(house.owner_phone) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- 🖨️ INSTITUTIONAL PRINT FOOTER (Visible ONLY during print) -->
            <div class="d-none d-print-block mt-4 text-end text-muted small" style="font-size: 8pt;">
                Talibon Polytechnic College &bull; E-BoardMate System Official Report &bull; Generated: {{ generatedAt }}
            </div>

        </div>
    </AdminLayout>
</template>

<style scoped>
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

.badge-soft-primary {
    background-color: rgba(13, 110, 253, 0.15);
    color: #0d6efd;
    border: 1px solid rgba(13, 110, 253, 0.25);
}

/* ========================================================= */
/* 🖨️ OFFICIAL PRINT MEDIA STYLESHEET (MATCHES TPC ATTACHED)  */
/* ========================================================= */
.official-print-table {
    width: 100% !important;
    border-collapse: collapse !important;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
    font-size: 9.5pt !important;
    color: #000000 !important;
    margin-top: 4px !important;
}

.official-print-table th,
.official-print-table td {
    border: 1px solid #000000 !important;
    padding: 5px 6px !important;
    color: #000000 !important;
    vertical-align: middle !important;
}

.official-print-table thead th {
    background-color: #f2f2f2 !important;
    font-weight: bold !important;
    text-transform: uppercase !important;
    font-size: 9pt !important;
    letter-spacing: 0.3px !important;
    text-align: center !important;
}

.print-banner-img {
    width: 100% !important;
    max-height: 85px !important;
    object-fit: contain !important;
}

.print-document-title {
    text-align: center !important;
    font-weight: bold !important;
    font-size: 13.5pt !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    margin: 8px 0 12px 0 !important;
    color: #000000 !important;
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
}

@media print {
    @page {
        size: letter portrait;
        margin: 8mm 10mm 10mm 10mm;
    }

    body, html, main, .container-fluid {
        background: #ffffff !important;
        color: #000000 !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 100% !important;
    }

    /* Hide non-printable elements */
    .d-print-none,
    nav,
    aside,
    button,
    .btn,
    header,
    canvas,
    footer,
    .navbar,
    .sidebar {
        display: none !important;
    }

    .d-print-block {
        display: block !important;
    }

    .ebm-card {
        box-shadow: none !important;
        border: none !important;
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
    }
}
</style>