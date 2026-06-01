<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    boardingHouse: {
        type: Object,
        default: null,
    },
});

const page = usePage();

const flashSuccess = computed(() => page.props.flash?.success || null);
const photoInput = ref(null);

const form = useForm({
    description: props.boardingHouse?.description || '',
    location_description: props.boardingHouse?.location_description || '',
    address: props.boardingHouse?.address || '',
    rent_price: props.boardingHouse?.rent_price || 0,
    total_rooms: props.boardingHouse?.total_rooms || 0,
    available_rooms: props.boardingHouse?.available_rooms || 0,
    total_bedspaces: props.boardingHouse?.total_bedspaces || 0,
    available_bedspaces: props.boardingHouse?.available_bedspaces || 0,
    amenities_text: props.boardingHouse?.amenities?.join(', ') || '',
    rules: props.boardingHouse?.rules || '',
});

const photoForm = useForm({
    photo: null,
    alt_text: '',
});

const primaryForm = useForm({});
const deleteForm = useForm({});

const submitListing = () => {
    form.put('/owner/listing', {
        preserveScroll: true,
    });
};

const submitPhoto = () => {
    photoForm.post('/owner/listing/photos', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            photoForm.reset();

            if (photoInput.value) {
                photoInput.value.value = '';
            }
        },
    });
};

const setPhotoFile = (event) => {
    photoForm.photo = event.target.files[0] || null;
};

const setPrimaryPhoto = (photo) => {
    primaryForm.post(photo.set_primary_url, {
        preserveScroll: true,
    });
};

const deletePhoto = (photo) => {
    if (!confirm('Are you sure you want to delete this photo?')) {
        return;
    }

    deleteForm.delete(photo.delete_url, {
        preserveScroll: true,
    });
};

const statusBadgeClass = computed(() => {
    if (!props.boardingHouse) {
        return 'text-bg-secondary';
    }

    if (props.boardingHouse.status === 'approved') {
        return 'text-bg-success';
    }

    if (props.boardingHouse.status === 'pending') {
        return 'text-bg-warning';
    }

    if (props.boardingHouse.status === 'rejected') {
        return 'text-bg-danger';
    }

    if (props.boardingHouse.status === 'deactivated') {
        return 'text-bg-secondary';
    }

    return 'text-bg-secondary';
});
</script>

