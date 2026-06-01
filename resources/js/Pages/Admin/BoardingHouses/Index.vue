<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Modal } from 'bootstrap';
import { computed, ref } from 'vue';

defineProps({
    owners: {
        type: Array,
        default: () => [],
    },
    boardingHouses: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success || null);

const listingForm = useForm({
    owner_id: '',
    name: '',
    description: '',
    location_description: '',
    address: '',
    latitude: '',
    longitude: '',
    rent_price: '',
    total_rooms: 0,
    available_rooms: 0,
    total_bedspaces: 0,
    available_bedspaces: 0,
});

const editForm = useForm({
    owner_id: '',
    name: '',
    description: '',
    location_description: '',
    address: '',
    latitude: '',
    longitude: '',
    rent_price: '',
    total_rooms: 0,
    available_rooms: 0,
    total_bedspaces: 0,
    available_bedspaces: 0,
});

const actionForm = useForm({
    reason: '',
});

const selectedListing = ref(null);
const selectedEditListing = ref(null);
const actionType = ref('');

const submitListing = () => {
    listingForm.post('/admin/boarding-houses', {
        preserveScroll: true,
        onSuccess: () => {
            listingForm.reset();
        },
    });
};

const openEditModal = (boardingHouse) => {
    selectedEditListing.value = boardingHouse;

    editForm.clearErrors();

    editForm.owner_id = boardingHouse.owner_id || '';
    editForm.name = boardingHouse.name || '';
    editForm.description = boardingHouse.description || '';
    editForm.location_description = boardingHouse.location_description || '';
    editForm.address = boardingHouse.address || '';
    editForm.latitude = boardingHouse.latitude || '';
    editForm.longitude = boardingHouse.longitude || '';
    editForm.rent_price = boardingHouse.rent_price || '';
    editForm.total_rooms = boardingHouse.total_rooms || 0;
    editForm.available_rooms = boardingHouse.available_rooms || 0;
    editForm.total_bedspaces = boardingHouse.total_bedspaces || 0;
    editForm.available_bedspaces = boardingHouse.available_bedspaces || 0;

    const modalElement = document.getElementById('editListingModal');

    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).show();
    }
};

const closeEditModal = () => {
    const modalElement = document.getElementById('editListingModal');

    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).hide();
    }
};

const submitEditListing = () => {
    if (!selectedEditListing.value) {
        return;
    }

    editForm.put(selectedEditListing.value.update_url, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            selectedEditListing.value = null;
        },
    });
};

const statusBadgeClass = (status) => {
    if (status === 'approved') {
        return 'text-bg-success';
    }

    if (status === 'pending') {
        return 'text-bg-warning';
    }

    if (status === 'rejected') {
        return 'text-bg-danger';
    }

    if (status === 'deactivated') {
        return 'text-bg-secondary';
    }

    return 'text-bg-secondary';
};

const formatPrice = (price) => {
    return Number(price || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
};

const openActionModal = (boardingHouse, type) => {
    selectedListing.value = boardingHouse;
    actionType.value = type;

    actionForm.reset();
    actionForm.clearErrors();

    const modalElement = document.getElementById('listingActionModal');

    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).show();
    }
};

const closeActionModal = () => {
    const modalElement = document.getElementById('listingActionModal');

    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).hide();
    }
};

const actionTitle = computed(() => {
    if (actionType.value === 'approve') {
        return 'Approve and Verify Listing';
    }

    if (actionType.value === 'reject') {
        return 'Reject Listing';
    }

    if (actionType.value === 'deactivate') {
        return 'Deactivate Listing';
    }

    if (actionType.value === 'reactivate') {
        return 'Reactivate Listing';
    }

    return 'Listing Action';
});

const actionBadgeClass = computed(() => {
    if (actionType.value === 'approve' || actionType.value === 'reactivate') {
        return 'text-bg-success';
    }

    if (actionType.value === 'reject') {
        return 'text-bg-danger';
    }

    if (actionType.value === 'deactivate') {
        return 'text-bg-secondary';
    }

    return 'text-bg-dark';
});

const actionButtonClass = computed(() => {
    if (actionType.value === 'approve' || actionType.value === 'reactivate') {
        return 'btn-success';
    }

    if (actionType.value === 'reject') {
        return 'btn-danger';
    }

    if (actionType.value === 'deactivate') {
        return 'btn-secondary';
    }

    return 'btn-ebm-primary';
});

