<script setup>
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Modal } from 'bootstrap';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    owners: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || null);
const flashError = computed(() => page.props.flash?.error || null);

const isLoading = ref(false);
let unhookStart = null;
let unhookFinish = null;

onMounted(() => {
    unhookStart = router.on('start', () => isLoading.value = true);
    unhookFinish = router.on('finish', () => isLoading.value = false);
});

onUnmounted(() => {
    if (unhookStart) unhookStart();
    if (unhookFinish) unhookFinish();
});

// --- SEARCH & DEBOUNCE LOGIC ---
const searchQuery = ref(props.filters?.search || '');
let searchTimeout = null;

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/owners', { search: searchQuery.value }, {
            preserveState: true,
            replace: true,
        });
    }, 350);
};

const clearSearch = () => {
    searchQuery.value = '';
    router.get('/admin/owners', {}, {
        preserveState: true,
        replace: true,
    });
};

// --- CREATE OWNER FORM ---
const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submitOwner = () => {
    createForm.post('/admin/owners', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
        },
    });
};

// --- STATUS TOGGLE ---
const statusForm = useForm({});

const toggleOwnerStatus = (owner) => {
    const action = owner.status === 'active' ? 'deactivate' : 'activate';
    if (!confirm(`Are you sure you want to ${action} this owner account?`)) {
        return;
    }
    statusForm.patch(owner.toggle_status_url, {
        preserveScroll: true,
    });
};

// --- EDIT OWNER MODAL ---
const selectedOwner = ref(null);
const editForm = useForm({
    name: '',
    email: '',
    phone: '',
});

const openEditModal = (owner) => {
    selectedOwner.value = owner;
    editForm.clearErrors();
    editForm.name = owner.name;
    editForm.email = owner.email;
    editForm.phone = owner.phone || '';

    const modalEl = document.getElementById('editOwnerModal');
    if (modalEl) Modal.getOrCreateInstance(modalEl).show();
};

const submitEditOwner = () => {
    if (!selectedOwner.value) return;
    editForm.put(selectedOwner.value.update_url, {
        preserveScroll: true,
        onSuccess: () => {
            const modalEl = document.getElementById('editOwnerModal');
            if (modalEl) Modal.getOrCreateInstance(modalEl).hide();
        },
    });
};

// --- RESET PASSWORD MODAL ---
const passwordForm = useForm({
    password: '',
});
const isCustomPassword = ref(false);

const openResetPasswordModal = (owner) => {
    selectedOwner.value = owner;
    passwordForm.clearErrors();
    passwordForm.password = '';
    isCustomPassword.value = false;

    const modalEl = document.getElementById('resetPasswordModal');
    if (modalEl) Modal.getOrCreateInstance(modalEl).show();
};

