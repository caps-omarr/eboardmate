<script setup>
import { Head, useForm, usePage, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Modal } from 'bootstrap';
import { computed, ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    owners: {
        type: Array,
        default: () => [],
    },
    boardingHouses: {
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

// --- SEARCH & DEBOUNCE ---
const searchQuery = ref(props.filters?.search || '');
let searchTimeout = null;

const handleSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/boarding-houses', { search: searchQuery.value }, {
            preserveState: true,
            replace: true,
        });
    }, 350);
};

const clearSearch = () => {
    searchQuery.value = '';
    router.get('/admin/boarding-houses', {}, {
        preserveState: true,
        replace: true,
    });
};

// --- CREATE FORM ---
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
    amenities_text: '',
    rules: '',
    allowed_genders: 'Any Gender (All)',
    includes_water: false,
    includes_electricity: false,
    water_billing_details: '',
    electricity_billing_details: '',
});

// --- EDIT FORM ---
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
    amenities_text: '',
    rules: '',
    allowed_genders: 'Any Gender (All)',
    includes_water: false,
    includes_electricity: false,
    water_billing_details: '',
    electricity_billing_details: '',
});

const actionForm = useForm({
    reason: '',
});

const deleteForm = useForm({
    confirm_name: '',
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
    editForm.amenities_text = Array.isArray(boardingHouse.amenities) ? boardingHouse.amenities.join(', ') : '';
    editForm.rules = boardingHouse.rules || '';
    editForm.allowed_genders = boardingHouse.allowed_genders || 'Any Gender (All)';
    editForm.includes_water = Boolean(boardingHouse.includes_water);
    editForm.includes_electricity = Boolean(boardingHouse.includes_electricity);
    editForm.water_billing_details = boardingHouse.water_billing_details || '';
    editForm.electricity_billing_details = boardingHouse.electricity_billing_details || '';

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
    if (!selectedEditListing.value) return;

    editForm.put(selectedEditListing.value.update_url, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
            selectedEditListing.value = null;
        },
    });
};

// --- TWO-STEP DELETE MODAL ---
const openDeleteModal = (boardingHouse) => {
    selectedListing.value = boardingHouse;
    deleteForm.reset();
    deleteForm.clearErrors();

    const modalElement = document.getElementById('deleteListingModal');
    if (modalElement) {
        Modal.getOrCreateInstance(modalElement).show();
    }
};

const submitDeleteListing = () => {
    if (!selectedListing.value) return;

    deleteForm.delete(selectedListing.value.delete_url, {
        preserveScroll: true,
        onSuccess: () => {
            const modalElement = document.getElementById('deleteListingModal');
            if (modalElement) {
                Modal.getOrCreateInstance(modalElement).hide();
            }
            selectedListing.value = null;
        },
    });
};

const statusBadgeClass = (status) => {
    if (status === 'approved') return 'badge-soft-success';
    if (status === 'pending') return 'badge-soft-warning';
    if (status === 'rejected') return 'badge-soft-danger';
    if (status === 'deactivated') return 'badge-soft-secondary';
    return 'badge-soft-secondary';
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
    if (actionType.value === 'approve' || actionType.value === 'reactivate') return 'bg-success text-white';
    if (actionType.value === 'reject') return 'bg-danger text-white';
    if (actionType.value === 'deactivate') return 'bg-warning text-dark';
    return 'bg-dark text-white';
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
    if (actionType.value === 'reject') return 'This will reject the listing. Provide a clear reason for the owner to fix issues.';
    if (actionType.value === 'deactivate') return 'This will deactivate the listing and remove it from public search and map visibility.';
    if (actionType.value === 'reactivate') return 'This will reactivate the listing and restore public visibility.';
    return '';
});

const submitAction = () => {
    if (!selectedListing.value || !actionType.value) return;

    let targetUrl = '';
    if (actionType.value === 'approve') targetUrl = selectedListing.value.approve_url;
    if (actionType.value === 'reject') targetUrl = selectedListing.value.reject_url;
    if (actionType.value === 'deactivate') targetUrl = selectedListing.value.deactivate_url;
    if (actionType.value === 'reactivate') targetUrl = selectedListing.value.reactivate_url;

    actionForm.patch(targetUrl, {
        preserveScroll: true,
        onSuccess: () => {
            closeActionModal();
            selectedListing.value = null;
            actionType.value = '';
        },
    });
};