<template>
    <OwnerLayout>
        <Head title="My Boarding House Listing | E-BoardMate" />

        <div class="container">
            <div
                v-if="flashSuccess"
                class="alert alert-success mb-4"
            >
                {{ flashSuccess }}
            </div>

            <div class="mb-4">
                <span class="badge badge-soft-green mb-3">
                    Owner Listing
                </span>

                <h1 class="fw-bold mb-2">
                    My Boarding House Listing
                </h1>

                <p class="ebm-muted mb-0">
                    Update your boarding house details, availability, amenities, house rules, and photos.
                </p>
            </div>

            <div
                v-if="!boardingHouse"
                class="ebm-card p-4 p-md-5"
            >
                <h2 class="h4 fw-bold mb-2">
                    No assigned boarding house yet
                </h2>

                <p class="ebm-muted mb-0">
                    Your owner account does not have an assigned boarding house listing yet. Please contact the super admin.
                </p>
            </div>

            <div
                v-else
                class="row g-4"
            >
                <div class="col-lg-4">
                    <div class="ebm-card p-4 mb-4">
                        <h2 class="h5 fw-bold mb-3">
                            Listing Status
                        </h2>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span
                                class="badge"
                                :class="statusBadgeClass"
                            >
                                {{ boardingHouse.status }}
                            </span>

                            <span
                                v-if="boardingHouse.is_verified"
                                class="badge text-bg-success"
                            >
                                Verified
                            </span>

                            <span
                                v-else
                                class="badge text-bg-light border text-dark"
                            >
                                Not Verified
                            </span>
                        </div>

                        <h3 class="h5 fw-bold mb-2">
                            {{ boardingHouse.name }}
                        </h3>

                        <p class="small ebm-muted mb-3">
                            Coordinates are managed by the super admin for map accuracy.
                        </p>

                        <div class="summary-list">
                            <div class="summary-item">
                                <span>Latitude</span>
                                <strong>{{ boardingHouse.latitude || 'Missing' }}</strong>
                            </div>

                            <div class="summary-item">
                                <span>Longitude</span>
                                <strong>{{ boardingHouse.longitude || 'Missing' }}</strong>
                            </div>
                        </div>

                        <div
                            v-if="boardingHouse.rejection_reason"
                            class="alert alert-danger mt-4 mb-0"
                        >
                            <strong>Rejection Reason:</strong>
                            {{ boardingHouse.rejection_reason }}
                        </div>

                        <div
                            v-if="boardingHouse.deactivated_reason"
                            class="alert alert-secondary mt-4 mb-0"
                        >
                            <strong>Deactivation Reason:</strong>
                            {{ boardingHouse.deactivated_reason }}
                        </div>
                    </div>

                    <div class="ebm-card p-4">
                        <h2 class="h5 fw-bold mb-3">
                            Upload Photo
                        </h2>

                        <form @submit.prevent="submitPhoto">
                            <div class="mb-3">
                                <label
                                    for="photo"
                                    class="form-label"
                                >
                                    Photo
                                </label>

                                <input
                                    id="photo"
                                    ref="photoInput"
                                    type="file"
                                    class="form-control"
                                    :class="{ 'is-invalid': photoForm.errors.photo }"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    @change="setPhotoFile"
                                >

                                <div class="form-text">
                                    Allowed: JPG, PNG, WebP. Maximum file size: 4MB.
                                </div>

                                <div
                                    v-if="photoForm.errors.photo"
                                    class="invalid-feedback"
                                >
                                    {{ photoForm.errors.photo }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="alt_text"
                                    class="form-label"
                                >
                                    Alt Text
                                </label>

                                <input
                                    id="alt_text"
                                    v-model="photoForm.alt_text"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': photoForm.errors.alt_text }"
                                    placeholder="Example: Front view of boarding house"
                                >

                                <div
                                    v-if="photoForm.errors.alt_text"
                                    class="invalid-feedback"
                                >
                                    {{ photoForm.errors.alt_text }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary w-100"
                                :disabled="photoForm.processing"
                            >
                                <span v-if="photoForm.processing">
                                    Uploading...
                                </span>

                                <span v-else>
                                    Upload Photo
                                </span>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="ebm-card p-4 mb-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                            <div>
                                <h2 class="h5 fw-bold mb-1">
                                    Uploaded Photos
                                </h2>

                                <p class="ebm-muted small mb-0">
                                    The primary photo appears first on the public detail page.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="boardingHouse.photos && boardingHouse.photos.length"
                            class="owner-photo-grid"
                        >
                            <div
                                v-for="photo in boardingHouse.photos"
                                :key="photo.id"
                                class="owner-photo-card"
                            >
                                <img
                                    :src="photo.url"
                                    :alt="photo.alt_text || boardingHouse.name"
                                    class="owner-photo-image"
                                >

                                <div class="owner-photo-body">
                                    <div class="d-flex justify-content-between gap-2 mb-2">
                                        <div>
                                            <span
                                                v-if="photo.is_primary"
                                                class="badge text-bg-success"
                                            >
                                                Primary
                                            </span>

                                            <span
                                                v-else
                                                class="badge text-bg-light border text-dark"
                                            >
                                                Photo
                                            </span>
                                        </div>
                                    </div>

                                    <p class="small ebm-muted mb-3">
                                        {{ photo.alt_text || 'No alt text provided.' }}
                                    </p>

                                    <div class="d-flex flex-column gap-2">
                                        <button
                                            v-if="!photo.is_primary"
                                            type="button"
                                            class="btn btn-sm btn-ebm-outline"
                                            :disabled="primaryForm.processing"
                                            @click="setPrimaryPhoto(photo)"
                                        >
                                            Set as Primary
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            :disabled="deleteForm.processing"
                                            @click="deletePhoto(photo)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="empty-state"
                        >
                            <div class="empty-state-icon">
                                🖼️
                            </div>

                            <h3 class="h5 fw-bold mb-2">
                                No photos uploaded yet
                            </h3>

                            <p class="ebm-muted mb-0">
                                Upload at least one photo to make your public listing more useful to students.
                            </p>
                        </div>
                    </div>

                    <div class="ebm-card p-4">
                        <h2 class="h5 fw-bold mb-3">
                            Edit Listing Details
                        </h2>

                        <form @submit.prevent="submitListing">
                            <div class="mb-3">
                                <label
                                    for="description"
                                    class="form-label"
                                >
                                    Description
                                </label>

                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.description }"
                                    rows="4"
                                    placeholder="Describe your boarding house"
                                />

                                <div
                                    v-if="form.errors.description"
                                    class="invalid-feedback"
                                >
                                    {{ form.errors.description }}
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
                                    v-model="form.location_description"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.location_description }"
                                    rows="3"
                                    placeholder="Example: near TPC gate, beside main road, walking distance from school"
                                />

                                <div
                                    v-if="form.errors.location_description"
                                    class="invalid-feedback"
                                >
                                    {{ form.errors.location_description }}
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
                                    v-model="form.address"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.address }"
                                    placeholder="Talibon, Bohol"
                                >

                                <div
                                    v-if="form.errors.address"
                                    class="invalid-feedback"
                                >
                                    {{ form.errors.address }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="rent_price"
                                    class="form-label"
                                >
                                    Monthly Rent
                                </label>

                                <input
                                    id="rent_price"
                                    v-model="form.rent_price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.rent_price }"
                                >

                                <div
                                    v-if="form.errors.rent_price"
                                    class="invalid-feedback"
                                >
                                    {{ form.errors.rent_price }}
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
                                        v-model="form.total_rooms"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.total_rooms }"
                                    >

                                    <div
                                        v-if="form.errors.total_rooms"
                                        class="invalid-feedback"
                                    >
                                        {{ form.errors.total_rooms }}
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
                                        v-model="form.available_rooms"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.available_rooms }"
                                    >

                                    <div
                                        v-if="form.errors.available_rooms"
                                        class="invalid-feedback"
                                    >
                                        {{ form.errors.available_rooms }}
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
                                        v-model="form.total_bedspaces"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.total_bedspaces }"
                                    >

                                    <div
                                        v-if="form.errors.total_bedspaces"
                                        class="invalid-feedback"
                                    >
                                        {{ form.errors.total_bedspaces }}
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
                                        v-model="form.available_bedspaces"
                                        type="number"
                                        min="0"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.available_bedspaces }"
                                    >

                                    <div
                                        v-if="form.errors.available_bedspaces"
                                        class="invalid-feedback"
                                    >
                                        {{ form.errors.available_bedspaces }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label
                                    for="amenities_text"
                                    class="form-label"
                                >
                                    Amenities
                                </label>

                                <input
                                    id="amenities_text"
                                    v-model="form.amenities_text"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.amenities_text }"
                                    placeholder="WiFi, Water, Electricity, Study Area"
                                >

                                <div class="form-text">
                                    Separate amenities using commas.
                                </div>

                                <div
                                    v-if="form.errors.amenities_text"
                                    class="invalid-feedback"
                                >
                                    {{ form.errors.amenities_text }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label
                                    for="rules"
                                    class="form-label"
                                >
                                    House Rules
                                </label>

                                <textarea
                                    id="rules"
                                    v-model="form.rules"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.rules }"
                                    rows="4"
                                    placeholder="Curfew, visitor rules, cleanliness rules, etc."
                                />

                                <div
                                    v-if="form.errors.rules"
                                    class="invalid-feedback"
                                >
                                    {{ form.errors.rules }}
                                </div>
                            </div>

                            <button
                                type="submit"
                                class="btn btn-ebm-primary"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">
                                    Saving...
                                </span>

                                <span v-else>
                                    Save Changes
                                </span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </OwnerLayout>
</template>