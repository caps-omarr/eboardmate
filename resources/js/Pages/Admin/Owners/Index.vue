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

    // 🚀 FIXED: Changed .post to .patch to match your Laravel routing!
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

        <div class="container-fluid py-2">
            <!-- FLASH SUCCESS MESSAGE -->
            <div
                v-if="flashSuccess"
                class="alert alert-success mb-4 border-0 shadow-sm"
            >
                {{ flashSuccess }}
            </div>

            <!-- HEADER SECTION -->
            <div class="mb-4">
                <span class="badge text-bg-dark mb-3 px-3 py-2">
                    Super Admin
                </span>

                <h1 class="text-body-emphasis fw-bold mb-2 transition-all">
                    Owner Account Management
                </h1>

                <p class="text-body-secondary mb-0 transition-all">
                    Create and manage boarding house owner accounts.
                </p>
            </div>

            <div class="row g-4">
                <!-- LEFT COLUMN: CREATE OWNER FORM -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all">
                        <h2 class="h5 text-body-emphasis fw-bold mb-4">
                            Create Owner Account
                        </h2>

                        <form @submit.prevent="submitOwner">
                            <div class="mb-3">
                                <label
                                    for="name"
                                    class="form-label text-body-emphasis fw-medium"
                                >
                                    Full Name
                                </label>

                                <input
                                    id="name"
                                    v-model="createForm.name"
                                    type="text"
                                    class="form-control border-secondary-subtle bg-body"
                                    :class="{ 'is-invalid': createForm.errors.name }"
                                    placeholder="Owner full name"
                                >

                                <div
                                    v-if="createForm.errors.name"
                                    class="invalid-feedback"
                                >
                                    {{ createForm.errors.name }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="email"
                                    class="form-label text-body-emphasis fw-medium"
                                >
                                    Email Address
                                </label>

                                <input
                                    id="email"
                                    v-model="createForm.email"
                                    type="email"
                                    class="form-control border-secondary-subtle bg-body"
                                    :class="{ 'is-invalid': createForm.errors.email }"
                                    placeholder="owner@example.com"
                                >

                                <div
                                    v-if="createForm.errors.email"
                                    class="invalid-feedback"
                                >
                                    {{ createForm.errors.email }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="phone"
                                    class="form-label text-body-emphasis fw-medium"
                                >
                                    Phone Number
                                </label>

                                <input
                                    id="phone"
                                    v-model="createForm.phone"
                                    type="text"
                                    class="form-control border-secondary-subtle bg-body"
                                    :class="{ 'is-invalid': createForm.errors.phone }"
                                    placeholder="09XXXXXXXXX"
                                >

                                <div
                                    v-if="createForm.errors.phone"
                                    class="invalid-feedback"
                                >
                                    {{ createForm.errors.phone }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="password"
                                    class="form-label text-body-emphasis fw-medium"
                                >
                                    Temporary Password
                                </label>

                                <input
                                    id="password"
                                    v-model="createForm.password"
                                    type="password"
                                    class="form-control border-secondary-subtle bg-body"
                                    :class="{ 'is-invalid': createForm.errors.password }"
                                    placeholder="Minimum 8 characters"
                                >

                                <div
                                    v-if="createForm.errors.password"
                                    class="invalid-feedback"
                                >
                                    {{ createForm.errors.password }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label
                                    for="password_confirmation"
                                    class="form-label text-body-emphasis fw-medium"
                                >
                                    Confirm Password
                                </label>

                                <input
                                    id="password_confirmation"
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    class="form-control border-secondary-subtle bg-body"
                                    placeholder="Repeat password"
                                >
                            </div>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary w-100 py-2 fw-semibold"
                                :disabled="createForm.processing"
                            >
                                <span v-if="createForm.processing">
                                    Creating...
                                </span>

                                <span v-else>
                                    Create Owner
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- RIGHT COLUMN: OWNERS TABLE & LIST -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4 bg-body-tertiary h-100 transition-all">
                        <div class="mb-4">
                            <h2 class="h5 text-body-emphasis fw-bold mb-1">
                                Owner Accounts
                            </h2>

                            <p class="text-body-secondary small mb-0">
                                Active owners can log in to the owner portal. Inactive owners are blocked from logging in.
                            </p>
                        </div>

                        <div
                            v-if="owners.data && owners.data.length"
                            class="d-flex flex-column justify-content-between h-100"
                        >
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-body-secondary text-uppercase small border-secondary-subtle">Owner</th>
                                            <th class="text-body-secondary text-uppercase small border-secondary-subtle">Contact</th>
                                            <th class="text-body-secondary text-uppercase small border-secondary-subtle">Assigned Listing</th>
                                            <th class="text-body-secondary text-uppercase small border-secondary-subtle">Status</th>
                                            <th class="text-body-secondary text-uppercase small border-secondary-subtle">Created</th>
                                            <th class="text-body-secondary text-uppercase small border-secondary-subtle text-end">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="owner in owners.data"
                                            :key="owner.id"
                                        >
                                            <td class="border-secondary-subtle">
                                                <div class="fw-semibold text-body-emphasis">
                                                    {{ owner.name }}
                                                </div>

                                                <div class="small text-body-secondary mt-1">
                                                    ID: {{ owner.id }}
                                                </div>
                                            </td>

                                            <td class="border-secondary-subtle">
                                                <div class="text-body-emphasis">
                                                    {{ owner.email }}
                                                </div>

                                                <div class="small text-body-secondary mt-1">
                                                    {{ owner.phone || 'No phone' }}
                                                </div>
                                            </td>

                                            <td class="border-secondary-subtle">
                                                <template v-if="owner.boarding_house">
                                                    <div class="fw-semibold text-body-emphasis">
                                                        {{ owner.boarding_house.name }}
                                                    </div>

                                                    <div class="small text-body-secondary mt-1">
                                                        {{ owner.boarding_house.status }}
                                                    </div>
                                                </template>

                                                <span
                                                    v-else
                                                    class="small text-body-secondary"
                                                >
                                                    No assigned listing
                                                </span>
                                            </td>

                                            <td class="border-secondary-subtle">
                                                <span
                                                    class="badge"
                                                    :class="statusBadgeClass(owner.status)"
                                                >
                                                    {{ owner.status }}
                                                </span>
                                            </td>

                                            <td class="small text-body-secondary border-secondary-subtle">
                                                {{ owner.created_at }}
                                            </td>

                                            <td class="border-secondary-subtle text-end">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm"
                                                    :class="owner.status === 'active' ? 'btn-outline-danger' : 'btn-outline-success'"
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
                            <nav v-if="owners.links && owners.links.length > 3" aria-label="Owner pagination" class="mt-4 border-top border-secondary-subtle pt-3">
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
                                            class="page-link border-secondary-subtle bg-body text-body" 
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
                        <div
                            v-else
                            class="d-flex flex-column align-items-center justify-content-center text-center p-5 h-100 rounded border border-secondary-subtle bg-body transition-all"
                        >
                            <div class="fs-1 mb-3">
                                👤
                            </div>

                            <h3 class="h5 text-body-emphasis fw-bold mb-2">
                                No owner accounts yet
                            </h3>

                            <p class="text-body-secondary mb-0">
                                Create an owner account to assign and manage boarding house listings.
                            </p>
                        </div>
                    </div>
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