<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
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

const statusBadgeClass = (status) => {
    if (status === 'pending') {
        return 'text-bg-warning';
    }

    if (status === 'approved') {
        return 'text-bg-success';
    }

    if (status === 'rejected') {
        return 'text-bg-danger';
    }

    if (status === 'expired' || status === 'cancelled') {
        return 'text-bg-secondary';
    }

    return 'text-bg-secondary';
};
</script>

<template>
    <AdminLayout>
        <Head title="Reports | E-BoardMate" />

        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center mb-4 no-print">
                <div>
                    <span class="badge text-bg-dark mb-3">
                        Super Admin
                    </span>

                    <h1 class="fw-bold mb-2">
                        Reports
                    </h1>

                    <p class="ebm-muted mb-0">
                        View and print reservation and boarding house summary reports.
                    </p>
                </div>

                <button
                    type="button"
                    class="btn btn-ebm-primary"
                    @click="printReport"
                >
                    Print Report
                </button>
            </div>

            <div class="print-report-header">
                <h1 class="fw-bold mb-1">
                    E-BoardMate Reservation Summary Report
                </h1>

                <p class="mb-1">
                    A Web-Based Boarding House Locator and Reservations System for Talibon Polytechnic College
                </p>

                <p class="ebm-muted mb-0">
                    Generated: {{ generatedAt }}
                </p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Total Owners
                        </span>

                        <strong class="dashboard-stat-value">
                            {{ stats.total_owners }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Boarding Houses
                        </span>

                        <strong class="dashboard-stat-value">
                            {{ stats.total_boarding_houses }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Approved Listings
                        </span>

                        <strong class="dashboard-stat-value text-success">
                            {{ stats.approved_boarding_houses }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Pending Listings
                        </span>

                        <strong class="dashboard-stat-value text-warning">
                            {{ stats.pending_boarding_houses }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6 col-xl-2">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Total
                        </span>

                        <strong class="dashboard-stat-value">
                            {{ stats.total_reservations }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-2">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Pending
                        </span>

                        <strong class="dashboard-stat-value text-warning">
                            {{ stats.pending_reservations }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-2">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Approved
                        </span>

                        <strong class="dashboard-stat-value text-success">
                            {{ stats.approved_reservations }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-2">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Rejected
                        </span>

                        <strong class="dashboard-stat-value text-danger">
                            {{ stats.rejected_reservations }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-2">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Expired
                        </span>

                        <strong class="dashboard-stat-value text-secondary">
                            {{ stats.expired_reservations }}
                        </strong>
                    </div>
                </div>

                <div class="col-md-6 col-xl-2">
                    <div class="ebm-card p-4">
                        <span class="dashboard-stat-label">
                            Cancelled
                        </span>

                        <strong class="dashboard-stat-value text-secondary">
                            {{ stats.cancelled_reservations }}
                        </strong>
                    </div>
                </div>
            </div>

            <div class="ebm-card p-4">
                <div class="mb-3">
                    <h2 class="h5 fw-bold mb-1">
                        Latest Reservation Records
                    </h2>

                    <p class="ebm-muted small mb-0">
                        Showing latest 100 reservation records.
                    </p>
                </div>

                <div
                    v-if="reservations.length"
                    class="table-responsive"
                >
                    <table class="table align-middle owner-table report-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Guest</th>
                                <th>Boarding House</th>
                                <th>Move-in Date</th>
                                <th>Status</th>
                                <th>Submitted</th>
                                <th>Responded</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="reservation in reservations"
                                :key="reservation.id"
                            >
                                <td>
                                    <strong>{{ reservation.reference_code }}</strong>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ reservation.guest_name }}
                                    </div>

                                    <div class="small ebm-muted">
                                        {{ reservation.guest_email }}
                                    </div>

                                    <div class="small ebm-muted">
                                        {{ reservation.guest_phone }}
                                    </div>
                                </td>

                                <td>
                                    {{ reservation.boarding_house_name }}
                                </td>

                                <td>
                                    {{ reservation.preferred_move_in_date }}
                                </td>

                                <td>
                                    <span
                                        class="badge"
                                        :class="statusBadgeClass(reservation.status)"
                                    >
                                        {{ reservation.status_label }}
                                    </span>
                                </td>

                                <td class="small ebm-muted">
                                    {{ reservation.created_at }}
                                </td>

                                <td class="small ebm-muted">
                                    {{ reservation.responded_at || 'Not yet' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-else
                    class="empty-state"
                >
                    <div class="empty-state-icon">
                        📋
                    </div>

                    <h3 class="h5 fw-bold mb-2">
                        No reservation records yet
                    </h3>

                    <p class="ebm-muted mb-0">
                        Reservation records will appear here.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>