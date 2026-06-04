<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    activityLogs: {
        type: Array,
        default: () => [],
    },
});

// --- BULK SELECTION LOGIC ---
const selectedLogs = ref([]);
const isProcessing = ref(false);

const selectAll = computed({
    get: () => props.activityLogs.length > 0 && selectedLogs.value.length === props.activityLogs.length,
    set: (value) => {
        if (value) {
            selectedLogs.value = props.activityLogs.map(log => log.id);
        } else {
            selectedLogs.value = [];
        }
    }
});

const archiveSelected = () => {
    if (!confirm(`Are you sure you want to archive ${selectedLogs.value.length} selected log(s)?`)) return;

    isProcessing.value = true;
    
 
    router.delete('/admin/activity-logs/bulk', {
        data: { ids: selectedLogs.value },
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            selectedLogs.value = []; 
            isProcessing.value = false;
        },
        onError: () => {
            isProcessing.value = false;
        }
    });
};
// ----------------------------

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
                <span class="badge text-bg-dark mb-3">Super Admin</span>
                <h1 class="fw-bold mb-2">Activity Logs</h1>
                <p class="ebm-muted mb-0">Review recent system activities for accountability and monitoring.</p>
            </div>

            <div class="ebm-card p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
                    <div>
                        <h2 class="h5 fw-bold mb-1">Recent Activities</h2>
                        <p class="ebm-muted small mb-0">Showing the latest activity log records.</p>
                    </div>
                    
                    <Transition name="fade">
                        <div v-if="selectedLogs.length > 0" class="d-flex align-items-center gap-3 bg-light border rounded px-3 py-2 shadow-sm">
                            <span class="fw-bold text-primary">{{ selectedLogs.length }} selected</span>
                            <button 
                                @click="archiveSelected" 
                                class="btn btn-sm btn-outline-danger d-flex align-items-center"
                                :disabled="isProcessing"
                            >
                                <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Archive Selected
                            </button>
                        </div>
                    </Transition>
                </div>

                <div v-if="activityLogs.length" class="table-responsive">
                    <table class="table align-middle owner-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input class="form-check-input" type="checkbox" v-model="selectAll">
                                </th>
                                <th>Date / Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Related Record</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="log in activityLogs" :key="log.id" :class="{ 'table-active': selectedLogs.includes(log.id) }">
                                <td>
                                    <input class="form-check-input" type="checkbox" :value="log.id" v-model="selectedLogs">
                                </td>
                                <td class="small ebm-muted">{{ log.created_at }}</td>
                                <td>
                                    <div class="fw-semibold">{{ log.user_name }}</div>
                                    <div class="small ebm-muted">{{ log.user_email || 'No email' }}</div>
                                </td>
                                <td>
                                    <span class="badge" :class="actionBadgeClass(log.action)">{{ log.action }}</span>
                                </td>
                                <td>{{ log.description }}</td>
                                <td class="small">
                                    <template v-if="log.boarding_house_name">
                                        <strong>Boarding House:</strong> {{ log.boarding_house_name }}
                                    </template>
                                    <br v-if="log.boarding_house_name && log.reservation_reference">
                                    <template v-if="log.reservation_reference">
                                        <strong>Reservation:</strong> {{ log.reservation_reference }}
                                    </template>
                                    <span v-if="!log.boarding_house_name && !log.reservation_reference" class="ebm-muted">None</span>
                                </td>
                                <td class="small ebm-muted">{{ log.ip_address || 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="empty-state">
                    <div class="empty-state-icon">📝</div>
                    <h3 class="h5 fw-bold mb-2">No activity logs yet</h3>
                    <p class="ebm-muted mb-0">System activities will appear here after users perform actions.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.table-active {
    background-color: rgba(13, 110, 253, 0.04) !important;
}
</style>