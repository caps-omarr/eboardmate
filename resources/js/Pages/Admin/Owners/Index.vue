<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

defineProps({
    owners: {
        type: Object,
        required: true,
    },
});

const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success || null);

const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const statusForm = useForm({});

const submitOwner = () => {
    createForm.post('/admin/owners', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
        },
    });
};

const toggleOwnerStatus = (owner) => {
    const action = owner.status === 'active' ? 'deactivate' : 'activate';

    if (!confirm(`Are you sure you want to ${action} this owner account?`)) {
        return;
    }

    statusForm.patch(owner.toggle_status_url, {
        preserveScroll: true,
    });
};

const statusBadgeClass = (status) => {
    return status === 'active'
        ? 'text-bg-success'
        : 'text-bg-secondary';
};

const cleanLabel = (label) => {
    if (!label) return '';
    if (label.includes('&laquo;')) return '« Previous';
    if (label.includes('&raquo;')) return 'Next »';
    return label;
};
</script>

<template>
    <AdminLayout>
        <Head title="Owner Accounts | E-BoardMate" />

        <div class="container pb-5 pt-2">
            
            <!-- FLASH SUCCESS MESSAGE -->
            <div v-if="flashSuccess" class="alert alert-success mb-4 border-0 shadow-sm">
                {{ flashSuccess }}
            </div>

            <!-- HEADER SECTION -->
            <header class="mb-4">
                <span class="badge bg-body text-body border border-secondary-subtle mb-3 px-3 py-2 rounded-pill shadow-sm">
                    Super Admin
                </span>

                <h1 class="text-body-emphasis fw-bold mb-2 tracking-tight">
                    Owner Account Management
                </h1>

                <p class="text-body-secondary mb-0 lead" style="font-size: 1.1rem;">
                    Create and manage boarding house owner accounts.
                </p>
            </header>

            <div class="row g-4">
                
                <!-- LEFT COLUMN: CREATE OWNER FORM -->
                <section class="col-lg-4" aria-label="Create Owner Form">
                    <div class="ebm-card border border-secondary-subtle shadow-sm p-4 bg-body-tertiary h-100">
                        <h2 class="h5 text-body-emphasis fw-bold mb-4 border-bottom border-secondary-subtle pb-2">
                            Create Owner Account
                        </h2>

                        <form @submit.prevent="submitOwner">
                            <div class="mb-3">
                                <label for="name" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                    Full Name
                                </label>
                                <input
                                    id="name"
                                    v-model="createForm.name"
                                    type="text"
                                    class="form-control border-secondary-subtle bg-body shadow-sm"
                                    :class="{ 'is-invalid': createForm.errors.name }"
                                    placeholder="Owner full name"
                                >
                                <div v-if="createForm.errors.name" class="invalid-feedback fw-bold">
                                    {{ createForm.errors.name }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                    Email Address
                                </label>
                                <input
                                    id="email"
                                    v-model="createForm.email"
                                    type="email"
                                    class="form-control border-secondary-subtle bg-body shadow-sm"
                                    :class="{ 'is-invalid': createForm.errors.email }"
                                    placeholder="owner@example.com"
                                >
                                <div v-if="createForm.errors.email" class="invalid-feedback fw-bold">
                                    {{ createForm.errors.email }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                    Phone Number
                                </label>
                                <input
                                    id="phone"
                                    v-model="createForm.phone"
                                    type="text"
                                    class="form-control border-secondary-subtle bg-body shadow-sm"
                                    :class="{ 'is-invalid': createForm.errors.phone }"
                                    placeholder="09XXXXXXXXX"
                                >
                                <div v-if="createForm.errors.phone" class="invalid-feedback fw-bold">
                                    {{ createForm.errors.phone }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                    Temporary Password
                                </label>
                                <input
                                    id="password"
                                    v-model="createForm.password"
                                    type="password"
                                    class="form-control border-secondary-subtle bg-body shadow-sm"
                                    :class="{ 'is-invalid': createForm.errors.password }"
                                    placeholder="Minimum 8 characters"
                                >
                                <div v-if="createForm.errors.password" class="invalid-feedback fw-bold">
                                    {{ createForm.errors.password }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                    Confirm Password
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    class="form-control border-secondary-subtle bg-body shadow-sm"
                                    placeholder="Repeat password"
                                >
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2 fw-bold shadow-sm" :disabled="createForm.processing">
                                <span v-if="createForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ createForm.processing ? 'Creating...' : 'Create Owner' }}
                            </button>
                        </form>
                    </div>
                </section>

                <!-- RIGHT COLUMN: OWNERS TABLE & LIST -->
                <section class="col-lg-8" aria-label="Owner Accounts Data Table">
                    <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle h-100 d-flex flex-column">
                        <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary">
                            <h2 class="h5 text-body-emphasis fw-bold mb-1">
                                Owner Accounts
                            </h2>
                            <p class="text-body-secondary small mb-0">
                                Active owners can log in to the owner portal. Inactive owners are blocked from logging in.
                            </p>
                        </div>

                        <div v-if="owners.data && owners.data.length" class="d-flex flex-column justify-content-between flex-grow-1 bg-body">
                            
                            <!-- 🚀 UX FIX: The "Window Box" Table Scroll -->
                            <div class="table-responsive custom-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <!-- 🚀 UX FIX: Sticky headers and text-nowrap -->
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Owner</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Contact</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Assigned Listing</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Created</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody class="border-top-0">
                                        <tr v-for="owner in owners.data" :key="owner.id">
                                            
                                            <!-- Owner Name & ID -->
                                            <td class="text-nowrap ps-4 border-secondary-subtle">
                                                <div class="fw-bold text-body-emphasis">{{ owner.name }}</div>
                                                <div class="small text-body-secondary mt-1">ID: {{ owner.id }}</div>
                                            </td>

                                            <!-- Contact Details -->
                                            <td class="text-nowrap border-secondary-subtle">
                                                <div class="text-body-emphasis fw-medium">{{ owner.email }}</div>
                                                <div class="small text-body-secondary mt-1">{{ owner.phone || 'No phone' }}</div>
                                            </td>

                                            <!-- Assigned Listing -->
                                            <td class="text-nowrap border-secondary-subtle" style="max-width: 250px;">
                                                <template v-if="owner.boarding_house">
                                                    <div class="fw-bold text-body-emphasis text-truncate">{{ owner.boarding_house.name }}</div>
                                                    <div class="small text-body-secondary mt-1">{{ owner.boarding_house.status }}</div>
                                                </template>
                                                <span v-else class="small text-body-secondary fst-italic">No assigned listing</span>
                                            </td>

                                            <!-- Status -->
                                            <td class="text-nowrap border-secondary-subtle">
                                                <span class="badge shadow-sm" :class="statusBadgeClass(owner.status)">
                                                    {{ owner.status }}
                                                </span>
                                            </td>

                                            <!-- Created Date -->
                                            <td class="small text-body-secondary text-nowrap border-secondary-subtle">
                                                {{ owner.created_at }}
                                            </td>

                                            <!-- Action Button -->
                                            <td class="border-secondary-subtle text-end pe-4">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm shadow-sm"
                                                    :class="owner.status === 'active' ? 'btn-outline-danger bg-body' : 'btn-success'"
                                                    :disabled="statusForm.processing"
                                                    @click="toggleOwnerStatus(owner)"
                                                >
                                                    {{ owner.status === 'active' ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- PAGINATION -->
                            <nav v-if="owners.links && owners.links.length > 3" aria-label="Owner pagination" class="p-3 bg-body-tertiary border-top border-secondary-subtle">
                                <ul class="pagination justify-content-end mb-0">
                                    <li 
                                        v-for="(link, index) in owners.links" 
                                        :key="index" 
                                        class="page-item" 
                                        :class="{ active: link.active, disabled: !link.url }"
                                    >
                                        <Link 
                                            v-if="link.url" 
                                            :href="link.url" 
                                            class="page-link border-secondary-subtle bg-body text-body shadow-sm" 
                                            preserve-scroll 
                                        >
                                            {{ cleanLabel(link.label) }}
                                        </Link>
                                        <span 
                                            v-else 
                                            class="page-link border-secondary-subtle bg-body text-body opacity-50"
                                        >
                                            {{ cleanLabel(link.label) }}
                                        </span>
                                    </li>
                                </ul>
                            </nav>
                        </div>

                        <!-- EMPTY STATE -->
                        <div v-else class="d-flex flex-column align-items-center justify-content-center text-center p-5 h-100 bg-body">
                            <div class="fs-1 mb-3 opacity-50">👤</div>
                            <h3 class="h5 text-body-emphasis fw-bold mb-2">No owner accounts yet</h3>
                            <p class="text-body-secondary mb-0">Create an owner account to assign and manage boarding house listings.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
/* 🪄 UX FIX: The Custom "Window Box" Table Scroll */
.custom-table-scroll {
    max-height: calc(100vh - 350px);
    min-height: 400px;
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
    box-shadow: inset 0 -1px 0 var(--bs-border-color); /* Adds the bottom border cleanly under the sticky header */
}

/* Typography refinements */
.tracking-tight {
    letter-spacing: -0.02em;
}
</style>