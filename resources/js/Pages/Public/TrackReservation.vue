<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { computed } from 'vue';

const page = usePage();

const trackingResult = computed(() => page.props.flash?.tracking_result || null);

const form = useForm({
    reference_code: '',
    email: '',
});

// 🚀 FIX: The background setInterval polling logic was completely removed.
// The app will no longer spam the server every 10 seconds, permanently fixing the 429 error.

const submitTracking = () => {
    form.post('/track-reservation', {
        preserveScroll: true,
    });
};

const statusBadgeClass = computed(() => {
    if (!trackingResult.value) {
        return 'text-bg-secondary';
    }

    if (trackingResult.value.status_type === 'success') {
        return 'text-bg-success';
    }

    if (trackingResult.value.status_type === 'warning') {
        return 'text-bg-warning';
    }

    if (trackingResult.value.status_type === 'danger') {
        return 'text-bg-danger';
    }

    return 'text-bg-secondary';
});
</script>

<template>
    <PublicLayout>
        <Head title="Track Reservation | E-BoardMate">
            <meta
                name="description"
                content="Track your E-BoardMate boarding house reservation using your reference code and email address."
            >
        </Head>

        <section class="py-5">
            <div class="container">
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-9 text-center">
                        <span class="badge rounded-pill badge-soft-green mb-3">
                            Reservation Tracking
                        </span>

                        <h1 class="fw-bold mb-3">
                            Track Your Boarding House Reservation
                        </h1>

                        <p class="ebm-muted mb-0">
                            Enter your reference code and email address to check your reservation status.
                        </p>
                    </div>
                </div>

                <div class="row justify-content-center g-4">
                    <div class="col-lg-5">
                        <div class="ebm-card p-4 p-md-5">
                            <h2 class="h4 fw-bold mb-3">
                                Tracking Form
                            </h2>

                            <div
                                v-if="form.errors.tracking"
                                class="alert alert-danger"
                            >
                                {{ form.errors.tracking }}
                            </div>

                            <form @submit.prevent="submitTracking">
                                <div class="mb-3">
                                    <label
                                        for="reference_code"
                                        class="form-label"
                                    >
                                        Reference Code
                                    </label>

                                    <input
                                        id="reference_code"
                                        v-model="form.reference_code"
                                        type="text"
                                        class="form-control text-uppercase"
                                        :class="{ 'is-invalid': form.errors.reference_code }"
                                        placeholder="EBM-2026-000001"
                                    >

                                    <div
                                        v-if="form.errors.reference_code"
                                        class="invalid-feedback"
                                    >
                                        {{ form.errors.reference_code }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label
                                        for="email"
                                        class="form-label"
                                    >
                                        Email Address
                                    </label>

                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.email }"
                                        placeholder="example@email.com"
                                    >

                                    <div
                                        v-if="form.errors.email"
                                        class="invalid-feedback"
                                    >
                                        {{ form.errors.email }}
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-ebm-primary w-100"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing">
                                        Checking...
                                    </span>

                                    <span v-else>
                                        Track Reservation
                                    </span>
                                </button>
                            </form>

                            <div class="mt-4">
                                <Link href="/map" class="small">
                                    View boarding house map
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div
                            v-if="trackingResult"
                            class="ebm-card p-4 p-md-5"
                        >
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-4">
                                <div>
                                    <span
                                        class="badge mb-3"
                                        :class="statusBadgeClass"
                                    >
                                        {{ trackingResult.status_label }}
                                    </span>

                                    <h2 class="h4 fw-bold mb-2">
                                        Reservation Details
                                    </h2>

                                    <p class="ebm-muted mb-0">
                                        {{ trackingResult.status_message }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="trackingResult.is_expired"
                                class="alert alert-warning"
                            >
                                <strong>Reference code expired.</strong>
                                This reservation is no longer active. You may submit a new reservation for this boarding house if slots are still available.
                            </div>

                            <div class="tracking-reference-box mb-4">
                                <span class="tracking-reference-label">
                                    Reference Code
                                </span>

                                <strong class="tracking-reference-code">
                                    {{ trackingResult.reference_code }}
                                </strong>
                            </div>

                            <div class="receipt-details">
                                <div class="receipt-row">
                                    <span>Boarding House</span>
                                    <strong>{{ trackingResult.boarding_house_name }}</strong>
                                </div>

                                <div class="receipt-row">
                                    <span>Status</span>
                                    <strong>{{ trackingResult.status_label }}</strong>
                                </div>

                                <div class="receipt-row">
                                    <span>Submitted At</span>
                                    <strong>{{ trackingResult.submitted_at }}</strong>
                                </div>

                                <div class="receipt-row">
                                    <span>Preferred Move-in Date</span>
                                    <strong>{{ trackingResult.preferred_move_in_date }}</strong>
                                </div>

                                <div
                                    v-if="trackingResult.expires_at && trackingResult.status === 'pending'"
                                    class="receipt-row"
                                >
                                    <span>Pending Until</span>
                                    <strong>{{ trackingResult.expires_at }}</strong>
                                </div>

                                <div
                                    v-if="trackingResult.approved_at"
                                    class="receipt-row"
                                >
                                    <span>Approved At</span>
                                    <strong>{{ trackingResult.approved_at }}</strong>
                                </div>

                                <div
                                    v-if="trackingResult.rejected_at"
                                    class="receipt-row"
                                >
                                    <span>Rejected At</span>
                                    <strong>{{ trackingResult.rejected_at }}</strong>
                                </div>

                                <div
                                    v-if="trackingResult.expired_at"
                                    class="receipt-row"
                                >
                                    <span>Expired At</span>
                                    <strong>{{ trackingResult.expired_at }}</strong>
                                </div>

                                <div
                                    v-if="trackingResult.cancelled_at"
                                    class="receipt-row"
                                >
                                    <span>Cancelled At</span>
                                    <strong>{{ trackingResult.cancelled_at }}</strong>
                                </div>
                            </div>

                            <div
                                v-if="trackingResult.owner_response"
                                class="alert alert-light border mt-4 mb-0"
                            >
                                <strong>Owner Response:</strong>
                                {{ trackingResult.owner_response }}
                            </div>

                            <div
                                v-if="trackingResult.can_apply_again"
                                class="alert alert-info mt-4 mb-0"
                            >
                                This reservation no longer blocks you from applying again. You may submit a new reservation if the boarding house still has available rooms or bedspaces.
                            </div>
                        </div>

                        <div
                            v-else
                            class="empty-state h-100 d-flex flex-column align-items-center justify-content-center"
                        >
                            <div class="empty-state-icon">
                                🔎
                            </div>

                            <h2 class="h5 fw-bold mb-2">
                                No reservation selected yet
                            </h2>

                            <p class="ebm-muted mb-0 text-center">
                                Your reservation details will appear here after you enter a valid reference code and email address.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>