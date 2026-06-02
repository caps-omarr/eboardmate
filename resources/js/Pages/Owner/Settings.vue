<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import OwnerLayout from '@/Layouts/OwnerLayout.vue';
import { computed, ref } from 'vue';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success || null);

// Form state
const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

// Toggle states for showing/hiding passwords
const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const submit = () => {
    form.put('/owner/settings/password', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <OwnerLayout>
        <Head title="Settings | E-BoardMate" />
        <div class="container py-5">
            <div class="ebm-card p-4 col-md-6 mx-auto">
                <h2 class="h4 fw-bold mb-4">Change Password</h2>
                
                <div v-if="flashSuccess" class="alert alert-success">{{ flashSuccess }}</div>

                <form @submit.prevent="submit">
                    <!-- Current Password -->
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-group has-validation">
                            <input 
                                :type="showCurrent ? 'text' : 'password'" 
                                v-model="form.current_password" 
                                class="form-control" 
                                :class="{'is-invalid': form.errors.current_password}"
                            >
                            <button 
                                class="btn btn-outline-secondary" 
                                type="button" 
                                @click="showCurrent = !showCurrent"
                                style="min-width: 70px;"
                            >
                                {{ showCurrent ? 'Hide' : 'Show' }}
                            </button>
                            <div class="invalid-feedback">{{ form.errors.current_password }}</div>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-group has-validation">
                            <input 
                                :type="showNew ? 'text' : 'password'" 
                                v-model="form.password" 
                                class="form-control" 
                                :class="{'is-invalid': form.errors.password}"
                            >
                            <button 
                                class="btn btn-outline-secondary" 
                                type="button" 
                                @click="showNew = !showNew"
                                style="min-width: 70px;"
                            >
                                {{ showNew ? 'Hide' : 'Show' }}
                            </button>
                            <!-- Error message for new password -->
                            <div v-if="form.errors.password" class="invalid-feedback d-block">
                                {{ form.errors.password }}
                            </div>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-group">
                            <input 
                                :type="showConfirm ? 'text' : 'password'" 
                                v-model="form.password_confirmation" 
                                class="form-control"
                            >
                            <button 
                                class="btn btn-outline-secondary" 
                                type="button" 
                                @click="showConfirm = !showConfirm"
                                style="min-width: 70px;"
                            >
                                {{ showConfirm ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success" :disabled="form.processing">
                        <span v-if="form.processing">Updating...</span>
                        <span v-else>Update Password</span>
                    </button>
                </form>
            </div>
        </div>
    </OwnerLayout>
</template>