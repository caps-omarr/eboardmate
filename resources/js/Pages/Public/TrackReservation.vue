<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import { computed, ref } from "vue";

const page = usePage();

const trackingResult = computed(
    () => page.props.flash?.tracking_result || null,
);

const form = useForm({
    reference_code: "",
    email: "",
});

const copied = ref(false);
const copyReferenceCode = async () => {
    if (!trackingResult.value?.reference_code) return;
    try {
        await navigator.clipboard.writeText(trackingResult.value.reference_code);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2200);
    } catch (err) {
        console.error("Clipboard copy failed:", err);
    }
};

const submitTracking = () => {
    form.post("/track-reservation", {
        preserveScroll: true,
    });
};

const statusBadgeClass = computed(() => {
    if (!trackingResult.value) {
        return "bg-secondary text-white";
    }

    if (trackingResult.value.status_type === "success") {
        return "bg-success text-white";
    }

    if (trackingResult.value.status_type === "warning") {
        return "bg-warning text-dark";
    }

    if (trackingResult.value.status_type === "danger") {
        return "bg-danger text-white";
    }

    return "bg-secondary text-white";
});
</script>

<template>
    <PublicLayout>
        <Head title="Track Reservation | E-BoardMate">
            <meta
                name="description"
                content="Track your E-BoardMate boarding house reservation using your reference code and email address."
            />
        </Head>

        <section class="py-4 py-lg-5">
            <div class="container">
                <!-- Top-Left Navigation Section (Law 4 & 5: Back on Top-Left) -->
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10 col-xl-9">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <Link
                                href="/boarding-houses"
                                class="btn btn-outline-secondary border-secondary-subtle px-3 py-2 fw-medium d-inline-flex align-items-center gap-2 transition-all"
                                style="min-height: 44px; border-radius: 10px;"
                                title="Return to Boarding House Listings"
                            >
                                <i class="bi bi-arrow-left fs-6"></i>
                                <span>Back to Accommodations</span>
                            </Link>
                        </div>

                        <!-- Header & Context (Law 1: Human-Crafted Copy & Intentional Hierarchy) -->
                        <div class="text-start text-sm-center pt-2">
                            <h1 class="display-6 fw-bold mb-2 text-body-emphasis tracking-tight">
                                Track Your Reservation Status
                            </h1>

                            <p class="text-body-secondary mb-0 mx-auto" style="max-width: 640px; line-height: 1.6;">
                                Enter the cryptographic reference code (<code class="text-success fw-bold">EBM-YYYY-XXXXXX</code>) and email address you used during your booking submission.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 2-Column Responsive Layout -->
                <div class="row justify-content-center g-4">
                    <!-- Left Column: Input Form (Law 3: 24px Form Gap, 8-12px Label Gap, 32px Submit Button Gap) -->
                    <div class="col-lg-5 col-xl-4">
                        <div class="card bg-body border border-secondary-subtle shadow-sm transition-all" style="border-radius: 20px; padding: 28px;">
                            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom border-secondary-subtle">
                                <i class="bi bi-search text-success fs-5"></i>
                                <h2 class="h5 fw-bold mb-0 text-body-emphasis">Search Booking</h2>
                            </div>

                            <!-- Error Alert (Law 6: Pinpoint Inline Feedback) -->
                            <div
                                v-if="form.errors.tracking"
                                class="alert alert-danger py-2.5 px-3 mb-3 border-danger-subtle d-flex align-items-center gap-2 small"
                                style="border-radius: 10px;"
                            >
                                <i class="bi bi-exclamation-circle-fill flex-shrink-0"></i>
                                <span>{{ form.errors.tracking }}</span>
                            </div>

                            <form @submit.prevent="submitTracking">
                                <!-- Form Group 1: Reference Code -->
                                <div style="margin-bottom: 24px;">
                                    <label
                                        for="reference_code"
                                        class="form-label fw-semibold text-body-emphasis small text-uppercase tracking-wider"
                                        style="margin-bottom: 8px; display: block;"
                                    >
                                        Reservation Reference Code
                                    </label>

                                    <!-- Enclosed Input Box (Law 6: Clear 1px Boundaries, Never Underline-Only) -->
                                    <div class="input-group">
                                        <span class="input-group-text bg-body-tertiary border-secondary-subtle text-secondary" style="border-top-left-radius: 10px; border-bottom-left-radius: 10px;">
                                            <i class="bi bi-ticket-perforated"></i>
                                        </span>
                                        <input
                                            id="reference_code"
                                            v-model="form.reference_code"
                                            type="text"
                                            class="form-control text-uppercase font-monospace border-secondary-subtle"
                                            :class="{ 'is-invalid': form.errors.reference_code }"
                                            placeholder="EBM-2026-XXXXXX"
                                            style="min-height: 48px; border-top-right-radius: 10px; border-bottom-right-radius: 10px; letter-spacing: 0.06em;"
                                            required
                                            autocomplete="off"
                                        />
                                    </div>

                                    <div
                                        v-if="form.errors.reference_code"
                                        class="text-danger small mt-1.5 d-flex align-items-center gap-1"
                                    >
                                        <i class="bi bi-x-circle"></i>
                                        <span>{{ form.errors.reference_code }}</span>
                                    </div>
                                    <div v-else class="text-body-secondary small mt-1.5" style="font-size: 0.78rem;">
                                        Generated upon reservation (e.g. EBM-2026-A8K9Z2).
                                    </div>
                                </div>

                                <!-- Form Group 2: Email Address (24px separation) -->
                                <div style="margin-bottom: 24px;">
                                    <label
                                        for="email"
                                        class="form-label fw-semibold text-body-emphasis small text-uppercase tracking-wider"
                                        style="margin-bottom: 8px; display: block;"
                                    >
                                        Student Email Address
                                    </label>

                                    <div class="input-group">
                                        <span class="input-group-text bg-body-tertiary border-secondary-subtle text-secondary" style="border-top-left-radius: 10px; border-bottom-left-radius: 10px;">
                                            <i class="bi bi-envelope"></i>
                                        </span>
                                        <input
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            class="form-control border-secondary-subtle"
                                            :class="{ 'is-invalid': form.errors.email }"
                                            placeholder="student@example.com"
                                            style="min-height: 48px; border-top-right-radius: 10px; border-bottom-right-radius: 10px;"
                                            required
                                            autocomplete="email"
                                        />
                                    </div>

                                    <div
                                        v-if="form.errors.email"
                                        class="text-danger small mt-1.5 d-flex align-items-center gap-1"
                                    >
                                        <i class="bi bi-x-circle"></i>
                                        <span>{{ form.errors.email }}</span>
                                    </div>
                                    <div v-else class="text-body-secondary small mt-1.5" style="font-size: 0.78rem;">
                                        Must match the email provided in the reservation form.
                                    </div>
                                </div>

                                <!-- Submit Button (Law 5: 32px Gap, Clear Action Verb, Minimum 48px Touch Target) -->
                                <button
                                    type="submit"
                                    class="btn btn-ebm-primary w-100 fw-bold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm transition-all"
                                    style="margin-top: 32px; min-height: 48px; border-radius: 10px;"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    <i v-else class="bi bi-search"></i>
                                    <span>{{ form.processing ? "Verifying Status..." : "Check Status" }}</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Result Details or Empty State Placeholder -->
                    <div class="col-lg-7 col-xl-6">
                        <!-- Active Tracking Result Card -->
                        <div
                            v-if="trackingResult"
                            class="card bg-body border border-secondary-subtle shadow-sm transition-all"
                            style="border-radius: 20px; padding: 28px;"
                        >
                            <!-- Top Status Banner & Establishment -->
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3 pb-3 border-bottom border-secondary-subtle">
                                <div>
                                    <!-- Law 7: Metrics & Details (Value primary, bold title) -->
                                    <span class="text-body-secondary small text-uppercase fw-semibold d-block mb-1">
                                        Boarding House Establishment
                                    </span>
                                    <h2 class="h4 fw-bold text-body-emphasis mb-0 d-flex align-items-center gap-2">
                                        <i class="bi bi-house-door text-success"></i>
                                        <span>{{ trackingResult.boarding_house_name }}</span>
                                    </h2>
                                </div>

                                <div class="text-end">
                                    <span
                                        class="badge px-3 py-2 fw-semibold text-uppercase tracking-wider shadow-sm"
                                        :class="statusBadgeClass"
                                        style="border-radius: 8px; font-size: 0.82rem;"
                                    >
                                        {{ trackingResult.status_label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Human-Readable Status Narrative -->
                            <p class="text-body-secondary mb-4 small" style="line-height: 1.6;">
                                {{ trackingResult.status_message }}
                            </p>

                            <!-- Expired Notification -->
                            <div
                                v-if="trackingResult.is_expired"
                                class="alert alert-warning py-2.5 px-3 mb-4 border-warning-subtle d-flex align-items-start gap-2 small"
                                style="border-radius: 10px;"
                            >
                                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-0.5"></i>
                                <div>
                                    <strong class="d-block mb-0.5">Reference Code Expired</strong>
                                    <span>This reservation timed out because the landlord did not adjudicate within 24 hours. The vacancy has been restored to the public pool.</span>
                                </div>
                            </div>

                            <!-- Concentric Reference Voucher Box (Law 3: Radius = 20 - 8 = 12px) -->
                            <div
                                class="border border-success-subtle bg-success-subtle bg-opacity-25 p-3 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3"
                                style="border-radius: 12px; border-style: dashed !important;"
                            >
                                <div>
                                    <span class="text-body-secondary text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                        Voucher Reference Code
                                    </span>
                                    <span class="d-block font-monospace fw-bold text-success" style="font-size: 1.5rem; letter-spacing: 0.08em;">
                                        {{ trackingResult.reference_code }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    @click="copyReferenceCode"
                                    class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1.5 px-3 py-2 fw-semibold transition-all"
                                    style="min-height: 44px; border-radius: 8px;"
                                    title="Copy reference code to clipboard"
                                >
                                    <i class="bi" :class="copied ? 'bi-check2-circle' : 'bi-clipboard'"></i>
                                    <span>{{ copied ? "Copied!" : "Copy Code" }}</span>
                                </button>
                            </div>

                            <!-- Structured Data Grid (Law 7: Avoid the Label:Value Trap, Value Primary, Label Secondary) -->
                            <div class="row g-3 mb-4">
                                <div class="col-sm-6">
                                    <div class="p-3 bg-body-tertiary border border-secondary-subtle" style="border-radius: 12px;">
                                        <span class="text-body-secondary text-uppercase fw-semibold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                            Preferred Move-in Date
                                        </span>
                                        <div class="fw-bold text-body-emphasis fs-6">
                                            {{ trackingResult.preferred_move_in_date || "Not Specified" }}
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <div class="p-3 bg-body-tertiary border border-secondary-subtle" style="border-radius: 12px;">
                                        <span class="text-body-secondary text-uppercase fw-semibold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                            Submission Timestamp
                                        </span>
                                        <div class="fw-bold text-body-emphasis fs-6">
                                            {{ trackingResult.submitted_at || "—" }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="trackingResult.expires_at && trackingResult.status === 'pending'"
                                    class="col-12"
                                >
                                    <div class="p-3 bg-body-tertiary border border-secondary-subtle d-flex align-items-center justify-content-between" style="border-radius: 12px;">
                                        <div>
                                            <span class="text-body-secondary text-uppercase fw-semibold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                                Reservation Hold Expiration
                                            </span>
                                            <div class="fw-bold text-warning-emphasis fs-6">
                                                {{ trackingResult.expires_at }}
                                            </div>
                                        </div>
                                        <i class="bi bi-clock-history text-warning fs-4"></i>
                                    </div>
                                </div>

                                <div v-if="trackingResult.approved_at" class="col-sm-6">
                                    <div class="p-3 bg-body-tertiary border border-secondary-subtle" style="border-radius: 12px;">
                                        <span class="text-body-secondary text-uppercase fw-semibold d-block mb-1" style="font-size: 0.72rem;">
                                            Approved Timestamp
                                        </span>
                                        <div class="fw-bold text-success fs-6">
                                            {{ trackingResult.approved_at }}
                                        </div>
                                    </div>
                                </div>

                                <div v-if="trackingResult.rejected_at" class="col-sm-6">
                                    <div class="p-3 bg-body-tertiary border border-secondary-subtle" style="border-radius: 12px;">
                                        <span class="text-body-secondary text-uppercase fw-semibold d-block mb-1" style="font-size: 0.72rem;">
                                            Rejected Timestamp
                                        </span>
                                        <div class="fw-bold text-danger fs-6">
                                            {{ trackingResult.rejected_at }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Landlord Response Note -->
                            <div
                                v-if="trackingResult.owner_response"
                                class="p-3 bg-body-tertiary border border-secondary-subtle mb-3"
                                style="border-radius: 12px;"
                            >
                                <span class="text-body-secondary text-uppercase fw-semibold d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.05em;">
                                    Landlord Remarks
                                </span>
                                <p class="mb-0 text-body-emphasis small font-italic" style="line-height: 1.5;">
                                    "{{ trackingResult.owner_response }}"
                                </p>
                            </div>

                            <!-- Can Apply Again Action -->
                            <div
                                v-if="trackingResult.can_apply_again"
                                class="d-flex align-items-center justify-content-between p-3 bg-body-tertiary border border-secondary-subtle mt-3"
                                style="border-radius: 12px;"
                            >
                                <div class="small text-body-secondary pe-2">
                                    Need to book another spot? You can now submit a fresh reservation.
                                </div>
                                <Link
                                    href="/boarding-houses"
                                    class="btn btn-sm btn-ebm-primary text-nowrap fw-semibold px-3 py-2"
                                    style="border-radius: 8px; min-height: 40px;"
                                >
                                    Browse Listings
                                </Link>
                            </div>
                        </div>

                        <!-- Empty State Placeholder (Law 8: Uniform 100% Outlined 2D Icons, No Raw Emojis) -->
                        <div
                            v-else
                            class="card bg-body border border-secondary-subtle shadow-sm h-100 d-flex flex-column align-items-center justify-content-center text-center p-4 p-md-5 transition-all"
                            style="border-radius: 20px; min-height: 360px;"
                        >
                            <div
                                class="d-inline-flex align-items-center justify-content-center bg-body-tertiary border border-secondary-subtle text-secondary mb-3.5"
                                style="width: 72px; height: 72px; border-radius: 18px;"
                            >
                                <i class="bi bi-search fs-2 text-success"></i>
                            </div>

                            <h2 class="h5 fw-bold text-body-emphasis mb-2">
                                No Reservation Query Submitted
                            </h2>

                            <p class="text-body-secondary mb-0 small mx-auto" style="max-width: 380px; line-height: 1.6;">
                                Enter your reservation reference code and student email in the form on the left to review your booking lifecycle, landlord remarks, and status.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
