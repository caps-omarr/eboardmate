<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    owner: {
        type: Object,
        default: () => ({}), // 🚀 FIX 1: Safely default to an empty object instead of requiring it
    }
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || null);

// --- PROFILE FORM STATE ---
const profileForm = useForm({
    name: props.owner?.name || '',   // 🚀 FIX 2: Added the '?' optional chaining
    email: props.owner?.email || '', // 🚀 FIX 3: Added the '?' optional chaining
});

// --- PASSWORD FORM STATE ---
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Toggle states for showing/hiding passwords
const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const submitProfile = () => {
    profileForm.put('/owner/settings/profile', {
        preserveScroll: true,
    });
};

const submitPassword = () => {
    passwordForm.put('/owner/settings/password', {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            showCurrent.value = false;
            showNew.value = false;
            showConfirm.value = false;
        },
    });
};
</script>

<template>
    <OwnerLayout>
        <Head title="Account Settings | E-BoardMate" />

        <div class="container pb-5 pt-2">
            
            <!-- FLASH SUCCESS MESSAGE -->
            <div v-if="flashSuccess" class="alert alert-success mb-4 shadow-sm border-0">
                {{ flashSuccess }}
            </div>

            <!-- HEADER -->
            <header class="mb-4">
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle mb-3 px-3 py-2 rounded-pill shadow-sm">
                    Owner Settings
                </span>
                <h1 class="fw-bold mb-2 tracking-tight text-body-emphasis">Account Settings</h1>
                <p class="text-body-secondary mb-0 lead" style="font-size: 1.1rem;">
                    Update your profile information, email address, and account password.
                </p>
            </header>

            <div class="row g-4">
                
                <!-- PROFILE UPDATE SECTION -->
                <section class="col-lg-6" aria-label="Profile Information">
                    <div class="ebm-card p-4 shadow-sm border border-secondary-subtle h-100 bg-body-tertiary">
                        <div class="mb-4 border-bottom border-secondary-subtle pb-3">
                            <h2 class="h5 fw-bold mb-1 text-body-emphasis">Profile Information</h2>
                            <p class="text-body-secondary small mb-0">Update your account's display name and email address.</p>
                        </div>

                        <form @submit.prevent="submitProfile">
                            <div class="mb-3">
                                <label for="name" class="form-label small fw-bold text-uppercase tracking-tight text-body-emphasis">Full Name</label>
                                <input
                                    id="name"
                                    v-model="profileForm.name"
                                    type="text"
                                    class="form-control border-secondary-subtle bg-body shadow-sm"
                                    :class="{ 'is-invalid': profileForm.errors.name }"
                                >
                                <div v-if="profileForm.errors.name" class="invalid-feedback fw-bold">{{ profileForm.errors.name }}</div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label small fw-bold text-uppercase tracking-tight text-body-emphasis">Email Address</label>
                                <input
                                    id="email"
                                    v-model="profileForm.email"
                                    type="email"
                                    class="form-control border-secondary-subtle bg-body shadow-sm"
                                    :class="{ 'is-invalid': profileForm.errors.email }"
                                >
                                <div v-if="profileForm.errors.email" class="invalid-feedback fw-bold">{{ profileForm.errors.email }}</div>
                                <div class="form-text small text-body-secondary mt-2">
                                    <i class="bi bi-info-circle"></i> This is the email where you will receive new reservation notifications.
                                </div>
                            </div>

                            <button type="submit" class="btn btn-success fw-bold shadow-sm px-4" :disabled="profileForm.processing">
                                <span v-if="profileForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ profileForm.processing ? 'Saving...' : 'Save Profile' }}
                            </button>
                        </form>
                    </div>
                </section>

                <!-- PASSWORD UPDATE SECTION -->
                <section class="col-lg-6" aria-label="Update Password">
                    <div class="ebm-card p-4 shadow-sm border border-secondary-subtle h-100 bg-body-tertiary">
                        <div class="mb-4 border-bottom border-secondary-subtle pb-3">
                            <h2 class="h5 fw-bold mb-1 text-body-emphasis">Update Password</h2>
                            <p class="text-body-secondary small mb-0">Ensure your account is using a long, random password to stay secure.</p>
                        </div>

                        <form @submit.prevent="submitPassword">
                            
                            <!-- Current Password with Toggle -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase tracking-tight text-body-emphasis">Current Password</label>
                                <div class="input-group has-validation shadow-sm">
                                    <input 
                                        :type="showCurrent ? 'text' : 'password'" 
                                        v-model="passwordForm.current_password" 
                                        class="form-control border-secondary-subtle bg-body" 
                                        :class="{'is-invalid': passwordForm.errors.current_password}"
                                    >
                                    <button 
                                        class="btn border-secondary-subtle bg-body text-body-secondary fw-medium" 
                                        type="button" 
                                        @click="showCurrent = !showCurrent"
                                        style="min-width: 70px;"
                                    >
                                        {{ showCurrent ? 'Hide' : 'Show' }}
                                    </button>
                                    <div v-if="passwordForm.errors.current_password" class="invalid-feedback fw-bold">{{ passwordForm.errors.current_password }}</div>
                                </div>
                            </div>

                            <!-- New Password with Toggle -->
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-uppercase tracking-tight text-body-emphasis">New Password</label>
                                <div class="input-group has-validation shadow-sm">
                                    <input 
                                        :type="showNew ? 'text' : 'password'" 
                                        v-model="passwordForm.password" 
                                        class="form-control border-secondary-subtle bg-body" 
                                        :class="{'is-invalid': passwordForm.errors.password}"
                                    >
                                    <button 
                                        class="btn border-secondary-subtle bg-body text-body-secondary fw-medium" 
                                        type="button" 
                                        @click="showNew = !showNew"
                                        style="min-width: 70px;"
                                    >
                                        {{ showNew ? 'Hide' : 'Show' }}
                                    </button>
                                    <div v-if="passwordForm.errors.password" class="invalid-feedback d-block fw-bold">
                                        {{ passwordForm.errors.password }}
                                    </div>
                                </div>
                            </div>

                            <!-- Confirm New Password with Toggle -->
                            <div class="mb-4">
                                <label class="form-label small fw-bold text-uppercase tracking-tight text-body-emphasis">Confirm New Password</label>
                                <div class="input-group shadow-sm">
                                    <input 
                                        :type="showConfirm ? 'text' : 'password'" 
                                        v-model="passwordForm.password_confirmation" 
                                        class="form-control border-secondary-subtle bg-body"
                                    >
                                    <button 
                                        class="btn border-secondary-subtle bg-body text-body-secondary fw-medium" 
                                        type="button" 
                                        @click="showConfirm = !showConfirm"
                                        style="min-width: 70px;"
                                    >
                                        {{ showConfirm ? 'Hide' : 'Show' }}
                                    </button>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-dark fw-bold shadow-sm px-4" :disabled="passwordForm.processing">
                                <span v-if="passwordForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ passwordForm.processing ? 'Updating...' : 'Update Password' }}
                            </button>
                        </form>
                    </div>
                </section>

            </div>
        </div>
    </OwnerLayout>
</template>

<style scoped>
.tracking-tight {
    letter-spacing: -0.02em;
}
</style>