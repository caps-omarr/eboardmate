<script setup>
import { router } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const showModal = ref(false);
const currentStep = ref(1); // Steps 1 to 4
const showGuideWidget = ref(false);

// Step 4 Quick Setup Filter State
const selectedGender = ref('all');
const selectedBudget = ref('all');

const ONBOARDING_KEY = 'hasSeenStudentTour';
const onboardingCompleted = ref(
    Boolean(localStorage.getItem(ONBOARDING_KEY) || localStorage.getItem('ebm_student_onboarding_completed'))
);

onMounted(() => {
    if (!onboardingCompleted.value) {
        // Automatically open the guided tour for first-time visitors after a brief delay
        setTimeout(() => {
            showModal.value = true;
        }, 600);
    }
});

const nextStep = () => {
    if (currentStep.value < 4) {
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

const finishTour = () => {
    showModal.value = false;
    onboardingCompleted.value = true;
    localStorage.setItem(ONBOARDING_KEY, 'true');
    localStorage.setItem('ebm_student_onboarding_completed', 'completed');

    // Build filter parameters for boarding houses list
    const filterData = {};
    if (selectedGender.value && selectedGender.value !== 'all') {
        filterData.gender = selectedGender.value;
    }
    if (selectedBudget.value && selectedBudget.value !== 'all') {
        filterData.budget = selectedBudget.value;
    }

    // Trigger Inertia visit to Boarding Houses search page
    router.visit('/boarding-houses', {
        data: filterData,
        preserveState: false,
    });
};

const skipTour = () => {
    showModal.value = false;
    onboardingCompleted.value = true;
    localStorage.setItem(ONBOARDING_KEY, 'true');
    localStorage.setItem('ebm_student_onboarding_completed', 'skipped');
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
    <!-- 🚀 4-STEP INTERACTIVE STUDENT ONBOARDING TOUR MODAL -->
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="showModal" class="student-onboarding-backdrop d-flex align-items-center justify-content-center p-3">
                <div 
                    class="student-onboarding-modal bg-body border border-secondary-subtle rounded-4 shadow-lg p-4 p-md-5 position-relative overflow-hidden d-flex flex-column" 
                    style="max-height: 85vh; max-height: 85dvh;"
                >
                    
                    <!-- Escape Hatch: Close Button -->
                    <button 
                        type="button" 
                        @click="skipTour" 
                        class="btn-close position-absolute top-0 end-0 m-3 shadow-none z-3 p-3" 
                        style="touch-action: manipulation;"
                        title="Close tour"
                        aria-label="Close tour"
                    ></button>

                    <!-- Top Header & Progress Bar -->
                    <div class="modal-header-section flex-shrink-0 mb-3 pe-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-1 fw-bold">
                                🎓 STEP {{ currentStep }} OF 4
                            </span>
                            <span class="small text-body-secondary fw-semibold">
                                Freshmen Student Guide
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div 
                                class="progress-bar bg-success transition-all" 
                                role="progressbar" 
                                :style="{ width: (currentStep / 4) * 100 + '%' }" 
                                :aria-valuenow="(currentStep / 4) * 100" 
                                aria-valuemin="0" 
                                aria-valuemax="100"
                            ></div>
                        </div>
                    </div>

                    <!-- SCROLLABLE MODAL BODY -->
                    <div class="modal-scrollable-body flex-grow-1 overflow-y-auto pe-1 my-2">

                        <!-- STEP 1: WELCOME TO E-BOARDMATE -->
                        <div v-if="currentStep === 1" class="step-content">
                            <div class="visual-placeholder bg-success bg-opacity-10 rounded-4 p-4 text-center mb-3 border border-success border-opacity-25">
                                <div class="display-3 mb-2">🎓🏠</div>
                                <span class="badge bg-success rounded-pill px-3 py-1 fw-semibold">Official Student Accommodations Locator</span>
                            </div>

                            <h2 class="h4 fw-bold text-body-emphasis mb-2">1. Welcome to E-BoardMate</h2>
                            <p class="text-body-secondary small mb-3 lh-base">
                                Welcome! E-BoardMate is your all-in-one system for discovering verified student accommodations around <strong>Talibon Polytechnic College</strong>. Compare prices, amenities, and available bedspaces easily.
                            </p>
                        </div>

                        <!-- STEP 2: IMPORTANT MAP & DISTANCES -->
                        <div v-if="currentStep === 2" class="step-content">
                            <div class="visual-placeholder bg-info bg-opacity-10 rounded-4 p-4 text-center mb-3 border border-info border-opacity-25">
                                <div class="display-4 mb-2">🗺️🚶‍♂️</div>
                                <div class="fw-bold text-info small">Interactive Satellite & Route Calculation</div>
                            </div>

                            <h2 class="h4 fw-bold text-body-emphasis mb-2">2. Important: Map & Distances</h2>
                            <p class="text-body-secondary small mb-3 lh-base">
                                To help you choose, we provide estimated walking distances to the TPC campus.
                            </p>
                            <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mb-3">
                                <p class="text-body-secondary small mb-0 lh-sm">
                                    <strong class="text-body-emphasis">Please note:</strong> These distance estimations are based on map data (broken lines) and may not perfectly reflect recent road changes or shortcuts. Always verify the exact location with the landlord during your visit!
                                </p>
                            </div>
                        </div>

                        <!-- STEP 3: ZERO-HASSLE RESERVATIONS & SAFETY -->
                        <div v-if="currentStep === 3" class="step-content">
                            <div class="visual-placeholder bg-warning bg-opacity-10 rounded-4 p-3 text-center mb-3 border border-warning border-opacity-25">
                                <div class="display-4 mb-1">📝🔒</div>
                                <div class="fw-bold text-warning-emphasis small">Instant Guest Requests & Anti-Scam Protection</div>
                            </div>

                            <h2 class="h4 fw-bold text-body-emphasis mb-2">3. Zero-Hassle Reservations & Safety</h2>
                            <p class="text-body-secondary small mb-3 lh-base">
                                You do <strong>NOT</strong> need an account to reserve! Just fill out a quick guest form and your request goes straight to the owner.
                            </p>

                            <!-- SAFETY WARNING ALERT -->
                            <div class="alert alert-warning border border-warning border-opacity-50 rounded-4 p-3 shadow-sm mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <i class="bi bi-shield-exclamation-fill fs-4 text-warning-emphasis flex-shrink-0"></i>
                                    <div class="small leading-normal">
                                        <strong class="d-block text-warning-emphasis fw-bold mb-1">⚠️ Safety First:</strong>
                                        E-BoardMate will <strong>NEVER</strong> ask for online payments. Always visit the boarding house in person before paying any GCash or cash downpayments to avoid scams.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 4: TRACK STATUS & QUICK SETUP -->
                        <div v-if="currentStep === 4" class="step-content">
                            <div class="visual-placeholder bg-primary bg-opacity-10 rounded-4 p-3 text-center mb-3 border border-primary border-opacity-25">
                                <div class="display-4 mb-1">🔍⚡</div>
                                <div class="fw-bold text-primary small">Live Approval Tracking & Custom Quick Setup</div>
                            </div>

                            <h2 class="h4 fw-bold text-body-emphasis mb-2">4. Track Status & Quick Setup</h2>
                            <p class="text-body-secondary small mb-3 lh-base">
                                After reserving, you'll get an <strong>EBM Tracking Code</strong> to check your approval status live.
                            </p>

                            <!-- INTERACTIVE QUICK SETUP DROPDOWNS -->
                            <div class="bg-body-tertiary p-3 rounded-4 border border-secondary-subtle mb-3">
                                <div class="fw-bold small text-body-emphasis mb-2 d-flex align-items-center gap-1">
                                    <i class="bi bi-sliders text-success"></i> Customize Your Initial Search
                                </div>
                                
                                <div class="row g-2">
                                    <div class="col-12 col-sm-6">
                                        <label for="onboarding-gender-select" class="form-label small text-body-secondary fw-semibold mb-1">I am looking for:</label>
                                        <select 
                                            id="onboarding-gender-select" 
                                            v-model="selectedGender" 
                                            class="form-select form-select-sm rounded-3 shadow-none p-2" 
                                            style="touch-action: manipulation;"
                                        >
                                            <option value="all">Any Gender (All)</option>
                                            <option value="Male Only">Male Only</option>
                                            <option value="Female Only">Female Only</option>
                                            <option value="Co-ed">Co-ed</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label for="onboarding-budget-select" class="form-label small text-body-secondary fw-semibold mb-1">My Max Budget:</label>
                                        <select 
                                            id="onboarding-budget-select" 
                                            v-model="selectedBudget" 
                                            class="form-select form-select-sm rounded-3 shadow-none p-2" 
                                            style="touch-action: manipulation;"
                                        >
                                            <option value="all">Any Price</option>
                                            <option value="600">₱600 / month</option>
                                            <option value="700">₱700 / month</option>
                                            <option value="800">₱800 / month</option>
                                            <option value="1000">₱1,000+ / month</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- TOUR NAVIGATION CONTROLS -->
                    <div class="modal-footer-controls d-flex align-items-center justify-content-between pt-3 border-top border-secondary-subtle flex-shrink-0">
                        <!-- Left Action -->
                        <button 
                            v-if="currentStep > 1" 
                            type="button"
                            @click="prevStep" 
                            class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-2 fw-semibold p-3"
                            style="touch-action: manipulation; min-height: 44px;"
                        >
                            <i class="bi bi-arrow-left me-1"></i> Back
                        </button>
                        <button 
                            v-else 
                            type="button"
                            @click="skipTour" 
                            class="btn btn-link text-body-secondary text-decoration-none small p-3"
                            style="touch-action: manipulation;"
                        >
                            Skip Tour
                        </button>

                        <!-- Right Action -->
                        <button 
                            v-if="currentStep < 4" 
                            type="button"
                            @click="nextStep" 
                            class="btn btn-ebm-primary rounded-pill px-4 py-2 fw-bold shadow-sm"
                            style="touch-action: manipulation; min-height: 44px;"
                        >
                            Next <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                        <button 
                            v-else 
                            type="button"
                            @click="finishTour" 
                            class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm"
                            style="touch-action: manipulation; min-height: 44px;"
                        >
                            Get Started Now 🎉
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- 📌 FLOATING SAFETY NET (RE-OPEN GUIDE WIDGET - Only shown if not completed) -->
    <Teleport to="body">
        <div v-if="!onboardingCompleted" class="student-guide-widget-wrapper position-fixed bottom-0 end-0 m-3 z-3">
            
            <!-- Expanded Quick Guide Card -->
            <Transition name="slide-up">
                <div v-if="showGuideWidget" class="card bg-body border border-secondary-subtle shadow-lg rounded-4 mb-2 p-3" style="width: 300px;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold small text-body-emphasis d-flex align-items-center gap-1">
                            <i class="bi bi-mortarboard-fill text-success"></i> Freshmen Student Guide
                        </span>
                        <button type="button" @click="toggleGuideWidget" class="btn-close shadow-none" style="font-size: 0.7rem;"></button>
                    </div>

                    <ul class="list-unstyled small text-body-secondary mb-3 d-flex flex-column gap-2">
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-circle">1</span>
                            <span>Browse verified listings near TPC</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-circle">2</span>
                            <span>Verify walking map distance</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-circle">3</span>
                            <span>No online payments / Visit in person</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success rounded-circle">4</span>
                            <span>Track reservation status online</span>
                        </li>
                    </ul>

                    <button type="button" @click="restartTour" class="btn btn-sm btn-outline-success rounded-pill w-100 fw-semibold py-2">
                        Re-open 4-Step Guided Tour
                    </button>
                </div>
            </Transition>

            <!-- Floating Trigger Button -->
            <button 
                type="button"
                @click="toggleGuideWidget" 
                class="btn btn-sm bg-body text-body-emphasis border border-secondary-subtle rounded-pill shadow-sm d-flex align-items-center gap-2 px-3 py-2 transition-all hover-bg-tertiary"
                style="touch-action: manipulation;"
            >
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
    max-width: 520px;
    width: 100%;
}

.visual-placeholder {
    transition: all 0.3s ease;
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
