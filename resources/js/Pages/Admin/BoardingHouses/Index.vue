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
    if (status === 'approved') return 'text-bg-success';
    if (status === 'pending') return 'text-bg-warning';
    if (status === 'rejected') return 'text-bg-danger';
    if (status === 'deactivated') return 'text-bg-secondary';
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
    if (actionType.value === 'approve') return 'Approve and Verify Listing';
    if (actionType.value === 'reject') return 'Reject Listing';
    if (actionType.value === 'deactivate') return 'Deactivate Listing';
    if (actionType.value === 'reactivate') return 'Reactivate Listing';
    return 'Listing Action';
});

const actionHeaderClass = computed(() => {
    if (actionType.value === 'approve' || actionType.value === 'reactivate') return 'bg-success';
    if (actionType.value === 'reject') return 'bg-danger';
    if (actionType.value === 'deactivate') return 'bg-warning text-dark';
    return 'bg-dark';
});

const actionButtonClass = computed(() => {
    if (actionType.value === 'approve' || actionType.value === 'reactivate') return 'btn-success';
    if (actionType.value === 'reject') return 'btn-danger';
    if (actionType.value === 'deactivate') return 'btn-warning';
    return 'btn-primary';
});

const actionNeedsReason = computed(() => {
    return actionType.value === 'reject' || actionType.value === 'deactivate';
});

