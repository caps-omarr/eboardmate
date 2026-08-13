<script setup>
import { Link, router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const showModal = ref(false);
const currentStep = ref(1); // 1, 2, 3
const showGuideWidget = ref(false);

const ONBOARDING_KEY = 'ebm_student_onboarding_completed';
const onboardingCompleted = ref(Boolean(localStorage.getItem(ONBOARDING_KEY)));

onMounted(() => {
    if (!onboardingCompleted.value) {
        // Automatically open the guided tour for first-time visitors
        setTimeout(() => {
            showModal.value = true;
        }, 600);
    }
});

const nextStep = () => {
    if (currentStep.value < 3) {
        currentStep.value++;
    } else {
        finishTour();
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const finishTour = (redirectUrl = '/boarding-houses') => {
    showModal.value = false;
    onboardingCompleted.value = true;
    localStorage.setItem(ONBOARDING_KEY, 'completed');
    if (redirectUrl) {
        router.visit(redirectUrl);
    }
};

const skipTour = () => {
    showModal.value = false;
    onboardingCompleted.value = true;
    localStorage.setItem(ONBOARDING_KEY, 'skipped');
};

const toggleGuideWidget = () => {
    showGuideWidget.value = !showGuideWidget.value;
};

const restartTour = () => {
    showGuideWidget.value = false;
    currentStep.value = 1;
    showModal.value = true;
};
</script>

<template>
    <!-- 🚀 STEP-BY-STEP GUIDED TOUR MODAL -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showModal" class="student-onboarding-backdrop d-flex align-items-center justify-content-center p-3">
                <div class="student-onboarding-modal bg-body border border-secondary-subtle rounded-4 shadow-lg p-4 p-md-5 position-relative overflow-hidden">
                    
                    <!-- Escape Hatch: Close X Button -->
                    <button @click="skipTour" class="btn-close position-absolute top-0 end-0 m-3 shadow-none z-3" title="Close tour"></button>

                    <!-- Top Progress Bar -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-1 fw-bold">
                                🎓 STEP {{ currentStep }} OF 3
                            </span>
                            <span class="small text-body-secondary fw-semibold">
                                Student Onboarding Tour
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div 
                                class="progress-bar bg-success transition-all" 
                                role="progressbar" 
                                :style="{ width: (currentStep / 3) * 100 + '%' }" 
                                :aria-valuenow="(currentStep / 3) * 100" 
                                aria-valuemin="0" 
                                aria-valuemax="100"
                            ></div>
                        </div>
                    </div>

                    <!-- STEP 1: EXPLORE MAP & LISTINGS -->
                    <div v-if="currentStep === 1" class="step-content">
                        <div class="step-icon-wrapper bg-success-subtle text-success mb-3">
                            <span class="fs-2">🏠</span>
                        </div>
                        
                        <h2 class="h4 fw-bold text-body-emphasis mb-2">1. Explore Verified Boarding Houses</h2>
                        <p class="text-body-secondary small mb-4 lh-base">
                            Browse verified student accommodations around Talibon Polytechnic College. Compare monthly rent rates, available bedspaces, photos, and exact walking distances to TPC campus.
                        </p>

                        <!-- Visual Card Preview -->
                        <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-success rounded-pill px-2 py-1"><small>Verified Owner</small></span>
                                <strong class="text-success small">₱800.00 / mo</strong>
                            </div>
                            <div class="fw-bold small text-body-emphasis mb-1">Jacque's Boarding House</div>
                            <div class="text-body-secondary small d-flex align-items-center gap-1" style="font-size: 0.75rem;">
                                <i class="bi bi-person-walking text-success"></i> 0.25 km walk to TPC Campus
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: ZERO ACCOUNT RESERVATION -->
                    <div v-if="currentStep === 2" class="step-content">
                        <div class="step-icon-wrapper bg-primary-subtle text-primary mb-3">
                            <span class="fs-2">📝</span>
                        </div>
                        
                        <h2 class="h4 fw-bold text-body-emphasis mb-2">2. Reserve Bedspace (No Account Required)</h2>
                        <p class="text-body-secondary small mb-4 lh-base">
                            Found your ideal boarding house? Fill out a quick guest reservation request with your name, phone, and email. No student passwords or account registration required!
                        </p>

                        <!-- Visual Card Preview -->
                        <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2 text-success fw-bold small">
                                <i class="bi bi-check-circle-fill"></i> Fast & 100% Free Guest Request
                            </div>
                            <p class="text-body-secondary mb-0" style="font-size: 0.75rem;">
                                Your request is sent directly to the landlord for review. You'll receive instant email notifications upon approval.
                            </p>
                        </div>
                    </div>

                    <!-- STEP 3: LIVE STATUS TRACKING -->
                    <div v-if="currentStep === 3" class="step-content">
                        <div class="step-icon-wrapper bg-warning-subtle text-warning mb-3">
                            <span class="fs-2">🔍</span>
                        </div>
                        
                        <h2 class="h4 fw-bold text-body-emphasis mb-2">3. Track Your Status Live</h2>
                        <p class="text-body-secondary small mb-4 lh-base">
                            After submitting, you'll receive a unique <strong>EBM</strong> tracking code (e.g. <code>EBM-8924</code>). Use it anytime on our Track Reservation page to check live approval updates.
                        </p>

                        <!-- Visual Card Preview -->
                        <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="small text-body-secondary">Tracking Code: <strong class="text-body-emphasis font-monospace">EBM-8924</strong></span>
                                <span class="badge bg-success rounded-pill"><small>Approved</small></span>
                            </div>
                            <div class="text-body-secondary" style="font-size: 0.75rem;">
                                Live updates directly from the boarding house landlord.
                            </div>
                        </div>
                    </div>

                    <!-- TOUR NAVIGATION CONTROLS -->
                    <div class="d-flex align-items-center justify-content-between pt-2 border-top border-secondary-subtle mt-2">
                        <!-- Left Action -->
                        <button v-if="currentStep > 1" @click="prevStep" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-semibold">
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <button v-else @click="skipTour" class="btn btn-link text-body-secondary text-decoration-none small p-0">
                            Skip Tour
                        </button>

                        <!-- Right Action -->
                        <button v-if="currentStep < 3" @click="nextStep" class="btn btn-ebm-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                            Next <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <button v-else @click="finishTour('/boarding-houses')" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm">
                            Get Started Now 🎉
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- 📌 FLOATING SAFETY NET (RE-OPEN GUIDE WIDGET - Only shown if not skipped/done) -->
    <Teleport to="body">
        <div v-if="!onboardingCompleted" class="student-guide-widget-wrapper position-fixed bottom-0 end-0 m-3 z-3">
            
            <!-- Expanded Quick Guide Card -->
            <Transition name="slide-up">
                <div v-if="showGuideWidget" class="card bg-body border border-secondary-subtle shadow-lg rounded-4 mb-2 p-3" style="width: 300px;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold small text-body-emphasis d-flex align-items-center gap-1">
                            <i class="bi bi-mortarboard-fill text-success"></i> Freshmen Student Guide
                        </span>
                        <button @click="toggleGuideWidget" class="btn-close shadow-none" style="font-size: 0.7rem;"></button>
                    </div>

                    <ul class="list-unstyled small text-body-secondary mb-3 d-flex flex-column gap-2">
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-circle">1</span>
                            <span>Browse verified listings near TPC</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-circle">2</span>
                            <span>Reserve bedspace with no account</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-circle">3</span>
                            <span>Track reservation status online</span>
                        </li>
                    </ul>

                    <button @click="restartTour" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold">
                        Re-open 3-Step Guided Tour
                    </button>
                </div>
            </Transition>

            <!-- Floating Trigger Button -->
            <button @click="toggleGuideWidget" class="btn btn-sm bg-body text-body-emphasis border border-secondary-subtle rounded-pill shadow-sm d-flex align-items-center gap-2 px-3 py-2 transition-all hover-bg-tertiary">
                <span class="badge bg-success-subtle text-success rounded-circle p-1">🎓</span>
                <span class="fw-semibold small">Student Guide</span>
            </button>
        </div>
    </Teleport>
</template>

<style scoped>
.student-onboarding-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(6px);
    z-index: 9999;
}

.student-onboarding-modal {
    max-width: 500px;
    width: 100%;
}

.step-icon-wrapper {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active, .slide-up-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.slide-up-enter-from, .slide-up-leave-to {
    transform: translateY(15px);
    opacity: 0;
}

.transition-all {
    transition: all 0.3s ease;
}
</style>
