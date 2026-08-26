<script setup>
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import OwnerLayout from "@/Layouts/OwnerLayout.vue";
import ThemeToggle from "@/Components/ThemeToggle.vue";
import { computed, ref } from "vue";

const page = usePage();
const user = computed(() => page.props.auth?.user || page.props.owner || {});
const flashSuccess = computed(() => page.props.flash?.success || null);

const activePolicyModal = ref(null);
const showLogoutModal = ref(false);

// 🚀 PROFILE UPDATE FORM (Handles Photo, Name, Email)
const profilePhotoInput = ref(null);
const profilePhotoPreview = ref(null);

const profileForm = useForm({
    _method: "PUT", // Used for file uploads on PUT/PATCH routes in Laravel
    name: user.value.name || "",
    email: user.value.email || "",
    photo: null,
});

const timestamp = ref(Date.now());

const avatarUrl = computed(() => {
    const rawUrl = user.value.avatar_url || user.value.profile_photo_url || (user.value.avatar && user.value.avatar.startsWith('http') ? user.value.avatar : null);
    if (!rawUrl) return null;
    return `${rawUrl}?t=${timestamp.value}`;
});

const updateProfile = () => {
    profileForm.post("/owner/settings/profile", {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            profilePhotoPreview.value = null;
            profileForm.photo = null;
            timestamp.value = Date.now();
            if (profilePhotoInput.value) profilePhotoInput.value.value = "";
        },
    });
};

const isCompressing = ref(false);

const compressAvatarImage = (file, maxWidth = 800, maxHeight = 800, quality = 0.85) => {
    return new Promise((resolve) => {
        if (!file || file.size <= 1.5 * 1024 * 1024) {
            resolve(file);
            return;
        }

        const img = new Image();
        const url = URL.createObjectURL(file);
        img.src = url;

        img.onload = () => {
            URL.revokeObjectURL(url);
            let width = img.width;
            let height = img.height;

            if (width > maxWidth || height > maxHeight) {
                if (width > height) {
                    height = Math.round((height * maxWidth) / width);
                    width = maxWidth;
                } else {
                    width = Math.round((width * maxHeight) / height);
                    height = maxHeight;
                }
            }

            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob((blob) => {
                if (blob) {
                    const compressedFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
                        type: "image/jpeg",
                        lastModified: Date.now(),
                    });
                    resolve(compressedFile);
                } else {
                    resolve(file);
                }
            }, "image/jpeg", quality);
        };

        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(file);
        };
    });
};

const setProfilePhoto = async (event) => {
    const file = event.target.files[0] || null;
    profileForm.clearErrors('photo');

    if (file) {
        isCompressing.value = true;
        try {
            const processedFile = await compressAvatarImage(file);
            if (processedFile.size > 15 * 1024 * 1024) {
                profileForm.setError('photo', 'Avatar photo is too large. Please choose an image under 15MB.');
                profileForm.photo = null;
                profilePhotoPreview.value = null;
                if (event.target) event.target.value = '';
                return;
            }
            profileForm.photo = processedFile;
            profilePhotoPreview.value = URL.createObjectURL(processedFile);
        } catch (e) {
            profileForm.photo = file;
            profilePhotoPreview.value = URL.createObjectURL(file);
        } finally {
            isCompressing.value = false;
        }
    } else {
        profileForm.photo = null;
        profilePhotoPreview.value = null;
    }
};

const triggerPhotoSelect = () => {
    if (profilePhotoInput.value) {
        profilePhotoInput.value.click();
    }
};

