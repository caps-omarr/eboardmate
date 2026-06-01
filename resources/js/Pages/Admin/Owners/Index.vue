<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

defineProps({
    owners: {
        type: Array,
        default: () => [],
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

    statusForm.post(owner.toggle_status_url, {
        preserveScroll: true,
    });
};

const statusBadgeClass = (status) => {
    return status === 'active'
        ? 'text-bg-success'
        : 'text-bg-secondary';
};
</script>

<template>
    <AdminLayout>
        <Head title="Owner Accounts | E-BoardMate" />

        <div class="container">
            <div
                v-if="flashSuccess"
                class="alert alert-success mb-4"
            >
                {{ flashSuccess }}
            </div>

            <div class="mb-4">
                <span class="badge text-bg-dark mb-3">
                    Super Admin
                </span>

                <h1 class="fw-bold mb-2">
                    Owner Account Management
                </h1>

                <p class="ebm-muted mb-0">
                    Create and manage boarding house owner accounts.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="ebm-card p-4">
                        <h2 class="h5 fw-bold mb-3">
                            Create Owner Account
                        </h2>

                        <form @submit.prevent="submitOwner">
                            <div class="mb-3">
                                <label
                                    for="name"
                                    class="form-label"
                                >
                                    Full Name
                                </label>

                                <input
                                    id="name"
                                    v-model="createForm.name"
                                    type="text"
                                    class="form-control"
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
                                    class="form-label"
                                >
                                    Email Address
                                </label>

                                <input
                                    id="email"
                                    v-model="createForm.email"
                                    type="email"
                                    class="form-control"
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
                                    class="form-label"
                                >
                                    Phone Number
                                </label>

                                <input
                                    id="phone"
                                    v-model="createForm.phone"
                                    type="text"
                                    class="form-control"
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
                                    class="form-label"
                                >
                                    Temporary Password
                                </label>

                                <input
                                    id="password"
                                    v-model="createForm.password"
                                    type="password"
                                    class="form-control"
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
                                    class="form-label"
                                >
                                    Confirm Password
                                </label>

                                <input
                                    id="password_confirmation"
                                    v-model="createForm.password_confirmation"
                                    type="password"
                                    class="form-control"
                                    placeholder="Repeat password"
                                >
                            </div>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary w-100"
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

                <div class="col-lg-8">
                    <div class="ebm-card p-4">
                        <div class="mb-3">
                            <h2 class="h5 fw-bold mb-1">
                                Owner Accounts
                            </h2>

                            <p class="ebm-muted small mb-0">
                                Active owners can log in to the owner portal. Inactive owners are blocked from logging in.
                            </p>
                        </div>

                        <div
                            v-if="owners.length"
                            class="table-responsive"
                        >
                            <table class="table align-middle owner-table">
                                <thead>
                                    <tr>
                                        <th>Owner</th>
                                        <th>Contact</th>
                                        <th>Assigned Listing</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="owner in owners"
                                        :key="owner.id"
                                    >
                                        <td>
                                            <div class="fw-semibold">
                                                {{ owner.name }}
                                            </div>

                                            <div class="small ebm-muted">
                                                ID: {{ owner.id }}
                                            </div>
                                        </td>

                                        <td>
                                            <div>
                                                {{ owner.email }}
                                            </div>

                                            <div class="small ebm-muted">
                                                {{ owner.phone || 'No phone' }}
                                            </div>
                                        </td>

                                        <td>
                                            <template v-if="owner.boarding_house">
                                                <div class="fw-semibold">
                                                    {{ owner.boarding_house.name }}
                                                </div>

                                                <div class="small ebm-muted">
                                                    {{ owner.boarding_house.status }}
                                                </div>
                                            </template>

                                            <span
                                                v-else
                                                class="small ebm-muted"
                                            >
                                                No assigned listing
                                            </span>
                                        </td>

                                        <td>
                                            <span
                                                class="badge"
                                                :class="statusBadgeClass(owner.status)"
                                            >
                                                {{ owner.status }}
                                            </span>
                                        </td>

                                        <td class="small ebm-muted">
                                            {{ owner.created_at }}
                                        </td>

                                        <td>
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

                        <div
                            v-else
                            class="empty-state"
                        >
                            <div class="empty-state-icon">
                                👤
                            </div>

                            <h3 class="h5 fw-bold mb-2">
                                No owner accounts yet
                            </h3>

                            <p class="ebm-muted mb-0">
                                Create an owner account to assign and manage boarding house listings.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>