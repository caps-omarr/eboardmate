<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
    activityLogs: {
        type: Array,
        default: () => [],
    },
});

const actionBadgeClass = (action) => {
    if (action.includes('approved') || action.includes('verified') || action.includes('reactivated')) {
        return 'text-bg-success';
    }

    if (action.includes('rejected') || action.includes('deleted') || action.includes('deactivated')) {
        return 'text-bg-danger';
    }

    if (action.includes('created') || action.includes('uploaded')) {
        return 'text-bg-primary';
    }

    if (action.includes('updated')) {
        return 'text-bg-warning';
    }

    return 'text-bg-secondary';
};
</script>

<template>
    <AdminLayout>
        <Head title="Activity Logs | E-BoardMate" />

        <div class="container">
            <div class="mb-4">
                <span class="badge text-bg-dark mb-3">
                    Super Admin
                </span>

                <h1 class="fw-bold mb-2">
                    Activity Logs
                </h1>

                <p class="ebm-muted mb-0">
                    Review recent system activities for accountability and monitoring.
                </p>
            </div>

            <div class="ebm-card p-4">
                <div class="mb-3">
                    <h2 class="h5 fw-bold mb-1">
                        Recent Activities
                    </h2>

                    <p class="ebm-muted small mb-0">
                        Showing the latest 150 activity log records.
                    </p>
                </div>

                <div
                    v-if="activityLogs.length"
                    class="table-responsive"
                >
                    <table class="table align-middle owner-table">
                        <thead>
                            <tr>
                                <th>Date / Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Related Record</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="log in activityLogs"
                                :key="log.id"
                            >
                                <td class="small ebm-muted">
                                    {{ log.created_at }}
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        {{ log.user_name }}
                                    </div>

                                    <div class="small ebm-muted">
                                        {{ log.user_email || 'No email' }}
                                    </div>
                                </td>

                                <td>
                                    <span
                                        class="badge"
                                        :class="actionBadgeClass(log.action)"
                                    >
                                        {{ log.action }}
                                    </span>
                                </td>

                                <td>
                                    {{ log.description }}
                                </td>

                                <td class="small">
                                    <template v-if="log.boarding_house_name">
                                        <strong>Boarding House:</strong>
                                        {{ log.boarding_house_name }}
                                    </template>

                                    <br v-if="log.boarding_house_name && log.reservation_reference">

                                    <template v-if="log.reservation_reference">
                                        <strong>Reservation:</strong>
                                        {{ log.reservation_reference }}
                                    </template>

                                    <span
                                        v-if="!log.boarding_house_name && !log.reservation_reference"
                                        class="ebm-muted"
                                    >
                                        None
                                    </span>
                                </td>

                                <td class="small ebm-muted">
                                    {{ log.ip_address || 'N/A' }}
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
                        📝
                    </div>

                    <h3 class="h5 fw-bold mb-2">
                        No activity logs yet
                    </h3>

                    <p class="ebm-muted mb-0">
                        System activities will appear here after users perform actions.
                    </p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>