const generateSecurePassword = () => {
    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    let generated = '';
    for (let i = 0; i < 10; i++) {
        generated += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    passwordForm.password = generated;
    isCustomPassword.value = true;
};

const submitResetPassword = () => {
    if (!selectedOwner.value) return;
    passwordForm.post(selectedOwner.value.reset_password_url, {
        preserveScroll: true,
        onSuccess: () => {
            const modalEl = document.getElementById('resetPasswordModal');
            if (modalEl) Modal.getOrCreateInstance(modalEl).hide();
        },
    });
};

// --- DELETE OWNER MODAL ---
const deleteForm = useForm({});

const openDeleteModal = (owner) => {
    selectedOwner.value = owner;
    const modalEl = document.getElementById('deleteOwnerModal');
    if (modalEl) Modal.getOrCreateInstance(modalEl).show();
};

const submitDeleteOwner = () => {
    if (!selectedOwner.value) return;
    deleteForm.delete(selectedOwner.value.delete_url, {
        preserveScroll: true,
        onSuccess: () => {
            const modalEl = document.getElementById('deleteOwnerModal');
            if (modalEl) Modal.getOrCreateInstance(modalEl).hide();
        },
    });
};

const statusBadgeClass = (status) => {
    return status === 'active'
        ? 'badge-soft-success'
        : 'badge-soft-secondary';
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
            
            <!-- FLASH MESSAGES -->
            <div v-if="flashSuccess" class="alert alert-success mb-4 border-0 shadow-sm d-flex align-items-center gap-2 rounded-4">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div class="flex-grow-1">{{ flashSuccess }}</div>
            </div>

            <div v-if="flashError" class="alert alert-danger mb-4 border-0 shadow-sm d-flex align-items-center gap-2 rounded-4">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div class="flex-grow-1">{{ flashError }}</div>
            </div>

            <!-- HEADER SECTION -->
            <header class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <span class="badge bg-body text-body border border-secondary-subtle mb-2 px-3 py-2 rounded-pill shadow-sm">
                        Super Admin Portal
                    </span>
                    <h1 class="text-body-emphasis fw-bold mb-1 tracking-tight">
                        Owner Account Management
                    </h1>
                    <p class="text-body-secondary mb-0">
                        Create, edit, reset credentials, and monitor boarding house owner accounts.
                    </p>
                </div>
            </header>

            <div class="row g-4">
                
                <!-- LEFT COLUMN: CREATE OWNER FORM -->
                <section class="col-lg-4" aria-label="Create Owner Form">
                    <div class="ebm-card border border-secondary-subtle shadow-sm p-4 bg-body-tertiary rounded-4 h-100">
                        <h2 class="h5 text-body-emphasis fw-bold mb-4 border-bottom border-secondary-subtle pb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-person-plus-fill text-success"></i> Create Owner Account
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
                                    class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2"
                                    :class="{ 'is-invalid': createForm.errors.name }"
                                    placeholder="e.g. Juan Dela Cruz"
                                    required
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
                                    class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2"
                                    :class="{ 'is-invalid': createForm.errors.email }"
                                    placeholder="owner@example.com"
                                    required
                                >
                                <div v-if="createForm.errors.email" class="invalid-feedback fw-bold">
                                    {{ createForm.errors.email }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                    Contact Number (Optional)
                                </label>
                                <input
                                    id="phone"
                                    v-model="createForm.phone"
                                    type="text"
                                    class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2"
                                    :class="{ 'is-invalid': createForm.errors.phone }"
                                    placeholder="09123456789"
                                >
                                <div v-if="createForm.errors.phone" class="invalid-feedback fw-bold">
                                    {{ createForm.errors.phone }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                    Password
                                </label>
                                <input
                                    id="password"
                                    v-model="createForm.password"
                                    type="password"
                                    class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2"
                                    :class="{ 'is-invalid': createForm.errors.password }"
                                    placeholder="Minimum 8 characters"
                                    required
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
                                    class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2"
                                    :class="{ 'is-invalid': createForm.errors.password_confirmation }"
                                    placeholder="Repeat password"
                                    required
                                >
                                <div v-if="createForm.errors.password_confirmation" class="invalid-feedback fw-bold">
                                    {{ createForm.errors.password_confirmation }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-success w-100 py-2 shadow-sm rounded-pill fw-semibold"
                                :disabled="createForm.processing"
                            >
                                <span v-if="createForm.processing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                                <i v-else class="bi bi-person-check-fill me-1"></i>
                                Create Owner Account
                            </button>
                        </form>
                    </div>
                </section>

                <!-- RIGHT COLUMN: OWNERS TABLE & LIST -->
                <section class="col-lg-8" aria-label="Owner Accounts Data Table">
                    <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle rounded-4 h-100 d-flex flex-column bg-body">
                        
                        <!-- Table Header & Search -->
                        <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                            <div>
                                <h2 class="h5 text-body-emphasis fw-bold mb-1">
                                    Owner Directory
                                </h2>
                                <p class="text-body-secondary small mb-0">
                                    Manage credentials, statuses, and associated properties.
                                </p>
                            </div>

                            <!-- Debounced Search Bar -->
                            <div class="position-relative" style="min-width: 260px;">
                                <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-body-secondary">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control form-control-sm ps-5 pe-4 rounded-pill border-secondary-subtle bg-body shadow-sm"
                                    placeholder="Search owner, email, phone..."
                                    @input="handleSearch"
                                >
                                <button
                                    v-if="searchQuery"
                                    type="button"
                                    class="btn btn-sm btn-link position-absolute top-50 end-0 translate-middle-y text-secondary text-decoration-none pe-2"
                                    @click="clearSearch"
                                >
                                    <i class="bi bi-x-circle-fill"></i>
                                </button>
                            </div>
                        </div>

                        <div v-if="owners.data && owners.data.length" class="d-flex flex-column justify-content-between flex-grow-1 bg-body">
                            
                            <div class="table-responsive custom-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Owner</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Contact</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Assigned Listing</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody v-if="isLoading" class="border-top-0 placeholder-glow">
                                        <tr v-for="i in 5" :key="i">
                                            <td class="ps-4"><span class="placeholder col-8 py-2 rounded bg-secondary bg-opacity-25"></span></td>
                                            <td><span class="placeholder col-10 py-2 rounded bg-secondary bg-opacity-25"></span></td>
                                            <td><span class="placeholder col-8 py-2 rounded bg-secondary bg-opacity-25"></span></td>
                                            <td><span class="placeholder col-6 py-2 rounded-pill bg-secondary bg-opacity-25"></span></td>
                                            <td class="text-end pe-4"><span class="placeholder col-6 py-2 rounded-pill bg-secondary bg-opacity-25"></span></td>
                                        </tr>
                                    </tbody>

                                    <tbody v-else class="border-top-0">
                                        <tr v-for="owner in owners.data" :key="owner.id">
                                            
                                            <!-- Owner Name & ID -->
                                            <td class="text-nowrap ps-4 border-secondary-subtle">
                                                <div class="fw-bold text-body-emphasis">{{ owner.name }}</div>
                                                <div class="small text-body-secondary">Registered: {{ owner.created_at }}</div>
                                            </td>

                                            <!-- Contact Details -->
                                            <td class="text-nowrap border-secondary-subtle">
                                                <div class="text-body-emphasis fw-medium">{{ owner.email }}</div>
                                                <div class="small text-body-secondary">{{ owner.phone || 'No phone' }}</div>
                                            </td>

                                            <!-- Assigned Listing -->
                                            <td class="text-nowrap border-secondary-subtle" style="max-width: 220px;">
                                                <template v-if="owner.boarding_house">
                                                    <div class="fw-bold text-body-emphasis text-truncate">{{ owner.boarding_house.name }}</div>
                                                    <span class="badge badge-soft-primary rounded-pill small">{{ owner.boarding_house.status }}</span>
                                                </template>
                                                <span v-else class="small text-body-secondary fst-italic">No assigned listing</span>
                                            </td>

                                            <!-- Status -->
                                            <td class="text-nowrap border-secondary-subtle">
                                                <span class="badge shadow-sm rounded-pill px-3 py-1 text-capitalize" :class="statusBadgeClass(owner.status)">
                                                    {{ owner.status }}
                                                </span>
                                            </td>

                                            <!-- Action Controls -->
                                            <td class="border-secondary-subtle text-end pe-4">
                                                <div class="d-flex justify-content-end align-items-center gap-1">
                                                    
                                                    <!-- Edit Profile -->
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-2"
                                                        title="Edit Profile"
                                                        style="width: 32px; height: 32px;"
                                                        @click="openEditModal(owner)"
                                                    >
                                                        <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                                                    </button>

                                                    <!-- Reset Password -->
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-primary rounded-circle d-inline-flex align-items-center justify-content-center p-2"
                                                        title="Reset Password"
                                                        style="width: 32px; height: 32px;"
                                                        @click="openResetPasswordModal(owner)"
                                                    >
                                                        <i class="bi bi-key-fill" style="font-size: 0.8rem;"></i>
                                                    </button>

                                                    <!-- Toggle Status -->
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm rounded-circle d-inline-flex align-items-center justify-content-center p-2"
                                                        :class="owner.status === 'active' ? 'btn-outline-warning' : 'btn-outline-success'"
                                                        :title="owner.status === 'active' ? 'Deactivate Account' : 'Activate Account'"
                                                        style="width: 32px; height: 32px;"
                                                        :disabled="statusForm.processing"
                                                        @click="toggleOwnerStatus(owner)"
                                                    >
                                                        <i :class="owner.status === 'active' ? 'bi bi-pause-fill' : 'bi bi-play-fill'" style="font-size: 0.85rem;"></i>
                                                    </button>

                                                    <!-- Delete Owner -->
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-outline-danger rounded-circle d-inline-flex align-items-center justify-content-center p-2"
                                                        title="Delete Account"
                                                        style="width: 32px; height: 32px;"
                                                        @click="openDeleteModal(owner)"
                                                    >
                                                        <i class="bi bi-trash-fill" style="font-size: 0.75rem;"></i>
                                                    </button>
                                                </div>
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
                            <div class="mb-3">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-secondary opacity-50">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <line x1="17" y1="8" x2="23" y2="8"></line>
                                </svg>
                            </div>
                            <h3 class="h5 text-body-emphasis fw-bold mb-1">No owner accounts found</h3>
                            <p class="text-body-secondary mb-3 small">
                                {{ searchQuery ? 'No owner accounts matched your search criteria.' : 'Create an owner account using the form on the left.' }}
                            </p>
                            <button v-if="searchQuery" class="btn btn-sm btn-outline-secondary rounded-pill" @click="clearSearch">
                                Clear Search Filter
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- ✏️ EDIT OWNER MODAL -->
        <div id="editOwnerModal" class="modal fade" tabindex="-1" aria-labelledby="editOwnerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-bottom border-secondary-subtle p-4">
                        <h2 id="editOwnerModalLabel" class="modal-title h5 fw-bold text-body-emphasis d-flex align-items-center gap-2">
                            <i class="bi bi-pencil-square text-success"></i> Edit Owner Profile
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form @submit.prevent="submitEditOwner">
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label fw-bold small text-body-secondary text-uppercase">Full Name</label>
                                <input id="edit_name" v-model="editForm.name" type="text" class="form-control rounded-3" :class="{ 'is-invalid': editForm.errors.name }" required>
                                <div v-if="editForm.errors.name" class="invalid-feedback">{{ editForm.errors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_email" class="form-label fw-bold small text-body-secondary text-uppercase">Email Address</label>
                                <input id="edit_email" v-model="editForm.email" type="email" class="form-control rounded-3" :class="{ 'is-invalid': editForm.errors.email }" required>
                                <div v-if="editForm.errors.email" class="invalid-feedback">{{ editForm.errors.email }}</div>
                            </div>

                            <div class="mb-2">
                                <label for="edit_phone" class="form-label fw-bold small text-body-secondary text-uppercase">Contact Number</label>
                                <input id="edit_phone" v-model="editForm.phone" type="text" class="form-control rounded-3" :class="{ 'is-invalid': editForm.errors.phone }" placeholder="09123456789">
                                <div v-if="editForm.errors.phone" class="invalid-feedback">{{ editForm.errors.phone }}</div>
                            </div>
                        </div>

                        <div class="modal-footer border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold" :disabled="editForm.processing">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 🔑 RESET PASSWORD MODAL -->
        <div id="resetPasswordModal" class="modal fade" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-bottom border-secondary-subtle p-4">
                        <h2 id="resetPasswordModalLabel" class="modal-title h5 fw-bold text-body-emphasis d-flex align-items-center gap-2">
                            <i class="bi bi-key-fill text-primary"></i> Reset Owner Password
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <form @submit.prevent="submitResetPassword">
                        <div class="modal-body p-4">
                            <p class="text-body-secondary small mb-3">
                                Reset password for <strong>{{ selectedOwner?.name }}</strong> ({{ selectedOwner?.email }}).
                            </p>

                            <div class="mb-3">
                                <label for="reset_password" class="form-label fw-bold small text-body-secondary text-uppercase">New Password</label>
                                <div class="input-group">
                                    <input
                                        id="reset_password"
                                        v-model="passwordForm.password"
                                        type="text"
                                        class="form-control rounded-start-3 font-monospace"
                                        :class="{ 'is-invalid': passwordForm.errors.password }"
                                        placeholder="Enter password or auto-generate"
                                    >
                                    <button type="button" class="btn btn-outline-primary" @click="generateSecurePassword">
                                        <i class="bi bi-magic me-1"></i> Generate 10-Char
                                    </button>
                                </div>
                                <div v-if="passwordForm.errors.password" class="invalid-feedback d-block">{{ passwordForm.errors.password }}</div>
                                <div class="form-text small text-body-secondary mt-1">
                                    Leaving this blank will auto-generate a secure 10-character string upon saving.
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" :disabled="passwordForm.processing">
                                Confirm Password Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 🗑️ DELETE OWNER MODAL -->
        <div id="deleteOwnerModal" class="modal fade" tabindex="-1" aria-labelledby="deleteOwnerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <div class="modal-header border-bottom border-secondary-subtle p-4 bg-danger bg-opacity-10">
                        <h2 id="deleteOwnerModalLabel" class="modal-title h5 fw-bold text-danger d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i> Delete Owner Account
                        </h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <p class="text-body-emphasis mb-2">
                            Are you sure you want to delete owner <strong>{{ selectedOwner?.name }}</strong>?
                        </p>
                        <div v-if="selectedOwner?.boarding_house" class="alert alert-warning border-0 rounded-3 small">
                            <i class="bi bi-exclamation-circle-fill me-1"></i>
                            <strong>Safety Action:</strong> This owner is assigned to <strong>{{ selectedOwner?.boarding_house?.name }}</strong>. Deleting this account will unlink the property and mark it as deactivated to prevent orphaned active listings.
                        </div>
                        <p class="text-body-secondary small mb-0">
                            This action soft-deletes the owner credentials and logs the transaction.
                        </p>
                    </div>

                    <div class="modal-footer border-top border-secondary-subtle p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold" :disabled="deleteForm.processing" @click="submitDeleteOwner">
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-table-scroll {
    max-height: calc(100vh - 350px);
    min-height: 400px;
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
</style>