// 🚀 PASSWORD UPDATE FORM
const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const updatePassword = () => {
    passwordForm.put("/owner/settings/password", {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
};

// 🚀 LOGOUT LOGIC (Matches OwnerLayout)
const logoutForm = useForm({});
const logout = () => {
    logoutForm.post("/owner/logout");
};
const confirmLogout = () => {
    showLogoutModal.value = false;
    logout();
};

// 🚀 AVATAR GENERATOR (Bulletproof)
const getInitials = (name) => {
    if (!name) return "??";
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2 && parts[0] && parts[1]) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return parts[0].substring(0, 2).toUpperCase();
};
</script>

<template>
    <OwnerLayout>
        <Head title="Settings & Profile | E-BoardMate" />

        <div class="container-fluid pb-5 px-0 px-md-3 max-w-desktop mx-auto">
            <!-- ALERTS -->
            <div
                v-if="flashSuccess"
                class="alert alert-success mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4 d-flex align-items-center gap-2"
            >
                <i class="bi bi-check-circle-fill"></i> {{ flashSuccess }}
            </div>
            <div
                v-if="profileForm.recentlySuccessful"
                class="alert alert-success mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4 d-flex align-items-center gap-2"
            >
                <i class="bi bi-check-circle-fill"></i> Profile updated
                successfully.
            </div>
            <div
                v-if="passwordForm.recentlySuccessful"
                class="alert alert-success mx-3 mx-md-0 mb-4 shadow-sm border-0 rounded-4 d-flex align-items-center gap-2"
            >
                <i class="bi bi-check-circle-fill"></i> Password updated
                successfully.
            </div>

            <!-- NATIVE HEADER SECTION -->
            <header
                class="d-flex justify-content-between align-items-center px-3 px-md-0 mb-4 pt-3"
            >
                <div>
                    <h1
                        class="fw-bold mb-0 text-body-emphasis"
                        style="font-size: 1.75rem"
                    >
                        Settings
                    </h1>
                    <span class="small text-body-secondary"
                        >Manage your account & app preferences</span
                    >
                </div>
            </header>

            <div class="row g-4 px-3 px-md-0 m-0 w-100">
                <!-- LEFT COLUMN: APP SETTINGS (Links, Theme, Logout) -->
                <div class="col-lg-5 p-0 pe-lg-3 order-2 order-lg-1">
                    <h2
                        class="h6 fw-bold text-body-secondary text-uppercase ms-2 mb-2 tracking-tight"
                    >
                        App Preferences
                    </h2>

                    <!-- Native Settings List Group -->
                    <div
                        class="bg-body rounded-4 shadow-sm border border-secondary-subtle mb-4 overflow-hidden"
                    >
                        <div
                            class="list-group list-group-flush native-list-group"
                        >
                            <!-- Theme Toggle Row -->
                            <div
                                class="list-group-item bg-transparent d-flex justify-content-between align-items-center p-3 py-3 border-secondary-subtle"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="settings-icon-box bg-body-tertiary text-body-emphasis rounded-3 d-flex align-items-center justify-content-center"
                                    >
                                        <i
                                            class="bi bi-moon-stars-fill fs-5"
                                        ></i>
                                    </div>
                                    <span class="fw-bold text-body-emphasis"
                                        >Appearance</span
                                    >
                                </div>
                                <ThemeToggle />
                            </div>

                            <!-- Install App Link -->
                            <Link
                                href="/owner/install"
                                class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center p-3 border-secondary-subtle"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="settings-icon-box bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center"
                                    >
                                        <i
                                            class="bi bi-phone-vibrate-fill fs-5"
                                        ></i>
                                    </div>
                                    <span class="fw-bold text-body-emphasis"
                                        >Install Landlord App</span
                                    >
                                </div>
                                <i
                                    class="bi bi-chevron-right text-secondary small"
                                ></i>
                            </Link>

                            <!-- About App Link -->
                            <button
                                type="button"
                                @click="activePolicyModal = 'about'"
                                class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center p-3 border-secondary-subtle"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="settings-icon-box bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center"
                                    >
                                        <i
                                            class="bi bi-info-circle-fill fs-5"
                                        ></i>
                                    </div>
                                    <span class="fw-bold text-body-emphasis"
                                        >About E-BoardMate</span
                                    >
                                </div>
                                <i
                                    class="bi bi-chevron-right text-secondary small"
                                ></i>
                            </button>

                            <!-- Platform Policy Link -->
                            <button
                                type="button"
                                @click="activePolicyModal = 'policy'"
                                class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center p-3 border-secondary-subtle"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="settings-icon-box bg-info bg-opacity-10 text-info rounded-3 d-flex align-items-center justify-content-center"
                                    >
                                        <i
                                            class="bi bi-file-earmark-text-fill fs-5"
                                        ></i>
                                    </div>
                                    <span class="fw-bold text-body-emphasis"
                                        >Platform Policy</span
                                    >
                                </div>
                                <i
                                    class="bi bi-chevron-right text-secondary small"
                                ></i>
                            </button>

                            <!-- Data Privacy Link -->
                            <button
                                type="button"
                                @click="activePolicyModal = 'privacy'"
                                class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center p-3 border-secondary-subtle"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="settings-icon-box bg-success bg-opacity-10 text-success rounded-3 d-flex align-items-center justify-content-center"
                                    >
                                        <i
                                            class="bi bi-shield-lock-fill fs-5"
                                        ></i>
                                    </div>
                                    <span class="fw-bold text-body-emphasis"
                                        >Data Privacy Policy</span
                                    >
                                </div>
                                <i
                                    class="bi bi-chevron-right text-secondary small"
                                ></i>
                            </button>

                            <!-- Logout Button -->
                            <button
                                @click="showLogoutModal = true"
                                class="list-group-item list-group-item-action bg-transparent d-flex justify-content-between align-items-center p-3 border-0"
                            >
                                <div class="d-flex align-items-center gap-3">
                                    <div
                                        class="settings-icon-box bg-danger bg-opacity-10 text-danger rounded-3 d-flex align-items-center justify-content-center"
                                    >
                                        <i
                                            class="bi bi-box-arrow-right fs-5"
                                        ></i>
                                    </div>
                                    <span class="fw-bold text-danger"
                                        >Log Out</span
                                    >
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: PROFILE & PASSWORD FORMS -->
                <div class="col-lg-7 p-0 ps-lg-3 order-1 order-lg-2">
                    <h2
                        class="h6 fw-bold text-body-secondary text-uppercase ms-2 mb-2 tracking-tight"
                    >
                        Account Details
                    </h2>

                    <!-- 🚀 Profile Information Form -->
                    <div
                        class="bg-body p-4 p-md-5 rounded-4 shadow-sm border border-secondary-subtle mb-4"
                    >
                        <h3 class="h5 fw-bold mb-1">Profile Information</h3>
                        <p class="text-body-secondary small mb-4">
                            Update your account's profile information and email
                            address.
                        </p>

                        <form @submit.prevent="updateProfile">
                            <!-- Profile Photo Upload -->
                            <div
                                class="d-flex align-items-center gap-4 mb-4 pb-3 border-bottom border-secondary-subtle"
                            >
                                <div class="position-relative">
                                    <!-- Dynamic Avatar Preview -->
                                    <div
                                        class="owner-avatar-lg shadow-sm border border-2 border-secondary-subtle bg-body-tertiary text-body-secondary fw-bold d-flex align-items-center justify-content-center flex-shrink-0"
                                    >
                                        <img
                                            v-if="profilePhotoPreview"
                                            :src="profilePhotoPreview"
                                            alt="New Preview"
                                            class="w-100 h-100 rounded-circle object-fit-cover"
                                        />
                                        <img
                                            v-else-if="avatarUrl"
                                            :src="avatarUrl"
                                            alt="Current Photo"
                                            class="w-100 h-100 rounded-circle object-fit-cover"
                                        />
                                        <span v-else>{{
                                            getInitials(profileForm.name)
                                        }}</span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <label
                                        for="photo"
                                        class="btn btn-sm btn-outline-secondary rounded-pill fw-medium px-4 py-2 mb-1 cursor-pointer position-relative z-2 d-inline-flex align-items-center gap-2"
                                        style="min-height: 48px; touch-action: manipulation;"
                                        @click="triggerPhotoSelect"
                                    >
                                        <i class="bi bi-camera-fill text-success fs-5"></i> Select New Photo
                                    </label>
                                    <input
                                        id="photo"
                                        ref="profilePhotoInput"
                                        type="file"
                                        class="d-none"
                                        accept="image/jpeg,image/png,image/webp"
                                        @change="setProfilePhoto"
                                    />
                                    <div
                                        class="small text-body-secondary opacity-75"
                                    >
                                        JPG, PNG, WebP up to 15MB
                                    </div>
                                    <div
                                        v-if="profileForm.errors.photo"
                                        class="text-danger small fw-bold mt-1"
                                    >
                                        {{ profileForm.errors.photo }}
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="name"
                                    class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1"
                                    >Full Name</label
                                >
                                <input
                                    id="name"
                                    v-model="profileForm.name"
                                    type="text"
                                    class="form-control bg-body-tertiary rounded-4 py-2"
                                    :class="{
                                        'is-invalid': profileForm.errors.name,
                                    }"
                                    required
                                />
                                <div
                                    v-if="profileForm.errors.name"
                                    class="invalid-feedback fw-bold ps-2"
                                >
                                    {{ profileForm.errors.name }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label
                                    for="email"
                                    class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1"
                                    >Email (Gmail)</label
                                >
                                <input
                                    id="email"
                                    v-model="profileForm.email"
                                    type="email"
                                    class="form-control bg-body-tertiary rounded-4 py-2"
                                    :class="{
                                        'is-invalid': profileForm.errors.email,
                                    }"
                                    required
                                />
                                <div
                                    v-if="profileForm.errors.email"
                                    class="invalid-feedback fw-bold ps-2"
                                >
                                    {{ profileForm.errors.email }}
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button
                                    type="submit"
                                    class="btn btn-native-primary rounded-pill px-4 fw-bold shadow-sm"
                                    :disabled="profileForm.processing"
                                >
                                    <span v-if="profileForm.processing"
                                        ><span
                                            class="spinner-border spinner-border-sm me-2"
                                        ></span
                                        >Saving...</span
                                    >
                                    <span v-else>Save Profile</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- 🚀 Update Password Form -->
                    <div
                        class="bg-body p-4 p-md-5 rounded-4 shadow-sm border border-secondary-subtle mb-4"
                    >
                        <h3 class="h5 fw-bold mb-1">Update Password</h3>
                        <p class="text-body-secondary small mb-4">
                            Ensure your account is using a long, random password
                            to stay secure.
                        </p>

                        <form @submit.prevent="updatePassword">
                            <div class="mb-3">
                                <label
                                    for="current_password"
                                    class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1"
                                    >Current Password</label
                                >
                                <input
                                    id="current_password"
                                    v-model="passwordForm.current_password"
                                    type="password"
                                    class="form-control bg-body-tertiary rounded-4 py-2"
                                    :class="{
                                        'is-invalid':
                                            passwordForm.errors
                                                .current_password,
                                    }"
                                    autocomplete="current-password"
                                />
                                <div
                                    v-if="passwordForm.errors.current_password"
                                    class="invalid-feedback fw-bold ps-2"
                                >
                                    {{ passwordForm.errors.current_password }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label
                                    for="password"
                                    class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1"
                                    >New Password</label
                                >
                                <input
                                    id="password"
                                    v-model="passwordForm.password"
                                    type="password"
                                    class="form-control bg-body-tertiary rounded-4 py-2"
                                    :class="{
                                        'is-invalid':
                                            passwordForm.errors.password,
                                    }"
                                    autocomplete="new-password"
                                />
                                <div
                                    v-if="passwordForm.errors.password"
                                    class="invalid-feedback fw-bold ps-2"
                                >
                                    {{ passwordForm.errors.password }}
                                </div>
                            </div>

                            <div class="mb-4">
                                <label
                                    for="password_confirmation"
                                    class="form-label fw-bold small text-body-secondary text-uppercase ms-1 mb-1"
                                    >Confirm Password</label
                                >
                                <input
                                    id="password_confirmation"
                                    v-model="passwordForm.password_confirmation"
                                    type="password"
                                    class="form-control bg-body-tertiary rounded-4 py-2"
                                    :class="{
                                        'is-invalid':
                                            passwordForm.errors
                                                .password_confirmation,
                                    }"
                                    autocomplete="new-password"
                                />
                                <div
                                    v-if="
                                        passwordForm.errors
                                            .password_confirmation
                                    "
                                    class="invalid-feedback fw-bold ps-2"
                                >
                                    {{
                                        passwordForm.errors
                                            .password_confirmation
                                    }}
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button
                                    type="submit"
                                    class="btn btn-dark rounded-pill px-4 fw-bold shadow-sm"
                                    :disabled="passwordForm.processing"
                                >
                                    <span v-if="passwordForm.processing"
                                        ><span
                                            class="spinner-border spinner-border-sm me-2"
                                        ></span
                                        >Saving...</span
                                    >
                                    <span v-else>Update Password</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- 📜 POLICY & ABOUT MODALS -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="activePolicyModal"
                    class="modal fade show d-block"
                    tabindex="-1"
                    style="
                        background: rgba(0, 0, 0, 0.65);
                        backdrop-filter: blur(4px);
                        z-index: 9999;
                    "
                >
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div
                            class="modal-content border-0 rounded-4 shadow-lg bg-body"
                        >
                            <!-- About E-BoardMate Modal Header & Content -->
                            <div
                                v-if="activePolicyModal === 'about'"
                                class="modal-body p-4 p-md-5"
                            >
                                <div
                                    class="d-flex align-items-center justify-content-between mb-3"
                                >
                                    <h3
                                        class="h4 fw-bold text-body-emphasis mb-0"
                                    >
                                        <i
                                            class="bi bi-info-circle-fill text-primary me-2"
                                        ></i>
                                        About E-BoardMate
                                    </h3>
                                    <button
                                        type="button"
                                        class="btn-close shadow-none"
                                        @click="activePolicyModal = null"
                                    ></button>
                                </div>
                                <p class="text-body-secondary lh-lg mb-3">
                                    E-BoardMate is a specialized web-based
                                    boarding house locator and reservation
                                    management system built exclusively for
                                    Talibon Polytechnic College (TPC) students
                                    and verified local landlords in Talibon,
                                    Bohol.
                                </p>
                                <p class="text-body-secondary lh-lg mb-4">
                                    Our mission is to eliminate student housing
                                    stress through real-time room availability
                                    tracking, satellite campus distance
                                    calculations, and instant account-free guest
                                    reservations.
                                </p>
                                <div class="d-flex justify-content-end">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary rounded-pill px-4"
                                        @click="activePolicyModal = null"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>

                            <!-- Platform Policy Content -->
                            <div
                                v-if="activePolicyModal === 'policy'"
                                class="modal-body p-4 p-md-5"
                            >
                                <div
                                    class="d-flex align-items-center justify-content-between mb-3"
                                >
                                    <h3
                                        class="h4 fw-bold text-body-emphasis mb-0"
                                    >
                                        <i
                                            class="bi bi-file-earmark-text-fill text-info me-2"
                                        ></i>
                                        Platform Policy
                                    </h3>
                                    <button
                                        type="button"
                                        class="btn-close shadow-none"
                                        @click="activePolicyModal = null"
                                    ></button>
                                </div>
                                <ul
                                    class="list-group list-group-flush mb-4 small text-body-secondary"
                                >
                                    <li
                                        class="list-group-item bg-transparent px-0 py-2"
                                    >
                                        <strong
                                            >1. Accurate Listing Data:</strong
                                        >
                                        All property details including rent
                                        price, available bedspaces, and photos
                                        must be true and updated.
                                    </li>
                                    <li
                                        class="list-group-item bg-transparent px-0 py-2"
                                    >
                                        <strong
                                            >2. Timely Reservation
                                            Review:</strong
                                        >
                                        Landlords agree to respond to student
                                        guest requests within 24 to 48 hours.
                                    </li>
                                    <li
                                        class="list-group-item bg-transparent px-0 py-2"
                                    >
                                        <strong>3. Fair Treatment:</strong>
                                        Discrimination based on gender,
                                        religion, or course is strictly
                                        prohibited on the platform.
                                    </li>
                                </ul>
                                <div class="d-flex justify-content-end">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary rounded-pill px-4"
                                        @click="activePolicyModal = null"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>

                            <!-- Data Privacy Policy Content -->
                            <div
                                v-if="activePolicyModal === 'privacy'"
                                class="modal-body p-4 p-md-5"
                            >
                                <div
                                    class="d-flex align-items-center justify-content-between mb-3"
                                >
                                    <h3
                                        class="h4 fw-bold text-body-emphasis mb-0"
                                    >
                                        <i
                                            class="bi bi-shield-lock-fill text-success me-2"
                                        ></i>
                                        Data Privacy Policy (RA 10173)
                                    </h3>
                                    <button
                                        type="button"
                                        class="btn-close shadow-none"
                                        @click="activePolicyModal = null"
                                    ></button>
                                </div>
                                <p class="text-body-secondary small lh-lg mb-3">
                                    In compliance with the Data Privacy Act of
                                    2012 (Republic Act No. 10173), E-BoardMate
                                    protects all personal information collected
                                    during reservation requests.
                                </p>
                                <p class="text-body-secondary small lh-lg mb-4">
                                    Student contact information (Full Name,
                                    Phone Number, Email) is processed solely for
                                    bedspace reservation coordination between
                                    the student and the verified landlord. We do
                                    not sell or monetize personal data.
                                </p>
                                <div class="d-flex justify-content-end">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary rounded-pill px-4"
                                        @click="activePolicyModal = null"
                                    >
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- 🚪 2-STEP LOGOUT CONFIRMATION MODAL -->
        <Teleport to="body">
            <Transition name="fade">
                <div
                    v-if="showLogoutModal"
                    class="modal fade show d-block"
                    tabindex="-1"
                    style="
                        background: rgba(0, 0, 0, 0.65);
                        backdrop-filter: blur(4px);
                        z-index: 9999;
                    "
                >
                    <div class="modal-dialog modal-dialog-centered modal-sm">
                        <div class="modal-content border-0 rounded-4 shadow-lg bg-body">
                            <div class="modal-body p-4 text-center">
                                <div
                                    class="mb-3 mx-auto bg-danger bg-opacity-10 text-danger fs-2"
                                    style="
                                        width: 56px;
                                        height: 56px;
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                    "
                                >
                                    🚪
                                </div>
                                <h3 class="h5 fw-bold mb-2 text-body-emphasis">Log Out Confirmation</h3>
                                <p class="text-body-secondary small mb-4">
                                    Are you sure you want to log out of your landlord account?
                                </p>
                                <div class="d-flex gap-2">
                                    <button
                                        type="button"
                                        class="btn btn-outline-secondary w-50 rounded-pill fw-semibold"
                                        @click="showLogoutModal = false"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-danger w-50 rounded-pill fw-bold"
                                        :disabled="logoutForm.processing"
                                        @click="confirmLogout"
                                    >
                                        <span
                                            v-if="logoutForm.processing"
                                            class="spinner-border spinner-border-sm me-1"
                                        ></span>
                                        Yes, Log Out
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </OwnerLayout>
</template>

<style scoped>
/* Restrict max width on desktop */
.max-w-desktop {
    max-width: 1200px;
}

/* =========================================
   NATIVE APP UI COMPONENTS
========================================== */

/* Standard Header Avatar */
.owner-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    font-size: 1.1rem;
}

/* Large Avatar in Profile Form */
.owner-avatar-lg {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    font-size: 2rem;
}

/* Native-style Settings List */
.native-list-group .list-group-item {
    transition: background-color 0.2s ease;
}
.native-list-group .list-group-item-action:active {
    background-color: rgba(var(--bs-secondary-bg-rgb), 0.5) !important;
}
.settings-icon-box {
    width: 38px;
    height: 38px;
}

/* Typography refinements */
.tracking-tight {
    letter-spacing: -0.02em;
}

/* Native-style Action buttons */
.btn-native-primary {
    background-color: #10b981;
    color: white;
    border: none;
}
.btn-native-primary:hover {
    background-color: #059669;
    color: white;
}

/* Clean Custom Form Control Shadows */
.form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
    border-color: #10b981;
}
</style>
