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

        <div class="container pb-5 pt-2">
            
            <!-- HEADER SECTION -->
            <header class="mb-4">
                <span class="text-uppercase small fw-bold text-secondary tracking-wider d-block mb-1">System Audit Trail</span>
                <h1 class="h3 text-body-emphasis fw-bold mb-1 tracking-tight">Activity Logs</h1>
                <p class="text-body-secondary mb-0">Chronological audit log of administrative actions, property verifications, and user account events.</p>
            </header>

            <section class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle rounded-4 d-flex flex-column bg-body-tertiary">
                <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div>
                        <h2 class="h5 text-body-emphasis fw-bold mb-1">Recent Activities</h2>
                        <p class="text-body-secondary small mb-0">Showing latest recorded system events and administrative interventions.</p>
                    </div>
                    
                    <Transition name="fade">
                        <div v-if="selectedLogs.length > 0" class="d-flex align-items-center gap-3 bg-body border border-secondary-subtle rounded-3 px-3 py-2 shadow-sm transition-all">
                            <span class="fw-bold text-primary small">{{ selectedLogs.length }} selected</span>
                            <button 
                                @click="archiveSelected" 
                                class="btn btn-sm btn-outline-danger rounded-3 d-flex align-items-center fw-semibold px-3 py-1.5"
                                style="min-height: 38px;"
                                :disabled="isProcessing"
                            >
                                <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Archive Selected
                            </button>
                        </div>
                    </Transition>
                </div>

                <!-- 🚀 UX FIX: The "Window Box" Table Scroll Wrapper -->
                <div v-if="activityLogs.length" class="table-responsive custom-table-scroll flex-grow-1 bg-body">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" style="width: 40px;" class="sticky-header bg-body-tertiary border-secondary-subtle ps-4">
                                    <input class="form-check-input shadow-none" type="checkbox" v-model="selectAll">
                                </th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Date / Time</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">User</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Action</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Description</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Related Record</th>
                                <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">IP Address</th>
                            </tr>
                        </thead>

                        <tbody class="border-top-0">
                            <tr v-for="log in activityLogs" :key="log.id" :class="{ 'table-active': selectedLogs.includes(log.id) }">
                                <td class="border-secondary-subtle ps-4">
                                    <input class="form-check-input shadow-none" type="checkbox" :value="log.id" v-model="selectedLogs">
                                </td>
                                <td class="small text-body-secondary border-secondary-subtle text-nowrap font-monospace">{{ log.created_at }}</td>
                                <td class="border-secondary-subtle text-nowrap">
                                    <div class="fw-semibold text-body-emphasis">{{ log.user_name }}</div>
                                    <div class="small text-body-secondary mt-0.5">{{ log.user_email || 'No email' }}</div>
                                </td>
                                <td class="border-secondary-subtle text-nowrap">
                                    <span class="badge rounded-2 px-2 py-1 font-monospace" :class="actionBadgeClass(log.action)">{{ log.action }}</span>
                                </td>
                                <td class="border-secondary-subtle text-body-emphasis" style="min-width: 250px; max-width: 350px;">{{ log.description }}</td>
                                <td class="small border-secondary-subtle" style="min-width: 200px;">
                                    <template v-if="log.boarding_house_name">
                                        <strong class="text-body-emphasis">Boarding House:</strong> <span class="text-body-secondary">{{ log.boarding_house_name }}</span>
                                    </template>
                                    <br v-if="log.boarding_house_name && log.reservation_reference">
                                    <template v-if="log.reservation_reference">
                                        <strong class="text-body-emphasis">Reservation:</strong> <span class="text-body-secondary">{{ log.reservation_reference }}</span>
                                    </template>
                                    <span v-if="!log.boarding_house_name && !log.reservation_reference" class="text-body-secondary fst-italic">None</span>
                                </td>
                                <td class="small text-body-secondary border-secondary-subtle text-nowrap text-end pe-4 font-monospace">{{ log.ip_address || 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- EMPTY STATE -->
                <div v-else class="d-flex flex-column align-items-center justify-content-center text-center p-5 bg-body">
                    <i class="bi bi-clock-history display-5 text-secondary opacity-50 mb-2 d-block"></i>
                    <h3 class="h5 text-body-emphasis fw-bold mb-1">No activity logs recorded</h3>
                    <p class="text-body-secondary small mb-0">System events and audit records will appear here as administrative actions occur.</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* 🪄 UX FIX: The Custom "Window Box" Table Scroll */
.custom-table-scroll {
    max-height: 600px;
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
    background-color: rgba(25, 135, 84, 0.08) !important;
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