const actionMessage = computed(() => {
    if (actionType.value === 'approve') return 'This will approve and verify the listing. It will become visible on the public map if it has valid coordinates.';
    if (actionType.value === 'reject') return 'This will reject the listing and hide it from the public map. A reason is required.';
    if (actionType.value === 'deactivate') return 'This will deactivate the listing and remove it from the public map. A reason is required.';
    if (actionType.value === 'reactivate') return 'This will reactivate and verify the listing again. It will appear on the public map if it has valid coordinates.';
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

        <div class="container pb-5 pt-2">
            
            <!-- ALERTS -->
            <div v-if="flashSuccess" class="alert alert-success mb-4 border-0 shadow-sm">
                {{ flashSuccess }}
            </div>

            <div v-if="actionForm.errors.listing" class="alert alert-danger mb-4 border-0 shadow-sm">
                {{ actionForm.errors.listing }}
            </div>

            <!-- HEADER SECTION -->
            <header class="mb-4">
                <span class="badge bg-body text-body border border-secondary-subtle mb-3 px-3 py-2 rounded-pill shadow-sm">
                    Super Admin
                </span>

                <h1 class="text-body-emphasis fw-bold mb-2 tracking-tight">
                    Boarding House Listings
                </h1>

                <p class="text-body-secondary mb-0 lead" style="font-size: 1.1rem;">
                    Create, edit, assign owners, verify, reject, deactivate, or reactivate boarding house listings.
                </p>
            </header>

            <div class="row g-4">
                
                <!-- LEFT COLUMN: CREATE FORM -->
                <section class="col-lg-4" aria-label="Create Boarding House Form">
                    <div class="ebm-card border border-secondary-subtle shadow-sm p-4 bg-body-tertiary h-100">
                        <h2 class="h5 text-body-emphasis fw-bold mb-4 border-bottom border-secondary-subtle pb-2">
                            Create Boarding House
                        </h2>

                        <form @submit.prevent="submitListing">
                            <div class="mb-3">
                                <label for="owner_id" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Assign Owner</label>
                                <select id="owner_id" v-model="listingForm.owner_id" class="form-select border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.owner_id }">
                                    <option value="">No owner assigned yet</option>
                                    <option v-for="owner in owners" :key="owner.id" :value="owner.id">
                                        {{ owner.name }} - {{ owner.email }}
                                    </option>
                                </select>
                                <div v-if="listingForm.errors.owner_id" class="invalid-feedback">{{ listingForm.errors.owner_id }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Name</label>
                                <input id="name" v-model="listingForm.name" type="text" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.name }" placeholder="Example Boarding House">
                                <div v-if="listingForm.errors.name" class="invalid-feedback">{{ listingForm.errors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Description</label>
                                <textarea id="description" v-model="listingForm.description" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.description }" rows="3" placeholder="Short description" />
                                <div v-if="listingForm.errors.description" class="invalid-feedback">{{ listingForm.errors.description }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="location_description" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Location Description</label>
                                <textarea id="location_description" v-model="listingForm.location_description" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.location_description }" rows="2" placeholder="Near TPC, beside main road, etc." />
                                <div v-if="listingForm.errors.location_description" class="invalid-feedback">{{ listingForm.errors.location_description }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Address</label>
                                <input id="address" v-model="listingForm.address" type="text" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.address }" placeholder="Talibon, Bohol">
                                <div v-if="listingForm.errors.address" class="invalid-feedback">{{ listingForm.errors.address }}</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="latitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Latitude</label>
                                    <input id="latitude" v-model="listingForm.latitude" type="number" step="0.0000001" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.latitude }" placeholder="10.1167">
                                    <div v-if="listingForm.errors.latitude" class="invalid-feedback">{{ listingForm.errors.latitude }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="longitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Longitude</label>
                                    <input id="longitude" v-model="listingForm.longitude" type="number" step="0.0000001" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.longitude }" placeholder="124.2833">
                                    <div v-if="listingForm.errors.longitude" class="invalid-feedback">{{ listingForm.errors.longitude }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="rent_price" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Monthly Rent (₱)</label>
                                <input id="rent_price" v-model="listingForm.rent_price" type="number" step="0.01" min="0" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.rent_price }" placeholder="2500">
                                <div v-if="listingForm.errors.rent_price" class="invalid-feedback">{{ listingForm.errors.rent_price }}</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="total_rooms" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Total Rooms</label>
                                    <input id="total_rooms" v-model="listingForm.total_rooms" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.total_rooms }">
                                </div>
                                <div class="col-6">
                                    <label for="available_rooms" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Available Rooms</label>
                                    <input id="available_rooms" v-model="listingForm.available_rooms" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.available_rooms }">
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6">
                                    <label for="total_bedspaces" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Total Beds</label>
                                    <input id="total_bedspaces" v-model="listingForm.total_bedspaces" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.total_bedspaces }">
                                </div>
                                <div class="col-6">
                                    <label for="available_bedspaces" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Available Beds</label>
                                    <input id="available_bedspaces" v-model="listingForm.available_bedspaces" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': listingForm.errors.available_bedspaces }">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mt-4 py-2 fw-bold shadow-sm" :disabled="listingForm.processing">
                                <span v-if="listingForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ listingForm.processing ? 'Creating...' : 'Create Listing' }}
                            </button>
                        </form>
                    </div>
                </section>

                <!-- RIGHT COLUMN: DATA TABLE -->
                <section class="col-lg-8" aria-label="Boarding Houses Data Table">
                    <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle h-100 d-flex flex-column">
                        <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary">
                            <h2 class="h5 text-body-emphasis fw-bold mb-1">Boarding House Listings</h2>
                            <p class="text-body-secondary small mb-0">Only approved and verified listings with coordinates appear on the public map.</p>
                        </div>

                        <!-- 🚀 UX FIX: The "Window Box" Table Scroll -->
                        <div v-if="boardingHouses.length" class="table-responsive custom-table-scroll flex-grow-1 bg-body">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <!-- 🚀 UX FIX: Sticky headers and text-nowrap -->
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Name</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Owner</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Rent & Slots</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Reason</th>
                                        <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">Action</th>
                                    </tr>
                                </thead>

                                <tbody class="border-top-0">
                                    <tr v-for="boardingHouse in boardingHouses" :key="boardingHouse.id">
                                        
                                        <!-- Name & Coordinates -->
                                        <td class="text-nowrap ps-4 border-secondary-subtle">
                                            <div class="fw-bold text-body-emphasis">{{ boardingHouse.name }}</div>
                                            <div class="small text-body-secondary mt-1">
                                                <template v-if="boardingHouse.latitude && boardingHouse.longitude">
                                                    <span class="font-monospace">{{ boardingHouse.latitude }}, {{ boardingHouse.longitude }}</span>
                                                </template>
                                                <span v-else class="text-danger fw-bold"><i class="bi bi-geo-alt-fill"></i> Missing Coords</span>
                                            </div>
                                            <div class="small text-body-secondary mt-1">{{ boardingHouse.created_at }}</div>
                                        </td>

                                        <!-- Owner -->
                                        <td class="text-nowrap border-secondary-subtle">
                                            <div class="fw-medium text-body-emphasis">{{ boardingHouse.owner_name }}</div>
                                            <div class="small text-body-secondary mt-1">{{ boardingHouse.owner_email || 'No email' }}</div>
                                        </td>

                                        <!-- Rent & Slots combined for space -->
                                        <td class="text-nowrap border-secondary-subtle">
                                            <div class="text-body-emphasis fw-bold mb-1">₱{{ formatPrice(boardingHouse.rent_price) }}</div>
                                            <div class="small text-body-secondary">Rms: <span class="fw-medium text-body-emphasis">{{ boardingHouse.available_rooms }}/{{ boardingHouse.total_rooms }}</span></div>
                                            <div class="small text-body-secondary">Beds: <span class="fw-medium text-body-emphasis">{{ boardingHouse.available_bedspaces }}/{{ boardingHouse.total_bedspaces }}</span></div>
                                        </td>

                                        <!-- Status -->
                                        <td class="text-nowrap border-secondary-subtle">
                                            <span class="badge shadow-sm" :class="statusBadgeClass(boardingHouse.status)">
                                                {{ boardingHouse.status }}
                                            </span>
                                            <div v-if="boardingHouse.is_verified" class="small text-success mt-1 fw-bold tracking-tight">
                                                <i class="bi bi-check-circle-fill"></i> Verified
                                            </div>
                                            <div v-else class="small text-body-secondary mt-1">Not verified</div>
                                        </td>

                                        <!-- Reason (Wrapped to prevent stretching) -->
                                        <td class="small border-secondary-subtle" style="min-width: 200px; max-width: 250px;">
                                            <template v-if="boardingHouse.rejection_reason">
                                                <strong class="text-danger">Rejected:</strong>
                                                <span class="text-body-secondary d-block mt-1">{{ boardingHouse.rejection_reason }}</span>
                                            </template>
                                            <template v-else-if="boardingHouse.deactivated_reason">
                                                <strong class="text-warning">Deactivated:</strong>
                                                <span class="text-body-secondary d-block mt-1">{{ boardingHouse.deactivated_reason }}</span>
                                            </template>
                                            <span v-else class="text-body-secondary fst-italic">None</span>
                                        </td>

                                        <!-- Actions -->
                                        <td class="border-secondary-subtle text-end pe-4">
                                            <div class="d-flex flex-column gap-2 justify-content-end align-items-end">
                                                <button type="button" class="btn btn-sm btn-secondary w-100 shadow-sm" @click="openEditModal(boardingHouse)">
                                                    Edit
                                                </button>

                                                <button v-if="boardingHouse.status === 'pending' || boardingHouse.status === 'rejected'" type="button" class="btn btn-sm btn-success w-100 shadow-sm" @click="openActionModal(boardingHouse, 'approve')">
                                                    Approve
                                                </button>

                                                <button v-if="boardingHouse.status === 'pending' || boardingHouse.status === 'approved'" type="button" class="btn btn-sm btn-outline-danger w-100 bg-body" @click="openActionModal(boardingHouse, 'reject')">
                                                    Reject
                                                </button>

                                                <button v-if="boardingHouse.status === 'approved'" type="button" class="btn btn-sm btn-outline-warning w-100 bg-body text-dark" @click="openActionModal(boardingHouse, 'deactivate')">
                                                    Deactivate
                                                </button>

                                                <button v-if="boardingHouse.status === 'deactivated'" type="button" class="btn btn-sm btn-success w-100 shadow-sm" @click="openActionModal(boardingHouse, 'reactivate')">
                                                    Reactivate
                                                </button>

                                                <span v-if="boardingHouse.status === 'deactivated'" class="small text-body-secondary mt-1">Hidden publicly</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="d-flex flex-column align-items-center justify-content-center text-center p-5 h-100 bg-body">
                            <div class="fs-1 mb-3 opacity-50">🏠</div>
                            <h3 class="h5 text-body-emphasis fw-bold mb-2">No boarding houses yet</h3>
                            <p class="text-body-secondary mb-0">Create the first boarding house listing using the form.</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- 🚀 UX FIX: PREMIUM EDIT MODAL -->
        <div id="editListingModal" class="modal fade" tabindex="-1" aria-labelledby="editListingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered border-0">
                <div class="modal-content bg-body shadow-lg border-0 overflow-hidden">
                    <form @submit.prevent="submitEditListing">
                        
                        <div class="modal-header border-secondary-subtle bg-body-tertiary pb-4">
                            <div>
                                <span class="badge text-bg-secondary mb-2 px-2 py-1 shadow-sm">Edit Listing</span>
                                <h2 id="editListingModalLabel" class="modal-title h4 fw-bold text-body-emphasis mb-0">
                                    {{ selectedEditListing?.name }}
                                </h2>
                            </div>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <div class="alert alert-secondary border-secondary-subtle mb-4 d-flex align-items-start gap-2 bg-body-tertiary">
                                <span><i class="bi bi-info-circle"></i></span>
                                <small>Update owner assignment, coordinates, rent, room count, and listing details. If the listing is already approved, changes will be saved immediately to the public map.</small>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-12">
                                    <label for="edit_owner_id" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Assign Owner</label>
                                    <select id="edit_owner_id" v-model="editForm.owner_id" class="form-select border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.owner_id }">
                                        <option value="">No owner assigned</option>
                                        <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }} - {{ owner.email }}</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="edit_name" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Boarding House Name</label>
                                    <input id="edit_name" v-model="editForm.name" type="text" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.name }">
                                </div>

                                <div class="col-md-6">
                                    <label for="edit_address" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Address</label>
                                    <input id="edit_address" v-model="editForm.address" type="text" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.address }">
                                </div>

                                <div class="col-md-12">
                                    <label for="edit_description" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Description</label>
                                    <textarea id="edit_description" v-model="editForm.description" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.description }" rows="3" />
                                </div>

                                <div class="col-md-12">
                                    <label for="edit_location_description" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Location Description</label>
                                    <textarea id="edit_location_description" v-model="editForm.location_description" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.location_description }" rows="2" />
                                </div>

                                <div class="col-md-4">
                                    <label for="edit_rent_price" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Monthly Rent</label>
                                    <input id="edit_rent_price" v-model="editForm.rent_price" type="number" step="0.01" min="0" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.rent_price }">
                                </div>

                                <div class="col-md-4">
                                    <label for="edit_latitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Latitude</label>
                                    <input id="edit_latitude" v-model="editForm.latitude" type="number" step="0.0000001" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.latitude }">
                                </div>

                                <div class="col-md-4">
                                    <label for="edit_longitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Longitude</label>
                                    <input id="edit_longitude" v-model="editForm.longitude" type="number" step="0.0000001" class="form-control border-secondary-subtle bg-body shadow-sm" :class="{ 'is-invalid': editForm.errors.longitude }">
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-secondary-subtle bg-body-tertiary">
                            <button type="button" class="btn btn-outline-secondary fw-medium" data-bs-dismiss="modal" :disabled="editForm.processing">Cancel</button>
                            <button type="submit" class="btn btn-success fw-bold shadow-sm px-4" :disabled="editForm.processing">
                                <span v-if="editForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editForm.processing ? 'Updating...' : 'Save Changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 🚀 UX FIX: PREMIUM ACTION (APPROVE/REJECT) MODAL -->
        <div id="listingActionModal" class="modal fade" tabindex="-1" aria-labelledby="listingActionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered border-0">
                <div class="modal-content bg-body shadow-lg border-0 overflow-hidden">
                    <form @submit.prevent="submitAction">
                        
                        <div class="modal-header border-bottom-0 text-white" :class="actionHeaderClass">
                            <div>
                                <h2 id="listingActionModalLabel" class="modal-title h5 fw-bold mb-0">
                                    {{ actionTitle }}
                                </h2>
                                <div class="small opacity-75 mt-1">{{ selectedListing?.name }}</div>
                            </div>
                            <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close" :class="{'btn-close-dark': actionType === 'deactivate'}"></button>
                        </div>

                        <div class="modal-body p-4">
                            <div v-if="actionForm.errors.listing" class="alert alert-danger mb-3 border-0 shadow-sm">{{ actionForm.errors.listing }}</div>

                            <p class="text-body-emphasis lead fs-6 mb-4">{{ actionMessage }}</p>

                            <div v-if="selectedListing && (!selectedListing.latitude || !selectedListing.longitude) && (actionType === 'approve' || actionType === 'reactivate')" class="alert alert-warning border-warning-subtle shadow-sm d-flex align-items-start gap-2">
                                <span>⚠️</span>
                                <small class="text-dark fw-medium">This listing has missing coordinates. It cannot be approved or reactivated until latitude and longitude are provided via the Edit menu.</small>
                            </div>

                            <div v-if="actionNeedsReason" class="mb-2">
                                <label for="reason" class="form-label text-body-emphasis fw-bold">Reason <span class="text-danger">*</span></label>
                                <textarea id="reason" v-model="actionForm.reason" class="form-control border-secondary-subtle bg-body-tertiary focus-ring focus-ring-danger shadow-sm" :class="{ 'is-invalid': actionForm.errors.reason }" rows="4" placeholder="Write the reason here..." />
                                <div v-if="actionForm.errors.reason" class="invalid-feedback fw-bold">{{ actionForm.errors.reason }}</div>
                            </div>
                        </div>

                        <div class="modal-footer border-secondary-subtle bg-body-tertiary">
                            <button type="button" class="btn btn-outline-secondary fw-medium" data-bs-dismiss="modal" :disabled="actionForm.processing">Cancel</button>
                            <button type="submit" class="btn fw-bold shadow-sm px-4" :class="actionButtonClass" :disabled="actionForm.processing">
                                <span v-if="actionForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ actionForm.processing ? 'Saving...' : 'Confirm Action' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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

/* Typography refinements */
.tracking-tight {
    letter-spacing: -0.02em;
}

/* Ensure the dark theme close button looks correct on warning backgrounds */
.btn-close-dark {
    filter: invert(1) grayscale(100%) brightness(200%);
}
</style>