const actionNeedsReason = computed(() => {
    return actionType.value === 'reject' || actionType.value === 'deactivate';
});

const actionMessage = computed(() => {
    if (actionType.value === 'approve') {
        return 'This will approve and verify the listing. It will become visible on the public map if it has valid coordinates.';
    }

    if (actionType.value === 'reject') {
        return 'This will reject the listing and hide it from the public map. A reason is required.';
    }

    if (actionType.value === 'deactivate') {
        return 'This will deactivate the listing and remove it from the public map. A reason is required.';
    }

    if (actionType.value === 'reactivate') {
        return 'This will reactivate and verify the listing again. It will appear on the public map if it has valid coordinates.';
    }

    return '';
});

const submitAction = () => {
    if (!selectedListing.value || !actionType.value) {
        return;
    }

    const targetUrl = selectedListing.value[`${actionType.value}_url`];

    actionForm.post(targetUrl, {
        preserveScroll: true,
        onSuccess: () => {
            closeActionModal();
            selectedListing.value = null;
            actionType.value = '';
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Boarding House Listings | E-BoardMate" />

        <div class="container">
            <div
                v-if="flashSuccess"
                class="alert alert-success mb-4"
            >
                {{ flashSuccess }}
            </div>

            <div
                v-if="actionForm.errors.listing"
                class="alert alert-danger mb-4"
            >
                {{ actionForm.errors.listing }}
            </div>

            <div class="mb-4">
                <span class="badge text-bg-dark mb-3">
                    Super Admin
                </span>

                <h1 class="fw-bold mb-2">
                    Boarding House Listing Management
                </h1>

                <p class="ebm-muted mb-0">
                    Create, edit, assign owners, verify, reject, deactivate, or reactivate boarding house listings.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="ebm-card p-4">
                        <h2 class="h5 fw-bold mb-3">
                            Create Boarding House
                        </h2>

                        <form @submit.prevent="submitListing">
                            <div class="mb-3">
                                <label
                                    for="owner_id"
                                    class="form-label"
                                >
                                    Assign Owner
                                </label>

                                <select
                                    id="owner_id"
                                    v-model="listingForm.owner_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': listingForm.errors.owner_id }"
                                >
                                    <option value="">
                                        No owner assigned yet
                                    </option>

                                    <option
                                        v-for="owner in owners"
                                        :key="owner.id"
                                        :value="owner.id"
                                    >
                                        {{ owner.name }} - {{ owner.email }}
                                    </option>
                                </select>

                                <div
                                    v-if="listingForm.errors.owner_id"
                                    class="invalid-feedback"
                                >
                                    {{ listingForm.errors.owner_id }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="name"
                                    class="form-label"
                                >
                                    Boarding House Name
                                </label>

                                <input
                                    id="name"
                                    v-model="listingForm.name"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': listingForm.errors.name }"
                                    placeholder="Example Boarding House"
                                >

                                <div
                                    v-if="listingForm.errors.name"
                                    class="invalid-feedback"
                                >
                                    {{ listingForm.errors.name }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="description"
                                    class="form-label"
                                >
                                    Description
                                </label>

                                <textarea
                                    id="description"
                                    v-model="listingForm.description"
                                    class="form-control"
                                    :class="{ 'is-invalid': listingForm.errors.description }"
                                    rows="3"
                                    placeholder="Short description"
                                />

                                <div
                                    v-if="listingForm.errors.description"
                                    class="invalid-feedback"
                                >
                                    {{ listingForm.errors.description }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="location_description"
                                    class="form-label"
                                >
                                    Location Description
                                </label>

                                <textarea
                                    id="location_description"
                                    v-model="listingForm.location_description"
                                    class="form-control"
                                    :class="{ 'is-invalid': listingForm.errors.location_description }"
                                    rows="2"
                                    placeholder="Near TPC, beside main road, etc."
                                />

                                <div
                                    v-if="listingForm.errors.location_description"
                                    class="invalid-feedback"
                                >
                                    {{ listingForm.errors.location_description }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="address"
                                    class="form-label"
                                >
                                    Address
                                </label>

                                <input
                                    id="address"
                                    v-model="listingForm.address"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': listingForm.errors.address }"
                                    placeholder="Talibon, Bohol"
                                >

                                <div
                                    v-if="listingForm.errors.address"
                                    class="invalid-feedback"
                                >
                                    {{ listingForm.errors.address }}
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label
                                        for="latitude"
                                        class="form-label"
                                    >
                                        Latitude
                                    </label>

                                    <input
                                        id="latitude"
                                        v-model="listingForm.latitude"
                                        type="number"
                                        step="0.0000001"
                                        class="form-control"
                                        :class="{ 'is-invalid': listingForm.errors.latitude }"
                                        placeholder="10.1167"
                                    >

                                    <div
                                        v-if="listingForm.errors.latitude"
                                        class="invalid-feedback"
                                    >
                                        {{ listingForm.errors.latitude }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="longitude"
                                        class="form-label"
                                    >
                                        Longitude
                                    </label>

                                    <input
                                        id="longitude"
                                        v-model="listingForm.longitude"
                                        type="number"
                                        step="0.0000001"
                                        class="form-control"
                                        :class="{ 'is-invalid': listingForm.errors.longitude }"
                                        placeholder="124.2833"
                                    >

                                    <div
                                        v-if="listingForm.errors.longitude"
                                        class="invalid-feedback"
                                    >
                                        {{ listingForm.errors.longitude }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label
                                    for="rent_price"
                                    class="form-label"
                                >
                                    Monthly Rent
                                </label>

                                <input
                                    id="rent_price"
                                    v-model="listingForm.rent_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': listingForm.errors.rent_price }"
                                    placeholder="2500"
                                >

                                <div
                                    v-if="listingForm.errors.rent_price"
                                    class="invalid-feedback"
                                >
                                    {{ listingForm.errors.rent_price }}
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label
                                        for="total_rooms"
                                        class="form-label"
                                    >
                                        Total Rooms
                                    </label>

                                    <input
                                        id="total_rooms"
                                        v-model="listingForm.total_rooms"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': listingForm.errors.total_rooms }"
                                    >

                                    <div
                                        v-if="listingForm.errors.total_rooms"
                                        class="invalid-feedback"
                                    >
                                        {{ listingForm.errors.total_rooms }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="available_rooms"
                                        class="form-label"
                                    >
                                        Available Rooms
                                    </label>

                                    <input
                                        id="available_rooms"
                                        v-model="listingForm.available_rooms"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': listingForm.errors.available_rooms }"
                                    >

                                    <div
                                        v-if="listingForm.errors.available_rooms"
                                        class="invalid-feedback"
                                    >
                                        {{ listingForm.errors.available_rooms }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="total_bedspaces"
                                        class="form-label"
                                    >
                                        Total Bedspaces
                                    </label>

                                    <input
                                        id="total_bedspaces"
                                        v-model="listingForm.total_bedspaces"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': listingForm.errors.total_bedspaces }"
                                    >

                                    <div
                                        v-if="listingForm.errors.total_bedspaces"
                                        class="invalid-feedback"
                                    >
                                        {{ listingForm.errors.total_bedspaces }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="available_bedspaces"
                                        class="form-label"
                                    >
                                        Available Bedspaces
                                    </label>

                                    <input
                                        id="available_bedspaces"
                                        v-model="listingForm.available_bedspaces"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': listingForm.errors.available_bedspaces }"
                                    >

                                    <div
                                        v-if="listingForm.errors.available_bedspaces"
                                        class="invalid-feedback"
                                    >
                                        {{ listingForm.errors.available_bedspaces }}
                                    </div>
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary w-100 mt-4"
                                :disabled="listingForm.processing"
                            >
                                <span v-if="listingForm.processing">
                                    Creating...
                                </span>

                                <span v-else>
                                    Create Listing
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="ebm-card p-4">
                        <div class="mb-3">
                            <h2 class="h5 fw-bold mb-1">
                                Boarding House Listings
                            </h2>

                            <p class="ebm-muted small mb-0">
                                Only approved and verified listings with coordinates appear on the public map.
                            </p>
                        </div>

                        <div
                            v-if="boardingHouses.length"
                            class="table-responsive"
                        >
                            <table class="table align-middle owner-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Owner</th>
                                        <th>Rent</th>
                                        <th>Slots</th>
                                        <th>Coordinates</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="boardingHouse in boardingHouses"
                                        :key="boardingHouse.id"
                                    >
                                        <td>
                                            <div class="fw-semibold">
                                                {{ boardingHouse.name }}
                                            </div>

                                            <div class="small ebm-muted">
                                                {{ boardingHouse.address || 'No address' }}
                                            </div>

                                            <div class="small ebm-muted">
                                                {{ boardingHouse.created_at }}
                                            </div>
                                        </td>

                                        <td>
                                            <div>
                                                {{ boardingHouse.owner_name }}
                                            </div>

                                            <div class="small ebm-muted">
                                                {{ boardingHouse.owner_email || 'No email' }}
                                            </div>
                                        </td>

                                        <td>
                                            ₱{{ formatPrice(boardingHouse.rent_price) }}
                                        </td>

                                        <td class="small">
                                            Rooms: {{ boardingHouse.available_rooms }} / {{ boardingHouse.total_rooms }}
                                            <br>
                                            Bedspaces: {{ boardingHouse.available_bedspaces }} / {{ boardingHouse.total_bedspaces }}
                                        </td>

                                        <td class="small">
                                            <template v-if="boardingHouse.latitude && boardingHouse.longitude">
                                                {{ boardingHouse.latitude }}
                                                <br>
                                                {{ boardingHouse.longitude }}
                                            </template>

                                            <span
                                                v-else
                                                class="text-danger"
                                            >
                                                Missing
                                            </span>
                                        </td>

                                        <td>
                                            <span
                                                class="badge"
                                                :class="statusBadgeClass(boardingHouse.status)"
                                            >
                                                {{ boardingHouse.status }}
                                            </span>

                                            <div
                                                v-if="boardingHouse.is_verified"
                                                class="small text-success mt-1"
                                            >
                                                Verified
                                            </div>

                                            <div
                                                v-else
                                                class="small ebm-muted mt-1"
                                            >
                                                Not verified
                                            </div>
                                        </td>

                                        <td class="small">
                                            <template v-if="boardingHouse.rejection_reason">
                                                <strong>Rejected:</strong>
                                                {{ boardingHouse.rejection_reason }}
                                            </template>

                                            <template v-else-if="boardingHouse.deactivated_reason">
                                                <strong>Deactivated:</strong>
                                                {{ boardingHouse.deactivated_reason }}
                                            </template>

                                            <span
                                                v-else
                                                class="ebm-muted"
                                            >
                                                None
                                            </span>
                                        </td>

                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-ebm-outline"
                                                    @click="openEditModal(boardingHouse)"
                                                >
                                                    Edit / Assign
                                                </button>

                                                <button
                                                    v-if="boardingHouse.status === 'pending' || boardingHouse.status === 'rejected'"
                                                    type="button"
                                                    class="btn btn-sm btn-success"
                                                    @click="openActionModal(boardingHouse, 'approve')"
                                                >
                                                    Approve
                                                </button>

                                                <button
                                                    v-if="boardingHouse.status === 'pending' || boardingHouse.status === 'approved'"
                                                    type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    @click="openActionModal(boardingHouse, 'reject')"
                                                >
                                                    Reject
                                                </button>

                                                <button
                                                    v-if="boardingHouse.status === 'approved'"
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    @click="openActionModal(boardingHouse, 'deactivate')"
                                                >
                                                    Deactivate
                                                </button>

                                                <button
                                                    v-if="boardingHouse.status === 'deactivated'"
                                                    type="button"
                                                    class="btn btn-sm btn-success"
                                                    @click="openActionModal(boardingHouse, 'reactivate')"
                                                >
                                                    Reactivate
                                                </button>

                                                <span
                                                    v-if="boardingHouse.status === 'deactivated'"
                                                    class="small ebm-muted"
                                                >
                                                    Hidden publicly
                                                </span>
                                            </div>
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
                                🏠
                            </div>

                            <h3 class="h5 fw-bold mb-2">
                                No boarding houses yet
                            </h3>

                            <p class="ebm-muted mb-0">
                                Create the first boarding house listing using the form.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            id="editListingModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="editListingModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-lg modal-dialog-scrollable reservation-dialog">
                <div class="modal-content reservation-modal-content">
                    <form @submit.prevent="submitEditListing">
                        <div class="modal-header">
                            <div>
                                <span class="badge text-bg-dark mb-2">
                                    Edit Listing
                                </span>

                                <h2
                                    id="editListingModalLabel"
                                    class="modal-title h5 fw-bold"
                                >
                                    {{ selectedEditListing?.name }}
                                </h2>
                            </div>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            />
                        </div>

                        <div class="modal-body">
                            <div class="alert alert-light border">
                                Update owner assignment, coordinates, rent, room count, and listing details. If the listing is already approved, changes will be saved immediately.
                            </div>

                            <div class="mb-3">
                                <label
                                    for="edit_owner_id"
                                    class="form-label"
                                >
                                    Assign Owner
                                </label>

                                <select
                                    id="edit_owner_id"
                                    v-model="editForm.owner_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': editForm.errors.owner_id }"
                                >
                                    <option value="">
                                        No owner assigned
                                    </option>

                                    <option
                                        v-for="owner in owners"
                                        :key="owner.id"
                                        :value="owner.id"
                                    >
                                        {{ owner.name }} - {{ owner.email }}
                                    </option>
                                </select>

                                <div
                                    v-if="editForm.errors.owner_id"
                                    class="invalid-feedback"
                                >
                                    {{ editForm.errors.owner_id }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="edit_name"
                                    class="form-label"
                                >
                                    Boarding House Name
                                </label>

                                <input
                                    id="edit_name"
                                    v-model="editForm.name"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': editForm.errors.name }"
                                >

                                <div
                                    v-if="editForm.errors.name"
                                    class="invalid-feedback"
                                >
                                    {{ editForm.errors.name }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="edit_description"
                                    class="form-label"
                                >
                                    Description
                                </label>

                                <textarea
                                    id="edit_description"
                                    v-model="editForm.description"
                                    class="form-control"
                                    :class="{ 'is-invalid': editForm.errors.description }"
                                    rows="3"
                                />

                                <div
                                    v-if="editForm.errors.description"
                                    class="invalid-feedback"
                                >
                                    {{ editForm.errors.description }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="edit_location_description"
                                    class="form-label"
                                >
                                    Location Description
                                </label>

                                <textarea
                                    id="edit_location_description"
                                    v-model="editForm.location_description"
                                    class="form-control"
                                    :class="{ 'is-invalid': editForm.errors.location_description }"
                                    rows="2"
                                />

                                <div
                                    v-if="editForm.errors.location_description"
                                    class="invalid-feedback"
                                >
                                    {{ editForm.errors.location_description }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="edit_address"
                                    class="form-label"
                                >
                                    Address
                                </label>

                                <input
                                    id="edit_address"
                                    v-model="editForm.address"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': editForm.errors.address }"
                                >

                                <div
                                    v-if="editForm.errors.address"
                                    class="invalid-feedback"
                                >
                                    {{ editForm.errors.address }}
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label
                                        for="edit_latitude"
                                        class="form-label"
                                    >
                                        Latitude
                                    </label>

                                    <input
                                        id="edit_latitude"
                                        v-model="editForm.latitude"
                                        type="number"
                                        step="0.0000001"
                                        class="form-control"
                                        :class="{ 'is-invalid': editForm.errors.latitude }"
                                    >

                                    <div
                                        v-if="editForm.errors.latitude"
                                        class="invalid-feedback"
                                    >
                                        {{ editForm.errors.latitude }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="edit_longitude"
                                        class="form-label"
                                    >
                                        Longitude
                                    </label>

                                    <input
                                        id="edit_longitude"
                                        v-model="editForm.longitude"
                                        type="number"
                                        step="0.0000001"
                                        class="form-control"
                                        :class="{ 'is-invalid': editForm.errors.longitude }"
                                    >

                                    <div
                                        v-if="editForm.errors.longitude"
                                        class="invalid-feedback"
                                    >
                                        {{ editForm.errors.longitude }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label
                                    for="edit_rent_price"
                                    class="form-label"
                                >
                                    Monthly Rent
                                </label>

                                <input
                                    id="edit_rent_price"
                                    v-model="editForm.rent_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': editForm.errors.rent_price }"
                                >

                                <div
                                    v-if="editForm.errors.rent_price"
                                    class="invalid-feedback"
                                >
                                    {{ editForm.errors.rent_price }}
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label
                                        for="edit_total_rooms"
                                        class="form-label"
                                    >
                                        Total Rooms
                                    </label>

                                    <input
                                        id="edit_total_rooms"
                                        v-model="editForm.total_rooms"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': editForm.errors.total_rooms }"
                                    >

                                    <div
                                        v-if="editForm.errors.total_rooms"
                                        class="invalid-feedback"
                                    >
                                        {{ editForm.errors.total_rooms }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="edit_available_rooms"
                                        class="form-label"
                                    >
                                        Available Rooms
                                    </label>

                                    <input
                                        id="edit_available_rooms"
                                        v-model="editForm.available_rooms"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': editForm.errors.available_rooms }"
                                    >

                                    <div
                                        v-if="editForm.errors.available_rooms"
                                        class="invalid-feedback"
                                    >
                                        {{ editForm.errors.available_rooms }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="edit_total_bedspaces"
                                        class="form-label"
                                    >
                                        Total Bedspaces
                                    </label>

                                    <input
                                        id="edit_total_bedspaces"
                                        v-model="editForm.total_bedspaces"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': editForm.errors.total_bedspaces }"
                                    >

                                    <div
                                        v-if="editForm.errors.total_bedspaces"
                                        class="invalid-feedback"
                                    >
                                        {{ editForm.errors.total_bedspaces }}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label
                                        for="edit_available_bedspaces"
                                        class="form-label"
                                    >
                                        Available Bedspaces
                                    </label>

                                    <input
                                        id="edit_available_bedspaces"
                                        v-model="editForm.available_bedspaces"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': editForm.errors.available_bedspaces }"
                                    >

                                    <div
                                        v-if="editForm.errors.available_bedspaces"
                                        class="invalid-feedback"
                                    >
                                        {{ editForm.errors.available_bedspaces }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                                :disabled="editForm.processing"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary"
                                :disabled="editForm.processing"
                            >
                                <span v-if="editForm.processing">
                                    Updating...
                                </span>

                                <span v-else>
                                    Save Changes
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div
            id="listingActionModal"
            class="modal fade"
            tabindex="-1"
            aria-labelledby="listingActionModalLabel"
            aria-hidden="true"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content reservation-result-modal-content">
                    <form @submit.prevent="submitAction">
                        <div class="modal-header">
                            <div>
                                <span
                                    class="badge mb-2"
                                    :class="actionBadgeClass"
                                >
                                    {{ actionTitle }}
                                </span>

                                <h2
                                    id="listingActionModalLabel"
                                    class="modal-title h5 fw-bold"
                                >
                                    {{ selectedListing?.name }}
                                </h2>
                            </div>

                            <button
                                type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close"
                            />
                        </div>

                        <div class="modal-body">
                            <div
                                v-if="actionForm.errors.listing"
                                class="alert alert-danger"
                            >
                                {{ actionForm.errors.listing }}
                            </div>

                            <p class="ebm-muted">
                                {{ actionMessage }}
                            </p>

                            <div
                                v-if="selectedListing && (!selectedListing.latitude || !selectedListing.longitude) && (actionType === 'approve' || actionType === 'reactivate')"
                                class="alert alert-warning"
                            >
                                This listing has missing coordinates. It cannot be approved or reactivated until latitude and longitude are provided.
                            </div>

                            <div
                                v-if="actionNeedsReason"
                                class="mb-3"
                            >
                                <label
                                    for="reason"
                                    class="form-label"
                                >
                                    Reason <span class="text-danger">*</span>
                                </label>

                                <textarea
                                    id="reason"
                                    v-model="actionForm.reason"
                                    class="form-control"
                                    :class="{ 'is-invalid': actionForm.errors.reason }"
                                    rows="4"
                                    placeholder="Write the reason here"
                                />

                                <div
                                    v-if="actionForm.errors.reason"
                                    class="invalid-feedback"
                                >
                                    {{ actionForm.errors.reason }}
                                </div>
                            </div>

                            <div class="alert alert-light border mb-0">
                                This action will be recorded in the activity logs for accountability.
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button
                                type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal"
                                :disabled="actionForm.processing"
                            >
                                Cancel
                            </button>

                            <button
                                type="submit"
                                class="btn"
                                :class="actionButtonClass"
                                :disabled="actionForm.processing"
                            >
                                <span v-if="actionForm.processing">
                                    Saving...
                                </span>

                                <span v-else>
                                    {{ actionTitle }}
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>