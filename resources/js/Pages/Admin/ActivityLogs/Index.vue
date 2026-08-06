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

        <div class="container-fluid py-2">
            <!-- HEADER SECTION -->
            <div class="mb-4">
                <span class="badge text-bg-dark mb-3 px-3 py-2">Super Admin</span>
                <h1 class="text-body-emphasis fw-bold mb-2 transition-all">Activity Logs</h1>
                <p class="text-body-secondary mb-0 transition-all">Review recent system activities for accountability and monitoring.</p>
            </div>

            <div class="card border-0 shadow-sm p-4 bg-body-tertiary transition-all">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h2 class="h5 text-body-emphasis fw-bold mb-1">Recent Activities</h2>
                        <p class="text-body-secondary small mb-0">Showing the latest activity log records.</p>
                    </div>
                    
                    <Transition name="fade">
                        <!-- Swapped hardcoded bg-light for bg-body to adapt to dark mode -->
                        <div v-if="selectedLogs.length > 0" class="d-flex align-items-center gap-3 bg-body border border-secondary-subtle rounded px-3 py-2 shadow-sm transition-all">
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

                <!-- TABLE SECTION -->
                <div v-if="activityLogs.length" class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="border-secondary-subtle">
                                    <input class="form-check-input" type="checkbox" v-model="selectAll">
                                </th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Date / Time</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">User</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Action</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Description</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">Related Record</th>
                                <th class="text-body-secondary text-uppercase small border-secondary-subtle">IP Address</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="log in activityLogs" :key="log.id" :class="{ 'table-active': selectedLogs.includes(log.id) }">
                                <td class="border-secondary-subtle">
                                    <input class="form-check-input" type="checkbox" :value="log.id" v-model="selectedLogs">
                                </td>
                                <td class="small text-body-secondary border-secondary-subtle">{{ log.created_at }}</td>
                                <td class="border-secondary-subtle">
                                    <div class="fw-semibold text-body-emphasis">{{ log.user_name }}</div>
                                    <div class="small text-body-secondary mt-1">{{ log.user_email || 'No email' }}</div>
                                </td>
                                <td class="border-secondary-subtle">
                                    <span class="badge" :class="actionBadgeClass(log.action)">{{ log.action }}</span>
                                </td>
                                <td class="border-secondary-subtle text-body-emphasis">{{ log.description }}</td>
                                <td class="small border-secondary-subtle">
                                    <template v-if="log.boarding_house_name">
                                        <strong class="text-body-emphasis">Boarding House:</strong> <span class="text-body-secondary">{{ log.boarding_house_name }}</span>
                                    </template>
                                    <br v-if="log.boarding_house_name && log.reservation_reference">
                                    <template v-if="log.reservation_reference">
                                        <strong class="text-body-emphasis">Reservation:</strong> <span class="text-body-secondary">{{ log.reservation_reference }}</span>
                                    </template>
                                    <span v-if="!log.boarding_house_name && !log.reservation_reference" class="text-body-secondary">None</span>
                                </td>
                                <td class="small text-body-secondary border-secondary-subtle">{{ log.ip_address || 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- EMPTY STATE -->
                <div v-else class="d-flex flex-column align-items-center justify-content-center text-center p-5 h-100 rounded border border-secondary-subtle bg-body transition-all">
                    <div class="fs-1 mb-3">📝</div>
                    <h3 class="h5 text-body-emphasis fw-bold mb-2">No activity logs yet</h3>
                    <p class="text-body-secondary mb-0">System activities will appear here after users perform actions.</p>
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

/* Smooth fade transitions for colors when toggling dark mode */
.transition-all {
    transition: background-color 0.3s ease-in-out, color 0.3s ease-in-out, border-color 0.3s ease-in-out;
}
</style>