const cleanLabel = (label) => {
    if (!label) return '';
    if (label.includes('&laquo;')) return '« Previous';
    if (label.includes('&raquo;')) return 'Next »';
    return label;
};

// Computed list for table rows
const houseList = computed(() => {
    if (Array.isArray(props.boardingHouses)) return props.boardingHouses;
    return props.boardingHouses?.data || [];
});

const houseLinks = computed(() => {
    return props.boardingHouses?.links || [];
});
</script>

<template>
    <AdminLayout>
        <Head title="Boarding Houses | E-BoardMate" />

        <div class="container-fluid max-w-desktop mx-auto pb-5 pt-2 px-3 px-md-4">
            
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
            <header class="mb-4">
                <span class="badge bg-body text-body border border-secondary-subtle mb-2 px-3 py-2 rounded-pill shadow-sm">
                    Super Admin Portal
                </span>
                <h1 class="text-body-emphasis fw-bold mb-1 tracking-tight">
                    Boarding House Property Management
                </h1>
                <p class="text-body-secondary mb-0">
                    Verify coordinates, inspect room allocations, manage listing lifecycles, and edit properties.
                </p>
            </header>

            <div class="row g-4">
                
                <!-- LEFT COLUMN: CREATE LISTING FORM -->
                <section class="col-lg-4" aria-label="Create Boarding House Form">
                    <div class="ebm-card border border-secondary-subtle shadow-sm p-4 bg-body-tertiary rounded-4 h-100">
                        <h2 class="h5 text-body-emphasis fw-bold mb-4 border-bottom border-secondary-subtle pb-2 d-flex align-items-center gap-2">
                            <i class="bi bi-house-add-fill text-success"></i> New Property Listing
                        </h2>

                        <form @submit.prevent="submitListing">
                            <div class="mb-3">
                                <label for="owner_id" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Assign Owner</label>
                                <select id="owner_id" v-model="listingForm.owner_id" class="form-select border-secondary-subtle bg-body shadow-sm rounded-3 py-2" :class="{ 'is-invalid': listingForm.errors.owner_id }">
                                    <option value="">No owner assigned</option>
                                    <option v-for="owner in owners" :key="owner.id" :value="owner.id">
                                        {{ owner.name }} - {{ owner.email }}
                                    </option>
                                </select>
                                <div v-if="listingForm.errors.owner_id" class="invalid-feedback">{{ listingForm.errors.owner_id }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Name</label>
                                <input id="name" v-model="listingForm.name" type="text" class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2" :class="{ 'is-invalid': listingForm.errors.name }" placeholder="e.g. Sunrise Boarding House" required>
                                <div v-if="listingForm.errors.name" class="invalid-feedback">{{ listingForm.errors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Address</label>
                                <input id="address" v-model="listingForm.address" type="text" class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2" :class="{ 'is-invalid': listingForm.errors.address }" placeholder="Talibon, Bohol">
                                <div v-if="listingForm.errors.address" class="invalid-feedback">{{ listingForm.errors.address }}</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="latitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Latitude</label>
                                    <input id="latitude" v-model="listingForm.latitude" type="number" step="0.0000001" min="-90" max="90" class="form-control border-secondary-subtle bg-body shadow-sm font-monospace rounded-3" :class="{ 'is-invalid': listingForm.errors.latitude }" placeholder="10.13605">
                                    <div v-if="listingForm.errors.latitude" class="invalid-feedback">{{ listingForm.errors.latitude }}</div>
                                </div>
                                <div class="col-6">
                                    <label for="longitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Longitude</label>
                                    <input id="longitude" v-model="listingForm.longitude" type="number" step="0.0000001" min="-180" max="180" class="form-control border-secondary-subtle bg-body shadow-sm font-monospace rounded-3" :class="{ 'is-invalid': listingForm.errors.longitude }" placeholder="124.32429">
                                    <div v-if="listingForm.errors.longitude" class="invalid-feedback">{{ listingForm.errors.longitude }}</div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="rent_price" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Monthly Rent (₱)</label>
                                <input id="rent_price" v-model="listingForm.rent_price" type="number" step="0.01" min="0" class="form-control border-secondary-subtle bg-body shadow-sm rounded-3 py-2 text-success fw-bold" :class="{ 'is-invalid': listingForm.errors.rent_price }" placeholder="2500" required>
                                <div v-if="listingForm.errors.rent_price" class="invalid-feedback">{{ listingForm.errors.rent_price }}</div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="total_rooms" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Total Rooms</label>
                                    <input id="total_rooms" v-model="listingForm.total_rooms" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm rounded-3" :class="{ 'is-invalid': listingForm.errors.total_rooms }">
                                </div>
                                <div class="col-6">
                                    <label for="available_rooms" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Avail Rooms</label>
                                    <input id="available_rooms" v-model="listingForm.available_rooms" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm rounded-3" :class="{ 'is-invalid': listingForm.errors.available_rooms }">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label for="total_bedspaces" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Total Beds</label>
                                    <input id="total_bedspaces" v-model="listingForm.total_bedspaces" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm rounded-3" :class="{ 'is-invalid': listingForm.errors.total_bedspaces }">
                                </div>
                                <div class="col-6">
                                    <label for="available_bedspaces" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Avail Beds</label>
                                    <input id="available_bedspaces" v-model="listingForm.available_bedspaces" type="number" min="0" class="form-control border-secondary-subtle bg-body shadow-sm rounded-3" :class="{ 'is-invalid': listingForm.errors.available_bedspaces }">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success w-100 mt-3 py-2 fw-bold shadow-sm rounded-pill" :disabled="listingForm.processing">
                                <span v-if="listingForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                <i v-else class="bi bi-plus-circle-fill me-1"></i>
                                Create Listing
                            </button>
                        </form>
                    </div>
                </section>

                <!-- RIGHT COLUMN: DATA TABLE -->
                <section class="col-lg-8" aria-label="Boarding Houses Data Table">
                    <div class="ebm-card p-0 overflow-hidden shadow-sm border border-secondary-subtle rounded-4 h-100 d-flex flex-column bg-body">
                        
                        <!-- Table Header & Search -->
                        <div class="p-4 border-bottom border-secondary-subtle bg-body-tertiary d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                            <div>
                                <h2 class="h5 text-body-emphasis fw-bold mb-1">
                                    Property Directory
                                </h2>
                                <p class="text-body-secondary small mb-0">
                                    Only verified listings with coordinates appear on the public locator map.
                                </p>
                            </div>

                            <!-- Search Filter -->
                            <div class="position-relative" style="min-width: 260px;">
                                <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-body-secondary">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control form-control-sm ps-5 pe-4 rounded-pill border-secondary-subtle bg-body shadow-sm"
                                    placeholder="Search house, owner, address..."
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

                        <!-- Data Table -->
                        <div v-if="houseList.length" class="d-flex flex-column justify-content-between flex-grow-1 bg-body">
                            <div class="table-responsive custom-table-scroll">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase ps-4">Property</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Owner</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Rent & Slots</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase">Status</th>
                                            <th scope="col" class="sticky-header text-nowrap bg-body-tertiary text-body-secondary fw-bold small text-uppercase text-end pe-4">Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody class="border-top-0">
                                        <tr v-for="boardingHouse in houseList" :key="boardingHouse.id">
                                            
                                            <!-- Name & Coordinates -->
                                            <td class="text-nowrap ps-4 border-secondary-subtle">
                                                <div class="fw-bold text-body-emphasis">{{ boardingHouse.name }}</div>
                                                <div class="small text-body-secondary mt-1">
                                                    <template v-if="boardingHouse.latitude && boardingHouse.longitude">
                                                        <span class="font-monospace"><i class="bi bi-geo-alt-fill text-primary"></i> {{ boardingHouse.latitude }}, {{ boardingHouse.longitude }}</span>
                                                    </template>
                                                    <span v-else class="text-danger fw-bold"><i class="bi bi-geo-alt-fill"></i> Missing Coords</span>
                                                </div>
                                                <div class="small text-body-secondary">{{ boardingHouse.address || 'No address' }}</div>
                                            </td>

                                            <!-- Owner -->
                                            <td class="text-nowrap border-secondary-subtle">
                                                <div class="fw-medium text-body-emphasis">{{ boardingHouse.owner_name }}</div>
                                                <div class="small text-body-secondary">{{ boardingHouse.owner_email || 'No email' }}</div>
                                            </td>

                                            <!-- Rent & Slots -->
                                            <td class="text-nowrap border-secondary-subtle">
                                                <div class="text-body-emphasis fw-bold mb-1 text-success">₱{{ formatPrice(boardingHouse.rent_price) }}/mo</div>
                                                <div class="small text-body-secondary">Rms: <span class="fw-medium text-body-emphasis">{{ boardingHouse.available_rooms }}/{{ boardingHouse.total_rooms }}</span> | Beds: <span class="fw-medium text-body-emphasis">{{ boardingHouse.available_bedspaces }}/{{ boardingHouse.total_bedspaces }}</span></div>
                                            </td>

                                            <!-- Status -->
                                            <td class="text-nowrap border-secondary-subtle">
                                                <span class="badge shadow-sm rounded-pill px-3 py-1 text-capitalize" :class="statusBadgeClass(boardingHouse.status)">
                                                    {{ boardingHouse.status }}
                                                </span>
                                                <div v-if="boardingHouse.is_verified" class="small text-success mt-1 fw-bold tracking-tight">
                                                    <i class="bi bi-check-circle-fill"></i> Verified
                                                </div>
                                                <div v-else class="small text-body-secondary mt-1">Not verified</div>
                                            </td>

                                            <!-- Actions -->
                                            <td class="border-secondary-subtle text-end pe-4">
                                                <div class="d-flex justify-content-end align-items-center gap-1">
                                                    
                                                    <!-- Edit Button -->
                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-2" title="Edit Listing Details" style="width: 32px; height: 32px;" @click="openEditModal(boardingHouse)">
                                                        <i class="bi bi-pencil-fill" style="font-size: 0.75rem;"></i>
                                                    </button>

                                                    <!-- Approve -->
                                                    <button v-if="boardingHouse.status === 'pending' || boardingHouse.status === 'rejected'" type="button" class="btn btn-sm btn-outline-success rounded-circle d-inline-flex align-items-center justify-content-center p-2" title="Approve & Verify" style="width: 32px; height: 32px;" @click="openActionModal(boardingHouse, 'approve')">
                                                        <i class="bi bi-check-lg" style="font-size: 0.9rem;"></i>
                                                    </button>

                                                    <!-- Reject -->
                                                    <button v-if="boardingHouse.status === 'pending' || boardingHouse.status === 'approved'" type="button" class="btn btn-sm btn-outline-warning rounded-circle d-inline-flex align-items-center justify-content-center p-2" title="Reject Listing" style="width: 32px; height: 32px;" @click="openActionModal(boardingHouse, 'reject')">
                                                        <i class="bi bi-x-lg" style="font-size: 0.8rem;"></i>
                                                    </button>

                                                    <!-- Deactivate -->
                                                    <button v-if="boardingHouse.status === 'approved'" type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center p-2" title="Deactivate Listing" style="width: 32px; height: 32px;" @click="openActionModal(boardingHouse, 'deactivate')">
                                                        <i class="bi bi-eye-slash-fill" style="font-size: 0.75rem;"></i>
                                                    </button>

                                                    <!-- Reactivate -->
                                                    <button v-if="boardingHouse.status === 'deactivated'" type="button" class="btn btn-sm btn-outline-success rounded-circle d-inline-flex align-items-center justify-content-center p-2" title="Reactivate Listing" style="width: 32px; height: 32px;" @click="openActionModal(boardingHouse, 'reactivate')">
                                                        <i class="bi bi-arrow-repeat" style="font-size: 0.85rem;"></i>
                                                    </button>

                                                    <!-- Two-Step Delete -->
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle d-inline-flex align-items-center justify-content-center p-2" title="Delete Listing Permanently" style="width: 32px; height: 32px;" @click="openDeleteModal(boardingHouse)">
                                                        <i class="bi bi-trash-fill" style="font-size: 0.75rem;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- PAGINATION -->
                            <nav v-if="houseLinks && houseLinks.length > 3" aria-label="Listing pagination" class="p-3 bg-body-tertiary border-top border-secondary-subtle">
                                <ul class="pagination justify-content-end mb-0">
                                    <li 
                                        v-for="(link, index) in houseLinks" 
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
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                </svg>
                            </div>
                            <h3 class="h5 text-body-emphasis fw-bold mb-1">No boarding houses found</h3>
                            <p class="text-body-secondary mb-3 small">
                                {{ searchQuery ? 'No listings matched your search criteria.' : 'Create a boarding house listing using the form on the left.' }}
                            </p>
                            <button v-if="searchQuery" class="btn btn-sm btn-outline-secondary rounded-pill" @click="clearSearch">
                                Clear Search Filter
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- ✏️ EDIT LISTING MODAL -->
        <div id="editListingModal" class="modal fade" tabindex="-1" aria-labelledby="editListingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow">
                    <form @submit.prevent="submitEditListing">
                        
                        <div class="modal-header border-bottom border-secondary-subtle bg-body-tertiary p-4">
                            <div>
                                <span class="badge badge-soft-primary mb-1 px-2 py-1 rounded-pill">Admin Listing Editor</span>
                                <h2 id="editListingModalLabel" class="modal-title h5 fw-bold text-body-emphasis mb-0">
                                    {{ selectedEditListing?.name }}
                                </h2>
                            </div>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="edit_owner_id" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Assign Owner</label>
                                    <select id="edit_owner_id" v-model="editForm.owner_id" class="form-select rounded-3 border-secondary-subtle" :class="{ 'is-invalid': editForm.errors.owner_id }">
                                        <option value="">No owner assigned</option>
                                        <option v-for="owner in owners" :key="owner.id" :value="owner.id">{{ owner.name }} - {{ owner.email }}</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="edit_name" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Property Name</label>
                                    <input id="edit_name" v-model="editForm.name" type="text" class="form-control rounded-3" :class="{ 'is-invalid': editForm.errors.name }" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="edit_address" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Address</label>
                                    <input id="edit_address" v-model="editForm.address" type="text" class="form-control rounded-3" :class="{ 'is-invalid': editForm.errors.address }">
                                </div>

                                <div class="col-md-4">
                                    <label for="edit_rent_price" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Monthly Rent (₱)</label>
                                    <input id="edit_rent_price" v-model="editForm.rent_price" type="number" step="0.01" min="0" class="form-control rounded-3 text-success fw-bold" :class="{ 'is-invalid': editForm.errors.rent_price }" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="edit_latitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                        <i class="bi bi-geo-alt-fill text-primary"></i> Latitude
                                    </label>
                                    <input id="edit_latitude" v-model="editForm.latitude" type="number" step="0.0000001" min="-90" max="90" class="form-control rounded-3 font-monospace" :class="{ 'is-invalid': editForm.errors.latitude }">
                                </div>

                                <div class="col-md-4">
                                    <label for="edit_longitude" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">
                                        <i class="bi bi-geo-alt-fill text-primary"></i> Longitude
                                    </label>
                                    <input id="edit_longitude" v-model="editForm.longitude" type="number" step="0.0000001" min="-180" max="180" class="form-control rounded-3 font-monospace" :class="{ 'is-invalid': editForm.errors.longitude }">
                                </div>

                                <div class="col-md-3">
                                    <label for="edit_total_rooms" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Total Rooms</label>
                                    <input id="edit_total_rooms" v-model="editForm.total_rooms" type="number" min="0" class="form-control rounded-3">
                                </div>

                                <div class="col-md-3">
                                    <label for="edit_available_rooms" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Available Rooms</label>
                                    <input id="edit_available_rooms" v-model="editForm.available_rooms" type="number" min="0" class="form-control rounded-3">
                                </div>

                                <div class="col-md-3">
                                    <label for="edit_total_bedspaces" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Total Beds</label>
                                    <input id="edit_total_bedspaces" v-model="editForm.total_bedspaces" type="number" min="0" class="form-control rounded-3">
                                </div>

                                <div class="col-md-3">
                                    <label for="edit_available_bedspaces" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Available Beds</label>
                                    <input id="edit_available_bedspaces" v-model="editForm.available_bedspaces" type="number" min="0" class="form-control rounded-3">
                                </div>

                                <div class="col-md-6">
                                    <label for="edit_allowed_genders" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Gender Accommodation</label>
                                    <select id="edit_allowed_genders" v-model="editForm.allowed_genders" class="form-select rounded-3">
                                        <option value="Any Gender (All)">Any Gender (All)</option>
                                        <option value="Male Only">Male Only</option>
                                        <option value="Female Only">Female Only</option>
                                        <option value="Separated by Room">Separated by Room</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="edit_amenities" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Amenities (Comma separated)</label>
                                    <input id="edit_amenities" v-model="editForm.amenities_text" type="text" class="form-control rounded-3" placeholder="WiFi, Study Area, Kitchen, CCTV">
                                </div>

                                <div class="col-md-12">
                                    <label for="edit_description" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Description</label>
                                    <textarea id="edit_description" v-model="editForm.description" class="form-control rounded-3" rows="3" placeholder="Listing description..." />
                                </div>

                                <div class="col-md-12">
                                    <label for="edit_location_description" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">Location Landmarks</label>
                                    <textarea id="edit_location_description" v-model="editForm.location_description" class="form-control rounded-3" rows="2" placeholder="Near TPC main gate, 5 mins walk..." />
                                </div>

                                <div class="col-md-12">
                                    <label for="edit_rules" class="form-label text-body-emphasis fw-medium small text-uppercase tracking-tight">House Rules & Curfew</label>
                                    <textarea id="edit_rules" v-model="editForm.rules" class="form-control rounded-3" rows="2" placeholder="10:00 PM Curfew, No smoking inside..." />
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" :disabled="editForm.processing">Cancel</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-semibold" :disabled="editForm.processing">
                                <span v-if="editForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ⚠️ ACTION (APPROVE / REJECT / DEACTIVATE) MODAL -->
        <div id="listingActionModal" class="modal fade" tabindex="-1" aria-labelledby="listingActionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
                    <form @submit.prevent="submitAction">
                        <div class="modal-header p-4" :class="actionHeaderClass">
                            <div>
                                <h2 id="listingActionModalLabel" class="modal-title h5 fw-bold mb-0">
                                    {{ actionTitle }}
                                </h2>
                                <div class="small opacity-75 mt-1">{{ selectedListing?.name }}</div>
                            </div>
                            <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <div class="modal-body p-4">
                            <div v-if="actionForm.errors.listing" class="alert alert-danger mb-3 border-0 shadow-sm">{{ actionForm.errors.listing }}</div>

                            <p class="text-body-emphasis mb-3">{{ actionMessage }}</p>

                            <div v-if="selectedListing && (!selectedListing.latitude || !selectedListing.longitude) && (actionType === 'approve' || actionType === 'reactivate')" class="alert alert-warning border-0 rounded-3 d-flex align-items-start gap-2 small">
                                <span>⚠️</span>
                                <div><strong>Missing Coordinates:</strong> Latitude and longitude must be provided via the Edit button before this listing can be activated on the map.</div>
                            </div>

                            <div v-if="actionNeedsReason" class="mb-2">
                                <label for="reason" class="form-label fw-bold small text-body-secondary text-uppercase">Reason <span class="text-danger">*</span></label>
                                <textarea id="reason" v-model="actionForm.reason" class="form-control rounded-3" :class="{ 'is-invalid': actionForm.errors.reason }" rows="3" placeholder="Provide a reason..." required />
                                <div v-if="actionForm.errors.reason" class="invalid-feedback fw-bold">{{ actionForm.errors.reason }}</div>
                            </div>
                        </div>

                        <div class="modal-footer border-top border-secondary-subtle p-3">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn rounded-pill px-4 fw-semibold" :class="actionButtonClass" :disabled="actionForm.processing">
                                Confirm Action
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 🗑️ TWO-STEP DELETE LISTING MODAL -->
        <div id="deleteListingModal" class="modal fade" tabindex="-1" aria-labelledby="deleteListingModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content rounded-4 border-0 shadow overflow-hidden">
                    <div class="modal-header border-bottom border-secondary-subtle p-4 bg-danger bg-opacity-10">
                        <h2 id="deleteListingModalLabel" class="modal-title h5 fw-bold text-danger d-flex align-items-center gap-2">
                            <i class="bi bi-exclamation-triangle-fill"></i> Permanent Listing Deletion
                        </h2>
                        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body p-4">
                        <p class="text-body-emphasis mb-2">
                            Are you sure you want to permanently delete <strong>{{ selectedListing?.name }}</strong>?
                        </p>
                        <div class="alert alert-danger border-0 rounded-3 small mb-3">
                            <i class="bi bi-shield-slash-fill me-1"></i>
                            <strong>Cascade Notice:</strong> Deleting this property will permanently remove its listing details, associated photos from disk, and related reservation histories.
                        </div>
                        <p class="text-body-secondary small mb-0">
                            This action cannot be undone. Public map caches will be purged immediately.
                        </p>
                    </div>

                    <div class="modal-footer border-top border-secondary-subtle p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold" :disabled="deleteForm.processing" @click="submitDeleteListing">
                            <span v-if="deleteForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                            Confirm Permanent Deletion
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
    min-height: 420px;
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

.badge-soft-warning {
    background-color: rgba(255, 193, 7, 0.2);
    color: #997404;
    border: 1px solid rgba(255, 193, 7, 0.35);
}

.badge-soft-danger {
    background-color: rgba(220, 53, 69, 0.15);
    color: #dc3545;
    border: 1px solid rgba(220, 53, 69, 0.25);
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